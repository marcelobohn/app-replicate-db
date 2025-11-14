# App Replicate DB

Aplicação Laravel 11 que expõe um pequeno CRUD de pessoas e demonstra replicação física do PostgreSQL (master/slave) usando Docker. O projeto nasceu para experimentar:

- Como configurar uma aplicação Laravel para ler/escrever em bancos diferentes.
- Como orquestrar PostgreSQL master/slave + app PHP em `docker-compose`.
- Como garantir que migrations e testes mantenham o schema consistente.

## Stack

- PHP 8.3 + Laravel 11
- PostgreSQL 16 (master/slave)
- Docker / Docker Compose
- PHPUnit

## Pré-requisitos

| Ferramenta     | Versão sugerida |
|----------------|-----------------|
| Docker         | 24+             |
| Docker Compose | 2.20+           |
| Make (opcional)| qualquer        |

## Configuração rápida

1. **Clone o repositório**  
   ```bash
   git clone <repo> app-replicate-db && cd app-replicate-db
   ```

2. **Configure variáveis**  
   ```bash
   cp .env.example .env
   # Ajuste credenciais se necessário (por padrão master/slave usam user postgres / secret)
   ```

3. **Monte os contêineres**  
   ```bash
   docker-compose build app
   docker-compose up -d
   ```
   O `dockerfile` instala PHP-FPM + Nginx, dependências do Laravel e configura o virtual host.

4. **Rode as migrations no master**  
   ```bash
   docker-compose exec app php artisan migrate
   ```

5. **(Opcional) Popular dados via factory**  
   ```bash
   docker-compose exec app php artisan tinker
   >>> App\Models\Person::factory()->count(3)->create();
   ```

## Serviços

| Serviço      | Porta host | Porta container | Observações                              |
|--------------|------------|-----------------|------------------------------------------|
| app          | 8000       | 80              | Nginx servindo Laravel                   |
| master-db    | 5432       | 5432            | PostgreSQL para escrita/migrations       |
| slave-db     | 5433       | 5432            | PostgreSQL read-only (replicação física) |

## Endpoints principais

| Método | Rota           | Descrição                                |
|--------|----------------|-------------------------------------------|
| GET    | `/`            | Tela padrão do Laravel                   |
| GET    | `/hello`       | Endpoint de teste “Hello World”          |
| GET    | `/api/persons` | Lista de pessoas em JSON                 |
| POST   | `/api/persons` | Cria pessoa `{ "nome": "...", "telefone": "..." }` |

Exemplo:

```bash
curl http://localhost:8000/api/persons
curl -X POST http://localhost:8000/api/persons \
     -H "Content-Type: application/json" \
     -d '{"nome":"Maria","telefone":"(11) 99999-0000"}'
```

## Replicação PostgreSQL

- **Master** (`postgres_master`) usa `wal_level=replica`, cria um usuário `replicator` e o replication slot `replication_slot`.
- **Slave** (`postgres_slave`) executa `postgres-slave/init-replica.sh`, que:
  1. Aguarda o master responder.
  2. Faz `pg_basebackup` completo para `/var/lib/postgresql/data`.
  3. Cria `standby.signal`, configura `primary_conninfo` e `primary_slot_name`.
  4. Inicia o PostgreSQL em hot-standby.

Quando o master recebe `php artisan migrate` ou inserts via API, os WALs são enviados para o slave automaticamente.

### Como verificar se replicação está ativa

```bash
# Master: veja as tabelas e o slot
docker-compose exec master-db psql -U postgres -d laravel_db -c "\dt persons"
docker-compose exec master-db psql -U postgres -d laravel_db -c "SELECT slot_name, active FROM pg_replication_slots;"

# Slave: precisa listar a mesma tabela
docker-compose exec slave-db psql -U postgres -d laravel_db -c "\dt persons"
docker-compose exec slave-db psql -U postgres -d laravel_db -c "SELECT status, conninfo FROM pg_stat_wal_receiver;"
```

Se o slave não possuir `laravel_db` ou o diretório `postgres-slave/data` estiver corrompido:

```bash
docker-compose stop slave-db
rm -rf postgres-slave/data && mkdir -p postgres-slave/data
docker-compose up -d slave-db
```

O init script realizará novamente o base backup.

## Rodando testes

```bash
docker-compose exec app php artisan test
```

- O teste unitário `tests/Unit/PersonTest.php` valida:
  - Campos `fillable` do modelo `Person`.
  - Persistência via factory (`PersonFactory`) usando SQLite. Se o driver `pdo_sqlite` não estiver disponível, o teste é marcado como `skipped`.
- `tests/Feature/ExampleTest.php` mantém o check básico do endpoint `/`.

## Estrutura relevante

| Caminho                                | Descrição                                                |
|----------------------------------------|----------------------------------------------------------|
| `app/Models/Person.php`                | Modelo Eloquent do cadastro de pessoas (`nome/telefone`) |
| `app/Http/Controllers/PersonController.php` | Endpoints REST (`index`, `store`)                    |
| `database/migrations/*persons*`        | Criação da tabela `persons`                             |
| `database/factories/PersonFactory.php` | Faker para gerar pessoas                                |
| `postgres-master/`                     | Configuração do nó primário                             |
| `postgres-slave/`                      | Scripts e configs do nó réplica                          |
| `docker-compose.yml`                   | Orquestra app + bancos                                  |

## Troubleshooting

- **404 em todas as rotas**: rebuild/restart após alterar o `dockerfile`, pois o Nginx customizado deve ser regravado (`docker-compose build app && docker-compose up -d app`).
- **`relation "persons" does not exist`**: rode `php artisan migrate` dentro do container `app` antes de chamar a API.
- **Erro ao subir o slave (`no such file or directory` no bind mount)**: a pasta `postgres-slave/data` precisa existir no host (`mkdir -p postgres-slave/data`) e estar vazia para que o `pg_basebackup` execute.
- **Testes de factory pulados**: instale a extensão `pdo_sqlite` no host para executar o teste de persistência localmente (dentro do container oficial ela já está disponível).

---

Qualquer dúvida, abra uma issue ou entre em contato. Boas replicações! 🚀
