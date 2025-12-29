# N8N Dispatch SaaS

Micro SaaS para gerenciar e executar disparos em massa via webhook N8N, com integração ao Google Sheets.

## Visão Geral

Este sistema permite criar campanhas de disparos automatizados usando dados de planilhas do Google Sheets. Os disparos são enviados para um webhook do N8N que você já possui, permitindo integrar com qualquer fluxo de automação.

### Principais Funcionalidades

- Autenticação JWT multi-usuário
- Importação de contatos do Google Sheets
- Criação e gerenciamento de campanhas
- Controle de quantidade de disparos
- Envio controlado via webhook N8N
- Monitoramento em tempo real
- Sistema de filas para processamento
- Estatísticas detalhadas

## Arquitetura

### Backend (Symfony 6.4 + PHP 8.1+)
- API REST
- Autenticação JWT (LexikJWTAuthenticationBundle)
- Integração com Google Sheets API
- Integração com webhook N8N
- PostgreSQL via Supabase

### Frontend (Vue 3 + Vite)
- Interface moderna e responsiva
- TailwindCSS para estilização
- Pinia para gerenciamento de estado
- Deploy na Vercel

## Estrutura do Projeto

```
.
├── backend/              # API Symfony
│   ├── config/          # Configurações
│   ├── src/
│   │   ├── Controller/  # Controllers da API
│   │   ├── Entity/      # Entidades (User, Campaign, Dispatch)
│   │   ├── Repository/  # Repositórios
│   │   └── Service/     # Serviços (Google Sheets, N8N, Dispatch)
│   └── public/          # Entry point
│
└── frontend/            # App Vue.js
    ├── src/
    │   ├── components/  # Componentes reutilizáveis
    │   ├── views/       # Páginas
    │   ├── stores/      # Pinia stores
    │   ├── services/    # Cliente API
    │   └── router/      # Rotas
    └── public/
```

## Instalação Rápida

### Pré-requisitos

- PHP 8.1+
- Composer
- Node.js 18+
- npm ou yarn
- Conta no Supabase
- Projeto no Google Cloud com Sheets API ativada
- Webhook N8N configurado

### 1. Clone o repositório

```bash
git clone <repository-url>
cd teste
```

### 2. Configure o Backend

```bash
cd backend

# Instalar dependências
composer install

# Configurar variáveis de ambiente
cp .env .env.local
# Edite .env.local com suas configurações

# Gerar chaves JWT
php bin/console lexik:jwt:generate-keypair

# Executar migrations (ou criar tabelas no Supabase)
php bin/console doctrine:migrations:migrate

# Iniciar servidor
symfony server:start
# ou
php -S localhost:8000 -t public/
```

### 3. Configure o Frontend

```bash
cd frontend

# Instalar dependências
npm install

# Configurar variáveis de ambiente
cp .env.example .env
# Edite .env com a URL da API

# Executar em desenvolvimento
npm run dev

# Build para produção
npm run build
```

## Configuração

### Supabase

1. Crie um projeto no [Supabase](https://supabase.com)
2. Copie a string de conexão PostgreSQL
3. Configure no `backend/.env.local`:
   ```
   DATABASE_URL="postgresql://postgres:[PASSWORD]@db.[PROJECT_REF].supabase.co:5432/postgres"
   ```

### Google Sheets API

1. Acesse [Google Cloud Console](https://console.cloud.google.com)
2. Crie um projeto ou use um existente
3. Ative a Google Sheets API
4. Crie credenciais (Service Account)
5. Baixe o JSON das credenciais
6. Salve em `backend/config/google-credentials.json`
7. Compartilhe suas planilhas com o email da service account

### Webhook N8N

1. No seu workflow N8N, adicione um nó Webhook
2. Configure o método POST
3. Copie a URL do webhook
4. Configure no `backend/.env.local`:
   ```
   N8N_WEBHOOK_URL=https://your-n8n-instance.com/webhook/your-hook-id
   ```

O payload enviado para o webhook terá o formato:
```json
{
  "contact": {
    "nome": "João Silva",
    "email": "joao@example.com",
    "telefone": "+5511999999999"
  },
  "metadata": {
    "campaign_id": 1,
    "dispatch_id": 123
  },
  "timestamp": "2024-01-15T10:30:00+00:00"
}
```

## Como Usar

### 1. Criar uma Campanha

1. Faça login ou registre-se
2. Clique em "Nova Campanha"
3. Preencha nome e descrição
4. Cole o ID da planilha do Google Sheets
5. Selecione a aba desejada
6. Visualize o preview dos dados
7. Defina a quantidade de disparos
8. Clique em "Criar Campanha"

### 2. Iniciar Disparos

1. Acesse a campanha criada
2. Clique em "Iniciar Campanha"
3. Os disparos serão criados e enviados para a fila
4. Acompanhe o progresso em tempo real

### 3. Processar Disparos

Os disparos podem ser processados de duas formas:

**Manualmente via API:**
```bash
curl -X POST http://localhost:8000/api/dispatches/process \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

**Automaticamente via Cron:**
```bash
# Adicione ao crontab para processar a cada 5 minutos
*/5 * * * * cd /path/to/backend && php bin/console app:process-dispatches
```

## Deploy

### Backend

Você pode fazer deploy do backend em:
- Heroku
- DigitalOcean App Platform
- Railway
- Render
- Qualquer VPS com PHP 8.1+

**Passos básicos:**
1. Configure as variáveis de ambiente
2. Execute `composer install --no-dev --optimize-autoloader`
3. Execute as migrations
4. Configure um servidor web (Nginx/Apache)

### Frontend (Vercel)

```bash
cd frontend
vercel
```

Ou conecte seu repositório GitHub ao Vercel para deploy automático.

**Variáveis de ambiente na Vercel:**
- `VITE_API_URL`: URL completa da API backend

## API Endpoints

### Autenticação
- `POST /api/auth/register` - Registrar usuário
- `POST /api/auth/login` - Login
- `GET /api/auth/me` - Dados do usuário

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
- `GET /api/dispatches/campaign/{campaignId}` - Listar disparos
- `POST /api/dispatches/process` - Processar disparos pendentes

### Google Sheets
- `POST /api/google-sheets/info` - Informações da planilha
- `POST /api/google-sheets/preview` - Preview dos dados

## Banco de Dados

### Entidades

**users**
- id, email, password, name, roles, created_at

**campaigns**
- id, user_id, name, description, google_sheet_id, sheet_name
- total_contacts, dispatch_limit, dispatched_count
- status, configuration, created_at, started_at, completed_at

**dispatches**
- id, campaign_id, contact_data, status
- response_data, error_message, retry_count
- created_at, sent_at

## Segurança

- Autenticação JWT com tokens de 1 hora
- Senhas hasheadas com bcrypt
- CORS configurado para permitir apenas frontend autorizado
- Validação de entrada em todos os endpoints
- Acesso a planilhas via Service Account do Google
- Proteção contra SQL Injection (Doctrine ORM)

## Monitoramento

O sistema fornece estatísticas em tempo real:
- Total de contatos
- Disparos enviados/pendentes/falhados
- Progresso percentual
- Auto-refresh a cada 10 segundos

## Troubleshooting

### Erro ao conectar Google Sheets

- Verifique se a API está ativada no Google Cloud
- Confirme que o arquivo de credenciais está correto
- Compartilhe a planilha com o email da service account

### Disparos não são processados

- Verifique se o webhook N8N está acessível
- Confirme a URL do webhook no .env
- Execute manualmente: `POST /api/dispatches/process`

### Erro 401 no frontend

- Verifique se o token JWT não expirou
- Confirme que as chaves JWT foram geradas corretamente
- Verifique a configuração de CORS no backend

## Licença

Proprietary - Uso pessoal

## Suporte

Para dúvidas e suporte, abra uma issue no repositório.
