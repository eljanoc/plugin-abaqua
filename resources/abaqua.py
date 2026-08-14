#!/usr/bin/env python3
import sys
import time
import re
import os
import json
import traceback
from datetime import datetime
from playwright.sync_api import sync_playwright

os.environ.setdefault('PLAYWRIGHT_BROWSERS_PATH', '/var/www/.cache/ms-playwright')

RUN_ID = datetime.now().strftime('%Y%m%d%H%M%S')

def log_debug(message):
    print(f"[run:{RUN_ID}] {message}", file=sys.stderr)

# === IDENTIFICATION DU SCRIPT ===
VERSION = "3.2_LOCKED_RUNID_STABLE"
# ================================

legacy_email = sys.argv[1] if len(sys.argv) > 1 else ""
legacy_password = sys.argv[2] if len(sys.argv) > 2 else ""

ABAQUA_EMAIL = os.environ.get("ABAQUA_EMAIL", legacy_email).strip()
ABAQUA_PASSWORD = os.environ.get("ABAQUA_PASSWORD", legacy_password)

if not ABAQUA_EMAIL or not ABAQUA_PASSWORD:
    log_debug("Erreur : Identifiants manquants.")
    print(json.dumps([]))
    sys.exit(1)

ABAQUA_URL = sys.argv[4].strip() if len(sys.argv) > 4 and sys.argv[4].strip() else "www.kyrnolia.fr"

# --- FONCTIONS UTILITAIRES ---
def convertir_date_fr(date_str):
    if not date_str: return None
    date_str = str(date_str).strip()
    if re.match(r"^\d{4}-\d{2}-\d{2}", date_str):
        try: return datetime.strptime(date_str[:10], "%Y-%m-%d")
        except ValueError: pass

    mois_dict = {"janvier":"01", "février":"02", "fevrier":"02", "mars":"03", "avril":"04", "mai":"05", "juin":"06", "juillet":"07", "août":"08", "aout":"08", "septembre":"09", "octobre":"10", "novembre":"11", "décembre":"12", "decembre":"12"}
    match = re.search(r"(?<!\d)(?P<day>\d{1,2})(?:\s*er)?\s+(?P<month>janvier|février|fevrier|mars|avril|mai|juin|juillet|août|aout|septembre|octobre|novembre|décembre|decembre)\s+(?P<year>20\d{2})", date_str, re.IGNORECASE)
    if match:
        try: return datetime(int(match.group("year")), int(mois_dict.get(match.group("month").lower(), 1)), int(match.group("day")))
        except ValueError: return None
    return None

def click_previous_page(page):
    selectors = [
        "button:has(path[d*='M27.9937'])", # La flèche gauche classique
        "[aria-label*='Précédent']", 
        "[aria-label*='précédent']", 
        "button:has-text('Précédent')",
        "button:has-text('précédent')"
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


def is_login_page(page):
    try:
        url = (page.url or '').lower()
        if '/connexion' in url or '/login' in url or '/callback' in url:
            return True
    except Exception:
        pass

    selectors = [
        "input[type='email']",
        "input[name*='mail']",
        "#username",
        "input[type='password']",
        "button:has-text('Se connecter')",
        "button:has-text('Connexion')",
    ]
    for selector in selectors:
        try:
            if page.locator(selector).count() > 0:
                return True
        except Exception:
            continue
    return False

# --- METHODE 2 : FALLBACK (Ancienne méthode visuelle) ---
def get_page_lines(page):
    try: texts = page.locator('body *').all_inner_texts()
    except Exception: texts = [page.inner_text('body')]
    lines = []
    previous_line = None
    for text in texts:
        if not isinstance(text, str): continue
        for line in text.splitlines():
            line = line.strip()
            if not line or line == previous_line: continue
            lines.append(line)
            previous_line = line
    return lines

def extract_results_dom(lines, dt_limite, mois_dict, resultats, existing_datetimes):
    date_pattern = re.compile(r"(?<!\d)(?P<day>\d{1,2})(?:\s*er)?\s+(?P<month>janvier|février|fevrier|mars|avril|mai|juin|juillet|août|aout|septembre|octobre|novembre|décembre|decembre)\s+(?P<year>20\d{2})", re.IGNORECASE)
    value_pattern = re.compile(r"(?P<val>\d{1,3}(?:[ \u00A0]\d{3})*(?:[.,]\d+)?)\s*[Ll]\b")
    stop_execution = False
    today = datetime.now().date()

    for i, ligne in enumerate(lines):
        match_date = date_pattern.search(ligne)
        if not match_date: continue
        date_str = match_date.group(0)
        dt_ligne = convertir_date_fr(date_str)
        if dt_ligne and dt_ligne.date() == today: continue
        if dt_ligne and dt_limite and dt_ligne <= dt_limite:
            stop_execution = True
            break
        valeur = None
        for j in range(i, min(i + 12, len(lines))):
            candidate = lines[j]
            if j > i and date_pattern.search(candidate): break
            if "indisponible" in candidate.lower(): break
            val_match = value_pattern.search(candidate)
            if val_match:
                try:
                    valeur = float(val_match.group('val').replace('\u00A0', '').replace(' ', '').replace(',', '.'))
                    break
                except ValueError: continue
        if valeur is not None and dt_ligne:
            datetime_jeedom = f"{match_date.group('year')}-{mois_dict.get(match_date.group('month').lower(), '01')}-{match_date.group('day').zfill(2)} 23:59:59"
            if datetime_jeedom not in existing_datetimes:
                existing_datetimes.add(datetime_jeedom)
                resultats.append({'conso': valeur, 'date': date_str, 'datetime': datetime_jeedom})
                log_debug(f"   -> [DOM] Trouvé : {date_str} - {valeur} L")
    return stop_execution

# ==========================================
# === FONCTION PRINCIPALE ===
# ==========================================
def run():
    maintenant = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    log_debug(f"=== SCRIPT Abaqua {VERSION} === {maintenant} ---")

    date_limite_str = sys.argv[3].strip() if len(sys.argv) > 3 and sys.argv[3].strip() else None
    dt_limite = convertir_date_fr(date_limite_str) if date_limite_str else None
    
    if dt_limite: log_debug(f"   -> Date limite : {date_limite_str}")
    else: log_debug("   -> Aucune date limite (Mode dynamique)")

    resultats = []
    api_responses = []
    api_seen_dates = set()
    
    try:
        with sync_playwright() as p:
            browser = p.chromium.launch(headless=True, args=["--disable-blink-features=AutomationControlled"])
            context = browser.new_context(locale="fr-FR", timezone_id="Europe/Paris", viewport={"width": 1920, "height": 1080}, user_agent="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36")
            page = context.new_page()
            page.add_init_script("Object.defineProperty(navigator, 'webdriver', {get: () => undefined})")

            # --- RADAR GLOBAL PERMANENT ---
            def handle_response(response):
                url = (response.url or '').lower()
                if "/journalieres" in url and "consommations/" in url and response.status == 200:
                    try:
                        data = response.json()
                        if isinstance(data, list):
                            for item in data:
                                if not isinstance(item, dict):
                                    continue
                                date_releve = item.get('date_releve')
                                if not date_releve:
                                    continue
                                if date_releve in api_seen_dates:
                                    continue
                                api_seen_dates.add(date_releve)
                                api_responses.append(item)
                            log_debug(f"   -> 📡 API : Bloc intercepté ({len(data)} jours). Total cumulé : {len(api_responses)}")
                    except Exception as e:
                        log_debug(f"   -> ⚠️ API : Erreur de lecture JSON - {e}")

            page.on("response", handle_response)
            # ------------------------------

            log_debug("1. Connexion...")
            page.goto(f"https://{ABAQUA_URL}/callback?action=login")
            try: page.locator("button:has-text('Accepter')").first.click(timeout=3000)
            except: pass 
            
            page.fill("input[type='email'], input[name*='mail'], #username", ABAQUA_EMAIL)
            page.fill("input[type='password']", ABAQUA_PASSWORD)
            page.keyboard.press("Enter")
            page.wait_for_timeout(5000)

            if is_login_page(page):
                log_debug("   -> ❌ Échec d'authentification : la page de connexion est encore affichée.")
                print(json.dumps([]))
                sys.exit(1)

            log_debug("2. Navigation vers la consommation...")
            page.goto(f"https://{ABAQUA_URL}/consommation")
            page.wait_for_load_state("domcontentloaded")
            page.wait_for_timeout(2000)
            
            try: page.locator("text='Du mois'").first.click(timeout=5000)
            except: pass
            
            # Attente initiale confortable pour laisser le premier mois charger
            log_debug("   -> Attente initiale des données API...")
            for _ in range(30):
                if api_responses:
                    break
                page.wait_for_timeout(1000)

            # --- METHODE 1 : TRAITEMENT DE L'API ---
            if api_responses:
                log_debug("3. 🟢 UTILISATION DES DONNÉES API (Mode Global Stable)")
                existing_datetimes = set()
                today = datetime.now().date()
                
                stop_execution = False
                pages_vides = 0
                
                while not stop_execution:
                    sorted_days = []
                    for jour in api_responses:
                        date_releve = jour.get('date_releve')
                        conso_dict = jour.get('consommation')
                        if not date_releve or not conso_dict:
                            continue
                        litre = conso_dict.get('litre')
                        if litre is None:
                            continue

                        try:
                            dt_ligne = datetime.strptime(date_releve[:10], "%Y-%m-%d")
                        except ValueError:
                            log_debug(f"   -> ⚠️ API : date invalide ignorée ({date_releve})")
                            continue

                        sorted_days.append((dt_ligne, date_releve[:10], litre))

                    sorted_days.sort(key=lambda x: x[0], reverse=True)

                    for dt_ligne, date_releve, litre in sorted_days:
                        if dt_ligne.date() == today:
                            continue

                        if dt_limite and dt_ligne <= dt_limite:
                            stop_execution = True
                            break

                        datetime_jeedom = f"{date_releve} 23:59:59"
                        if datetime_jeedom not in existing_datetimes:
                            existing_datetimes.add(datetime_jeedom)
                            resultats.append({'conso': float(litre), 'date': date_releve, 'datetime': datetime_jeedom})
                            log_debug(f"   -> 🎯 API : {date_releve} - {litre} L")

                    if stop_execution:
                        log_debug("   -> Date limite atteinte via API.")
                        break
                        
                    # Pagination sécurisée
                    taille_api_avant_clic = len(api_responses)
                    if click_previous_page(page):
                        log_debug("   -> Demande du mois précédent...")
                        
                        # On attend que le radar attrape de nouvelles données (10s max)
                        attente_reseau = 0
                        while len(api_responses) == taille_api_avant_clic and attente_reseau < 10:
                            page.wait_for_timeout(1000)
                            attente_reseau += 1
                        
                        # Si au bout de 10s la taille de api_responses n'a pas bougé, c'est qu'il n'y a plus de mois dispo
                        if len(api_responses) == taille_api_avant_clic:
                            pages_vides += 1
                            log_debug(f"   -> ℹ️ Pas de nouveau mois reçu (Compteur vide : {pages_vides})")
                            if pages_vides >= 2:
                                log_debug("   -> 🛑 Fin de l'historique API atteinte.")
                                break
                        else:
                            pages_vides = 0
                    else:
                        log_debug("   -> 🛑 Bouton précédent introuvable ou fin de pagination.")
                        break

            # --- METHODE 2 : FALLBACK DOM ---
            else:
                log_debug("3. 🟠 API INTROUVABLE : Bascule sur le Fallback DOM")
                existing_datetimes = set()
                stop_execution = False
                pages_vides = 0
                mois_dict = {"janvier":"01", "février":"02", "fevrier":"02", "mars":"03", "avril":"04", "mai":"05", "juin":"06", "juillet":"07", "août":"08", "aout":"08", "septembre":"09", "octobre":"10", "novembre":"11", "décembre":"12", "decembre":"12"}

                while True:
                    lignes = get_page_lines(page)
                    resultats_avant = len(resultats)
                    stop_execution = extract_results_dom(lignes, dt_limite, mois_dict, resultats, existing_datetimes)

                    if stop_execution:
                        break
                    
                    if not dt_limite:
                        if len(resultats) == resultats_avant:
                            pages_vides += 1
                            if pages_vides >= 2: break
                        else:
                            pages_vides = 0 

                    if click_previous_page(page):
                        page.wait_for_timeout(4000)
                    else:
                        break

            # --- BILAN ---
            if resultats: log_debug(f"   -> ✅ Bilan : {len(resultats)} nouvelle(s) valeur(s).")
            else: log_debug("   -> ℹ️ Bilan : Aucune nouvelle donnée (ou déjà à jour).")
            
            browser.close()
            log_debug(f"\n--- Fin du script ---\n")

    except Exception as e:
        log_debug(f"\n❌ ERREUR CRITIQUE : {e}\n{traceback.format_exc()}")
        print(json.dumps([]))
        sys.exit(1)

    print(json.dumps(resultats))

if __name__ == "__main__":
    run()