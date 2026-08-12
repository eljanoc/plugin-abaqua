<?php
require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
require_once dirname(__FILE__) . '/../resources/install_widget.php';

function abaqua_install() {
    // Code exécuté lors de l'activation du plugin
    abaqua_install_widget();
}

function abaqua_update() {
    // Code exécuté lors d'une mise à jour
    // Réinstaller le widget au besoin
    abaqua_install_widget();
}

function abaqua_remove() {
    // Code exécuté lors de la suppression du plugin
    abaqua_remove_widget();
}
?>
