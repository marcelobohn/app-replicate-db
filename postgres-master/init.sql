-- Criar usuário para replicação
CREATE USER replicator WITH REPLICATION PASSWORD 'replicator_pass';

-- Criar slot de replicação
SELECT pg_create_physical_replication_slot('replication_slot');

-- Permitir conexões de replicação da rede Docker (172.18.0.0/16)
-- Isso será adicionado ao pg_hba.conf através de um script separado
