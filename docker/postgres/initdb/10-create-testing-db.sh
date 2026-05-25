#!/bin/bash
# Выполняется автоматически при первой инициализации контейнера Postgres.
# Создаёт отдельную базу для тестов, чтобы прогон тестов не трогал dev-данные.
set -e

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" <<-EOSQL
    CREATE DATABASE template_testing;
    GRANT ALL PRIVILEGES ON DATABASE template_testing TO $POSTGRES_USER;
EOSQL
