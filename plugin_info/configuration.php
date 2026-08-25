<?php
if (!isConnect('admin')) {
    throw new Exception(__('401 - Accès non autorisé', __FILE__));
}

$debugMode = config::byKey('debugMode', 'abaqua', 0);
$debugPath = config::byKey('debugPath', 'abaqua', dirname(__DIR__) . '/data/debug');
$debugMaxFiles = config::byKey('debugMaxFiles', 'abaqua', 20);
?>
<fieldset>
    <legend><i class="fas fa-cogs"></i> {{Configuration générale}}</legend>
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
            <p class="form-control-static">{{Les captures se trouvent dans le répertoire html/plugins/abaqua/data/debug}}</p>
        </div>
    </div>
    <div class="form-group">
        <label class="col-sm-3 control-label">{{Nombre max de fichiers}}</label>
        <div class="col-sm-2">
            <input type="number" min="1" max="200" class="configKey form-control" data-l1key="debugMaxFiles" value="<?php echo init('debugMaxFiles', $debugMaxFiles); ?>" />
        </div>
    </div>
</fieldset>
