# Local Docker development for CMS

Prerequisites:
- Docker and Docker Compose installed on your machine.

Quick start:

1. Build and start containers:

```bash
./run-local.sh start
```

2. Install PHP dependencies inside the `php` container (if needed):

```bash
docker compose exec php bash -lc "composer install"
```

3. Configure database connection:
- Edit `config/db - ready.php` and set host to `db`, user `dev`, password `dev`, database `cms` (or change `docker-compose.yml` credentials).

4. Run web installer in browser:
- Open http://localhost:8080/install/ and follow the installer steps.

Open a shell in PHP container:

```bash
docker compose exec php bash
```

Notes:
- The app code is mounted into containers from the host — you can edit files locally and refresh the browser.
- If you need to import SQL directly into the database, use the MySQL client on the host or run:

```bash
docker compose exec db bash -c "mysql -u root -proot cms < /var/www/html/install/gielda-budowlana.sql"
```
