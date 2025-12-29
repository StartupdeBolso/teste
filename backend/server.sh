#!/bin/bash

echo "🚀 Iniciando servidor PHP..."
echo ""
echo "Versão do PHP:"
php --version | head -1
echo ""
echo "Servidor rodando em: http://localhost:8000"
echo ""
echo "Credenciais de teste:"
echo "  Email: admin@teste.com"
echo "  Senha: admin123"
echo ""
echo "Pressione Ctrl+C para parar"
echo ""

php -S localhost:8000 -t public/
