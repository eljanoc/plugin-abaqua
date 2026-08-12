<?php
// Test CLI pour installer le widget Abaqua sans passer par Jeedom
chdir(__DIR__);
require_once __DIR__ . '/install_widget.php';

abaqua_install_widget();

$target = '/var/www/html/data/customTemplates/dashboard/cmd.action.other.Abaqua_log.html';
if (file_exists($target)) {
    echo "INSTALLED: $target\n";
    echo file_get_contents($target);
} else {
    echo "FAILED: $target not found\n";
}

?>
