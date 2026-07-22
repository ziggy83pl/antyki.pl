#!/usr/bin/env python3
import os
import re
import sys
import uuid
import requests
import subprocess
from bs4 import BeautifulSoup
from PIL import Image
import urllib.parse

# Headers for scraping
HEADERS = {
    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36"
}

import json

def query_php_helper(action, args=None, stdin_data=None):
    """Run the secure PHP database helper inside the php container."""
    cmd = [
        "docker", "compose", "exec", "-T", "php",
        "php", "scripts/import_helper.php", action
    ]
    if args:
        cmd.extend(args)
    
    res = subprocess.run(
        cmd,
        input=stdin_data,
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
        text=True,
        encoding="utf-8"
    )
    if res.returncode != 0:
        raise Exception(f"PHP Helper Error: {res.stderr.strip()}")
    return res.stdout

def make_slug(s):
    """Generate a clean URL slug matching the PHP slug function logic."""
    s = s.strip()
    s = re.sub(r'[\s%]+', '-', s)
    s = re.sub(r'[\$:,\/=\\?]', '', s)
    replacements = {
        'Ę': 'E', 'Ó': 'O', 'Ą': 'A', 'Ś': 'S', 'Ł': 'L', 'Ż': 'Z', 'Ź': 'Z', 'Ć': 'C', 'Ń': 'N',
        'ę': 'e', 'ó': 'o', 'ą': 'a', 'ś': 's', 'ł': 'l', 'ż': 'z', 'ź': 'z', 'ć': 'c', 'ń': 'n'
    }
    for k, v in replacements.items():
        s = s.replace(k, v)
    s = s.lower()
    s = re.sub(r'[^a-z0-9\-_]', '', s)
    s = re.sub(r'-+', '-', s)
    return s

def load_states():
    """Load states from the database to map scraped locations."""
    data = query_php_helper("load_states")
    voivodeships = {}
    cities = {}
    
    for line in data.strip().split('\n'):
        if not line:
            continue
        parts = line.split('\t')
        if len(parts) >= 4:
            s_id = int(parts[0])
            parent_id = int(parts[1])
            slug = parts[2].lower()
            name = parts[3].lower()
            
            if parent_id == 0:
                voivodeships[slug] = s_id
                voivodeships[name] = s_id
            else:
                cities[slug] = (s_id, parent_id)
                cities[name] = (s_id, parent_id)
                
    return voivodeships, cities

def map_location(city_name, state_name, voivodeships, cities):
    """Map city and state names to database IDs."""
    state_id = 0
    state2_id = 0
    
    # Try mapping voivodeship (state_id)
    if state_name:
        state_clean = make_slug(state_name).replace("-wojewodztwo", "")
        for k, v in voivodeships.items():
            if state_clean in k or k in state_clean:
                state_id = v
                break
                
    # Try mapping city (state2_id)
    if city_name:
        city_clean = city_name.strip().lower()
        city_slug = make_slug(city_name)
        if city_slug in cities:
            state2_id, parent_id = cities[city_slug]
            if not state_id:
                state_id = parent_id
        elif city_clean in cities:
            state2_id, parent_id = cities[city_clean]
            if not state_id:
                state_id = parent_id
                
    # Default fallback to Śląskie if not resolved
    if not state_id:
        state_id = 12
        
    return state_id, state2_id

def is_construction_related(title, desc):
    """Verify if the listing is relevant to construction (jobs, machines, commissions)."""
    text = (title + " " + desc).lower()
    
    # Exclude keywords to ignore real estate, household items, clothing, cars
    exclude_keywords = [
        "mieszkanie", "mieszkania", "mieszkań", "domy", "działka", "dzialka", "działki", "dzialki",
        "pokój", "pokoj", "pokoje", "lokal ", "lokale", "nieruchomość", "nieruchomosc", "samochód",
        "samochod", "auto ", "motocykl", "części", "czesci", "część", "kosiarka", "kosiarki", "serwis", "szkło",
        "szklo", "serownica", "kabaret", "dekoracyjny", "bohemia", "prl", "zabudowa paki", "pokrywa skrzyni",
        "ogrody", "ogrodowe", "meble", "kuchni", "szafa"
    ]
    if any(w in text for w in exclude_keywords):
        return False
        
    construction_keywords = [
        "kopark", "ładowark", "ladowark", "minikopark", "betoniark", "rusztowan", 
        "szalunk", "wkrętark", "wiertark", "szlifierka", "młot udarowy", "młot wyburzeniowy", 
        "zagęszczark", "narzędzia", "narzedzia", "budow", "budowy", "budownic", "remont", 
        "wykończe", "wykoncze", "malowanie", "płytki", "gładzie", "kafelkow", "murarz", 
        "tynk", "cieśla", "zbrojenie", "podwykonaw", "zatrudni", "dam pracę", "dam prace", 
        "szukam pracy", "pomocnik budow", "majster", "dekarz", "hydraulik", "instalacje"
    ]
    return any(w in text for w in construction_keywords)

def map_category(title, desc):
    """Determine category_id based on words in title/description."""
    text = (title + " " + desc).lower()
    
    # Praca w budownictwie (ID 25)
    if any(w in text for w in ["dam pracę", "dam prace", "zatrudnię", "zatrudnie", "szukam pomocnika", "szukam murarza", "praca dla pomocnika", "praca murarz", "praca dekarz", "szukam pracy", "poszukuje pracy", "szuka pracy"]):
        if any(w in text for w in ["szukam pracy", "poszukuje pracy", "szuka pracy"]):
            return 27 # Szukam pracy
        elif any(w in text for w in ["pomocnik", "pomocnika"]):
            return 29 # Poszukuje pomocnika
        elif any(w in text for w in ["majster", "majstra", "murarz", "murarza", "cieśla", "cieśli"]):
            return 28 # Poszukuje majstra
        else:
            return 26 # Dam pracę
            
    # Maszyny i sprzęt (ID 8)
    if any(w in text for w in ["koparka", "ładowarka", "ladowark", "koparki", "koparko", "minikoparka", "wynajem koparki", "betoniarka", "rusztowania", "szalunki", "stemple", "wiertarka", "wkrętarka", "szlifierka", "młot udarowy"]):
        if any(w in text for w in ["koparka", "ładowarka", "ladowark", "koparki", "koparko", "minikoparka"]):
            return 9 # Koparki i ładowarki
        elif "betoniarka" in text:
            return 10 # Betoniarki
        elif any(w in text for w in ["rusztowan", "szalunk", "stemple"]):
            return 11 # Rusztowania i szalunki
        elif any(w in text for w in ["wiertarka", "wkrętarka", "szlifierka", "młot"]):
            return 12 # Elektronarzędzia
        else:
            return 13 # Wynajem sprzętu
            
    # Zlecenia Budowlane (ID 21)
    if any(w in text for w in ["zlece", "zlecę", "zlecenie", "szukam podwykonawcy", "szukam wykonawcy", "do wykonania", "remont", "wykończenie", "malowanie", "płytki", "gładzie", "docieplenia"]):
        if any(w in text for w in ["zlece budowe", "zlecę budowę", "wybudowanie domu"]):
            return 22 # Zlecę budowę
        elif any(w in text for w in ["remont", "wykończen", "malowanie", "płytki", "gładzie", "kafelkowanie"]):
            return 23 # Zlecę remont
        else:
            return 24 # Szukam podwykonawcy
            
    # Default Fallback
    return 23 # Zlecę remont (Zlecenia Budowlane)

def scrape_tablica(keyword, voivodeships, cities):
    """Scrape listings from tablica.com for a keyword."""
    search_url = f"https://tablica.com/search.html?search_text={urllib.parse.quote(keyword)}"
    print(f"Scraping tablica.com for '{keyword}'...")
    
    offers = []
    try:
        resp = requests.get(search_url, headers=HEADERS, timeout=10)
        if resp.status_code != 200:
            return []
            
        soup = BeautifulSoup(resp.text, "html.parser")
        links = soup.find_all("a", href=re.compile(r"/ogloszenie-"))
        seen_urls = set()
        
        for a in links:
            url = a.get("href")
            if not url or url in seen_urls:
                continue
            seen_urls.add(url)
            
            # Fetch detail page
            print(f"  Fetching details: {url}")
            try:
                detail_resp = requests.get(url, headers=HEADERS, timeout=10)
                if detail_resp.status_code != 200:
                    continue
                detail_soup = BeautifulSoup(detail_resp.text, "html.parser")
                
                # Title
                title_meta = detail_soup.find("meta", {"property": "og:title"})
                title = title_meta["content"] if title_meta else ""
                title_clean = re.sub(r"\s*\(.*?\)\s*", "", title).strip()
                if not title_clean:
                    continue
                    
                # Description
                desc_meta = detail_soup.find("meta", {"property": "og:description"})
                desc = desc_meta["content"] if desc_meta else ""
                
                # Filter out irrelevant listings
                if not is_construction_related(title_clean, desc):
                    print(f"    Skipping off-topic listing: '{title_clean}'")
                    continue
                    
                # Price
                price_el = detail_soup.find("span", class_="price")
                price_val = 0
                if price_el:
                    price_digits = re.sub(r"[^\d]", "", price_el.text)
                    if price_digits:
                        price_val = int(price_digits)
                        
                # Images
                images = []
                for img in detail_soup.find_all("img", class_="single-photo"):
                    src = img.get("src")
                    if src and src not in images:
                        images.append(src)
                        
                # Location and State
                location = ""
                state = ""
                loc_links = detail_soup.find_all("a", href=re.compile(r"/ogloszenia/"))
                for l_a in loc_links:
                    href = l_a.get("href")
                    text = l_a.text.strip()
                    if not href:
                        continue
                    if any(s in href.lower() for s in ["lubelskie", "lubuskie", "lodzkie", "dolnoslaskie", "kujawsko", "malopolskie", "mazowieckie", "opolskie", "podkarpackie", "podlaskie", "pomorskie", "slaskie", "swietokrzyskie", "warminsko", "wielkopolskie", "zachodniopomorskie"]):
                        state = text
                    else:
                        if text and text not in ["Start", "Dodaj", "Zaloguj się", "Cennik", "Firmy", "FAQ"]:
                            location = text
                            
                state_id, state2_id = map_location(location, state, voivodeships, cities)
                cat_id = map_category(title_clean, desc)
                
                offers.append({
                    "title": title_clean,
                    "desc": desc,
                    "price": price_val,
                    "images": images,
                    "address": location or state or "Polska",
                    "state_id": state_id,
                    "state2_id": state2_id,
                    "category_id": cat_id,
                    "type_id": 4 if "zlece" in keyword or "praca" in keyword else 3,
                    "source": "tablica.com"
                })
                
                # Limit to 5 per keyword search
                if len(offers) >= 5:
                    break
                    
            except Exception as e:
                print(f"    Error parsing detail: {e}")
                
    except Exception as e:
        print(f"  Error searching tablica.com: {e}")
        
    return offers

def scrape_sprzedajemy(keyword, voivodeships, cities):
    """Scrape listings from sprzedajemy.pl for a keyword."""
    search_url = f"https://sprzedajemy.pl/szukaj?inp_text_all={urllib.parse.quote(keyword)}"
    print(f"Scraping sprzedajemy.pl for '{keyword}'...")
    
    offers = []
    try:
        resp = requests.get(search_url, headers=HEADERS, timeout=10)
        if resp.status_code != 200:
            return []
            
        soup = BeautifulSoup(resp.text, "html.parser")
        links = soup.find_all("a", href=True)
        seen_urls = set()
        
        for a in links:
            href = a.get("href")
            # Filter standard listings
            if href and re.match(r"^/[a-zA-Z0-9\-]+-nr\d+$", href):
                url = "https://sprzedajemy.pl" + href
                if url in seen_urls:
                    continue
                seen_urls.add(url)
                
                # Fetch detail page
                print(f"  Fetching details: {url}")
                try:
                    detail_resp = requests.get(url, headers=HEADERS, timeout=10)
                    if detail_resp.status_code != 200:
                        continue
                    detail_soup = BeautifulSoup(detail_resp.text, "html.parser")
                    
                    # Title
                    title_meta = detail_soup.find("meta", {"property": "og:title"})
                    title = title_meta["content"] if title_meta else ""
                    title_clean = title.split(" - Sprzedajemy.pl")[0].strip()
                    if not title_clean:
                        continue
                        
                    # Description
                    desc_el = detail_soup.find("div", class_="offerDescription")
                    if not desc_el:
                        desc_el = detail_soup.find("span", class_="entry-content")
                    desc = desc_el.text.strip() if desc_el else ""
                    if not desc:
                        desc_meta = detail_soup.find("meta", {"property": "og:description"})
                        desc = desc_meta["content"] if desc_meta else ""
                        
                    # Filter out irrelevant listings
                    if not is_construction_related(title_clean, desc):
                        print(f"    Skipping off-topic listing: '{title_clean}'")
                        continue
                        
                    # Price
                    price_el = detail_soup.find("strong", class_="price")
                    if not price_el:
                        price_el = detail_soup.find(class_="offer-price-box")
                    price_val = 0
                    if price_el:
                        price_digits = re.sub(r"[^\d]", "", price_el.text)
                        if price_digits:
                            price_val = int(price_digits)
                            
                    # Images
                    images = []
                    for img in detail_soup.find_all("img", class_="js-gallerySlide"):
                        src = img.get("src")
                        if src:
                            if src.startswith("//"):
                                src = "https:" + src
                            if src not in images:
                                images.append(src)
                                
                    # Location
                    loc_el = detail_soup.find("span", class_="location")
                    location = loc_el.text.strip() if loc_el else ""
                    # State
                    state = ""
                    for el in detail_soup.find_all(["a", "span"]):
                        text = el.text.strip()
                        if any(s in text.lower() for s in ["lubelskie", "lubuskie", "łódzkie", "dolnośląskie", "kujawsko", "małopolskie", "mazowieckie", "opolskie", "podkarpackie", "podlaskie", "pomorskie", "śląskie", "świętokrzyskie", "warmińsko", "wielkopolskie", "zachodniopomorskie"]):
                            state = text
                            break
                            
                    state_id, state2_id = map_location(location, state, voivodeships, cities)
                    cat_id = map_category(title_clean, desc)
                    
                    offers.append({
                        "title": title_clean,
                        "desc": desc,
                        "price": price_val,
                        "images": images,
                        "address": location or "Polska",
                        "state_id": state_id,
                        "state2_id": state2_id,
                        "category_id": cat_id,
                        "type_id": 4 if "zlece" in keyword or "praca" in keyword else 3,
                        "source": "sprzedajemy.pl"
                    })
                    
                    if len(offers) >= 5:
                        break
                        
                except Exception as e:
                    print(f"    Error parsing detail: {e}")
                    
    except Exception as e:
        print(f"  Error searching sprzedajemy.pl: {e}")
        
    return offers

def import_offer_to_db(offer):
    """Insert an offer and its images into the database."""
    # Check if offer already exists by title
    exists_res = query_php_helper("check_offer_exists", [offer['title']]).strip()
    if int(exists_res) > 0:
        print(f"  Skipping duplicate offer: '{offer['title']}'")
        return
        
    slug = make_slug(offer["title"])
    code = uuid.uuid4().hex[:12]
    
    offer_data = {
        "title": offer["title"],
        "slug": slug,
        "price": offer["price"],
        "address": offer["address"],
        "category_id": offer["category_id"],
        "state_id": offer["state_id"],
        "state2_id": offer["state2_id"],
        "type_id": offer["type_id"],
        "desc": offer["desc"],
        "code": code
    }
    
    print(f"  Inserting offer: '{offer['title']}' under category ID {offer['category_id']}")
    res_json = query_php_helper("import_offer", stdin_data=json.dumps(offer_data))
    res = json.loads(res_json)
    
    if not res.get("success"):
        raise Exception(f"Failed to import offer to DB: {res.get('error')}")
    
    if res.get("status") == "duplicate":
        print(f"  Skipping duplicate offer (detected by DB): '{offer['title']}'")
        return
        
    offer_id = res["offer_id"]
    
    # Import Images
    photo_folder = "2026/06/"
    photos_dir = f"/home/aniazbyszek/Pulpit/cms/upload/photos/{photo_folder}"
    os.makedirs(photos_dir, exist_ok=True)
    
    for i, img_url in enumerate(offer["images"][:5]): # Max 5 images per listing
        try:
            # Determine extension
            ext = ".webp" if ".webp" in img_url.lower() else ".jpg"
            img_filename = f"{slug}_{i}{ext}"
            thumb_filename = f"{slug}_{i}_thumb{ext}"
            
            full_img_path = os.path.join(photos_dir, img_filename)
            full_thumb_path = os.path.join(photos_dir, thumb_filename)
            
            # Download image
            img_data = requests.get(img_url, headers=HEADERS, timeout=10).content
            with open(full_img_path, "wb") as f:
                f.write(img_data)
                
            # Create thumbnail
            with Image.open(full_img_path) as im:
                im.thumbnail((300, 300))
                im.save(full_thumb_path)
                
            # Insert into photo table
            photo_data = {
                "offer_id": offer_id,
                "position": i,
                "folder": photo_folder,
                "thumb": thumb_filename,
                "url": img_filename
            }
            photo_res_json = query_php_helper("insert_photo", stdin_data=json.dumps(photo_data))
            photo_res = json.loads(photo_res_json)
            if not photo_res.get("success"):
                print(f"    Failed to save image {img_filename} to DB: {photo_res.get('error')}")
            else:
                print(f"    Saved image {i}: {img_filename}")
                
        except Exception as e:
            print(f"    Error saving image {img_url}: {e}")

def main():
    print("Starting import of construction listings...")
    
    # Load database states
    voivodeships, cities = load_states()
    print(f"Loaded {len(voivodeships)} voivodeship mappings and {len(cities)} city mappings.")
    
    # Scrape listings from tablica.com and sprzedajemy.pl
    # Expanded keywords with working search terms
    keywords = ["koparka", "remont", "budowa", "praca", "szalunki", "rusztowanie", "szlifierka", "wiertarka", "agregat"]
    
    scraped_offers = []
    for kw in keywords:
        scraped_offers.extend(scrape_tablica(kw, voivodeships, cities))
        # Add a delay between scrapes to be friendly
        import time
        time.sleep(1)
        
    for kw in ["koparki", "szalunki", "rusztowania", "remonty-budowlane"]:
        scraped_offers.extend(scrape_sprzedajemy(kw, voivodeships, cities))
        import time
        time.sleep(1)
        
    print(f"\nScraped total of {len(scraped_offers)} listings. Importing to DB...")
    
    # Import each offer
    imported_count = 0
    for offer in scraped_offers:
        try:
            import_offer_to_db(offer)
            imported_count += 1
        except Exception as e:
            print(f"Error importing offer '{offer['title']}': {e}")
            
    print(f"\nImport finished! Imported {imported_count} listings.")
    
    # Recalculate category counts using native PHP class function
    print("Recalculating subcategory counts...")
    cmd = [
        "docker", "compose", "exec", "-T", "php",
        "php", "-r", "require 'config/config.php'; category::refreshAllSubcategories();"
    ]
    res = subprocess.run(cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
    if res.returncode == 0:
        print("Subcategory counts recalculated successfully!")
    else:
        print(f"Failed to recalculate subcategory counts: {res.stderr}")

if __name__ == "__main__":
    main()
