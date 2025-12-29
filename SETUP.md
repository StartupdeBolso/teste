# Guia de Configuração Passo a Passo

Este guia te ajudará a configurar e executar o N8N Dispatch SaaS do zero.

## Pré-requisitos

Antes de começar, certifique-se de ter:

- [ ] PHP 8.1 ou superior instalado
- [ ] Composer instalado
- [ ] Node.js 18+ instalado
- [ ] npm ou yarn instalado
- [ ] Conta no Supabase (gratuita)
- [ ] Conta no Google Cloud (gratuita)
- [ ] Workflow N8N com webhook configurado

## Passo 1: Configurar Supabase

### 1.1 Criar projeto

1. Acesse [supabase.com](https://supabase.com)
2. Clique em "New Project"
3. Preencha os dados:
   - Nome do projeto
   - Database Password (anote esta senha!)
   - Região (escolha a mais próxima)
4. Aguarde a criação do projeto (1-2 minutos)

### 1.2 Criar tabelas

1. No painel do Supabase, vá em "SQL Editor"
2. Clique em "New Query"
3. Copie o conteúdo do arquivo `backend/database.sql`
4. Cole no editor e clique em "Run"
5. Verifique se as tabelas foram criadas em "Table Editor"

### 1.3 Obter string de conexão

1. Vá em "Settings" > "Database"
2. Em "Connection String" > "URI", copie a string
3. Substitua `[YOUR-PASSWORD]` pela senha do banco
4. Anote esta string, será usada no backend

## Passo 2: Configurar Google Sheets API

### 2.1 Criar projeto no Google Cloud

1. Acesse [console.cloud.google.com](https://console.cloud.google.com)
2. Clique em "Select a project" > "New Project"
3. Dê um nome ao projeto e clique em "Create"

### 2.2 Ativar Google Sheets API

1. No menu lateral, vá em "APIs & Services" > "Library"
2. Busque por "Google Sheets API"
3. Clique em "Enable"

### 2.3 Criar Service Account

1. Vá em "APIs & Services" > "Credentials"
2. Clique em "Create Credentials" > "Service Account"
3. Preencha:
   - Service account name: `n8n-dispatch-saas`
   - Service account ID: (gerado automaticamente)
4. Clique em "Create and Continue"
5. Pule os passos opcionais e clique em "Done"

### 2.4 Gerar chave JSON

1. Na lista de Service Accounts, clique no email criado
2. Vá na aba "Keys"
3. Clique em "Add Key" > "Create new key"
4. Selecione "JSON" e clique em "Create"
5. Um arquivo JSON será baixado
6. Renomeie para `google-credentials.json`
7. Mova para `backend/config/google-credentials.json`

### 2.5 Compartilhar planilhas

Para cada planilha que você quiser usar:

1. Abra a planilha no Google Sheets
2. Clique em "Share"
3. Cole o email da service account (está no arquivo JSON, campo `client_email`)
4. Dê permissão de "Viewer"
5. Clique em "Send"

## Passo 3: Configurar Webhook N8N

### 3.1 No seu workflow N8N existente

1. Adicione um nó "Webhook"
2. Configure:
   - HTTP Method: POST
   - Path: qualquer caminho que desejar
3. Ative o workflow
4. Copie a URL do webhook
5. Anote esta URL, será usada no backend

### 3.2 Testar webhook (opcional)

```bash
curl -X POST https://your-n8n-instance.com/webhook/your-path \
  -H "Content-Type: application/json" \
  -d '{
    "contact": {
      "nome": "Teste",
      "email": "teste@example.com"
    },
    "metadata": {
      "campaign_id": 1,
      "dispatch_id": 1
    }
  }'
```

## Passo 4: Configurar Backend

### 4.1 Instalar dependências

```bash
cd backend
composer install
```

### 4.2 Configurar variáveis de ambiente

```bash
cp .env .env.local
```

Edite `backend/.env.local`:

```env
APP_ENV=dev
APP_SECRET=ALTERE_PARA_STRING_ALEATORIA

# Supabase - cole a string de conexão
DATABASE_URL="postgresql://postgres:SUA_SENHA@db.xxxxx.supabase.co:5432/postgres"

# JWT - escolha uma senha forte
JWT_PASSPHRASE=sua_senha_jwt_forte

# Google Sheets - caminho do arquivo JSON
GOOGLE_APPLICATION_CREDENTIALS=%kernel.project_dir%/config/google-credentials.json

# N8N - URL do webhook
N8N_WEBHOOK_URL=https://your-n8n.com/webhook/your-path

# CORS - permitir frontend
CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1|.*\.vercel\.app)(:[0-9]+)?$'
```

### 4.3 Gerar chaves JWT

```bash
php bin/console lexik:jwt:generate-keypair
```

Isso criará:
- `config/jwt/private.pem`
- `config/jwt/public.pem`

### 4.4 Testar conexão com banco

```bash
php bin/console doctrine:query:sql "SELECT 1"
```

Se retornar erro, verifique a string de conexão.

### 4.5 Iniciar servidor

```bash
symfony server:start
```

Ou se não tiver Symfony CLI:

```bash
php -S localhost:8000 -t public/
```

Teste em: http://localhost:8000/api

## Passo 5: Configurar Frontend

### 5.1 Instalar dependências

```bash
cd frontend
npm install
```

### 5.2 Configurar variáveis de ambiente

```bash
cp .env.example .env
```

Edite `frontend/.env`:

```env
VITE_API_URL=/api
```

Para desenvolvimento, `/api` fará proxy para `http://localhost:8000/api`.

### 5.3 Iniciar servidor de desenvolvimento

```bash
npm run dev
```

Acesse: http://localhost:3000

## Passo 6: Testar o Sistema

### 6.1 Registrar usuário

1. Acesse http://localhost:3000
2. Clique em "Não tem uma conta? Registre-se"
3. Preencha nome, email e senha
4. Clique em "Registrar"

### 6.2 Criar primeira campanha

1. Clique em "Nova Campanha"
2. Preencha:
   - Nome: "Teste Inicial"
   - Descrição: "Primeira campanha de teste"
3. Cole o ID de uma planilha do Google Sheets
4. Aguarde carregar as informações
5. Selecione a aba
6. Verifique o preview
7. Defina quantidade de disparos (comece com 5 para testar)
8. Clique em "Criar Campanha"

### 6.3 Iniciar campanha

1. Na página da campanha, clique em "Iniciar Campanha"
2. Confirme

### 6.4 Processar disparos

Em outro terminal, execute:

```bash
cd backend
php bin/console app:process-dispatches
```

Ou use a API:

```bash
curl -X POST http://localhost:8000/api/dispatches/process \
  -H "Authorization: Bearer SEU_TOKEN_JWT"
```

### 6.5 Verificar disparos

1. No frontend, veja o progresso em tempo real
2. No N8N, verifique se os webhooks foram recebidos
3. No Supabase, verifique a tabela `dispatches`

## Passo 7: Configurar Processamento Automático

### 7.1 Cronjob (Linux/Mac)

```bash
crontab -e
```

Adicione:

```cron
*/5 * * * * cd /path/to/backend && php bin/console app:process-dispatches
```

Isso processará disparos a cada 5 minutos.

### 7.2 Task Scheduler (Windows)

1. Abra "Task Scheduler"
2. Create Task > Trigger: Every 5 minutes
3. Action: Start program `php.exe`
4. Arguments: `/path/to/backend/bin/console app:process-dispatches`

## Passo 8: Deploy (Opcional)

### 8.1 Deploy do Frontend na Vercel

```bash
cd frontend
npm install -g vercel
vercel
```

Configure a variável de ambiente:
- `VITE_API_URL`: URL completa da API (ex: https://api.seudominio.com/api)

### 8.2 Deploy do Backend

Para Heroku, Railway, Render ou similar:

1. Configure as variáveis de ambiente
2. Execute migrations/SQL no banco de produção
3. Configure servidor web (Nginx/Apache)
4. Faça upload das chaves JWT
5. Faça upload do google-credentials.json

## Troubleshooting

### Backend não inicia

- Verifique se PHP 8.1+ está instalado: `php -v`
- Verifique se as extensões estão instaladas: `php -m`
- Verifique se a porta 8000 está livre

### Frontend não conecta na API

- Verifique se o backend está rodando
- Verifique CORS no backend
- Abra DevTools > Network para ver erros

### Google Sheets não carrega

- Verifique se a API está ativada
- Verifique se o arquivo JSON está no local correto
- Verifique se a planilha foi compartilhada com a service account

### Disparos não são enviados

- Verifique se o webhook N8N está ativo
- Teste o webhook manualmente com curl
- Verifique logs em `backend/var/log/`

## Próximos Passos

- Configure backup do banco de dados
- Configure monitoramento (Sentry, etc)
- Implemente rate limiting
- Configure logs estruturados
- Adicione testes automatizados

## Suporte

Se precisar de ajuda:

1. Verifique os logs em `backend/var/log/`
2. Abra DevTools no navegador
3. Consulte a documentação no README.md
4. Abra uma issue no repositório
