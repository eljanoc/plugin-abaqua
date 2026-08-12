<?php
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class abaqua extends eqLogic {

    // --- 1. GESTION DES DEPENDANCES ---
    
    private static function getPluginBasePath() {
        $path = dirname(__FILE__) . '/../../';
        $realPath = realpath($path);
        return ($realPath !== false) ? $realPath : $path;
    }

    private static function getPythonPath() {
        $configuredPath = config::byKey('pythonPath', 'abaqua', '');
        if (!empty($configuredPath)) {
            return $configuredPath;
        }

        $externalPath = '/var/www/abaqua_venv/bin/python';
        if (file_exists($externalPath)) {
            return $externalPath;
        }

        $pluginPath = self::getPluginBasePath();
        return $pluginPath . '/abaqua_venv/bin/python';
    }

    public static function dependancy_info() {
        $return = array();
        $return['log'] = 'abaqua_update';
        $return['progress_file'] = jeedom::getTmpFolder('abaqua') . '/dependancy';
        
        if (file_exists(jeedom::getTmpFolder('abaqua') . '/dependancy')) {
            $return['state'] = 'in_progress';
        } elseif (file_exists(self::getPythonPath())) {
            $return['state'] = 'ok';
        } else {
            $return['state'] = 'nok';
        }
        return $return;
    }

    public static function dependancy_install() {
        log::remove('abaqua_update');
        return array(
            'script' => dirname(__FILE__) . '/../../resources/install.sh ' . jeedom::getTmpFolder('abaqua') . '/dependancy', 
            'log' => 'abaqua_update'
        );
    }


    // --- 2. DECLENCHEURS JEEDOM ---

    public function postSave() {
        $cmd_refresh = $this->getCmd(null, 'refresh');
        if (!is_object($cmd_refresh)) {
            $cmd_refresh = new abaquaCmd();
            $cmd_refresh->setLogicalId('refresh');
            $cmd_refresh->setName('Rafraîchir');
            $cmd_refresh->setType('action');
            $cmd_refresh->setSubType('other');
            $cmd_refresh->setEqLogic_id($this->getId());
            $cmd_refresh->setIsVisible(1);
            $cmd_refresh->setDisplay('icon', '<i class="fas fa-sync"></i>');
            $cmd_refresh->save();
        }

        $cmd_conso = $this->getCmd(null, 'conso_jour');
        if (!is_object($cmd_conso)) {
            $cmd_conso = new abaquaCmd();
            $cmd_conso->setLogicalId('conso_jour');
            $cmd_conso->setName('Consommation jour');
            $cmd_conso->setType('info');
            $cmd_conso->setSubType('numeric');
            $cmd_conso->setUnite('L');
            $cmd_conso->setEqLogic_id($this->getId());
            $cmd_conso->setIsVisible(1);
            $cmd_conso->setIsHistorized(1);
            $cmd_conso->save();
        }
    }


    // --- 3. EXECUTION DU SCRIPT PYTHON ---

    public function refreshData() {
        log::add('abaqua', 'info', $this->getHumanName() . ' : Début de la synchronisation Abaqua.');

        $username = $this->getConfiguration('username');
        $password = $this->getConfiguration('password');
        
        $fournisseur = $this->getConfiguration('fournisseur', 'www.kyrnolia.fr');
        
        if (empty($username) || empty($password)) {
            log::add('abaqua', 'error', $this->getHumanName() . ' : Identifiants manquants.');
            return;
        }

        $date_limite = '';
        $cmd_conso = $this->getCmd(null, 'conso_jour');
        
        if (is_object($cmd_conso)) {
            $sql = 'SELECT datetime FROM history WHERE cmd_id = :cmd_id ORDER BY datetime DESC LIMIT 1';
            $row = DB::Prepare($sql, array('cmd_id' => $cmd_conso->getId()), DB::FETCH_TYPE_ROW);
            
            if (is_array($row) && isset($row['datetime'])) {
                $date_limite = $row['datetime'];
            } else {
                $sqlArch = 'SELECT datetime FROM historyArch WHERE cmd_id = :cmd_id ORDER BY datetime DESC LIMIT 1';
                $rowArch = DB::Prepare($sqlArch, array('cmd_id' => $cmd_conso->getId()), DB::FETCH_TYPE_ROW);
                
                if (is_array($rowArch) && isset($rowArch['datetime'])) {
                    $date_limite = $rowArch['datetime'];
                }
            }
        }
        
        log::add('abaqua', 'info', $this->getHumanName() . ' : Date limite récupérée en BDD : ' . ($date_limite ? $date_limite : 'Aucune'));
        
        $pluginPath = self::getPluginBasePath();
        $pythonPath = self::getPythonPath();
        $scriptPath = $pluginPath . '/resources/abaqua.py';
        $homePath = jeedom::getTmpFolder('abaqua');

        $eqName = $this->getHumanName();
        $eqNameSafe = preg_replace('/[^A-Za-z0-9._-]+/', '_', $eqName);
        $eqNameSafe = preg_replace('/_{2,}/', '_', $eqNameSafe);
        $eqNameSafe = trim($eqNameSafe, '_');
        $logDir = dirname(log::getPathToLog('abaqua'));
        $log_path = $logDir . '/abaqua_' . $eqNameSafe;

        if (!is_dir($homePath)) {
            @mkdir($homePath, 0755, true);
        }

        if (!file_exists($pythonPath)) {
            log::add('abaqua', 'error', $this->getHumanName() . ' : Environnement Python introuvable : ' . $pythonPath);
            return;
        }

        if (!file_exists($scriptPath)) {
            log::add('abaqua', 'error', $this->getHumanName() . ' : Script Python introuvable : ' . $scriptPath);
            return;
        }

        $cmd = array(
            $pythonPath,
            $scriptPath,
            $username,
            $password,
            $date_limite,
            $fournisseur,
        );

        $descriptorspec = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w'),
        );
        $processEnv = $_ENV;
        $processEnv['HOME'] = $homePath;
        $processEnv['ABAQUA_EQ_NAME'] = $eqName;

        $process = proc_open($cmd, $descriptorspec, $pipes, null, $processEnv);
        $output = '';
        $stderr = '';
        $return_var = 1;
        $success = false;

        if (is_resource($process)) {
            fclose($pipes[0]);
            stream_set_blocking($pipes[1], false);
            stream_set_blocking($pipes[2], false);
            $stderr_buffer = '';

            while (true) {
                $read = array();
                if (!feof($pipes[1])) {
                    $read[] = $pipes[1];
                }
                if (!feof($pipes[2])) {
                    $read[] = $pipes[2];
                }
                if (empty($read)) {
                    break;
                }

                $write = null;
                $except = null;
                if (stream_select($read, $write, $except, 1) === false) {
                    break;
                }

                foreach ($read as $r) {
                    $data = stream_get_contents($r);
                    if ($data === false || $data === '') {
                        continue;
                    }
                    if ($r === $pipes[1]) {
                        $output .= $data;
                    } else {
                        $stderr .= $data;
                        $stderr_buffer .= $data;
                        while (($pos = strpos($stderr_buffer, "\n")) !== false) {
                            $line = substr($stderr_buffer, 0, $pos);
                            $stderr_buffer = substr($stderr_buffer, $pos + 1);
                            // Append to the global Jeedom plugin log
                            file_put_contents($log_path, date('Y-m-d H:i:s') . ' [python debug] ' . trim($line) . PHP_EOL, FILE_APPEND);
                        }
                    }
                }

                $status = proc_get_status($process);
                if (!$status['running']) {
                    break;
                }
            }
            if ($stderr_buffer !== '') {
                // Append remaining stderr buffer to the global Jeedom plugin log
                file_put_contents($log_path, date('Y-m-d H:i:s') . ' [python debug] ' . trim($stderr_buffer) . PHP_EOL, FILE_APPEND);
            }

            $output .= stream_get_contents($pipes[1]);
            $stderr .= stream_get_contents($pipes[2]);
            $status = proc_get_status($process);
            $exitcode = $status['exitcode'];
            fclose($pipes[1]);
            fclose($pipes[2]);
            $return_var = proc_close($process);
            if ($return_var === -1) {
                if ($exitcode !== -1) {
                    $return_var = $exitcode;
                } elseif ($output !== '') {
                    $decoded = json_decode($output, true);
                    if (json_last_error() === JSON_ERROR_NONE && !preg_match('/Traceback|Exception|Error|ERR|raise /i', $stderr)) {
                        $return_var = 0;
                    }
                }
            }
        } else {
            log::add('abaqua', 'error', $this->getHumanName() . ' : Impossible de démarrer le processus Python.');
        }

        if ($return_var === 0) {
            $success = true;
        } else {
            log::add('abaqua', 'error', $this->getHumanName() . ' : Le script Python a échoué avec le code de sortie ' . $return_var . '.');
            if ($output !== '') {
                log::add('abaqua', 'error', $this->getHumanName() . ' : Sortie du script : ' . substr($output, 0, 2000));
            } elseif ($stderr !== '') {
                log::add('abaqua', 'error', $this->getHumanName() . ' : Erreurs Python présentes dans le log Python en temps réel.');
            } else {
                log::add('abaqua', 'debug', $this->getHumanName() . ' : Le script Python n’a retourné aucune sortie.');
            }
        }

        if (!empty($output)) {
            $data = json_decode($output, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                log::add('abaqua', 'error', $this->getHumanName() . ' : JSON invalide reçu (' . json_last_error_msg() . ').');
                log::add('abaqua', 'debug', $this->getHumanName() . ' : Sortie brute du script : ' . substr($output, 0, 2000));
            } elseif (is_array($data) && count($data) > 0) {
                $data = array_reverse($data); 

                if (is_object($cmd_conso)) {
                    $count = 0;
                    foreach ($data as $r) {
                        if (isset($r['conso']) && isset($r['datetime'])) {
                            // Jeedom historise la valeur et force l'alignement des deux dates
                            $cmd_conso->event($r['conso'], $r['datetime']);
                            $count++;
                        }
                    }
                    log::add('abaqua', 'info', $this->getHumanName() . ' : ' . $count . ' jours insérés.');

                    // --- SÉPARATION DÉFINITIVE DES DATES ---
                    if ($count > 0) {
                        // 1. On modifie la clé de cache exacte pour la date de collecte (Heure de l'exécution)
                        $cmd_conso->setCache('collectDate', date('Y-m-d H:i:s'));
                    }
                }
            } else {
                log::add('abaqua', 'warning', $this->getHumanName() . ' : Le script a produit une sortie JSON vide ou non exploitable.');
                log::add('abaqua', 'debug', $this->getHumanName() . ' : Sortie brute du script : ' . substr($output, 0, 2000));
            }
        } elseif ($success) {
            log::add('abaqua', 'debug', $this->getHumanName() . ' : Le script Python s’est exécuté sans erreur, mais n’a renvoyé aucune sortie.');
        }

        if ($success) {
            // On met à jour l'heure de dernière communication de l'équipement
            $this->setStatus('lastCommunication', date('Y-m-d H:i:s'));
            log::add('abaqua', 'info', $this->getHumanName() . ' : Synchronisation terminée avec succès.');
        }
    }
}


// --- 4. CLASSE DES COMMANDES ---

class abaquaCmd extends cmd {
    public function execute($_options = array()) {
        $eqLogic = $this->getEqLogic();
        if ($this->getLogicalId() == 'refresh') {
            $eqLogic->refreshData();
        }
    }
}
?>