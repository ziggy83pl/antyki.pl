# TODO - Najważniejsze poprawki i optymalizacje

## A. Krytyczne zadania (Bezpieczeństwo i Architektura)
- [x] Zastąpić haszowanie haseł `md5()` bezpiecznym `password_hash()` / `password_verify()` dla admina i użytkowników (wymusić reset starych haseł MD5 w bazie).
- [x] Przechowywać dane dostępowe do bazy danych w bezpiecznym pliku `.env` poza katalogiem publicznym, zamiast jawnego kodu w `config/db.php`.
- [x] Wyłączyć wyświetlanie błędów (`display_errors = Off`) w środowisku produkcyjnym i włączyć bezpieczne logowanie błędów do wydzielonego pliku (np. w `tmp/php-error.log`).
- [x] Zabezpieczyć instalator: dodać sprawdzanie pliku blokady `install.lock` lub automatycznie blokować dostęp do `/install` po poprawnym skonfigurowaniu bazy danych.
- [x] Wzmocnić mechanizm sesji:
  - [x] Włączyć flagi ciasteczek sesji `HttpOnly`, `Secure` i `SameSite=Strict`.
  - [x] Wdrożyć walidację IP i User-Agent w sesjach użytkownika i administratora w celu ochrony przed przejęciem sesji (Session Hijacking).
  - [x] Dodać automatyczne wygasanie sesji po okresie bezczynności.
- [x] Wdrożyć pełne zabezpieczenie CSRF (Cross-Site Request Forgery) we wszystkich formularzaw POST i zapytaniach AJAX (np. w panelu admina).
- [x] Usunąć duplikaty bibliotek frontendowych: w repo zostały po jednej kopii CKEditor, jQuery i Bootstrap dla frontu oraz panelu.

## B. Baza danych i optymalizacja
- [x] Przekształcić bazę do `utf8mb4_unicode_ci` oraz upewnić się, że tabele i kolumny używają spójnego kodowania (obecnie `utf8_polish_ci`).
- [x] Dodać unikalne indeksy dla `slug` w tabelach: `state`, `type`. (Indeksy dla `offer`, `category`, `info`, `option` już istniały).
- [x] Zmienić pola logiczne typu `int(1)` na `TINYINT(1)` / `BOOLEAN` tam, gdzie to możliwe.
- [x] Rozdzielić czarne listy (`black_list_ip`, `black_list_email`) ze wspólnego pola tekstowego w tabeli `settings` do osobnych tabel z indeksami, aby uniknąć parsowania długich stringów przy każdym żądaniu.
- [x] Wprowadzić w `cron-daily.php` automatyczne czyszczenie starych rekordów logów (`admin_logs`, `logs_offer`, `logs_user`, `logs_mail`) starszych niż np. 30/90 dni.

## C. Walidacja i czyszczenie danych (Sanitization)
- [x] Przejrzeć wszystkie miejsca zapisu plików i przesyłania danych (`install/index.php`, uploady) i wprowadzić ścisłą walidację plików (sprawdzanie nagłówków obrazów, MIME-type, a nie tylko rozszerzenia pliku).
- [x] Upewnić się, że wszystkie zapytania SQL używają bindowania parametrów (Prepared Statements) w PDO (np. w `statistics_ajax.php` i zapytaniach dynamicznych).
- [x] Wprowadzić rzutowanie typów (np. `(int)`) dla wszystkich parametrów ID przekazywanych w zapytaniach GET i POST w kontrolerach.

## D. Panel Administratora, Frontend i UX
- [x] Przeprowadzić migrację panelu administratora z Bootstrap 3 do Bootstrap 5:
  - [x] Dostosować klasy układu (np. `col-md-offset-*` -> `offset-md-*`, `pull-right` -> `float-end`, `panel` -> `card`).
  - [x] Zastąpić usunięte ikony Glyphicons nowoczesną biblioteką (Bootstrap Icons lub Font Awesome).
  - [x] Zaktualizować składnię inicjalizacji modali i dropdownów w JS na standard Bootstrap 5.
- [x] Zmodernizować plik `admin/js/engine_admin.js`:
  - [x] Wyeliminować zmienne globalne, stosując `const` oraz `let`.
  - [x] Dodać obsługę błędów dla zapytań AJAX (`.fail()` / `catch`).
  - [x] Przepisać zapytania na nowocześniejsze `fetch()` API zamiast `$.post()`.
- [x] Przejrzeć pliki językowe pod kątem duplikatów i poprawić nieprawidłowe klucze.
- [x] Zastąpić przestarzałe komponenty i biblioteki (CKEditor 4, stare Angular/Bootstrap) lub zaktualizować je do nowszych wersji.
- [x] Rozważyć lazy-loading obrazów dla galerii ogłoszeń i optymalizację zasobów JS/CSS.

---
*Data aktualizacji: 2026-06-29*

## E. Nowe funkcjonalności (Pomysły na rozwój)
- [ ] **Galeria realizacji ("Przed i Po") na profilach wykonawców**: Dodanie możliwości uploadu zdjęć przez fachowców do własnej galerii z opisem, budujące zaufanie klientów.
- [ ] **Przycisk "Szybka Wycena" (Formularz kontaktowy)**: Wprowadzenie na profilu wykonawcy przycisku do szybkiej prośby o wycenę (wymiary, lokalizacja, termin), przesyłającego ustandaryzowaną wiadomość do fachowca.
- [ ] **Interaktywna mapa (Lokalizator fachowców)**: Wyszukiwarka oparta na mapie, ułatwiająca znalezienie fachowców i ofert w najbliższej okolicy (np. w promieniu 30 km).
- [ ] **Weryfikacja firm (Plakietka "Zweryfikowany Wykonawca")**: Opcja podania numeru NIP/KRS przez firmę i weryfikacji przez administratora. Po weryfikacji przyznanie odznaki na profilu.
- [ ] **System powiadomień (Alerty)**: Funkcja pozwalająca fachowcom zapisać się na powiadomienia e-mail/SMS, kiedy w ich województwie i kategorii pojawia się nowa praca.
