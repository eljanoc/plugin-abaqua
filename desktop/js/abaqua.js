// Gestion du bouton de configuration globale du plugin
$('#bt_pluginConfiguration').on('click', function () {
    window.location.href = 'index.php?v=d&p=plugin&id=abaqua';
});

function syncAbaquaFournisseurField() {
    var select = $('#abaqua-fournisseur-select');
    var custom = $('#abaqua-fournisseur-custom');
    if (!select.length || !custom.length) {
        return;
    }

    var selected = select.val();
    if (selected && selected !== 'custom') {
        custom.val(selected);
        custom.hide();
    } else {
        custom.show();
    }
}

$('#abaqua-fournisseur-select').on('change', function () {
    syncAbaquaFournisseurField();
});

// Action au clic sur le bouton "Ajouter une commande"
$('#bt_addabaquaCmd').on('click', function () {
    addCmdToTable();
});

// Nettoyage automatique avant la sauvegarde (Évite l'erreur rouge des lignes vides)
function preSaveEqLogic() {
    $('#table_cmd tbody tr.cmd').each(function () {
        var name = $(this).find('.cmdAttr[data-l1key=name]').val();
        if (!isset(name) || name.trim() === '') {
            $(this).remove();
        }
    });

    var select = $('#abaqua-fournisseur-select');
    var custom = $('#abaqua-fournisseur-custom');
    if (select.length && custom.length) {
        if (select.val() && select.val() !== 'custom') {
            custom.val(select.val());
        }
    }

    return true;
}

$(document).ready(function () {
    var select = $('#abaqua-fournisseur-select');
    var custom = $('#abaqua-fournisseur-custom');
    if (!select.length || !custom.length) {
        return;
    }

    var current = custom.val();
    if (current === 'www.kyrnolia.fr' || current === 'www.eau.veolia.fr') {
        select.val(current);
        custom.hide();
    } else if (current) {
        select.val('custom');
        custom.show();
    } else {
        select.val('');
        custom.hide();
    }

    syncAbaquaFournisseurField();
});

// Fonction pour dessiner une ligne dans le tableau
function addCmdToTable(_cmd) {
    if (!isset(_cmd)) {
        var _cmd = {configuration: {}};
    }
    if (!isset(_cmd.configuration)) {
        _cmd.configuration = {};
    }

    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
    
    // Colonne 1 : Nom (Le LogicalID est caché pour éviter l'effet de doublon)
    tr += '<td>';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="id" style="display: none;" />';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="logicalId" style="display: none;" />';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" placeholder="{{Nom de la commande}}" style="width: 100%;">';
    tr += '</td>';
    
    // Colonne 2 : Type et Sous-type (Alignés côte à côte proprement)
    tr += '<td>';
    tr += '<div style="display: flex; gap: 10px;">';
    tr += '<select class="cmdAttr form-control input-sm" data-l1key="type">';
    tr += '<option value="info">{{Info}}</option>';
    tr += '<option value="action">{{Action}}</option>';
    tr += '</select>';
    tr += '<select class="cmdAttr form-control input-sm" data-l1key="subType">';
    tr += '<option value="numeric">{{Numérique}}</option>';
    tr += '<option value="string">{{Texte}}</option>';
    tr += '<option value="binary">{{Binaire}}</option>';
    tr += '<option value="other">{{Autre}}</option>';
    tr += '</select>';
    tr += '</div>';
    tr += '</td>';
    
    // Colonne 3 : Paramètres et Unité (Alignés sur la même ligne)
    tr += '<td>';
    tr += '<div style="display: flex; align-items: center; gap: 15px;">';
    tr += '<span>Unité: <input class="cmdAttr form-control input-sm" data-l1key="unite" style="width: 60px; display:inline-block;" placeholder="ex: L"></span>';
    tr += '<label class="checkbox-inline" style="margin: 0;"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" checked/>{{Historiser}}</label>';
    tr += '<label class="checkbox-inline" style="margin: 0;"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/>{{Afficher}}</label>';
    tr += '</div>';
    tr += '</td>';
    
    // Colonne 4 : Actions (Configuration, Test, Suppression)
    tr += '<td>';
    if (is_numeric(_cmd.id)) {
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> ';
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> {{Tester}}</a> ';
    }
    
    // SÉCURITÉ : On affiche le bouton "Supprimer" UNIQUEMENT si ce ne sont pas les commandes de base
    if (_cmd.logicalId !== 'refresh' && _cmd.logicalId !== 'conso_jour') {
        tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove" style="margin-top: 3px;" title="{{Supprimer}}"></i>';
    }
    
    tr += '</td>';
    tr += '</tr>';

    $('#table_cmd tbody').append(tr);
    var tr_last = $('#table_cmd tbody tr').last();
    
    // Application correcte des valeurs au tableau
    tr_last.setValues(_cmd, '.cmdAttr');
    if (isset(_cmd.type)) {
        tr_last.find('.cmdAttr[data-l1key=type]').val(init(_cmd.type));
    }
    jeedom.cmd.changeType(tr_last, init(_cmd.subType));
}