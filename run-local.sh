#!/usr/bin/env bash

# Jeśli skrypt został uruchomiony przez /bin/sh zamiast bash, przełącz na bash
if [ -z "${BASH_VERSION:-}" ]; then
  exec bash "$0" "$@"
fi

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" >/dev/null 2>&1 && pwd)"
cd "$SCRIPT_DIR"

# Wykrywanie polecenia Docker Compose (docker compose v2 vs docker-compose v1)
if docker compose version >/dev/null 2>&1; then
  DOCKER_COMPOSE="docker compose"
elif command -v docker-compose >/dev/null 2>&1; then
  DOCKER_COMPOSE="docker-compose"
else
  echo "Błąd: Nie znaleziono polecenia 'docker compose' ani 'docker-compose'."
  echo "Upewnij się, że Docker oraz Docker Compose są zainstalowane."
  exit 1
fi

# Sprawdzanie dostępności deamona Docker
if ! docker info >/dev/null 2>&1; then
  echo "Błąd: Nie można połączyć się z serwerem Docker (daemon)."
  echo "Sprawdź, czy usługa Docker jest uruchomiona i czy twój użytkownik ma odpowiednie uprawnienia."
  exit 1
fi

usage() {
  cat <<'EOF'
Usage: ./run-local.sh [command]

Commands:
  start     Build and start Docker containers, then open the browser.
  stop      Stop and remove Docker containers.
  restart   Rebuild and restart the application.
  status    Show Docker Compose service status.
  logs      Follow web and PHP container logs.
  help      Show this help message.
EOF
}

if [[ $# -gt 1 ]]; then
  usage
  exit 1
fi

COMMAND="${1:-start}"
BASE_URL="http://127.0.0.1:8080"

case "$COMMAND" in
  start)
    $DOCKER_COMPOSE up --build -d
    echo "Lokalne środowisko CMS zostało uruchomione. Oczekiwanie na http://127.0.0.1:8080/..."

    READY=0
    for i in {1..30}; do
      if curl -sSf "$BASE_URL/" >/dev/null 2>&1; then
        READY=1
        break
      fi
      sleep 1
    done

    echo "Strona jest dostępna pod adresem: $BASE_URL/"
    if [ "$READY" -eq 1 ]; then
      if command -v xdg-open >/dev/null 2>&1; then
        xdg-open "$BASE_URL/" >/dev/null 2>&1 || true
      fi
    else
      echo "Ostrzeżenie: Kontenery uruchomiono, ale strona nie odpowiedziała w ciągu 30s."
      echo "Możesz sprawdzić logi poleceniem: ./run-local.sh logs"
    fi
    ;;
  stop)
    $DOCKER_COMPOSE down
    ;;
  restart)
    $DOCKER_COMPOSE down
    $DOCKER_COMPOSE up --build -d
    ;;
  status)
    $DOCKER_COMPOSE ps
    ;;
  logs)
    $DOCKER_COMPOSE logs --tail=50 -f web php
    ;;
  help|--help|-h)
    usage
    ;;
  *)
    echo "Nieznana komenda: $COMMAND"
    usage
    exit 1
    ;;
esac