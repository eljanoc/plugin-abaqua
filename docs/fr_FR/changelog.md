# Changelog Abaqua

## Version 1.0 (Stable)
* Création initiale du plugin.
* Support du fournisseur Kyrnolia (et portails similaires).
* Récupération automatique de la consommation journalière en Litres.
* Scraping headless via Python et Playwright.
* Gestion stricte de l'historique : ajout exclusif des nouvelles valeurs pour éviter les doublons.
* Séparation native de la Date de valeur (jour de l'eau) et de la Date de collecte (heure d'exécution du script) sur le widget Jeedom.
* Création automatique des commandes `Rafraîchir` et `Consommation jour`.

## Version Beta
* Création automatique de la commande `Log` (Action / Autre) à la sauvegarde d'un équipement.
* Association automatique de la commande `Log` au widget custom `cmd.action.other.Abaqua_log.html`.