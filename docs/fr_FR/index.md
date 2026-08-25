# Plugin Abaqua

## Description
Le plugin **Abaqua** permet de récupérer automatiquement et d'historiser votre consommation d'eau quotidienne directement depuis le site web de votre fournisseur d'eau (comme Kyrnolia, par défaut, ou d'autres portails compatibles).

Il se connecte à votre espace client, extrait les relevés quotidiens (en litres) et les intègre proprement dans l'historique de Jeedom avec la date exacte de consommation (Date de valeur) distincte de la date de synchronisation (Date de collecte).

## Prérequis
Ce plugin exécute un script Python s'appuyant sur un navigateur virtuel (Playwright) pour lire les données.
Lors de l'installation des dépendances, le plugin va automatiquement :
* Créer un environnement virtuel Python.
* Installer les modules nécessaires (`playwright`, etc.).
* Télécharger les navigateurs Chromium requis pour le scraping en arrière-plan.

**Important :** L'installation des dépendances peut prendre beaucoup de temps, parfois 10 minutes ou plus, selon la puissance de votre box domotique et la vitesse de votre connexion Internet.

**Attention :** ne lancez pas plusieurs installations des dépendances en même temps. Le script utilise un verrou pour empêcher les doublons, mais relancer plusieurs fois peut allonger inutilement le temps d'installation.

**Suivi :** Pendant l'installation, vous pouvez suivre l'état d'avancement dans le fichier de log `abaqua_dep` ou via le fichier de progression du plugin.

## Configuration du plugin
Après avoir téléchargé et activé le plugin, aucune configuration générale n'est requise au niveau de la page de gestion des plugins. La configuration se fait équipement par équipement.

Depuis la version actuelle, un champ global **Chemin vers Python** est disponible dans la page de configuration du plugin. Il permet de pointer vers l'interpréteur Python utilisé par Abaqua, par exemple : `/var/www/html/plugins/abaqua/resources/abaqua_venv/bin/python`.

## Configuration des équipements
Rendez-vous dans le menu **Plugins** > **Énergie** (ou la catégorie que vous avez choisie) > **Abaqua**.
Cliquez sur "Ajouter" pour créer un nouvel équipement.

Dans l'onglet **Équipement**, remplissez les informations standards de Jeedom (Nom, Objet parent, Catégorie, Activer, Visible).

Dans l'onglet **Configuration**, vous devez obligatoirement renseigner vos identifiants de connexion à votre espace client :
* **Fournisseur :** L'URL de votre fournisseur d'eau (par défaut : `www.kyrnolia.fr`).
* **Identifiant (Email) :** L'adresse email utilisée pour vous connecter à votre espace client.
* **Mot de passe :** Le mot de passe de votre espace client.

## Les Commandes
Dès la sauvegarde de votre équipement, le plugin génère automatiquement les commandes suivantes :

* **Consommation jour (Info / Numérique) :** Cette commande stocke la consommation en Litres (L). Elle est historisée par défaut. La date de valeur correspond au jour exact de la consommation (telle qu'affichée par le fournisseur), garantissant un graphique fidèle à la réalité.
* **Rafraîchir (Action / Autre) :** Permet de forcer une synchronisation immédiate avec le fournisseur. Le plugin interroge le site et télécharge toutes les nouvelles données disponibles depuis la dernière date enregistrée dans l'historique de Jeedom. On peut suivre la récupération des valeurs de consommation dans le log abaqua,
* **Log (Action / Autre) :** Ouvre le widget de consultation du log détaillé de l'équipement (`abaqua_<nom_equipement>`). Cette commande est créée automatiquement et utilise le template custom `cmd.action.other.Abaqua_log.html` installé dans `data/customTemplates/dashboard`.

## Fonctionnement technique et astuces
* **Intelligence de synchronisation :** Le plugin interroge l'historique de Jeedom. Il ne télécharge et n'ajoute que les nouvelles valeurs strictement postérieures à la dernière date enregistrée. A la première synchronisation, quand il n'y a pas encore d'historique, l'équipement va télécharger toutes les valeurs quotidiennes de consommation disponibles sur le site. Vu le nombre de valeurs à rapatrier, plusieurs mois, cette opération peut prendre du temps, on peut la suivre dans le log abaqua.
* **Date de collecte vs Date de valeur :** Lors du survol de la valeur sur votre dashboard, après un refresh, vous verrez la date réelle de la consommation (Date de valeur) séparée de la date à laquelle le plugin a effectué sa dernière vérification fructueuse (Date de collecte).