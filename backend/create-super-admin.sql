-- Criar Super Admin de Teste
-- Execute este SQL no Supabase após executar a migration

-- 1. Criar organização de teste (se não existir)
INSERT INTO organizations (name, webhook_url, phone_number, is_active, created_at)
VALUES ('Admin Organization', 'https://webhook.site/test', '11999999999', TRUE, NOW())
ON CONFLICT DO NOTHING;

-- 2. Criar usuário Super Admin
-- Email: admin@teste.com
-- Senha: admin123
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
    roles = '["ROLE_USER", "ROLE_SUPER_ADMIN"]'::json,
    organization_id = (SELECT id FROM organizations WHERE name = 'Admin Organization' OR name = 'Organização Padrão' LIMIT 1);

-- Verificar se foi criado
SELECT
    id,
    email,
    name,
    roles,
    organization_id,
    created_at
FROM users
WHERE email = 'admin@teste.com';
