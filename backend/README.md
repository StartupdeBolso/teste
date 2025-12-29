# N8N Dispatch SaaS - Backend API

Backend API em Symfony para o micro SaaS de disparos via webhook N8N.

## Requisitos

- PHP 8.1 ou superior
- Composer
- PostgreSQL (Supabase)
- Extensões PHP: pdo_pgsql, ctype, iconv, json

## Instalação

### 1. Instalar dependências

```bash
cd backend
composer install
```

### 2. Configurar variáveis de ambiente

```bash
cp .env .env.local
```

Edite o arquivo `.env.local` e configure:

- `DATABASE_URL`: String de conexão do Supabase
- `APP_SECRET`: Chave secreta do Symfony (gere uma aleatória)
- `JWT_PASSPHRASE`: Senha para as chaves JWT
- `N8N_WEBHOOK_URL`: URL do webhook do seu agente N8N
- `CORS_ALLOW_ORIGIN`: Domínio do frontend (Vercel)

### 3. Gerar chaves JWT

```bash
php bin/console lexik:jwt:generate-keypair
```

Isso criará as chaves em `config/jwt/private.pem` e `config/jwt/public.pem`.

### 4. Configurar Google Sheets API

1. Acesse o [Google Cloud Console](https://console.cloud.google.com)
2. Crie um novo projeto ou use um existente
3. Ative a Google Sheets API
4. Crie credenciais (Service Account)
5. Baixe o arquivo JSON das credenciais
6. Salve como `config/google-credentials.json`

### 5. Criar banco de dados

Execute as migrations:

```bash
php bin/console doctrine:migrations:migrate
```

Ou crie as tabelas manualmente no Supabase usando o SQL:

```sql
-- Execute o conteúdo gerado por:
php bin/console doctrine:schema:create --dump-sql
```

## Executar localmente

```bash
symfony server:start
```

Ou com PHP built-in server:

```bash
php -S localhost:8000 -t public/
```

## Endpoints da API

### Autenticação

- `POST /api/auth/register` - Registrar novo usuário
- `POST /api/auth/login` - Login (retorna JWT token)
- `GET /api/auth/me` - Dados do usuário autenticado

### Campanhas

- `GET /api/campaigns` - Listar campanhas
- `POST /api/campaigns` - Criar campanha
- `GET /api/campaigns/{id}` - Detalhes da campanha
- `PUT /api/campaigns/{id}` - Atualizar campanha
- `DELETE /api/campaigns/{id}` - Deletar campanha
- `POST /api/campaigns/{id}/start` - Iniciar campanha
- `POST /api/campaigns/{id}/pause` - Pausar campanha
- `POST /api/campaigns/{id}/resume` - Retomar campanha

### Disparos

- `GET /api/dispatches/campaign/{campaignId}` - Listar disparos de uma campanha
- `POST /api/dispatches/process` - Processar disparos pendentes

### Google Sheets

- `POST /api/google-sheets/info` - Obter informações da planilha
- `POST /api/google-sheets/preview` - Visualizar dados da planilha

## Processamento de disparos

Para processar os disparos pendentes, você pode:

1. Criar um cronjob:

```bash
*/5 * * * * cd /path/to/backend && php bin/console app:process-dispatches
```

2. Ou usar um worker:

```bash
php bin/console messenger:consume async
```

## Deploy

### Supabase

1. Crie um projeto no Supabase
2. Copie a string de conexão do PostgreSQL
3. Execute as migrations

### Backend (Heroku, DigitalOcean, etc)

Configure as variáveis de ambiente e faça deploy do código.

## Estrutura do projeto

```
backend/
├── bin/              # Scripts CLI
├── config/           # Configurações
├── public/           # Ponto de entrada público
├── src/
│   ├── Controller/   # Controllers da API
│   ├── Entity/       # Entidades do banco
│   ├── Repository/   # Repositórios
│   ├── Service/      # Serviços
│   └── Security/     # Configurações de segurança
└── migrations/       # Migrations do banco
```
