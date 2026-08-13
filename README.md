# Abaqua

Abaqua est un plugin Jeedom permettant de récupérer automatiquement la consommation d'eau d'un compteur communicant et de l'enregistrer dans l'historique Jeedom.

## Important

Ce plugin nécessite de disposer d'un compteur d'eau communicant compatible avec la plateforme du fournisseur.

Il a été testé avec le site Kyrnolia (Veolia Corse). Il est donc prévu pour fonctionner avec ce type d'interface et devrait également fonctionner sur le site Veolia ainsi que sur certaines de ses succursales, selon la structure de la plateforme et les éventuels changements de code.

Pour les autres fournisseurs d'eau, le plugin devra probablement être adapté aux spécificités de leur site et de leur mécanisme d'accès aux données.

## Fonctionnalités

- récupération automatique des données de consommation
- historique Jeedom des valeurs journalières
- création automatique des commandes du plugin
- rafraîchissement manuel des données
- prise en charge des comptes compatibles avec les sites testés et proches

## Prérequis

Le plugin nécessite :

- Jeedom
- Python
- les dépendances de fonctionnement du plugin
- un compteur d'eau communicant associé à un fournisseur compatible

Les dépendances sont installées automatiquement lors de l'activation du plugin.

## Installation

1. Téléchargez le plugin.
2. Activez-le depuis Jeedom.
3. Vérifiez que l'installation des dépendances est terminée.
4. Configurez votre compte et votre équipement.

## Configuration

Dans la configuration du plugin, renseignez :

- l'identifiant de connexion
- le mot de passe
- les informations nécessaires à l'équipement

## Utilisation

Une fois le plugin configuré :

- les données sont récupérées automatiquement
- les valeurs sont ajoutées à l'historique Jeedom
- vous pouvez forcer une mise à jour manuellement via la commande de rafraîchissement

## Compatibilité

- Testé : Kyrnolia / Veolia Corse
- Potentiellement compatible : Veolia et ses succursales selon configuration et évolution du site
- Autres fournisseurs : adaptation probable nécessaire selon la structure du site et des identifiants fournis par le fournisseur

## Support

Pour toute question ou problème, consultez la documentation du plugin ou le support associé.
