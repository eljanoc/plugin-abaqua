# Changelog Abaqua

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