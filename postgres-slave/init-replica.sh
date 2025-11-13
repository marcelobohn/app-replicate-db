#!/bin/bash
set -e

# Espera o master iniciar
until pg_isready -h master-db -p 5432; do sleep 1; done

# Para replicação inicial
pg_basebackup -h master-db -D /var/lib/postgresql/data -U replicator -P -v --wal-method=stream

# Configura recovery
echo "standby_mode = 'on'" >> /var/lib/postgresql/data/postgresql.conf
echo "primary_conninfo = 'host=master-db port=5432 user=replicator password=replicator_pass'" >> /var/lib/postgresql/data/postgresql.conf
echo "trigger_file = '/tmp/postgresql.trigger'" >> /var/lib/postgresql/data/postgresql.conf
