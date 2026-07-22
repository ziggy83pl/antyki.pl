# 📋 TODO — Wdrożenie CMS na GitHub + InfinityFree

## ✅ ETAP 1 — Przygotowanie projektu lokalnie

- [ ] Zainstaluj Git na komputerze → https://git-scm.com
- [ ] Zainstaluj FileZilla (FTP backup) → https://filezilla-project.org
- [ ] Utwórz plik `.gitignore` w katalogu projektu:
  ```
  /vendor/
  config/config.php
  tmp/
  uploads/
  *.log
  .env
  ```
- [ ] Utwórz plik `config/config.example.php` (kopia config.php bez haseł)
- [ ] Sprawdź czy w kodzie nie ma żadnych haseł ani danych dostępowych "na twardo"
- [ ] Uruchom `composer install` lokalnie i sprawdź czy działa
- [ ] Przetestuj CMS lokalnie (XAMPP / Laragon)

---

## ✅ ETAP 2 — Założenie konta GitHub i wgranie projektu

- [ ] Załóż konto na https://github.com (jeśli nie masz)
- [ ] Utwórz nowe repozytorium (np. `cms-ogloszenia`) — ustaw jako **Private** (prywatne!)
- [ ] Zainicjuj repozytorium lokalnie:
  ```bash
  git init
  git add .
  git commit -m "Pierwszy commit - inicjalizacja CMS"
  git branch -M main
  git remote add origin https://github.com/TWOJ_LOGIN/cms-ogloszenia.git
  git push -u origin main
  ```
- [ ] Sprawdź czy na GitHub nie widać pliku `config.php` z hasłami — jeśli tak, usuń go natychmiast i dodaj do `.gitignore`

---

## ✅ ETAP 3 — Założenie konta InfinityFree

- [ ] Zarejestruj się na https://infinityfree.com
- [ ] Utwórz nowe konto hostingowe (wybierz darmowy plan)
- [ ] Zanotuj dane FTP:
  - Serwer FTP: `ftpupload.net`
  - Login FTP: `(z panelu InfinityFree)`
  - Hasło FTP: `(z panelu InfinityFree)`
  - Katalog: `/htdocs`
- [ ] Utwórz bazę danych MySQL w panelu InfinityFree:
  - Zanotuj: nazwę bazy, login, hasło, host bazy
- [ ] Zaimportuj strukturę bazy przez phpMyAdmin (panel InfinityFree)
- [ ] Wgraj ręcznie przez FileZilla pliki CMS do katalogu `/htdocs`
- [ ] Wgraj `config/config.php` z danymi do bazy **ręcznie przez FTP** (nigdy przez GitHub!)
- [ ] Sprawdź czy strona działa w przeglądarce

---

## ✅ ETAP 4 — Auto-deploy GitHub → InfinityFree (GitHub Actions + FTP)

> Dzięki temu każdy `git push` automatycznie aktualizuje pliki na serwerze!

- [ ] W repozytorium GitHub utwórz plik:
  `.github/workflows/deploy.yml`

  ```yaml
  name: Deploy CMS na InfinityFree

  on:
    push:
      branches:
        - main

  jobs:
    deploy:
      runs-on: ubuntu-latest
      steps:
        - name: Pobierz kod
          uses: actions/checkout@v4

        - name: Instalacja Composer
          run: composer install --no-dev --optimize-autoloader

        - name: Deploy przez FTP
          uses: SamKirkland/FTP-Deploy-Action@v4.3.5
          with:
            server: ftpupload.net
            username: ${{ secrets.FTP_USERNAME }}
            password: ${{ secrets.FTP_PASSWORD }}
            server-dir: /htdocs/
            exclude: |
              **/.git*
              **/.git*/**
              **/node_modules/**
              config/config.php
              tmp/**
              uploads/**
  ```

- [ ] Dodaj dane FTP jako **GitHub Secrets** (Settings → Secrets → Actions):
  - `FTP_USERNAME` → login FTP z InfinityFree
  - `FTP_PASSWORD` → hasło FTP z InfinityFree
- [ ] Zrób testowy commit i sprawdź czy deploy działa automatycznie:
  ```bash
  git add .
  git commit -m "Test auto-deploy"
  git push
  ```
- [ ] Sprawdź zakładkę **Actions** na GitHub — powinien być zielony znaczek ✅

---

## ✅ ETAP 5 — Konfiguracja cron-job.org (zamiast crona serwera)

- [ ] Załóż darmowe konto na https://cron-job.org
- [ ] Utwórz zadania cron według potrzeb, np:
  ```
  Codziennie 02:00 → https://twojastrona.infinityfreeapp.com/cron/cleanup.php
  Co tydzień      → https://twojastrona.infinityfreeapp.com/cron/report.php
  ```
- [ ] Zabezpiecz skrypty cron tajnym tokenem (żeby nikt obcy nie mógł ich wywołać):
  ```php
  // cron/cleanup.php
  if ($_GET['token'] !== 'TAJNY_TOKEN_123') { die('Brak dostępu'); }
  ```

---

## ✅ ETAP 6 — Bezpieczeństwo (ważne!)

- [ ] Upewnij się że `config/config.php` jest w `.gitignore` i **nigdy nie trafia na GitHub**
- [ ] Zmień domyślne hasło admina CMS
- [ ] Sprawdź czy panel admina jest chroniony hasłem
- [ ] Włącz SSL (https) w panelu InfinityFree — jest darmowy
- [ ] Regularnie rób backup bazy danych (phpMyAdmin → Eksport)

---

## ✅ ETAP 7 — Testowanie z innymi użytkownikami

- [ ] Zaproś znajomych do rejestracji i dodawania ogłoszeń
- [ ] Sprawdź działanie na telefonie (responsywność)
- [ ] Przetestuj dodawanie zdjęć do ogłoszeń
- [ ] Sprawdź działanie wyszukiwarki i kategorii
- [ ] Zbieraj uwagi i poprawki → commituj na GitHub → auto-deploy na serwer

---

## ✅ ETAP 8 — Migracja na lh.pl (gdy CMS gotowy)

- [ ] Wykup hosting na lh.pl
- [ ] Eksportuj bazę danych z InfinityFree (phpMyAdmin → Eksport → SQL)
- [ ] Zaktualizuj plik deploy.yml — zmień dane FTP na dane z lh.pl
- [ ] Zaktualizuj `config/config.php` z nowymi danymi bazy
- [ ] Zaimportuj bazę na lh.pl
- [ ] Przetestuj czy wszystko działa
- [ ] Podepnij własną domenę

---

## 📌 Jak wygląda codzienna praca po konfiguracji

```
Edytujesz kod lokalnie
        ↓
git add . && git commit -m "Opis zmian"
        ↓
git push
        ↓
GitHub Actions automatycznie wgrywa przez FTP na InfinityFree
        ↓
Strona zaktualizowana w ~2 minuty ✅
```

---

## ⚠️ Ważne uwagi

| Uwaga | Szczegół |
|---|---|
| `config.php` | Zawsze wgrywaj ręcznie przez FTP, nigdy przez GitHub |
| `uploads/` (zdjęcia) | Nie są synchronizowane przez GitHub — zostają na serwerze |
| `vendor/` | Composer instaluje się automatycznie podczas deploy |
| Backup bazy | Rób raz w tygodniu przez phpMyAdmin |
| Repozytorium | Ustaw jako **Private** — CMS nie powinien być publiczny |
