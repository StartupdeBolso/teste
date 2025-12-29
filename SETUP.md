# Guia de Configuração - N8N Dispatch SaaS

Este guia vai te ajudar a configurar o sistema do zero em **menos de 10 minutos**.

## O que você precisa

- [ ] PHP 8.1 ou superior
- [ ] Composer
- [ ] Node.js 18+
- [ ] Conta no Supabase (gratuita)
- [ ] Workflow N8N com webhook

---

## Passo 1: Configurar Supabase (5 minutos)

### 1.1 Criar projeto

1. Acesse [supabase.com](https://supabase.com) e faça login (ou crie uma conta grátis)
2. No dashboard, clique no botão **"New project"**
3. Escolha sua **Organization** (se for seu primeiro projeto, crie uma nova)
4. Preencha os dados:
   - **Name**: `n8n-dispatch-saas` (ou qualquer nome)
   - **Database Password**: Crie uma senha forte
     - **ANOTE EM UM LUGAR SEGURO!**
     - Exemplo: `MinhaS3nh@2024!`
   - **Region**: Escolha a mais próxima
     - Brasil: `South America (São Paulo)`
     - Outros: escolha o mais próximo
   - **Pricing Plan**: `Free` (gratuito)
5. Clique em **"Create new project"**
6. Aguarde 1-2 minutos (uma barra de progresso vai aparecer)

### 1.2 Criar tabelas do banco

1. No menu lateral esquerdo, clique em **"SQL Editor"** (ícone `</>`)
2. Clique no botão **"New query"** (canto superior direito)
3. No seu computador, abra o arquivo `backend/database.sql`
4. Copie **TODO** o conteúdo (Ctrl+A, Ctrl+C)
5. Cole no editor SQL do Supabase (Ctrl+V)
6. Clique em **"Run"** (ou pressione Ctrl+Enter)
7. Você deve ver: **"Success. No rows returned"** (em verde)

**Confirmar que funcionou:**
- Clique em **"Table Editor"** no menu lateral
- Você deve ver **4 tabelas**: `users`, `campaigns`, `contacts`, `dispatches`

### 1.3 Obter string de conexão

1. No menu lateral, clique no ícone de **engrenagem** (⚙️) para abrir **"Project Settings"**
2. Clique em **"Database"** no menu de configurações
3. Role a página até **"Connection string"**
4. Você verá várias abas, clique em **"URI"** (não use "Session mode")
5. Copie a string completa (clique no ícone de copiar)
6. Ela será algo assim:
   ```
   postgresql://postgres.[abc123]:[YOUR-PASSWORD]@aws-0-sa-east-1.pooler.supabase.com:6543/postgres
   ```

**IMPORTANTE - Substituir a senha:**
- Onde está `[YOUR-PASSWORD]`, substitua pela senha que você criou no passo 1.1
- **ANTES**: `...:[YOUR-PASSWORD]@aws...`
- **DEPOIS**: `...:MinhaS3nh@2024!@aws...`

**Exemplo final:**
```
postgresql://postgres.abc123:MinhaS3nh@2024!@aws-0-sa-east-1.pooler.supabase.com:6543/postgres
```

7. **Copie e guarde esta string completa** (com a senha substituída)

**Esqueceu a senha?**
- Em Project Settings > Database > Clique em "Reset database password"

✅ **Supabase configurado!**

---

## Passo 2: Configurar Webhook N8N (2 minutos)

### 2.1 No seu workflow N8N

1. Abra seu workflow no N8N
2. Adicione um nó **"Webhook"** (se ainda não tiver)
3. Configure:
   - **HTTP Method**: `POST`
   - **Path**: escolha um nome (ex: `disparo-whatsapp`)
   - **Response Mode**: `Respond Immediately`
4. **Ative o workflow** (botão "Active" no canto superior direito)
5. Copie a **URL completa** que aparece no nó Webhook
   - Exemplo: `https://seu-n8n.com/webhook/disparo-whatsapp`
6. **Guarde esta URL**

### 2.2 Formato do payload que você vai receber

```json
{
  "contact": {
    "nome": "João Silva",
    "telefone": "11999999999",
    "enviado": ""
  },
  "metadata": {
    "campaign_id": 1,
    "dispatch_id": 123
  },
  "timestamp": "2024-01-15T10:30:00+00:00"
}
```

### 2.3 Formato da resposta que seu webhook deve retornar

**Sucesso:**
```json
{
  "success": true,
  "sent": "sim"
}
```

**Erro:**
```json
{
  "success": false,
  "sent": "não"
}
```

✅ **Webhook N8N configurado!**

---

## Passo 3: Configurar Backend (3 minutos)

### 3.1 Instalar dependências

Abra o terminal na pasta do projeto e execute:

```bash
cd backend
composer install
```

Aguarde (pode levar 1-2 minutos). Você verá várias linhas de instalação.

### 3.2 Configurar variáveis de ambiente

```bash
cp .env.example .env.local
```

Abra o arquivo `.env.local` em um editor de texto e edite:

```env
# 1. Deixe como está
APP_ENV=dev

# 2. Troque por uma string aleatória qualquer
APP_SECRET=minha_string_super_secreta_123

# 3. Cole a string de conexão do Supabase (do Passo 1.3)
DATABASE_URL="postgresql://postgres.abc:MinhaS3nh@2024!@aws-0-sa-east-1.pooler.supabase.com:6543/postgres"

# 4. Deixe como está
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem

# 5. Crie uma senha para o JWT (pode ser qualquer coisa)
JWT_PASSPHRASE=MinhaS3nhaJWT2024

# 6. Cole a URL do webhook do N8N (do Passo 2.1)
N8N_WEBHOOK_URL=https://seu-n8n.com/webhook/disparo-whatsapp

# 7. Deixe como está
CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1|.*\.vercel\.app)(:[0-9]+)?$'
```

Salve o arquivo.

### 3.3 Gerar chaves JWT

No terminal, execute:

```bash
php bin/console lexik:jwt:generate-keypair
```

Deve aparecer: `✓ Keys successfully generated!`

### 3.4 Iniciar servidor

```bash
# Se você tem Symfony CLI:
symfony server:start

# OU se não tiver:
php -S localhost:8000 -t public/
```

**Deixe este terminal aberto!**

Você deve ver algo como:
```
[OK] Server listening on http://127.0.0.1:8000
```

✅ **Backend rodando em http://localhost:8000**

---

## Passo 4: Configurar Frontend (2 minutos)

### 4.1 Instalar dependências

Abra um **NOVO terminal** (deixe o backend rodando) e execute:

```bash
cd frontend
npm install
```

Aguarde (1-2 minutos).

### 4.2 Configurar variáveis de ambiente

```bash
cp .env.example .env
```

O arquivo `.env` já vem configurado:

```env
VITE_API_URL=/api
```

Não precisa mudar nada!

### 4.3 Iniciar servidor

```bash
npm run dev
```

Você deve ver:

```
  VITE v5.0.0  ready in xxx ms

  ➜  Local:   http://localhost:3000/
  ➜  Network: use --host to expose
```

✅ **Frontend rodando em http://localhost:3000**

---

## Passo 5: Testar o Sistema (5 minutos)

### 5.1 Acessar e criar conta

1. Abra seu navegador em: **http://localhost:3000**
2. Clique em **"Não tem uma conta? Registre-se"**
3. Preencha:
   - **Nome**: Seu nome
   - **Email**: seu@email.com
   - **Senha**: mínimo 6 caracteres
4. Clique em **"Registrar"**

Você será redirecionado para o dashboard!

### 5.2 Preparar planilha de teste

Use o arquivo `exemplo-planilha.csv` que está na raiz do projeto.

**OU crie sua própria planilha:**

**CSV (recomendado para teste):**
```csv
nome,telefone,enviado
João Silva,11999999999,
Maria Santos,11988888888,
Pedro Oliveira,11977777777,
```

**Excel (.xlsx):**

| nome | telefone | enviado |
|------|----------|---------|
| João Silva | 11999999999 | |
| Maria Santos | 11988888888 | |
| Pedro Oliveira | 11977777777 | |

**Regras importantes:**
- ✅ Primeira linha SEMPRE: `nome,telefone,enviado`
- ✅ Coluna `enviado` deve estar vazia
- ✅ Telefone apenas números (sem espaços, parênteses ou traços)

### 5.3 Criar primeira campanha

1. No dashboard, clique em **"Nova Campanha"**
2. Preencha:
   - **Nome**: "Teste Inicial"
   - **Descrição**: "Primeira campanha"
3. **Arraste a planilha** para a área de upload (ou clique para selecionar)
4. Aguarde... você verá um **preview dos dados**
5. Verifique se os dados estão corretos
6. Em "Quantidade de Disparos", escolha **3** (para testar)
7. Clique em **"Criar Campanha"**

### 5.4 Iniciar campanha

1. Você será redirecionado para a página da campanha
2. Clique em **"Iniciar Campanha"**
3. Confirme no popup
4. Status mudará para **"Ativa"**
5. Veja "Pendentes: 3"

### 5.5 Processar disparos

Abra um **TERCEIRO terminal** e execute:

```bash
cd backend
php bin/console app:process-dispatches
```

Ou via API:

```bash
# Copie o token do localStorage do navegador (F12 > Application > Local Storage)
curl -X POST http://localhost:8000/api/dispatches/process \
  -H "Authorization: Bearer SEU_TOKEN"
```

### 5.6 Ver resultados

1. Volte para a página da campanha
2. Clique em **"Atualizar Dados"**
3. Veja:
   - Pendentes diminuindo
   - Enviados aumentando
   - Progresso aumentando

4. Verifique no N8N se os webhooks chegaram
5. No Supabase:
   - Table Editor > `dispatches`
   - Veja os registros criados

✅ **Sistema funcionando!**

---

## Passo 6: Processamento Automático (Opcional)

### Linux/Mac - Cron

```bash
crontab -e
```

Adicione (substitua o caminho):

```cron
*/5 * * * * cd /caminho/completo/backend && php bin/console app:process-dispatches
```

### Windows - Agendador de Tarefas

1. Abra "Agendador de Tarefas"
2. Criar Tarefa Básica
3. Gatilho: Repetir a cada 5 minutos
4. Ação: Iniciar programa
   - **Programa**: `php.exe`
   - **Argumentos**: `C:\caminho\backend\bin\console app:process-dispatches`

---

## Formato da Planilha

### Estrutura Obrigatória

```csv
nome,telefone,enviado
```

- **nome**: Nome do contato
- **telefone**: Apenas números (11999999999)
- **enviado**: Deixar vazio

### Exemplo Completo

```csv
nome,telefone,enviado
João Silva,11999999999,
Maria Santos,11988888888,
Pedro Oliveira,11977777777,
Ana Costa,11966666666,
```

### Formatos Aceitos

- ✅ CSV (.csv) - UTF-8
- ✅ Excel 2007+ (.xlsx)
- ✅ Excel 97-2003 (.xls)

---

## Troubleshooting

### Backend não inicia

**Erro: "Port 8000 already in use"**
```bash
php -S localhost:8080 -t public/
```

**Erro: "Class not found"**
```bash
composer dump-autoload
```

### Erro ao conectar Supabase

**"could not connect to server"**
- Verifique a senha na `DATABASE_URL`
- Certifique-se de substituir `[YOUR-PASSWORD]`

**"relation users does not exist"**
- Execute o SQL novamente no Supabase
- Verifique em Table Editor se as tabelas existem

### Erro no upload

**"No file uploaded"**
- Selecione um arquivo
- Máximo: 10MB

**"File type not supported"**
- Use apenas CSV, XLS ou XLSX

**"No contacts found"**
- Primeira linha deve ter: `nome,telefone,enviado`
- Verifique se há dados nas linhas seguintes

### Disparos não processam

1. Execute `php bin/console app:process-dispatches`
2. Teste o webhook N8N:
   ```bash
   curl -X POST https://seu-n8n.com/webhook/seu-path \
     -H "Content-Type: application/json" \
     -d '{"contact":{"nome":"Teste","telefone":"11999999999"}}'
   ```
3. Verifique se o workflow N8N está ativo
4. Veja logs em `backend/var/log/dev.log`

### Frontend não conecta

**Erro 401**
- Faça logout e login novamente
- Limpe o cache (Ctrl+Shift+Delete)

**Erro CORS**
- Verifique se o backend está em `localhost:8000`

---

## Deploy em Produção

### Backend
- Heroku, Railway, DigitalOcean, VPS
- Configure variáveis de ambiente
- Use `APP_ENV=prod`
- Execute SQL no Supabase de produção

### Frontend
- Vercel (grátis):
  ```bash
  cd frontend
  vercel
  ```
- Configure `VITE_API_URL` com URL do backend

---

## Suporte

- Logs: `backend/var/log/`
- DevTools: F12 > Console
- README.md para documentação completa

---

**Pronto! Seu sistema está funcionando!** 🎉
