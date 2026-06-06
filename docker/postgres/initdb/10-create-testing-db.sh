#!/bin/bash
# Runs automatically on first initialization of the Postgres container.
# Creates a separate database for tests so the test run never touches dev data.
set -e

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" <<-EOSQL
    CREATE DATABASE template_testing;
    GRANT ALL PRIVILEGES ON DATABASE template_testing TO $POSTGRES_USER;
EOSQL
