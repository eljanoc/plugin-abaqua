<?php
require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
require_once dirname(__FILE__) . '/../resources/install_widget.php';

function abaqua_install() {
    // Code exécuté lors de l'activation du plugin
    try {
        log::add('abaqua', 'info', 'Début installation du widget Abaqua');
        abaqua_install_widget();
        log::add('abaqua', 'info', 'Widget Abaqua installé dans data/customTemplates/dashboard');
    } catch (Exception $e) {
        log::add('abaqua', 'error', 'Erreur lors de l\'installation du widget Abaqua : ' . $e->getMessage());
    }
}

function abaqua_update() {
    // Code exécuté lors d'une mise à jour
    // Réinstaller le widget au besoin
    try {
        log::add('abaqua', 'info', 'Mise à jour : réinstallation du widget Abaqua');
        abaqua_install_widget();
        log::add('abaqua', 'info', 'Widget Abaqua réinstallé');
    } catch (Exception $e) {
        log::add('abaqua', 'error', 'Erreur lors de la réinstallation du widget Abaqua : ' . $e->getMessage());
    }
}

function abaqua_remove() {
    // Code exécuté lors de la suppression du plugin
    try {
        log::add('abaqua', 'info', 'Suppression du widget Abaqua');
        abaqua_remove_widget();
        log::add('abaqua', 'info', 'Widget Abaqua supprimé de data/customTemplates/dashboard');
    } catch (Exception $e) {
        log::add('abaqua', 'error', 'Erreur lors de la suppression du widget Abaqua : ' . $e->getMessage());
    }
}
?>
