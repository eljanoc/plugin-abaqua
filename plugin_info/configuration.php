<?php
if (!isConnect('admin')) {
    throw new Exception(__('401 - Accès non autorisé', __FILE__));
}

$pythonPath = config::byKey('pythonPath', 'abaqua', '');
$debugMode = config::byKey('debugMode', 'abaqua', 0);
$debugPath = config::byKey('debugPath', 'abaqua', '/var/www/html/log');
$debugMaxFiles = config::byKey('debugMaxFiles', 'abaqua', 20);
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

<fieldset>
    <legend><i class="fas fa-bug"></i> {{Debug scraping}}</legend>
    <div class="form-group">
        <label class="col-sm-3 control-label">{{Activer le debug de capture}}</label>
        <div class="col-sm-3">
            <input type="checkbox" class="configKey" data-l1key="debugMode" value="1" <?php echo ($debugMode == 1 || $debugMode === '1' || $debugMode === true) ? 'checked' : ''; ?> />
        </div>
        <span class="col-sm-4 help-block">{{Quand activé, Abaqua enregistre les pages HTML et captures de débogage en cas d’échec ou de résultat vide.}}</span>
    </div>
    <div class="form-group">
        <label class="col-sm-3 control-label">{{Dossier de debug}}</label>
        <div class="col-sm-5">
            <input class="configKey form-control" data-l1key="debugPath" value="<?php echo init('debugPath', $debugPath); ?>" placeholder="/var/www/html/log" />
        </div>
        <span class="col-sm-4 help-block">{{Pour voir les captures dans Analyse > Logs, utiliser de préférence /var/www/html/log.}}</span>
    </div>
    <div class="form-group">
        <label class="col-sm-3 control-label">{{Nombre max de fichiers}}</label>
        <div class="col-sm-2">
            <input type="number" min="1" max="200" class="configKey form-control" data-l1key="debugMaxFiles" value="<?php echo init('debugMaxFiles', $debugMaxFiles); ?>" />
        </div>
    </div>
</fieldset>
