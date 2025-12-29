# 🚀 GUIA COMPLETO - PASSO A PASSO

**Sistema Multi-Tenant SaaS - N8N Dispatch**

---

## ✅ CHECKLIST RÁPIDO

- [ ] 1. Configurar Supabase
- [ ] 2. Executar migrations
- [ ] 3. Criar super admin
- [ ] 4. Configurar .env do backend
- [ ] 5. Iniciar servidor backend
- [ ] 6. Testar login

---

## 📋 PASSO 1: SUPABASE (5 minutos)

### 1.1 - Criar Projeto no Supabase

1. Acesse: https://supabase.com
2. Clique em **"New Project"**
3. Preencha:
   - **Name**: n8n-dispatch-saas
   - **Database Password**: Anote essa senha!
   - **Region**: South America (São Paulo)
4. Clique em **"Create new project"**
5. Aguarde ~2 minutos

### 1.2 - Pegar Informações de Conexão

1. No projeto, clique em **⚙️ Settings** (ícone de engrenagem no menu lateral)
2. Clique em **"Database"**
3. Role até **"Connection String"**
4. Clique na aba **"URI"**
5. Copie a string que aparece (formato: `postgresql://postgres.[ref]:[password]@...`)
6. **IMPORTANTE**: Substitua `[password]` pela senha que você criou!

Exemplo final:
```
postgresql://postgres.abcdefgh:SuaSenhaAqui@aws-0-sa-east-1.pooler.supabase.com:5432/postgres
```

---

## 📋 PASSO 2: EXECUTAR MIGRATIONS NO SUPABASE (3 minutos)

### 2.1 - Abrir SQL Editor

1. No Supabase, clique em **"SQL Editor"** no menu lateral (ícone `</>`)
2. Clique em **"New query"**

### 2.2 - Executar Migration Principal

**Copie e cole este SQL completo:**

```sql
-- Migration: Multi-Tenant SaaS
CREATE TABLE IF NOT EXISTS organizations (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    webhook_url VARCHAR(500),
    phone_number VARCHAR(50),
    settings JSON,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

ALTER TABLE users
ADD COLUMN IF NOT EXISTS organization_id INTEGER REFERENCES organizations(id) ON DELETE SET NULL;

ALTER TABLE campaigns
ADD COLUMN IF NOT EXISTS organization_id INTEGER REFERENCES organizations(id) ON DELETE CASCADE;

CREATE INDEX IF NOT EXISTS idx_organizations_is_active ON organizations(is_active);
CREATE INDEX IF NOT EXISTS idx_users_organization_id ON users(organization_id);
CREATE INDEX IF NOT EXISTS idx_campaigns_organization_id ON campaigns(organization_id);

INSERT INTO organizations (name, webhook_url, is_active, created_at)
VALUES ('Organização Padrão', NULL, TRUE, NOW())
ON CONFLICT DO NOTHING;

UPDATE users
SET organization_id = (SELECT id FROM organizations WHERE name = 'Organização Padrão' LIMIT 1)
WHERE organization_id IS NULL;

UPDATE campaigns c
SET organization_id = (
    SELECT u.organization_id FROM users u WHERE u.id = c.user_id LIMIT 1
)
WHERE organization_id IS NULL;
```

Clique em **"Run"** (ou Ctrl+Enter)

Deve aparecer: ✅ **"Success. No rows returned"**

### 2.3 - Criar Super Admin

**No mesmo SQL Editor, execute:**

```sql
-- Criar Super Admin
INSERT INTO organizations (name, webhook_url, phone_number, is_active)
VALUES ('Admin Organization', 'https://webhook.site/test', '11999999999', TRUE)
ON CONFLICT DO NOTHING;

INSERT INTO users (organization_id, email, name, password, roles, created_at)
VALUES (
    (SELECT id FROM organizations WHERE name = 'Admin Organization' OR name = 'Organização Padrão' LIMIT 1),
    'admin@teste.com',
    'Super Admin',
    '$2y$12$QkQA7qRZEURs9RSra7b2w.w1OV0m2LjLglVeEOXuCi7UxnQL/ZSf6',
    '["ROLE_USER", "ROLE_SUPER_ADMIN"]'::json,
    NOW()
)
ON CONFLICT (email) DO UPDATE
SET password = '$2y$12$QkQA7qRZEURs9RSra7b2w.w1OV0m2LjLglVeEOXuCi7UxnQL/ZSf6',
    roles = '["ROLE_USER", "ROLE_SUPER_ADMIN"]'::json;
```

Clique em **"Run"**

Deve aparecer: ✅ **"Success. No rows returned"**

### 2.4 - Verificar Tabelas

1. Clique em **"Table Editor"** no menu lateral
2. Você deve ver **5 tabelas**:
   - ✅ organizations
   - ✅ users
   - ✅ campaigns
   - ✅ contacts
   - ✅ dispatches

3. Clique na tabela **users**
4. Você deve ver: **admin@teste.com**

**PRONTO! Banco configurado!** ✅

---

## 📋 PASSO 3: CONFIGURAR BACKEND (2 minutos)

### 3.1 - Editar .env

1. Abra o arquivo: `backend/.env`
2. Edite a linha **DATABASE_URL**:

```bash
DATABASE_URL="postgresql://postgres.abcdefgh:SuaSenhaAqui@aws-0-sa-east-1.pooler.supabase.com:5432/postgres"
```

**Substitua pela sua connection string do Supabase!**

### 3.2 - Executar Script de Setup

No terminal, execute:

```bash
cd backend
./setup.sh
```

Você deve ver:
```
🚀 SETUP COMPLETO - N8N DISPATCH SAAS
✓ Dependências OK
✓ Chave privada gerada
✓ Chave pública gerada
✓ JWT_PASSPHRASE configurado
✓ Cache limpo
✅ SETUP CONCLUÍDO COM SUCESSO!
```

---

## 📋 PASSO 4: INICIAR SERVIDOR (1 minuto)

No terminal (dentro de `backend/`):

```bash
php -S localhost:8000 -t public/
```

Você deve ver:
```
PHP 8.x Development Server (http://localhost:8000) started
```

**Deixe esse terminal aberto!**

---

## 📋 PASSO 5: TESTAR O SISTEMA (2 minutos)

### 5.1 - Testar Login via API

Abra outro terminal e teste:

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@teste.com","password":"admin123"}'
```

**Resposta esperada:**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refresh_token": "..."
}
```

✅ **Se viu o token = FUNCIONOU!**

### 5.2 - Testar Endpoint /me

Copie o token da resposta acima e teste:

```bash
curl http://localhost:8000/api/auth/me \
  -H "Authorization: Bearer SEU_TOKEN_AQUI"
```

**Resposta esperada:**
```json
{
  "id": 1,
  "email": "admin@teste.com",
  "name": "Super Admin",
  "roles": ["ROLE_USER", "ROLE_SUPER_ADMIN"],
  "isSuperAdmin": true,
  "isOrgAdmin": true,
  "organization": {
    "id": 1,
    "name": "Admin Organization",
    "webhookUrl": "https://webhook.site/test",
    "phoneNumber": "11999999999",
    "isActive": true
  }
}
```

✅ **PERFEITO! Backend funcionando!**

---

## 📋 PASSO 6: FRONTEND (Opcional - 2 minutos)

### 6.1 - Configurar Frontend

1. Abra: `frontend/.env`
2. Verifique se está assim:

```bash
VITE_API_URL=http://localhost:8000
```

### 6.2 - Iniciar Frontend

No terminal (dentro de `frontend/`):

```bash
npm install
npm run dev
```

### 6.3 - Acessar e Testar Login

1. Abra: http://localhost:3000
2. Faça login com:
   - **Email**: admin@teste.com
   - **Senha**: admin123

✅ **Deve funcionar!**

---

## 🎯 RESUMO DOS COMANDOS

```bash
# 1. Backend - Configurar e iniciar
cd backend
./setup.sh
php -S localhost:8000 -t public/

# 2. Frontend - Instalar e iniciar (outro terminal)
cd frontend
npm install
npm run dev
```

---

## 🔑 CREDENCIAIS PADRÃO

```
Email: admin@teste.com
Senha: admin123
```

**⚠️ IMPORTANTE: Troque essa senha depois!**

---

## 🧪 TESTAR TODAS AS FUNCIONALIDADES

### 1. Login
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@teste.com","password":"admin123"}'
```

### 2. Ver Perfil
```bash
curl http://localhost:8000/api/auth/me \
  -H "Authorization: Bearer TOKEN"
```

### 3. Listar Organizations
```bash
curl http://localhost:8000/api/organizations \
  -H "Authorization: Bearer TOKEN"
```

### 4. Dashboard Admin
```bash
curl http://localhost:8000/api/admin/dashboard \
  -H "Authorization: Bearer TOKEN"
```

### 5. Criar Nova Organization
```bash
curl -X POST http://localhost:8000/api/organizations \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Empresa Teste",
    "webhookUrl": "https://webhook.site/abc123",
    "phoneNumber": "11988888888"
  }'
```

### 6. Criar Campanha
```bash
curl -X POST http://localhost:8000/api/campaigns \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Campanha Teste",
    "description": "Primeira campanha de teste"
  }'
```

---

## ❌ TROUBLESHOOTING

### Erro: "No route found for POST /api/auth/login"

**Solução:**
```bash
cd backend
php bin/console cache:clear
```

### Erro: "Invalid credentials"

**Solução:**
Verifique se executou o SQL de criação do super admin no Supabase.

### Erro: "Unable to find the wrapper "https""

**Solução:**
```bash
# Verificar extensões PHP
php -m | grep -i "curl\|openssl"

# Se não aparecer, instalar:
sudo apt-get install php-curl php-openssl
```

### Erro: "SQLSTATE[08006] Connection refused"

**Solução:**
Verifique o DATABASE_URL no .env - deve ter a senha correta do Supabase.

### Erro: "An exception occurred in driver: SQLSTATE[08006]"

**Solução:**
```bash
# Testar conexão com Supabase
psql "sua-connection-string-aqui"

# Se não conectar, verificar:
# 1. Senha está correta?
# 2. IP está na whitelist? (Supabase > Settings > Database > Connection Pooling)
```

---

## 📚 PRÓXIMOS PASSOS

1. ✅ Criar mais organizações via API
2. ✅ Criar campanhas e fazer upload de contatos
3. ✅ Configurar webhooks N8N para cada organização
4. ✅ Testar disparos
5. ⏳ Implementar frontend completo (em desenvolvimento)

---

## 🔐 SEGURANÇA

**TROQUE ESSAS SENHAS EM PRODUÇÃO:**

1. Super admin password (admin123)
2. JWT_PASSPHRASE no .env
3. APP_SECRET no .env
4. Database password no Supabase

---

## 📞 SUPORTE

Problemas? Verifique:
1. Logs do PHP: Olhe o terminal onde rodou `php -S`
2. Logs do Supabase: SQL Editor > Logs
3. Network no DevTools (F12) do navegador

---

**✅ TUDO CONFIGURADO E FUNCIONANDO!** 🎉
