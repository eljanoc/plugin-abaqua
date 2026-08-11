<?php
if (!isConnect('admin')) {
    throw new Exception(__('401 - Accès non autorisé', __FILE__));
}

$pythonPath = config::byKey('pythonPath', 'abaqua', '');
?>
<fieldset>
    <legend><i class="fas fa-cogs"></i> {{Configuration générale}}</legend>
    <div class="form-group">
        <label class="col-sm-3 control-label">{{Chemin vers Python}}</label>
        <div class="col-sm-3">
            <input class="configKey form-control" data-l1key="pythonPath" value="<?php echo init('pythonPath', $pythonPath); ?>" placeholder="/var/www/abaqua_venv/bin/python" />
        </div>
        <span class="col-sm-4 help-block">{{Chemin complet vers l'interpréteur Python utilisé par Abaqua.}}</span>
    </div>
</fieldset>
