# Sistema Multi-Tenant SaaS - Documentação

Este documento explica como funciona o sistema de multi-tenancy (organizações) e permissões do N8N Dispatch SaaS.

## Arquitetura Multi-Tenant

O sistema foi projetado para ser um **SaaS completo** onde múltiplas organizações podem usar a plataforma de forma isolada.

### Estrutura

```
Organizations (Empresas/Contas)
├── Users (Usuários da organização)
│   ├── Super Admin (gerencia tudo)
│   ├── Org Admin (gerencia sua organização)
│   └── User (usa o sistema)
└── Campaigns (Campanhas isoladas por organização)
    ├── Contacts (Contatos da campanha)
    └── Dispatches (Disparos)
```

## Níveis de Permissão

### 1. **Super Admin** (`ROLE_SUPER_ADMIN`)

- **Acesso total** ao sistema
- Pode criar/editar/deletar **todas** as organizações
- Vê e gerencia **todos** os usuários de todas as organizações
- Vê **todas** as campanhas de todas as organizações
- Acessa o painel administrativo global

**Quando usar:** Você (dono do SaaS) gerenciando a plataforma.

### 2. **Organization Admin** (`ROLE_ORG_ADMIN`)

- Gerencia **sua própria organização**
- Vê **todas as campanhas** da organização (não apenas as dele)
- Pode editar configurações da organização (webhook, telefone, etc)
- **Não pode** deletar a organização
- **Não pode** ver outras organizações

**Quando usar:** Gerente/Admin de uma empresa cliente.

### 3. **User** (`ROLE_USER`)

- Usa o sistema normalmente
- Vê **apenas suas próprias campanhas**
- Pode criar/editar/deletar suas campanhas
- Pode visualizar configurações da organização

**Quando usar:** Usuário normal de uma empresa cliente.

## Como Funciona

### Registro de Novo Usuário

Existem **3 formas** de se registrar:

#### 1. Criar Nova Organização

```json
POST /api/auth/register
{
  "name": "João Silva",
  "email": "joao@example.com",
  "password": "senha123",
  "organizationName": "Minha Empresa",
  "webhookUrl": "https://n8n.com/webhook/abc123",
  "phoneNumber": "11999999999"
}
```

- Cria uma nova organização
- Usuário se torna **Org Admin** automaticamente
- Define webhook e telefone da organização

#### 2. Entrar em Organização Existente

```json
POST /api/auth/register
{
  "name": "Maria Santos",
  "email": "maria@example.com",
  "password": "senha123",
  "organizationId": 1
}
```

- Entra em uma organização existente
- Usuário é **User** normal
- Usa o webhook da organização

#### 3. Registro Simples (Sem Organização)

```json
POST /api/auth/register
{
  "name": "Pedro Costa",
  "email": "pedro@example.com",
  "password": "senha123"
}
```

- Cria usuário **sem organização**
- Pode ser associado depois pelo Super Admin

### Configuração por Organização

Cada organização tem suas próprias configurações:

```javascript
{
  "id": 1,
  "name": "Minha Empresa",
  "webhookUrl": "https://n8n.com/webhook/abc123",  // Webhook próprio
  "phoneNumber": "11999999999",                     // Telefone próprio
  "settings": {                                     // Configurações customizadas
    "timezone": "America/Sao_Paulo",
    "theme": "dark",
    "notifications": true
  },
  "isActive": true
}
```

### Isolamento de Dados

- **Campanhas**: Cada campanha pertence a uma organização
- **Contatos**: Isolados por campanha (consequentemente por organização)
- **Disparos**: Usam o webhook da organização

**Exemplo:**
- Organização A com 1000 contatos
- Organização B com 2000 contatos
- Org A **nunca** vê contatos de Org B

## Endpoints da API

### Organizações

```bash
# Listar organizações (suas ou todas se for super admin)
GET /api/organizations

# Ver detalhes de uma organização
GET /api/organizations/{id}

# Criar organização (apenas super admin)
POST /api/organizations
{
  "name": "Nova Empresa",
  "webhookUrl": "https://...",
  "phoneNumber": "11999999999"
}

# Editar organização (org admin ou super admin)
PUT /api/organizations/{id}
{
  "webhookUrl": "https://novo-webhook.com/...",
  "phoneNumber": "11988888888"
}

# Deletar organização (apenas super admin)
DELETE /api/organizations/{id}

# Ver estatísticas da organização
GET /api/organizations/{id}/stats
```

### Administração (Super Admin)

```bash
# Dashboard global
GET /api/admin/dashboard

# Listar todos os usuários (com filtro por organização)
GET /api/admin/users?organizationId=1&page=1&limit=50

# Editar qualquer usuário
PUT /api/admin/users/{id}
{
  "name": "Novo Nome",
  "roles": ["ROLE_USER", "ROLE_ORG_ADMIN"],
  "organizationId": 2
}

# Deletar usuário
DELETE /api/admin/users/{id}

# Ver usuários de uma organização
GET /api/admin/organizations/{id}/users

# Ver todas as campanhas do sistema
GET /api/admin/campaigns/all?page=1&limit=50
```

### Campanhas (com permissões)

As campanhas agora respeitam as permissões:

```bash
# Listar campanhas
GET /api/campaigns
# - Super Admin: vê TODAS
# - Org Admin: vê todas da sua organização
# - User: vê apenas as suas

# Ver campanha específica
GET /api/campaigns/{id}
# - Verifica se você tem permissão para ver

# Criar campanha
POST /api/campaigns
# - Automaticamente associada à sua organização
# - Usa o webhook da organização

# Editar/Deletar/Iniciar
PUT /api/campaigns/{id}
DELETE /api/campaigns/{id}
POST /api/campaigns/{id}/start
# - Verifica permissões antes de executar
```

## Como Usar - Cenários Práticos

### Cenário 1: Você é o Dono do SaaS

1. **Criar o primeiro Super Admin:**

```sql
-- No Supabase SQL Editor
UPDATE users
SET roles = '["ROLE_USER", "ROLE_SUPER_ADMIN"]'::json
WHERE email = 'seu-email@example.com';
```

2. **Criar organizações para clientes:**

```bash
POST /api/organizations
{
  "name": "Cliente ABC Ltda",
  "webhookUrl": "https://cliente-abc-n8n.com/webhook/xyz",
  "phoneNumber": "11999999999"
}
```

3. **Criar usuário admin para o cliente:**

```bash
POST /api/admin/users
{
  "name": "Admin Cliente ABC",
  "email": "admin@clienteabc.com",
  "roles": ["ROLE_USER", "ROLE_ORG_ADMIN"],
  "organizationId": 1
}
```

### Cenário 2: Cliente Usando o Sistema

1. **Cliente se registra criando sua organização:**

```bash
POST /api/auth/register
{
  "name": "João Silva",
  "email": "joao@minhaempresa.com",
  "password": "senha123",
  "organizationName": "Minha Empresa",
  "webhookUrl": "https://meu-n8n.com/webhook/abc"
}
```

2. **Adicionar mais usuários na equipe:**

Outros membros da equipe se registram com:

```bash
POST /api/auth/register
{
  "name": "Maria Santos",
  "email": "maria@minhaempresa.com",
  "password": "senha123",
  "organizationId": 1  # ID da organização
}
```

3. **Criar campanhas normalmente:**

```bash
POST /api/campaigns
{
  "name": "Campanha Vendas Q1",
  "description": "Disparos do primeiro trimestre"
}
# Automaticamente usa o webhook da organização
```

### Cenário 3: Migração de Sistema Existente

Se você já tem o sistema rodando e quer adicionar multi-tenancy:

1. **Execute a migration:**

```sql
-- No Supabase SQL Editor
-- Execute: backend/migration-multi-tenant.sql
```

2. **Configure a organização padrão:**

```sql
UPDATE organizations
SET webhook_url = 'https://seu-webhook-atual.com/...'
WHERE name = 'Organização Padrão';
```

3. **Promova seu usuário a super admin:**

```sql
UPDATE users
SET roles = '["ROLE_USER", "ROLE_SUPER_ADMIN"]'::json
WHERE email = 'seu-email@example.com';
```

## Fluxo de Dados dos Disparos

```
1. Usuário cria campanha
   └─> Campanha associada à organização do usuário

2. Upload de arquivo
   └─> Contatos salvos na campanha

3. Iniciar campanha
   └─> Cria disparos

4. Processar disparos
   ├─> Verifica limite diário da campanha
   ├─> Pega webhook_url da organização
   ├─> Envia para o webhook específico
   └─> Atualiza estatísticas
```

**Importante:** Cada organização usa seu próprio webhook N8N!

## Verificação de Segurança

O sistema usa **Symfony Voters** para garantir segurança:

### CampaignVoter

```php
// Verifica automaticamente:
- Se é super admin → permite tudo
- Se é da mesma organização → permite se for org admin
- Se é o dono da campanha → permite
- Caso contrário → nega acesso
```

### OrganizationVoter

```php
// Verifica automaticamente:
- Super admin → pode tudo
- Org admin → pode editar sua org (mas não deletar)
- User → pode apenas visualizar
```

## Endpoint /me

Agora retorna informações da organização:

```json
GET /api/auth/me

{
  "id": 1,
  "name": "João Silva",
  "email": "joao@example.com",
  "roles": ["ROLE_USER", "ROLE_ORG_ADMIN"],
  "isSuperAdmin": false,
  "isOrgAdmin": true,
  "organization": {
    "id": 1,
    "name": "Minha Empresa",
    "webhookUrl": "https://meu-n8n.com/webhook/abc",
    "phoneNumber": "11999999999",
    "isActive": true
  },
  "createdAt": "2024-01-15T10:00:00+00:00"
}
```

## Próximos Passos

1. ✅ Backend multi-tenant completo
2. ⏳ **Frontend** - Criar interfaces para:
   - Gerenciamento de organizações
   - Painel de admin
   - Configurações da organização
   - Convite de usuários
3. ⏳ Sistema de convites por email
4. ⏳ Billing/Assinaturas (Stripe)
5. ⏳ Logs de auditoria

## Troubleshooting

### Erro: "No webhook URL configured"

- Sua organização não tem webhook configurado
- Configure em `PUT /api/organizations/{id}`

### Erro: "Access denied"

- Você não tem permissão para acessar esse recurso
- Verifique se está na mesma organização
- Verifique suas roles

### Usuário sem organização

- Super admin pode associar: `PUT /api/admin/users/{id}`
- Ou usuário cria nova organização

---

**Pronto!** Seu sistema agora é um SaaS completo multi-tenant! 🚀
