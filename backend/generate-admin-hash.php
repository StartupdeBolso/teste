<?php
// Script para gerar hash de senha para o Super Admin
// Execute: php generate-admin-hash.php

$password = 'admin123';
$hash = password_hash($password, PASSWORD_BCRYPT);

echo "\n=================================\n";
echo "SENHA: {$password}\n";
echo "HASH: {$hash}\n";
echo "=================================\n\n";

echo "Use este SQL no Supabase:\n\n";

echo <<<SQL
-- Criar Super Admin
INSERT INTO users (organization_id, email, name, password, roles, created_at)
VALUES (
    (SELECT id FROM organizations WHERE name = 'Admin Organization' OR name = 'Organização Padrão' LIMIT 1),
    'admin@teste.com',
    'Super Admin',
    '{$hash}',
    '["ROLE_USER", "ROLE_SUPER_ADMIN"]'::json,
    NOW()
)
ON CONFLICT (email) DO UPDATE
SET password = '{$hash}',
    roles = '["ROLE_USER", "ROLE_SUPER_ADMIN"]'::json;

SQL;

echo "\n\nCredenciais:\n";
echo "Email: admin@teste.com\n";
echo "Senha: admin123\n\n";
