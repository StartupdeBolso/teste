#!/bin/bash

echo "=================================="
echo "🚀 SETUP COMPLETO - N8N DISPATCH SAAS"
echo "=================================="
echo ""

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

cd /home/user/teste/backend

echo "📦 1. Verificando dependências..."
if ! composer check-platform-reqs > /dev/null 2>&1; then
    echo -e "${YELLOW}Instalando dependências...${NC}"
    composer install --no-interaction
fi
echo -e "${GREEN}✓ Dependências OK${NC}"
echo ""

echo "🔑 2. Gerando chaves JWT..."
# Criar diretório se não existir
mkdir -p config/jwt

# Gerar chave privada
openssl genrsa -out config/jwt/private.pem 4096 2>/dev/null
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Chave privada gerada${NC}"
else
    echo -e "${RED}✗ Erro ao gerar chave privada${NC}"
    exit 1
fi

# Gerar chave pública
openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem 2>/dev/null
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Chave pública gerada${NC}"
else
    echo -e "${RED}✗ Erro ao gerar chave pública${NC}"
    exit 1
fi

# Definir permissões corretas
chmod 644 config/jwt/private.pem
chmod 644 config/jwt/public.pem

echo ""

echo "📝 3. Configurando .env..."
# Verificar se .env existe
if [ ! -f .env ]; then
    cp .env .env.backup 2>/dev/null
fi

# Gerar passphrase aleatória
JWT_PASS=$(openssl rand -base64 32)

# Atualizar JWT_PASSPHRASE no .env
if grep -q "JWT_PASSPHRASE=" .env; then
    sed -i "s|JWT_PASSPHRASE=.*|JWT_PASSPHRASE=${JWT_PASS}|g" .env
    echo -e "${GREEN}✓ JWT_PASSPHRASE configurado${NC}"
else
    echo "JWT_PASSPHRASE=${JWT_PASS}" >> .env
    echo -e "${GREEN}✓ JWT_PASSPHRASE adicionado${NC}"
fi

# Verificar DATABASE_URL
if grep -q "DATABASE_URL=.*\[PASSWORD\]" .env; then
    echo -e "${YELLOW}⚠ ATENÇÃO: Configure seu DATABASE_URL no .env com a senha do Supabase!${NC}"
fi

echo ""

echo "🧹 4. Limpando cache..."
php bin/console cache:clear --no-warmup > /dev/null 2>&1
echo -e "${GREEN}✓ Cache limpo${NC}"
echo ""

echo "✅ 5. Verificando configuração..."
echo ""
echo "Chaves JWT:"
echo "  - Privada: $(ls -lh config/jwt/private.pem | awk '{print $5}')"
echo "  - Pública: $(ls -lh config/jwt/public.pem | awk '{print $5}')"
echo ""

echo "=================================="
echo "✅ SETUP CONCLUÍDO COM SUCESSO!"
echo "=================================="
echo ""
echo "📋 PRÓXIMOS PASSOS:"
echo ""
echo "1️⃣  Configure o DATABASE_URL no .env:"
echo "   ${YELLOW}DATABASE_URL=\"postgresql://postgres:SUA_SENHA@db.SEU_PROJETO.supabase.co:5432/postgres\"${NC}"
echo ""
echo "2️⃣  Execute as migrations no Supabase (SQL Editor):"
echo "   ${YELLOW}backend/migration-multi-tenant.sql${NC}"
echo ""
echo "3️⃣  Crie o super admin no Supabase (SQL Editor):"
echo "   ${YELLOW}backend/create-super-admin.sql${NC}"
echo ""
echo "4️⃣  Inicie o servidor:"
echo "   ${GREEN}php -S localhost:8000 -t public/${NC}"
echo ""
echo "5️⃣  Teste o login:"
echo "   Email: ${GREEN}admin@teste.com${NC}"
echo "   Senha: ${GREEN}admin123${NC}"
echo ""
echo "=================================="
