#!/bin/bash

# Quick Start Script per FornitoriPezziDB
# Questo script avvia rapidamente l'applicazione

set -e

echo "🚀 FornitoriPezziDB - Quick Start"
echo "=================================="
echo ""

# Verifica PHP
if ! command -v php &> /dev/null; then
    echo "❌ PHP non trovato. Installare PHP 7.4+"
    exit 1
fi

echo "✅ PHP $(php -v | head -n 1 | awk '{print $2}')"

# Verifica se è già in uso
if netstat -tuln 2>/dev/null | grep -q ":8080 "; then
    echo "⚠️  Porta 8080 già in uso"
    read -p "Usare porta diversa? (es. 8888) o continuare? [s/N]: " -n 1 -r
    if [[ $REPLY =~ ^[Ss]$ ]]; then
        PORT=8888
    else
        PORT=8080
    fi
else
    PORT=8080
fi

# Controlla dipendenze Composer
if [ ! -d "vendor" ]; then
    echo "Installando dipendenze Composer..."
    composer install --no-progress
fi

# Controlla e crea .env se necessario
if [ ! -f ".env" ]; then
    echo "Creando file .env..."
    cp .env.example .env
    echo "✅ File .env creato. Controlla le credenziali MySQL!"
fi

# Importa database
echo ""
echo "📦 Importando schema del database..."
source .env

# Tenta importazione
if ! mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" < database.sql 2>/dev/null; then
    echo "⚠️  Errore nell'importazione del database"
    echo "Assicurati che MySQL sia in esecuzione e le credenziali in .env siano corrette"
    read -p "Continuare comunque? [s/N]: " -n 1 -r
    if [[ ! $REPLY =~ ^[Ss]$ ]]; then
        exit 1
    fi
fi

echo "✅ Database pronto"

# Avvia server
echo ""
echo "🌐 Avviando server PHP sulla porta $PORT..."
echo "📍 URL: http://localhost:$PORT/login.html"
echo ""
echo "🔐 CREDENZIALI DI TEST:"
echo "   Admin: admin / password123"
echo "   Fornitore: acme_user / password123"
echo ""
echo "Premi Ctrl+C per fermare il server"
echo ""

php -S localhost:$PORT -t public/
