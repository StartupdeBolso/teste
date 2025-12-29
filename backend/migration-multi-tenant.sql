-- Migration: Transformar sistema single-tenant em multi-tenant SaaS
-- Execute este script no Supabase SQL Editor se você já tem o banco configurado
-- Este script adiciona suporte a organizações e permissões

-- 1. Criar tabela de organizações
CREATE TABLE IF NOT EXISTS organizations (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    webhook_url VARCHAR(500),
    phone_number VARCHAR(50),
    settings JSON,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

-- 2. Adicionar coluna organization_id na tabela users
ALTER TABLE users
ADD COLUMN IF NOT EXISTS organization_id INTEGER REFERENCES organizations(id) ON DELETE SET NULL;

-- 3. Adicionar coluna organization_id na tabela campaigns
ALTER TABLE campaigns
ADD COLUMN IF NOT EXISTS organization_id INTEGER REFERENCES organizations(id) ON DELETE CASCADE;

-- 4. Criar índices para performance
CREATE INDEX IF NOT EXISTS idx_organizations_is_active ON organizations(is_active);
CREATE INDEX IF NOT EXISTS idx_users_organization_id ON users(organization_id);
CREATE INDEX IF NOT EXISTS idx_campaigns_organization_id ON campaigns(organization_id);

-- 5. Criar organization padrão para dados existentes
INSERT INTO organizations (name, webhook_url, is_active, created_at)
VALUES ('Organização Padrão', NULL, TRUE, NOW())
ON CONFLICT DO NOTHING
RETURNING id;

-- 6. Associar usuários existentes à organization padrão
UPDATE users
SET organization_id = (SELECT id FROM organizations WHERE name = 'Organização Padrão' LIMIT 1)
WHERE organization_id IS NULL;

-- 7. Associar campanhas existentes à organization do seu usuário
UPDATE campaigns c
SET organization_id = (
    SELECT u.organization_id
    FROM users u
    WHERE u.id = c.user_id
    LIMIT 1
)
WHERE organization_id IS NULL;

-- 8. Adicionar comentários
COMMENT ON TABLE organizations IS 'Tabela de organizações (multi-tenant SaaS)';
COMMENT ON COLUMN users.organization_id IS 'Organização à qual o usuário pertence';
COMMENT ON COLUMN campaigns.organization_id IS 'Organização à qual a campanha pertence';

-- 9. OPCIONAL: Promover primeiro usuário a super admin
-- Descomente e ajuste o email se desejar
-- UPDATE users
-- SET roles = '["ROLE_USER", "ROLE_SUPER_ADMIN"]'::json
-- WHERE email = 'seu-email@example.com';

-- 10. OPCIONAL: Copiar webhook global para organization padrão
-- Descomente e adicione sua URL de webhook
-- UPDATE organizations
-- SET webhook_url = 'https://seu-n8n.com/webhook/seu-path'
-- WHERE name = 'Organização Padrão';

-- Verificar migração
SELECT
    'Organizations' as tabela, COUNT(*) as registros
FROM organizations
UNION ALL
SELECT
    'Users with org' as tabela, COUNT(*) as registros
FROM users WHERE organization_id IS NOT NULL
UNION ALL
SELECT
    'Campaigns with org' as tabela, COUNT(*) as registros
FROM campaigns WHERE organization_id IS NOT NULL;
