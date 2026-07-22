
# Zapisz pełny raport TODO do pliku
todo_content = """# TODO.md — OgłoszeniaNova Admin Panel
## Kompleksowa lista zmian: bezpieczeństwo, optymalizacja, migracja Bootstrap 5

---

## 🔴 PRIORYTET 1: Bezpieczeństwo (KRYTYCZNE)

### PHP Backend
- [x] **SQL Injection w `statistics_ajax.php`** — default case w `plot_select()` ma błędne parametry SQL
  ```php
  // OBECNIE (BŁĄD):
  $sth = $db->prepare('SELECT date, count(1) as number FROM '._DB_PREFIX_.'logs_offer WHERE 1=2 :date_from :date_to');
  // POPRAWKA: Usunąć ten case lub dodać poprawne AND date>=:date_from AND date<=:date_to
  ```
- [x] **Brak CSRF w `statistics_ajax.php`** — dodać `checkToken()` przed przetwarzaniem danych
- [x] **Brak walidacji dat w `statistics_ajax.php`** — `$_POST['date_from']` i `$_POST['date_to']` przekazywane bezpośrednio do SQL
- [x] **Brak castowania ID w `ajax.php`** — `$post['id']>0` nie zabezpiecza przed string injection w innych miejscach
- [x] **Globalne zmienne `$db`** w `admin.class.php` — zamienić na Dependency Injection
- [x] **MD5 fallback w `admin.class.php::login()`** — usunąć wsparcie dla MD5, wymusić reset hasła
- [x] **Brak regeneracji session ID po loginie** — dodać `session_regenerate_id(true)`
- [x] Brak walidacji IP przy sesji — porównywać IP z sesji z aktualnym
- [x] Brak limitu czasu sesji — dodano weryfikację i odliczanie JS w panelu
- [x] **Brak `HttpOnly; Secure; SameSite=Strict` w ciasteczkach sesji
- [x] **Path Traversal w `admin.class.php`** — `basename(dirname($_SERVER['REQUEST_URI']))` może być manipulowane
- [x] **Brak rate limiting per-user w `admin.class.php::login()`** — limit jest tylko per-IP
- [x] **Brak obsługi błędów w `statistics_ajax.php`** — brak try-catch dla zapytań SQL

### JavaScript (`engine_admin.js`)
- [x] **Brak deklaracji zmiennych** — wszystkie zmienne są globalne (brak `var`/`let`/`const`)
  ```javascript
  // OBECNIE:
  $target = $('.'+object.data('target'));  // globalna!
  // POPRAWKA:
  const $target = $('.'+object.data('target'));
  ```
- [x] **Brak obsługi błędów AJAX** — brak `.fail()` w `$.post`
- [x] **Niebezpieczne przekierowanie** — `window.location.href = window.location` zamiast `reload()`
- [x] **Brak sanityzacji danych** — dane z `data-*` wysyłane bezpośrednio do serwera

---

## 🟡 PRIORYTET 2: Migracja Bootstrap 3 → 5

### Globalne zmiany (wszystkie pliki HTML/Twig)
- [x] **Klasy grid** — `col-md-*` → `col-md-*` (pozostaje), `col-lg-offset-*` → `offset-lg-*`
- [x] **Klasy offset** — `col-md-offset-*` → `offset-md-*`
- [x] **Klasy text** — `text-right` → `text-end`, `text-left` → `text-start`
- [x] **Klasy float** — `pull-right` → `float-end`, `pull-left` → `float-start`
- [x] **Klasy center** — `center-block` → `mx-auto d-block`
- [x] **Klasy hidden** — `hidden-*` → `d-none d-*-block`
- [x] **Klasy visible** — `visible-*` → `d-block d-*-none`
- [x] **Klasy label** — `label label-*` → `badge bg-*`
- [x] **Klasy well** — `well` → `card card-body`
- [x] **Klasy panel** — `panel panel-*` → `card`
- [x] **Klasy panel-heading** → `card-header`
- [x] **Klasy panel-body** → `card-body`
- [x] **Klasy panel-footer** → `card-footer`
- [x] **Klasy thumbnail** → `img-thumbnail`
- [x] **Klasy list-group-item** — sprawdzić zmiany w padding/margin

### Formularze
- [x] **`form-horizontal`** — USUNIĘTE w BS5, użyć `row` + `col-*` z `g-3` (gutters)
- [x] **`form-group`** → `mb-3` (lub `mb-3 row` dla horizontal)
- [x] **`control-label`** → `form-label` (lub `col-form-label` dla horizontal)
- [x] **`form-control-static`** → `form-control-plaintext`
- [x] **`input-group-addon`** → `input-group-text`
- [x] **`input-group-btn`** → usunięte, przycisk bezpośrednio w `input-group`
- [x] **`help-block`** → `form-text` + `text-muted`
- [x] **`has-error`/`has-success`/`has-warning`** → `is-invalid`/`is-valid` na input + `invalid-feedback`/`valid-feedback`
- [x] **`help-block`** → `form-text` (zamiast `invalid-feedback` dla opisów)
- [x] **`form-inline`** → użyć `row row-cols-lg-auto g-3 align-items-center`

### Przyciski
- [x] **`btn-default`** → `btn-secondary`
- [x] **`btn-xs`** → usunięte, użyć własnych klas lub `btn-sm`
- [x] **`btn-block`** → `d-grid gap-2` lub `w-100`
- [x] **`text-uppercase`** — pozostaje, ale można rozważyć utility classes

### Tabele
- [x] **`table-condensed`** → `table-sm`
- [x] **`table-responsive`** → `table-responsive` (pozostaje, ale sprawdzić breakpointy)

### Nawigacja i komponenty
- [x] **`navbar-default`** → `navbar-light bg-light`
- [x] **`navbar-inverse`** → `navbar-dark bg-dark`
- [x] **`nav-stacked`** → `flex-column` w `nav`
- [x] **`navbar-fixed-bottom`** → `fixed-bottom`
- [x] **`navbar-fixed-top`** → `fixed-top`
- [x] **`breadcrumb`** — sprawdzić strukturę (może wymagać `nav` z `aria-label`)

### Modale (Bootstrap Modal)
- [x] **Atrybuty data** — `data-toggle="modal"` → `data-bs-toggle="modal"`
- [x] **Atrybuty target** — `data-target="#id"` → `data-bs-target="#id"`
- [x] **Atrybuty dismiss** — `data-dismiss="modal"` → `data-bs-dismiss="modal"`
- [x] **Struktura modal** — sprawdzić czy wymaga `modal-dialog-centered` itp.
- [x] **`modal-title`** — pozostaje, ale sprawdzić padding
- [x] **`close`** → `btn-close` (zmiana struktury: `<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>`)
  ```html
  <!-- BS3 -->
  <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
  <!-- BS5 -->
  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
  ```

### Dropdowny
- [x] **`dropdown-toggle`** — dodać `data-bs-toggle="dropdown"` (zamiast `data-toggle`)
- [x] **`data-toggle="dropdown"`** → `data-bs-toggle="dropdown"`
- [x] **`caret`** — usunięte, dodać własny arrow lub użyć `dropdown-toggle` (BS5 dodaje automatycznie)

### Tooltipy i Popovery
- [x] **`data-toggle="tooltip"`** → `data-bs-toggle="tooltip"`
- [x] **`data-toggle="popover"`** → `data-bs-toggle="popover"`
- [x] **Inicjalizacja JS** — `$("[data-toggle='tooltip']").tooltip()` → nowa składnia Bootstrap 5

### Glyphicons → Bootstrap Icons / Font Awesome
- [x] **USUNIĘTE w BS5** — Glyphicons nie są częścią Bootstrap 5
- [x] **Opcja 1: Bootstrap Icons** — `npm i bootstrap-icons`, klasy `bi bi-*`
- [x] **Opcja 2: Font Awesome** — `npm i @fortawesome/fontawesome-free`, klasy `fas fa-*`
- [x] **Wymiana wszystkich `glyphicon glyphicon-*`** na wybraną bibliotekę
  ```html
  <!-- BS3 -->
  <span class="glyphicon glyphicon-user"></span>
  <!-- BS5 + Bootstrap Icons -->
  <i class="bi bi-person"></i>
  <!-- BS5 + Font Awesome -->
  <i class="fas fa-user"></i>
  ```

### JavaScript API
- [x] **jQuery dependency** — BS5 nie wymaga jQuery, ale może współpracować
- [x] **`$('#modal').modal('show')`** → `new bootstrap.Modal(document.getElementById('modal')).show()`
- [x] **`$('#tooltip').tooltip()`** → `new bootstrap.Tooltip(document.getElementById('tooltip'))`
- [x] **`$('#dropdown').dropdown()`** → `new bootstrap.Dropdown(document.getElementById('dropdown'))`
- [x] **Eventy** — `shown.bs.modal` (pozostaje), ale sprawdzić wszystkie eventy

### Pliki do zmiany (szczegółowo per plik):

#### `login.html`
- [x] `col-md-4 col-md-offset-4` → `col-md-4 offset-md-4`
- [x] `panel panel-default` → `card`
- [x] `panel-heading` → `card-header`
- [x] `panel-body` → `card-body`
- [x] `btn btn-success btn-block` → `btn btn-success w-100` (lub `d-grid`)
- [x] `navbar navbar-fixed-bottom` → `navbar fixed-bottom`
- [x] `text-center small` → `text-center text-muted` (small pozostaje, ale text-muted dla koloru)

#### `admin.html`
- [x] Wszystkie modale — zmiana `data-toggle` → `data-bs-toggle`, `data-target` → `data-bs-target`
- [x] Wszystkie `close` → `btn-close`
- [x] `panel panel-default` → `card`
- [x] `panel-heading` → `card-header`
- [x] `panel-body` → `card-body`
- [x] `panel-title` → `card-title` (lub h5/h6 z klasą)
- [x] `table` — sprawdzić czy wymaga `table-sm` zamiast `table-condensed`
- [x] `text-danger` — pozostaje
- [x] `glyphicon glyphicon-*` → nowe ikony
- [x] `btn btn-danger` / `btn btn-success` / `btn btn-primary` — pozostają
- [x] `form-control` — pozostaje
- [x] `text-center` — pozostaje

#### `article.html`, `articles.html`, `categories.html`, `info.html`, `info_page.html`, `index_page.html`, `login_page.html`, `mailing.html`, `mails.html`, `offers.html`, `option.html`, `options.html`, `settings*.html`, `states.html`, `statistics.html`, `types.html`, `users.html`
- [x] Wszystkie powyższe zmiany globalne
- [x] Wszystkie komponenty modal — aktualizacja atrybutów data-*
- [x] Wszystkie formularze — aktualizacja klas formularzy
- [x] Wszystkie panele — zamiana na karty
- [x] Wszystkie tabele — sprawdzić `table-condensed` → `table-sm`

#### `404.html`
- [x] `text-danger` — pozostaje
- [x] `glyphicon glyphicon-ban-circle` → nowa ikona

#### `settings.html` (najwięcej zmian)
- [x] `form-horizontal` → `row g-3` z `col-sm-*`
- [x] `control-label` → `col-form-label`
- [x] `col-sm-3 control-label` → `col-sm-3 col-form-label`
- [x] `col-sm-9` → pozostaje, ale w `row`
- [x] `col-sm-9 col-sm-offset-3` → `col-sm-9 offset-sm-3`
- [x] `col-sm-10 col-sm-offset-2` → `col-sm-10 offset-sm-2`
- [x] `input-group-addon` → `input-group-text`
- [x] `help-block` → `form-text text-muted`
- [x] `has-error` → `is-invalid` (jeśli używane)
- [x] Wszystkie checkboxy w `form-group` — sprawdzić czy wymagają `form-check`
  ```html
  <!-- BS3 -->
  <label><input type="checkbox" name="..."> Tekst</label>
  <!-- BS5 -->
  <div class="form-check">
    <input class="form-check-input" type="checkbox" name="..." id="...">
    <label class="form-check-label" for="...">Tekst</label>
  </div>
  ```

#### `statistics.html`
- [x] `form-horizontal` → `row g-3`
- [x] `col-md-3`, `col-md-2` — pozostają, ale sprawdzić gutters
- [x] `btn btn-primary text-uppercase` — pozostaje
- [x] `datepicker` — sprawdzić kompatybilność z BS5 (może wymagać aktualizacji pluginu)

#### `users.html`, `offers.html`, `logs_*.html`
- [x] `form-horizontal` → `row g-3`
- [x] `form-group form-group-sm` → `mb-3 row`
- [x] `col-md-*` — pozostają
- [x] `text-right` → `text-end`
- [x] `table parent_select_checkbox table-striped table-bordered table-condensed` → `table parent_select_checkbox table-striped table-bordered table-sm`
- [x] Wszystkie `glyphicon glyphicon-*` → nowe ikony
- [x] Wszystkie modale — aktualizacja

---

## 🟢 PRIORYTET 3: Optymalizacja i Modernizacja

### JavaScript
- [x] **Usunąć jQuery** — przepisać `engine_admin.js` na Vanilla JS (lub zostawić jQuery, ale zmodernizować)
- [x] **ES6+** — użyć `const`/`let`, arrow functions, template literals
- [x] **Moduły** — podzielić kod na moduły (np. `modal.js`, `ajax.js`, `forms.js`)
- [x] **Event delegation** — poprawić (obecnie jest, ale można usprawnić)
- [x] **Debounce/Throttle** — dodać dla eventów scroll/resize (jeśli używane)
- [ ] **Lazy loading** — dla obrazków w tabelach (`loading="lazy"`)
- [x] **Obsługa błędów** — dodać `try-catch` i `Promise.catch`
- [x] **Fetch API** — zamiast `$.post` użyć `fetch()`
  ```javascript
  // NOWOCZESNIE:
  fetch('php/ajax.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({data: mydata, send: 'ok'})
  })
  .then(r => r.json())
  .then(data => window.location.reload())
  .catch(err => console.error(err));
  ```

### PHP
- [ ] **PHP 8.1+** — użyć typów (return types, param types), union types
- [ ] **PDO** — już jest, ale dodać `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`
- [ ] **Prepared statements** — wszędzie (braki w `statistics_ajax.php`)
- [x] **Dependency Injection** — zamiast `global $db`
- [x] **Namespace** — dodać `namespace App\\Admin;`
- [x] **Autoloading** — PSR-4 z Composer
- [ ] **Exceptions** — własne klasy wyjątków
- [ ] **Validation** — dedykowana klasa do walidacji (zamiast rozproszonych sprawdzeń)
- [ ] **Sanitization** — dedykowana klasa do czyszczenia danych wejściowych
- [ ] **Logging** — Monolog zamiast własnej tabeli `admin_logs`
- [ ] **Configuration** — zamiast `global $settings`, użyć klasy Config
- [ ] **Router** — zamiast `?controller=...`, użyć pretty URLs z routerem
- [ ] **Middleware** — do autentykacji, CSRF, rate limiting
- [ ] **Template Engine** — Twig jest OK, ale zaktualizować do wersji 3.x
- [ ] **Asset Pipeline** — Vite/Webpack do bundlowania CSS/JS
- [ ] **Composer** — zarządzanie zależnościami

### Baza danych
- [ ] **Indeksy** — dodać brakujące indeksy na często wyszukiwanych kolumnach
- [ ] **Migracje** — Phinx/Laravel Migrations zamiast ręcznych zmian
- [ ] **Seedy** — do testowych danych
- [ ] **Backup** — automatyczne backupy

### CSS
- [ ] **Sass/SCSS** — zamiast plain CSS
- [ ] **CSS Variables** — dla kolorów, spacingu
- [ ] **Custom Properties** — `var(--primary-color)`
- [ ] **Utility-first** — rozważyć Tailwind CSS (opcjonalnie)
- [ ] **Dark mode** — `prefers-color-scheme: dark`
- [ ] **Responsive images** — `srcset`, `sizes`
- [ ] **Container queries** — dla komponentów (nowoczesne)

### HTML/Twig
- [ ] **Semantic HTML** — `<header>`, `<main>`, `<footer>`, `<section>`, `<article>`
- [ ] **A11y** — `aria-label`, `role`, `tabindex` tam gdzie potrzebne
- [ ] **WAI-ARIA** — poprawić w modalach, formularzach
- [ ] **Lang attribute** — `<html lang="pl">` (lub dynamicznie)
- [ ] **Meta tags** — `viewport` jest, ale dodać `theme-color`, `description`
- [ ] **Favicon** — wszystkie rozmiary (favicon.ico, apple-touch-icon, manifest)
- [ ] **Open Graph** — dla sharingu w social media
- [ ] **Schema.org** — structured data dla SEO

### Performance
- [ ] **Minifikacja** — CSS/JS (w produkcji)
- [ ] **Gzip/Brotli** — kompresja na serwerze
- [ ] **CDN** — dla Bootstrap, jQuery (jeśli zostaje), ikon
- [ ] **Caching** — Redis/Memcached dla sesji i danych
- [ ] **OPcache** — włączyć w PHP
- [ ] **HTTP/2** — push dla krytycznych zasobów
- [ ] **Service Worker** — PWA capabilities (opcjonalnie)
- [ ] **Lighthouse** — audyt performance, accessibility, best practices, SEO

---

## 🔵 PRIORYTET 4: Funkcjonalności

### Nowe funkcje
- [ ] **API REST** — dla AJAX (zamiast `php/ajax.php`)
- [ ] **WebSockets** — real-time notyfikacje (opcjonalnie)
- [ ] **2FA** — TOTP dla adminów (Google Authenticator)
- [ ] **RBAC** — Role-Based Access Control (zamiast tylko admin/moderator)
- [ ] **Audit log** — szczegółowy log zmian (kto, co, kiedy)
- [ ] **Wersjonowanie** — wersje ofert, artykułów (soft delete + historia)
- [ ] **Bulk actions** — masowe operacje na ofertach/użytkownikach (już częściowo jest)
- [ ] **Export** — CSV/Excel/PDF z danymi
- [ ] **Import** — CSV z ofertami/użytkownikami
- [ ] **Scheduler** — cron do automatycznych zadań (zamiast ręcznego odświeżania)
- [ ] **Email templates** — lepszy edytor (zamiast CKEditor dla maili)
- [ ] **Media library** — lepszy manager plików (zamiast RoxyFileman)
- [ ] **Search** — full-text search (Elasticsearch/Meilisearch)
- [ ] **Filtry** — zaawansowane filtrowanie w tabelach
- [ ] **Sortowanie** — drag-and-drop zamiast strzałek
- [ ] **Dashboard** — wykresy, statystyki, quick actions
- [ ] **Notifications** — toast notifications zamiast alertów
- [ ] **Keyboard shortcuts** — dla power-userów
- [ ] **Bulk upload** — drag-and-drop zdjęć
- [ ] **Image optimization** — automatyczna kompresja WebP/AVIF
- [ ] **Responsive tables** — lepsze UX na mobile
- [ ] **Mobile app** — PWA lub natywna (opcjonalnie)

---

## 📋 Checklist per plik

### Pliki PHP
| Plik | Bezpieczeństwo | Optymalizacja | PHP 8+ | Testy |
|------|---------------|---------------|--------|-------|
| `admin.class.php` | 🔴 | 🟡 | 🟢 | 🔴 |
| `ajax.php` | 🔴 | 🟡 | 🟢 | 🔴 |
| `statistics_ajax.php` | 🔴 | 🔴 | 🟢 | 🔴 |

### Pliki JS
| Plik | Bezpieczeństwo | ES6+ | Moduły | Testy |
|------|---------------|------|--------|-------|
| `engine_admin.js` | 🔴 | 🔴 | 🔴 | 🔴 |

### Pliki HTML/Twig (wszystkie)
| Zmiana | Status |
|--------|--------|
| Bootstrap 5 classes | 🟢 |
| Semantic HTML | 🟡 |
| A11y | 🟡 |
| Responsive | 🟡 |

---

## 🛠️ Narzędzia rekomendowane

- **Bundler**: Vite (szybki, nowoczesny) lub Webpack
- **CSS**: Sass/SCSS lub Tailwind CSS
- **JS**: Vanilla ES6+ lub Vue.js 3 (dla bardziej interaktywnego UI)
- **PHP**: PHP 8.2+, Composer, PSR-4 autoloading
- **DB**: MySQL 8.0+ lub PostgreSQL 15+
- **Cache**: Redis
- **Search**: Meilisearch (prostszy niż Elasticsearch)
- **Testing**: PHPUnit (PHP), Jest/Vitest (JS), Playwright (E2E)
- **Linting**: PHP_CodeSniffer, ESLint, Stylelint
- **Formatting**: PHP-CS-Fixer, Prettier
- **CI/CD**: GitHub Actions lub GitLab CI
- **Monitoring**: Sentry (błędy), New Relic (performance)

---

## 📅 Sugerowany timeline

### Faza 1: Bezpieczeństwo (1-2 tygodnie)
- Poprawić wszystkie luki w PHP
- Dodać CSRF, walidację, sanitization
- Zaktualizować sesje i logowanie

### Faza 2: Bootstrap 5 (2-3 tygodnie)
- Zmienić wszystkie klasy HTML
- Zamienić Glyphicons na Bootstrap Icons
- Przepisać JS modali/dropdownów
- Przetestować wszystkie widoki

### Faza 3: Modernizacja JS (1 tydzień)
- Przepisać `engine_admin.js` na ES6+
- Dodać moduły, obsługę błędów
- Zaktualizować pluginy (datepicker, CKEditor, jqPlot)

### Faza 4: Refaktoryzacja PHP (2-3 tygodnie)
- Dependency Injection
- Namespaces, typy
- Router, Middleware
- Testy jednostkowe

### Faza 5: Optymalizacja i nowe funkcje (2-4 tygodnie)
- Performance
- Nowe funkcje
- A11y, SEO
- Monitoring

**Całkowity szacowany czas: 8-12 tygodni** (przy 1 osobie na pełny etat)

---

## ⚠️ Uwagi

1. **Zrób backup przed zmianami!**
2. **Testuj na stagingu** przed wdrożeniem na produkcję
3. **Migracja haszy MD5** — użytkownicy z MD5 muszą zresetować hasło
4. **Kompatybilność wsteczna** — rozważyć czy potrzebna (np. dla starych URLi)
5. **Dokumentacja** — aktualizować przy każdej zmianie
"""

with open('/mnt/agents/output/TODO.md', 'w', encoding='utf-8') as f:
    f.write(todo_content)

print("✅ Plik TODO.md zapisany!")
print(f"📄 Rozmiar: {len(todo_content)} znaków")
