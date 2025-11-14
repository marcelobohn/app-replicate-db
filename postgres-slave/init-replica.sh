#!/bin/bash
set -euo pipefail

REPL_USER=${REPLICATION_USER:-replicator}
REPL_PASSWORD=${REPLICATION_PASSWORD:-replicator_pass}
MASTER_HOST=${MASTER_HOST:-master-db}
MASTER_PORT=${MASTER_PORT:-5432}
REPL_SLOT=${REPLICATION_SLOT:-replication_slot}
DATA_DIR=/var/lib/postgresql/data

# Aguarda o master aceitar conexões
until pg_isready -h "$MASTER_HOST" -p "$MASTER_PORT" >/dev/null 2>&1; do
    echo "Aguardando master ($MASTER_HOST:$MASTER_PORT) ficar disponível..."
    sleep 1
done

echo "Master disponível. Preparando base backup..."

# Garantir diretório vazio para o basebackup
rm -rf "${DATA_DIR:?}/"*

export PGPASSWORD="$REPL_PASSWORD"
pg_basebackup \
    -h "$MASTER_HOST" \
    -p "$MASTER_PORT" \
    -D "$DATA_DIR" \
    -U "$REPL_USER" \
    -P \
    -R \
    -X stream \
    -S "$REPL_SLOT"

cat <<EOF >> "$DATA_DIR/postgresql.auto.conf"
primary_conninfo = 'host=$MASTER_HOST port=$MASTER_PORT user=$REPL_USER password=$REPL_PASSWORD'
primary_slot_name = '$REPL_SLOT'
hot_standby = on
EOF

touch "$DATA_DIR/standby.signal"
chown -R postgres:postgres "$DATA_DIR"
echo "Replica configurada. Inicializando PostgreSQL em modo standby."
