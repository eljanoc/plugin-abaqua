#!/bin/bash

# --- CONFIGURATION STRICTE ---
# Arrête le script immédiatement si une commande critique échoue
set -e
# Supprime les animations pour garder le log propre dans Jeedom
export CI=1

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PLUGIN_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
LOG_DIR="/var/www/html/log"
LOG_FILE="$LOG_DIR/abaqua_dep"
DATA_DIR="$PLUGIN_DIR/data"
PLAYWRIGHT_BROWSERS_PATH="$DATA_DIR/ms-playwright"
VENV_DIR="$PLUGIN_DIR/resources/abaqua_venv"

MODE="foreground"
PROGRESS_FILE=""
if [ "$1" = "--background" ]; then
    MODE="background"
    PROGRESS_FILE="$2"
else
    PROGRESS_FILE="$1"
fi

mkdir -p "$LOG_DIR"
touch "$LOG_FILE"
if [ -n "$SUDO" ]; then
    $SUDO chown www-data:www-data "$LOG_FILE" || true
    $SUDO chmod 664 "$LOG_FILE" || true
fi
exec >> "$LOG_FILE" 2>&1

write_progress() {
    if [ -n "$PROGRESS_FILE" ]; then
        echo "$1" > "$PROGRESS_FILE" 2>/dev/null || true
    fi
}

start_background() {
    if [ -n "$PROGRESS_FILE" ]; then
        mkdir -p "$(dirname "$PROGRESS_FILE")"
        write_progress 0
    fi
    if ! command -v nohup >/dev/null 2>&1; then
        echo "Erreur : nohup est requis pour le mode arrière-plan."
        exit 1
    fi
    nohup "$0" --background "$PROGRESS_FILE" >/dev/null 2>&1 &
    echo "L'installation des dépendances Abaqua démarre en arrière-plan."
    exit 0
}

if [ "$MODE" = "foreground" ]; then
    start_background
fi

# --- PROTECTION ANTI-DOUBLONS ---
LOCK_FILE="/tmp/abaqua_install.lock"
if [ -f "$LOCK_FILE" ]; then
    echo "Une installation est déjà en cours. Annulation de ce doublon."
    write_progress 100
    exit 0
fi
touch "$LOCK_FILE"

# --- DÉTECTION DES DROITS ROOT / SUDO ---
SUDO=""
if [ "$EUID" -ne 0 ]; then
    if command -v sudo >/dev/null 2>&1; then
        if sudo -n true >/dev/null 2>&1; then
            SUDO="sudo"
        else
            echo "Erreur : l'installation des dépendances Abaqua nécessite des droits root ou sudo sans mot de passe."
            write_progress 100
            exit 1
        fi
    else
        echo "Erreur : l'installation des dépendances Abaqua nécessite des droits root, et sudo n'est pas disponible."
        write_progress 100
        exit 1
    fi
fi

# --- FONCTION DE SÉCURITÉ DE FIN DE SCRIPT ---
# Cette fonction s'exécutera TOUJOURS, que le script réussisse ou plante.
cleanup() {
    rm -f "$LOCK_FILE"
    if [ -n "$PROGRESS_FILE" ]; then
        rm -f "$PROGRESS_FILE"
    fi
    $SUDO chown www-data:www-data "$LOG_FILE" || true
    $SUDO chmod 664 "$LOG_FILE" || true
}
trap cleanup EXIT
# --------------------------------

echo "********************************************************"
echo "* Début de l'installation des dépendances Abaqua      *"
echo "********************************************************"
write_progress 10

run_cmd() {
    if [ -n "$SUDO" ]; then
        sudo env "$@"
    else
        env "$@"
    fi
}

echo "-> Détection du gestionnaire de paquets..."
PKG_INSTALL=""
PKG_UPDATE=""
PKG_ENV="DEBIAN_FRONTEND=noninteractive"

if command -v apt-get >/dev/null 2>&1; then
    PKG_INSTALL="apt-get install -y"
    PKG_UPDATE="apt-get update"
    PKG_ENV="DEBIAN_FRONTEND=noninteractive"
elif command -v dnf >/dev/null 2>&1; then
    PKG_INSTALL="dnf install -y"
    PKG_UPDATE="dnf makecache --refresh"
elif command -v yum >/dev/null 2>&1; then
    PKG_INSTALL="yum install -y"
    PKG_UPDATE="yum makecache"
elif command -v zypper >/dev/null 2>&1; then
    PKG_INSTALL="zypper -n install"
    PKG_UPDATE="zypper refresh"
elif command -v pacman >/dev/null 2>&1; then
    PKG_INSTALL="pacman -Syu --noconfirm"
    PKG_UPDATE="true"
else
    echo "Erreur : gestionnaire de paquets non supporté. Installez manuellement python3, python3-venv et python3-pip."
    write_progress 100
    exit 1
fi

echo "-> Mise à jour des dépôts système..."
if ! run_cmd $PKG_ENV $PKG_UPDATE; then
    echo "Erreur : impossible de mettre à jour les dépôts système."
    write_progress 100
    exit 1
fi
write_progress 20

echo "-> Installation des dépendances système (venv et pip)..."
PACKAGES="python3 python3-venv python3-pip"
if command -v pacman >/dev/null 2>&1; then
    PACKAGES="python python-virtualenv python-pip"
fi

if ! run_cmd $PKG_ENV $PKG_INSTALL $PACKAGES; then
    echo "Erreur : impossible d'installer les paquets Python requis. Vérifiez la disponibilité des paquets et la connexion réseau."
    write_progress 100
    exit 1
fi

if ! python3 -m venv --help >/dev/null 2>&1; then
    echo "Erreur : python3-venv ne semble pas disponible après l'installation."
    write_progress 100
    exit 1
fi

if ! python3 -m pip --version >/dev/null 2>&1; then
    echo "Erreur : python3-pip ne semble pas disponible après l'installation."
    write_progress 100
    exit 1
fi

write_progress 30

echo "-> Nettoyage de l'ancien environnement virtuel..."
$SUDO rm -rf "$VENV_DIR"
write_progress 40

echo "-> Création de l'environnement Python propre..."
run_cmd python3 -m venv --clear "$VENV_DIR"
write_progress 50

echo "-> Installation des bibliothèques Python..."
run_cmd "$VENV_DIR/bin/pip" install --upgrade pip
run_cmd "$VENV_DIR/bin/pip" install --progress-bar off --no-cache-dir playwright
write_progress 60

echo "-> Installation des librairies système requises par Chromium..."
run_cmd "$VENV_DIR/bin/playwright" install-deps chromium
write_progress 70

echo "-> Préparation du répertoire de destination..."
$SUDO mkdir -p "$DATA_DIR"
$SUDO mkdir -p "$PLAYWRIGHT_BROWSERS_PATH"
write_progress 80

echo "-> Téléchargement du navigateur Chromium..."
export PLAYWRIGHT_BROWSERS_PATH="$PLAYWRIGHT_BROWSERS_PATH"
run_cmd PLAYWRIGHT_BROWSERS_PATH="$PLAYWRIGHT_BROWSERS_PATH" "$VENV_DIR/bin/playwright" install chromium
write_progress 90

echo "-> Attribution des droits finaux..."
$SUDO chown -R www-data:www-data "$VENV_DIR" || true
$SUDO chown -R www-data:www-data "$DATA_DIR" || true
write_progress 100

echo "-> Installation du widget dashboard Abaqua..."
if php "$SCRIPT_DIR/install_widget.php"; then
    echo "-> Widget dashboard Abaqua installé."
else
    echo "Erreur : installation du widget dashboard Abaqua impossible."
    exit 1
fi

echo "********************************************************"
echo "* Installation terminée avec succès !                  *"
echo "********************************************************"
exit 0