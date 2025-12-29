# N8N Dispatch SaaS

Micro SaaS simples para gerenciar e executar disparos em massa via webhook N8N. Faça upload de planilhas (CSV, Excel) e envie os dados para seu webhook do N8N.

## Visão Geral

Este sistema permite criar campanhas de disparos automatizados usando dados de planilhas. Você faz upload do arquivo (CSV, XLS ou XLSX) diretamente no site, e os disparos são enviados para um webhook do N8N que você já possui.

### Principais Funcionalidades

- ✅ **Simples de configurar** - Apenas Supabase e webhook N8N
- ✅ Autenticação JWT multi-usuário
- ✅ Upload de planilhas (CSV, Excel)
- ✅ Preview dos dados antes de criar campanha
- ✅ Controle de quantidade de disparos
- ✅ Monitoramento em tempo real
- ✅ Sistema de filas para processamento
- ✅ Estatísticas detalhadas

## Arquitetura

### Backend (Symfony 6.4 + PHP 8.1+)
- API REST
- Autenticação JWT
- Upload e processamento de arquivos CSV/XLSX
- Envio para webhook N8N
- PostgreSQL via Supabase

### Frontend (Vue 3 + Vite)
- Interface moderna e responsiva
- Upload de arquivos drag-and-drop
- Preview de dados
- Monitoramento em tempo real
- Deploy na Vercel

## Instalação Rápida

### Pré-requisitos

- PHP 8.1+
- Composer
- Node.js 18+
- npm ou yarn
- Conta no Supabase (gratuita)
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
# Edite .env.local com suas configurações:
# - DATABASE_URL (Supabase)
# - JWT_PASSPHRASE
# - N8N_WEBHOOK_URL

# Gerar chaves JWT
php bin/console lexik:jwt:generate-keypair

# Executar migrations (ou SQL no Supabase)
# Execute o SQL em backend/database.sql no Supabase

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
2. No SQL Editor, execute o conteúdo de `backend/database.sql`
3. Copie a string de conexão e cole em `backend/.env.local`

```env
DATABASE_URL="postgresql://postgres:[PASSWORD]@db.[PROJECT_REF].supabase.co:5432/postgres"
```

### Webhook N8N

1. No seu workflow N8N, adicione um nó Webhook
2. Configure o método POST
3. Copie a URL do webhook
4. Cole em `backend/.env.local`:

```env
N8N_WEBHOOK_URL=https://your-n8n-instance.com/webhook/your-hook-id
```

O payload enviado será:
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

1. Faça login no sistema
2. Clique em "Nova Campanha"
3. Preencha nome e descrição
4. Faça upload da planilha (CSV, XLS ou XLSX)
   - A primeira linha deve conter os cabeçalhos
5. Visualize o preview dos dados
6. Defina a quantidade de disparos
7. Clique em "Criar Campanha"

### 2. Iniciar Disparos

1. Acesse a campanha criada
2. Clique em "Iniciar Campanha"
3. Os disparos serão criados e enviados para a fila
4. Acompanhe o progresso em tempo real

### 3. Processar Disparos

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

Deploy em Heroku, DigitalOcean, Railway ou VPS:
1. Configure as variáveis de ambiente
2. Execute `composer install --no-dev`
3. Execute as migrations/SQL
4. Configure o servidor web

### Frontend (Vercel)

```bash
cd frontend
vercel
```

Configure a variável de ambiente:
- `VITE_API_URL`: URL completa da API backend

## API Endpoints

### Autenticação
- `POST /api/auth/register` - Registrar usuário
- `POST /api/auth/login` - Login
- `GET /api/auth/me` - Dados do usuário

### Campanhas
- `GET /api/campaigns` - Listar campanhas
- `POST /api/campaigns` - Criar campanha
- `GET /api/campaigns/{id}` - Detalhes
- `PUT /api/campaigns/{id}` - Atualizar
- `DELETE /api/campaigns/{id}` - Deletar
- `POST /api/campaigns/{id}/start` - Iniciar
- `POST /api/campaigns/{id}/pause` - Pausar
- `POST /api/campaigns/{id}/resume` - Retomar

### Upload de Arquivos
- `POST /api/files/upload/{campaignId}` - Upload de arquivo
- `GET /api/files/preview/{campaignId}` - Preview dos dados

### Disparos
- `GET /api/dispatches/campaign/{campaignId}` - Listar disparos
- `POST /api/dispatches/process` - Processar disparos pendentes

## Banco de Dados

### Tabelas

- **users**: Usuários do sistema
- **campaigns**: Campanhas de disparo
- **contacts**: Contatos importados dos arquivos
- **dispatches**: Disparos individuais

## Formatos de Arquivo Suportados

- **CSV**: Separado por vírgula, codificação UTF-8
- **XLS**: Excel 97-2003
- **XLSX**: Excel 2007+

**Importante**: A primeira linha deve conter os cabeçalhos (nomes das colunas).

## Exemplo de Planilha

| nome | email | telefone |
|------|-------|----------|
| João Silva | joao@example.com | 11999999999 |
| Maria Santos | maria@example.com | 11988888888 |

## Troubleshooting

### Erro ao fazer upload

- Verifique se a primeira linha contém cabeçalhos
- Certifique-se de que o arquivo está em UTF-8
- Tamanho máximo recomendado: 10MB

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
