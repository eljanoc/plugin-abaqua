# Changelog Abaqua

## Version 2.2.2 (Beta)
* Réinstallation obligatoire des dépendances lors de la mise à jour vers cette version afin de supprimer les anciens caches Playwright et le venv obsolète.
* Déplacement du virtualenv Python dans le plugin, sous `resources/abaqua_venv`.
* Déplacement du cache Playwright dans le dossier Plugin `data/ms-playwright` pour éviter les artefacts globaux dans `/var/www`.
* Correction des chemins runtime pour que le script Python et le cache navigateur utilisent le même répertoire local au plugin.
* Exclusion des dossiers de runtime du plugin de la sauvegarde Jeedom via la méthode `backupExclude()`.
* Le dossier de debug est désormais écrit dans `plugins/abaqua/data/debug` et le chemin est rendu lisible dans la configuration.
* Ajout d'un fichier de démarrage et de résumé de debug pour valider le bon fonctionnement du mode debug même sans erreur.
* Nettoyage des références obsolètes vers `/var/www/.cache/ms-playwright` et des anciennes config de chemin Python dans l'interface.
* Mise à jour de la version interne Python pour refléter le correctif de debug et le nouveau layout du runtime.
* Nettoyage automatique des anciens chemins de runtime lors de la mise à jour ou de la réinstallation du plugin, sans action manuelle côté utilisateur.

## Version 2.2.1 (Beta)
* Déplacement du virtualenv Python dans le plugin, sous `resources/abaqua_venv`.
* Déplacement du cache Playwright dans le dossier Plugin `data/ms-playwright` pour éviter les artefacts globaux dans `/var/www`.
* Correction des chemins runtime pour que le script Python et le cache navigateur utilisent le même répertoire local au plugin.
* Exclusion des dossiers de runtime du plugin de la sauvegarde Jeedom via la méthode `backupExclude()`.
* Le dossier de debug est désormais écrit dans `plugins/abaqua/data/debug` et le chemin est rendu lisible dans la configuration.
* Ajout d'un fichier de démarrage et de résumé de debug pour valider le bon fonctionnement du mode debug même sans erreur.
* Nettoyage des références obsolètes vers `/var/www/.cache/ms-playwright` et des anciennes config de chemin Python dans l'interface.
* Mise à jour de la version interne Python pour refléter le correctif de debug et le nouveau layout du runtime.
* Nettoyage automatique des anciens chemins de runtime lors de la mise à jour ou de la réinstallation du plugin, sans action manuelle côté utilisateur.

## Version 2.1.1 (Beta)
* Ajout d'un verrou d'exécution côté plugin pour empêcher les synchronisations concurrentes sur un même équipement.
* Correction des explorations en double causées par des lancements simultanés du script Python.
* Ajout d'un identifiant d'exécution (`run_id`) dans les logs Python pour tracer clairement chaque run.
* Renforcement de la fiabilité du mode API-first (tri et filtrage plus robustes des jours journaliers).

## Version 1.3 (Beta)
* Migration de la collecte vers une stratégie API-first avec fallback DOM uniquement en secours.
* Stabilisation de la récupération journalière en capturant les réponses réseau de type `journalieres` et en les dédupliquant.
* Correction du comportement en historique vide : le plugin ne repose plus uniquement sur un moment précis du DOM pour détecter les nouvelles données.
* Renforcement de la déduplication sur les dates déjà historisées pour éviter les doublons sur une même journée.
* Amélioration de la logique de pagination et du traitement du mode dynamique sans date limite.

## Version 1.2 (Beta)
* Ajout d’un mode de debug de scraping configurable depuis la configuration du plugin.
* Sauvegarde des pages HTML et des payloads JSON en cas d’échec d’authentification, de redirection vers la page de connexion, ou de page vide/inexploitée.
* Logs Python explicites pour distinguer les échecs d’authentification, les redirections sur le login, les pages vides et les erreurs critiques.
* Limite configurable du nombre de fichiers de debug conservés pour éviter l’encombrement du disque.
* Correction de la traçabilité de débogage pour faciliter l’analyse des changements côté fournisseur.

## Version 1.0 (Stable)
* Création initiale du plugin.
* Support du fournisseur Kyrnolia (et portails similaires).
* Récupération automatique de la consommation journalière en Litres.
* Scraping headless via Python et Playwright.
* Gestion stricte de l'historique : ajout exclusif des nouvelles valeurs pour éviter les doublons.
* Séparation native de la Date de valeur (jour de l'eau) et de la Date de collecte (heure d'exécution du script) sur le widget Jeedom.
* Création automatique des commandes `Rafraîchir`,  `Consommation jour`et `Log` (Action / Autre) à la sauvegarde d'un équipement.
* Création automatique du widget `cmd.action.other.Abaqua_log.html` lors de l'installation du plugin et association automatique de la commande `Log` au widget lors de la création d'un équipement.