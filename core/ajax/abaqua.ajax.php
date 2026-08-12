<?php

require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');

try {
    if (!isConnect('admin')) {
        throw new Exception(__('401 - Acces non autorise', __FILE__));
    }

    ajax::init();

    if (init('action') === 'getEquipmentLogName') {
        $eqLogicId = intval(init('eqLogic_id'));
        ajax::success(abaqua::getEquipmentLogNameByEqLogicId($eqLogicId));
    }

    throw new Exception(__('Aucune methode correspondante a :', __FILE__) . ' ' . init('action'));
} catch (Exception $e) {
    ajax::error(displayException($e), $e->getCode());
}
