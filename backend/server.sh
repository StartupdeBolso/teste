#!/bin/bash

echo "🚀 Iniciando servidor com PHP 8.4..."
echo ""
echo "Servidor rodando em: http://localhost:8000"
echo ""
echo "Credenciais de teste:"
echo "  Email: admin@teste.com"
echo "  Senha: admin123"
echo ""
echo "Pressione Ctrl+C para parar"
echo ""

/usr/bin/php8.4 -S localhost:8000 -t public/
