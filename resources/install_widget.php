<?php
// Script d'installation/suppression du widget dashboard Abaqua

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
        throw new Exception('Échec de la copie du widget: ' . $src . ' -> ' . $dest);
    }
    @chmod($dest, 0644);
    return true;
}

function abaqua_remove_widget() {
    $webroot = realpath(dirname(__FILE__) . '/../../..');
    if ($webroot === false) throw new Exception('Impossible de résoudre le chemin webroot');

    $file = $webroot . '/data/customTemplates/dashboard/cmd.action.other.Abaqua_log.html';
    if (file_exists($file)) {
        if (!@unlink($file)) {
            throw new Exception('Impossible de supprimer le fichier: ' . $file);
        }
    }
    return true;
}

?>
