#!/usr/bin/env python3
import sys
import time
import re
import os
import json
import traceback
from datetime import datetime
from playwright.sync_api import sync_playwright

# Forcer le bon cache Playwright quand le script est lancé depuis Jeedom
os.environ.setdefault('PLAYWRIGHT_BROWSERS_PATH', '/var/www/.cache/ms-playwright')

# Fonction pour que les print aillent dans le log Jeedom
def log_debug(message):
    print(message, file=sys.stderr)

# === IDENTIFICATION DU SCRIPT ===
VERSION = "1.64.7_DYNAMIQUE"
# ================================

legacy_email = sys.argv[1] if len(sys.argv) > 1 else ""
legacy_password = sys.argv[2] if len(sys.argv) > 2 else ""

ABAQUA_EMAIL = os.environ.get("ABAQUA_EMAIL", legacy_email).strip()
ABAQUA_PASSWORD = os.environ.get("ABAQUA_PASSWORD", legacy_password)

if not ABAQUA_EMAIL or not ABAQUA_PASSWORD:
    log_debug("Erreur : Identifiants manquants.")
    print(json.dumps([]))
    sys.exit(1)

# On récupère le fournisseur en argument 4 (s'il existe), sinon on met Kyrnolia par défaut
if len(sys.argv) > 4 and sys.argv[4].strip():
    ABAQUA_URL = sys.argv[4].strip()
else:
    ABAQUA_URL = "www.kyrnolia.fr"

def convertir_date_fr(date_str):
    if not date_str:
        return None
    
    date_str = str(date_str).strip()
    
    # 1. Si c'est une date venant de Jeedom (ex: "2026-07-21 23:59:59" ou "2026-07-21")
    if re.match(r"^\d{4}-\d{2}-\d{2}", date_str):
        try:
            return datetime.strptime(date_str[:10], "%Y-%m-%d")
        except ValueError:
            pass

    # 2. Sinon format texte français du site web
    mois_dict = {"janvier":"01", "février":"02", "fevrier":"02", "mars":"03", "avril":"04", "mai":"05", "juin":"06", "juillet":"07", "août":"08", "aout":"08", "septembre":"09", "octobre":"10", "novembre":"11", "décembre":"12", "decembre":"12"}
    match = re.search(r"(?<!\d)(?P<day>\d{1,2})(?:\s*er)?\s+(?P<month>janvier|février|fevrier|mars|avril|mai|juin|juillet|août|aout|septembre|octobre|novembre|décembre|decembre)\s+(?P<year>20\d{2})", date_str, re.IGNORECASE)
    if match:
        try:
            jour = int(match.group("day"))
            mois = int(mois_dict.get(match.group("month").lower(), 1))
            annee = int(match.group("year"))
            return datetime(annee, mois, jour)
        except ValueError:
            return None
    return None

def get_page_lines(page):
    try:
        texts = page.locator('body *').all_inner_texts()
    except Exception:
        texts = [page.inner_text('body')]

    lines = []
    previous_line = None
    for text in texts:
        if not isinstance(text, str):
            continue
        for line in text.splitlines():
            line = line.strip()
            if not line:
                continue
            if line == previous_line:
                continue
            lines.append(line)
            previous_line = line
    return lines

def click_previous_page(page):
    selectors = [
        "button:has(path[d*='M27.9937'])",
        "button:has-text('Précédent')",
        "button:has-text('précédent')",
        "button:has-text('Mois précédent')",
        "a:has-text('Précédent')",
        "a:has-text('précédent')",
        "[aria-label*='Précédent']",
        "[aria-label*='précédent']",
    ]
    for selector in selectors:
        try:
            element = page.locator(selector).first
            if element.is_visible(timeout=2000):
                element.click(force=True)
                return True
        except Exception:
            continue
    return False


def extract_results(lines, dt_limite, mois_dict, resultats, existing_datetimes):
    date_pattern = re.compile(r"(?<!\d)(?P<day>\d{1,2})(?:\s*er)?\s+(?P<month>janvier|février|fevrier|mars|avril|mai|juin|juillet|août|aout|septembre|octobre|novembre|décembre|decembre)\s+(?P<year>20\d{2})", re.IGNORECASE)
    value_pattern = re.compile(r"(?P<val>\d{1,3}(?:[ \u00A0]\d{3})*(?:[.,]\d+)?)\s*[Ll]\b")
    stop_execution = False
    today = datetime.now().date()

    for i, ligne in enumerate(lines):
        match_date = date_pattern.search(ligne)
        if not match_date:
            continue

        date_str = match_date.group(0)
        dt_ligne = convertir_date_fr(date_str)
        if dt_ligne is None:
            continue

        if dt_ligne.date() > today:
            continue

        if dt_limite and dt_ligne <= dt_limite:
            stop_execution = True
            break

        valeur = None
        for j in range(i + 1, min(i + 3, len(lines))):
            candidate = lines[j]
            if date_pattern.search(candidate):
                continue
            if "indisponible" in candidate.lower():
                continue
            val_match = value_pattern.search(candidate)
            if val_match:
                val_str = val_match.group('val').replace('\u00A0', '').replace(' ', '').replace(',', '.')
                try:
                    valeur = float(val_str)
                    break
                except ValueError:
                    continue

        if valeur is not None and dt_ligne:
            day = match_date.group('day').zfill(2)
            month = mois_dict.get(match_date.group('month').lower(), '01')
            year = match_date.group('year')
            datetime_jeedom = f"{year}-{month}-{day} 23:59:59"
            if datetime_jeedom not in existing_datetimes:
                existing_datetimes.add(datetime_jeedom)
                resultats.append({'conso': valeur, 'date': date_str, 'datetime': datetime_jeedom})
                log_debug(f"   -> 🎯 Trouvé : {date_str} - {valeur} L")

    return stop_execution


def run():
    maintenant = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    log_debug(f"=== SCRIPT Abaqua {VERSION} === {maintenant} ---")

    # --- LOGIQUE DE DATE LIMITE ---
    date_limite_str = sys.argv[3].strip() if len(sys.argv) > 3 and sys.argv[3].strip() else None
    dt_limite = convertir_date_fr(date_limite_str) if date_limite_str else None
    
    if dt_limite:
        log_debug(f"   -> Date limite reçue de Jeedom : {date_limite_str}")
    else:
        log_debug("   -> Aucune date limite reçue (Mode dynamique activé)")
        
    log_debug(f"   -> Fournisseur ciblé : {ABAQUA_URL}")
    # --------------------------------

    stop_execution = False
    resultats = []
    pages_vides_consecutives = 0
    
    mois_dict = {"janvier":"01", "février":"02", "fevrier":"02", "mars":"03", "avril":"04", "mai":"05", "juin":"06", "juillet":"07", "août":"08", "aout":"08", "septembre":"09", "octobre":"10", "novembre":"11", "décembre":"12", "decembre":"12"}

    # --- DÉBUT DU BLOC DE SÉCURITÉ ---
    try:
        with sync_playwright() as p:
            browser = p.chromium.launch(
                headless=True,
                args=["--disable-blink-features=AutomationControlled"]
            )
            
            context = browser.new_context(
                locale="fr-FR", 
                timezone_id="Europe/Paris",
                viewport={"width": 1920, "height": 1080},
                user_agent="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
            )
            
            page = context.new_page()
            page.add_init_script("Object.defineProperty(navigator, 'webdriver', {get: () => undefined})")

            log_debug("1. Connexion...")
            page.goto(f"https://{ABAQUA_URL}/callback?action=login")
            try: 
                page.locator("button:has-text('Accepter')").first.click(timeout=3000)
            except: 
                pass 
            
            page.wait_for_selector("input[type='email'], input[name*='mail'], #username")
            page.fill("input[type='email'], input[name*='mail'], #username", ABAQUA_EMAIL)
            page.fill("input[type='password']", ABAQUA_PASSWORD)
            
            page.keyboard.press("Enter")
            page.wait_for_timeout(5000)

            log_debug("2. Navigation...")
            page.wait_for_load_state("domcontentloaded")
            page.goto(f"https://{ABAQUA_URL}/consommation")
            page.wait_for_load_state("domcontentloaded")
            time.sleep(3)
            
            try: 
                page.locator("text='Du mois'").first.click(timeout=5000)
                log_debug("   -> Bouton 'Du mois' cliqué avec succès.")
            except Exception as e: 
                log_debug(f"   -> ⚠️ Attention: Impossible de cliquer sur 'Du mois'. ({e})")
            time.sleep(5)
            
            log_debug("3. Extraction des données...")
            existing_datetimes = set()
            while True:
                lignes = get_page_lines(page)
                
                resultats_avant_page = len(resultats)
                stop_execution = extract_results(lignes, dt_limite, mois_dict, resultats, existing_datetimes)

                if stop_execution:
                    log_debug(f"   -> Date limite atteinte. Arrêt normal.")
                    break
                
                # Gestion dynamique si aucune date limite n'a été fournie
                resultats_apres_page = len(resultats)
                if not dt_limite:
                    if resultats_apres_page == resultats_avant_page:
                        pages_vides_consecutives += 1
                        log_debug(f"   -> ℹ️ Page sans donnée exploitable (Compteur vide : {pages_vides_consecutives})")
                        if pages_vides_consecutives >= 2:  
                            log_debug("   -> 🛑 Fin de l'historique réel atteinte (Mode dynamique). Arrêt.")
                            break
                    else:
                        pages_vides_consecutives = 0 

                if click_previous_page(page):
                    log_debug("   -> Clic sur la page précédente détecté.")
                    time.sleep(5)
                else:
                    log_debug("   -> Bouton précédent introuvable ou pagination terminée.")
                    break
                
            if not resultats:
                if stop_execution:
                    log_debug("   -> ℹ️ Bilan : Aucune nouvelle donnée à récupérer, Jeedom est déjà à jour.")
                else:
                    log_debug("   -> ⚠️ Bilan : Aucun relevé n'a pu être extrait.")
            else:
                log_debug(f"   -> ✅ Bilan : {len(resultats)} nouvelle(s) valeur(s) extraite(s).")

            browser.close()
            maintenant = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
            log_debug(f"\n---Fin du script normale: {maintenant} ---\n")

    except Exception as e:
        log_debug("\n===========================================")
        log_debug("❌ ERREUR CRITIQUE DANS ABAQUA")
        log_debug("===========================================")
        log_debug(f"Type d'erreur : {type(e).__name__}")
        log_debug(f"Message       : {str(e)}")
        log_debug("--- TRACEBACK COMPLET ---")
        log_debug(traceback.format_exc())
        log_debug("===========================================\n")
        
        print(json.dumps([]))
        sys.exit(1)

    print(json.dumps(resultats))

if __name__ == "__main__":
    run()