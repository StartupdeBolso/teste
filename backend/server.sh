#!/bin/bash

# N8N Dispatch SaaS - Server Startup Script
# Usa o PHP padrão do sistema (deve ser >= 8.1)

cd "$(dirname "$0")"

echo "=================================="
echo "🚀 N8N DISPATCH SAAS - SERVIDOR"
echo "=================================="
echo ""
echo "📌 Versão do PHP:"
/usr/bin/php --version | head -1
echo ""
echo "🌐 Servidor rodando em: http://localhost:8000"
echo ""
echo "🔐 Credenciais de teste:"
echo "   Email: admin@teste.com"
echo "   Senha: admin123"
echo ""
echo "⚠️  Pressione Ctrl+C para parar o servidor"
echo "=================================="
echo ""

# Inicia o servidor
/usr/bin/php -S localhost:8000 -t public/
