# Abaqua

## Important

Ce plugin nécessite de disposer d'un compteur d'eau communicant compatible avec la plateforme du fournisseur.

Il a été testé avec le site Kyrnolia (Veolia Corse). Il est donc prévu pour fonctionner avec ce type d'interface et devrait également fonctionner sur le site Veolia ainsi que sur certaines de ses succursales, selon la structure de la plateforme et les éventuels changements de code.

Pour les autres fournisseurs d'eau, le plugin devra probablement être adapté aux spécificités de leur site et de leur mécanisme d'accès aux données.

Abaqua est un plugin Jeedom pour récupérer automatiquement la consommation d'eau quotidienne depuis le portail de votre fournisseur d'eau, puis l'historiser dans Jeedom.

## Description

Le plugin se connecte à votre espace client, lit les consommations journalières et les enregistre dans l'historique Jeedom avec une séparation claire entre :

- la date de valeur : le jour exact de consommation
- la date de collecte : la date où le plugin a vérifié le site

Il est conçu pour fonctionner avec des fournisseurs compatibles, par défaut Kyrnolia, et pour rester robuste face aux changements de page ou de structure du site.

## Fonctionnalités

- récupération automatique de la consommation journalière
- intégration dans l'historique Jeedom
- création automatique des commandes Jeedom
- prise en charge d'un widget de log dédié
- débogage avancé des pages HTML en cas d'échec de scraping
- logs détaillés pour diagnostiquer les problèmes d'authentification ou de structure de page
- rafraîchissement manuel des données
- prise en charge des comptes compatibles avec les sites testés et proches

## Prérequis

Le plugin utilise Python et Playwright pour naviguer sur le site et extraire les données.

Il nécessite également de disposer d'un compteur d'eau communicant associé à un fournisseur compatible.

Lors de l'installation, il va automatiquement :

- créer un environnement virtuel Python
- installer les dépendances nécessaires
- télécharger le navigateur Chromium requis

Attention : l'installation des dépendances peut prendre plusieurs minutes selon votre matériel et votre connexion.

## Installation

1. Télécharger le plugin.
2. Activer le plugin depuis Jeedom.
3. Lancer l'installation des dépendances du plugin.
4. Vérifier que le plugin est bien installé et que l'environnement Python est opérationnel.

## Configuration

### Configuration du plugin

Dans la configuration du plugin, vous pouvez régler :

- le chemin vers Python
- le mode debug de capture de page
- le dossier de debug
- le nombre maximum de fichiers sauvegardés
- le fournisseur d'eau, y compris via une URL saisie manuellement

### Configuration d'un équipement

Créez un équipement Abaqua et renseignez :

- le fournisseur
- votre identifiant email
- votre mot de passe

## Commandes créées automatiquement

Le plugin crée automatiquement :

- Consommation jour : valeur quotidienne historique en litres
- Rafraîchir : déclenche une synchronisation immédiate
- Log : ouvre le log de l'équipement dans un widget dédié

## Débogage

Si un site ne répond pas comme prévu, vous pouvez activer le mode debug depuis la configuration du plugin.

Dans ce cas, Abaqua sauvegarde :

- les pages HTML de redirection ou d'erreur
- un fichier JSON de contexte en cas de crash
- le chemin exact du fichier dans les logs

Cela permet d'analyser précisément le comportement du site sans refaire toute la session manuellement.

## Sécurité

Le plugin ne stocke pas les mots de passe en clair dans les fichiers de debug.
Mais il est recommandé de garder le mode debug uniquement pendant la résolution d'un problème et de limiter le nombre de fichiers conservés.

## Compatibilité

- Testé : Kyrnolia / Veolia Corse
- Potentiellement compatible : Veolia et ses succursales selon configuration et évolution du site
- Autres fournisseurs : adaptation probable nécessaire selon la structure du site et des identifiants fournis par le fournisseur

## Changelog

Consultez [docs/fr_FR/changelog.md](docs/fr_FR/changelog.md) pour le détail des versions.

## Support

Pour les signalements, questions ou demandes d'évolution, utilisez le dépôt GitHub du plugin ou le support associé au plugin Jeedom.
