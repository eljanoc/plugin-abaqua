<?php
// Script d'installation/suppression du widget dashboard Abaqua

function abaqua_remove_path($path) {
    if (!file_exists($path)) {
        return true;
    }

    if (is_file($path) || is_link($path)) {
        if (!@unlink($path)) {
            throw new Exception('Impossible de supprimer le fichier: ' . $path);
        }
        return true;
    }

    $items = scandir($path);
    if ($items === false) {
        throw new Exception('Impossible de lire le dossier: ' . $path);
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        abaqua_remove_path($path . '/' . $item);
    }

    if (!@rmdir($path)) {
        throw new Exception('Impossible de supprimer le dossier: ' . $path);
    }
    return true;
}

function abaqua_install_widget() {
    $src = dirname(__FILE__) . '/cmd.action.other.Abaqua_log.html';
    $webroot = realpath(dirname(__FILE__) . '/../../..');
    if ($webroot === false) throw new Exception('Impossible de résoudre le chemin webroot');

    $destDir = $webroot . '/data/customTemplates/dashboard';
    if (!is_dir($destDir)) {
        if (!@mkdir($destDir, 0755, true)) {
            throw new Exception('Impossible de créer le dossier ' . $destDir);
        }
    }

    $dest = $destDir . '/cmd.action.other.Abaqua_log.html';
    if (!file_exists($src)) throw new Exception('Fichier source introuvable: ' . $src);

    if (!@copy($src, $dest)) {
        $sudo = 'sudo -n ';
        if (class_exists('system') && method_exists('system', 'getCmdSudo')) {
            $sudo = system::getCmdSudo();
        }

        $cmd = $sudo . 'cp ' . escapeshellarg($src) . ' ' . escapeshellarg($dest) . ' 2>&1';
        $output = trim(shell_exec($cmd));
        if (!file_exists($dest) || md5_file($src) !== md5_file($dest)) {
            throw new Exception('Échec de la copie du widget: ' . $src . ' -> ' . $dest . ($output !== '' ? ' (' . $output . ')' : ''));
        }
    }

    if (!@chmod($dest, 0644)) {
        $sudo = 'sudo -n ';
        if (class_exists('system') && method_exists('system', 'getCmdSudo')) {
            $sudo = system::getCmdSudo();
        }
        @shell_exec($sudo . 'chmod 0644 ' . escapeshellarg($dest) . ' 2>&1');
    }

    return true;
}

function abaqua_remove_widget() {
    $webroot = realpath(dirname(__FILE__) . '/../../..');
    if ($webroot === false) throw new Exception('Impossible de résoudre le chemin webroot');

    $dashboardFile = $webroot . '/data/customTemplates/dashboard/cmd.action.other.Abaqua_log.html';
    $mobileFile = $webroot . '/data/customTemplates/mobile/cmd.action.other.Abaqua_log.html';

    if (file_exists($dashboardFile) && !@unlink($dashboardFile)) {
        throw new Exception('Impossible de supprimer le fichier: ' . $dashboardFile);
    }
    if (file_exists($mobileFile) && !@unlink($mobileFile)) {
        throw new Exception('Impossible de supprimer le fichier: ' . $mobileFile);
    }

    return true;
}

function abaqua_remove_runtime_dependencies() {
    $targets = array('/var/www/abaqua_venv', '/var/www/.cache/ms-playwright');
    foreach ($targets as $target) {
        if (!file_exists($target)) {
            continue;
        }

        $cmd = system::getCmdSudo() . 'rm -rf ' . escapeshellarg($target) . ' 2>&1';
        com_shell::execute($cmd);

        if (file_exists($target)) {
            throw new Exception('Impossible de supprimer le dossier: ' . $target);
        }
    }

    return true;
}

if (PHP_SAPI === 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

    try {
        abaqua_install_widget();
        log::add('abaqua', 'info', 'Widget Abaqua installé via le script de dépendances');
        echo "Widget Abaqua installé avec succès\n";
        exit(0);
    } catch (Exception $e) {
        log::add('abaqua', 'error', 'Erreur installation widget via dépendances : ' . $e->getMessage());
        fwrite(STDERR, "Erreur installation widget Abaqua : " . $e->getMessage() . "\n");
        exit(1);
    }
}

?>
