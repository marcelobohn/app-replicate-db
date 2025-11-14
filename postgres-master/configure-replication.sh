#!/bin/bash
set -e

# Este script é executado após a inicialização do banco de dados
# Aguardar o PostgreSQL iniciar completamente
until pg_isready -U postgres; do
  echo "Aguardando PostgreSQL iniciar..."
  sleep 1
done

# Aguardar mais um pouco para garantir que o banco está totalmente inicializado
sleep 2

# Adicionar regras de replicação ao pg_hba.conf se ainda não existirem
PG_HBA="/var/lib/postgresql/data/pg_hba.conf"

if [ -f "$PG_HBA" ]; then
    if ! grep -q "replicator.*172.18.0.0/16" "$PG_HBA"; then
        echo "Configurando pg_hba.conf para permitir replicação da rede Docker..."
        echo "" >> "$PG_HBA"
        echo "# Allow replication connections from Docker network" >> "$PG_HBA"
        echo "host    replication     replicator      172.18.0.0/16           md5" >> "$PG_HBA"
        echo "host    replication     replicator      172.17.0.0/16           md5" >> "$PG_HBA"
        echo "host    all             all             172.18.0.0/16           md5" >> "$PG_HBA"
        echo "host    all             all             172.17.0.0/16           md5" >> "$PG_HBA"
        
        # Recarregar configuração
        psql -U postgres -c "SELECT pg_reload_conf();" || true
        echo "Configuração de replicação aplicada com sucesso!"
    else
        echo "Configuração de replicação já existe no pg_hba.conf"
    fi
else
    echo "Arquivo pg_hba.conf não encontrado, aguardando inicialização..."
fi

