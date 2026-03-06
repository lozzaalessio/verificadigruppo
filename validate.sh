#!/bin/bash

# Script di validazione post-implementazione
# Testa che tutto sia configurato correttamente

set -e

echo "🔍 FornitoriPezziDB - Validazione Post-Implementazione"
echo "====================================================="
echo ""

# Colori ANSI
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

pass() {
    echo -e "${GREEN}✅${NC} $1"
}

fail() {
    echo -e "${RED}❌${NC} $1"
    exit 1
}

warn() {
    echo -e "${YELLOW}⚠️ ${NC} $1"
}

echo "1️⃣  Verificando file principali..."
echo ""

# Check Core Files
[ -f "public/index.php" ] && pass "public/index.php trovato" || fail "public/index.php mancante"
[ -f "src/Database.php" ] && pass "src/Database.php trovato" || fail "src/Database.php mancante"
[ -f "src/Auth.php" ] && pass "src/Auth.php trovato" || fail "src/Auth.php mancante"
[ -f "database.sql" ] && pass "database.sql trovato" || fail "database.sql mancante"

echo ""
echo "2️⃣  Verificando file new web interface..."
echo ""

# Check Web Files
[ -f "public/login.html" ] && pass "public/login.html trovato" || fail "public/login.html mancante"
[ -f "public/dashboard-admin.html" ] && pass "dashboard-admin.html trovato" || fail "dashboard-admin.html mancante"
[ -f "public/dashboard-fornitore.html" ] && pass "dashboard-fornitore.html trovato" || fail "dashboard-fornitore.html mancante"
[ -f "public/styles.css" ] && pass "styles.css trovato" || fail "styles.css mancante"
[ -f "public/dashboard-common.js" ] && pass "dashboard-common.js trovato" || fail "dashboard-common.js mancante"

echo ""
echo "3️⃣  Verificando file middleware..."
echo ""

# Check Middleware
[ -f "src/Middleware/AuthMiddleware.php" ] && pass "AuthMiddleware.php trovato" || fail "AuthMiddleware.php mancante"
[ -f "src/Middleware/AdminMiddleware.php" ] && pass "AdminMiddleware.php trovato" || fail "AdminMiddleware.php mancante"

echo ""
echo "4️⃣  Verificando file documentazione..."
echo ""

# Check Documentation
[ -f "IMPLEMENTAZIONE.md" ] && pass "IMPLEMENTAZIONE.md trovato" || fail "IMPLEMENTAZIONE.md mancante"
[ -f "API_REFERENCE.md" ] && pass "API_REFERENCE.md trovato" || fail "API_REFERENCE.md mancante"
[ -f "CHANGELOG.md" ] && pass "CHANGELOG.md trovato" || fail "CHANGELOG.md mancante"
[ -f "DEPLOYMENT.md" ] && pass "DEPLOYMENT.md trovato" || fail "DEPLOYMENT.md mancante"

echo ""
echo "5️⃣  Verificando schema database..."
echo ""

# Check database schema
grep -q "CREATE TABLE Users" database.sql && pass "Tabella Users nel schema" || fail "Tabella Users mancante"
grep -q "CREATE TABLE Fornitori" database.sql && pass "Tabella Fornitori nel schema" || fail "Tabella Fornitori mancante"
grep -q "CREATE TABLE Pezzi" database.sql && pass "Tabella Pezzi nel schema" || fail "Tabella Pezzi mancante"
grep -q "CREATE TABLE Catalogo" database.sql && pass "Tabella Catalogo nel schema" || fail "Tabella Catalogo mancante"

# Check new fields
grep -q "user_id INT" database.sql && pass "Campo user_id in Fornitori" || warn "Campo user_id potrebbe essere mancante"
grep -q "quantita INT" database.sql && pass "Campo quantita in Catalogo" || warn "Campo quantita potrebbe essere mancante"
grep -q "descrizione TEXT" database.sql && pass "Campo descrizione in Pezzi" || warn "Campo descrizione potrebbe essere mancante"

echo ""
echo "6️⃣  Verificando API endpoints..."
echo ""

# Check API endpoints in index.php
grep -q "/api/auth/login" public/index.php && pass "API /api/auth/login presente" || fail "/api/auth/login mancante"
grep -q "/api/fornitori" public/index.php && pass "API /api/fornitori presente" || fail "/api/fornitori mancante"
grep -q "/api/pezzi" public/index.php && pass "API /api/pezzi presente" || fail "/api/pezzi mancante"
grep -q "/api/catalogo" public/index.php && pass "API /api/catalogo presente" || fail "/api/catalogo mancante"

echo ""
echo "7️⃣  Verificando configurazione .env..."
echo ""

# Check .env
if [ ! -f ".env" ]; then
    warn ".env non trovato"
    if [ -f ".env.example" ]; then
        pass ".env.example trovato - esegui: cp .env.example .env"
    else
        fail ".env.example mancante"
    fi
else
    pass ".env trovato"
    source .env
    [ -n "$DB_HOST" ] && pass "DB_HOST configurato" || warn "DB_HOST non configurato"
    [ -n "$DB_USER" ] && pass "DB_USER configurato" || warn "DB_USER non configurato"
    [ -n "$DB_NAME" ] && pass "DB_NAME configurato" || warn "DB_NAME non configurato"
fi

echo ""
echo "8️⃣  Verificando dependenze PHP..."
echo ""

if ! command -v php &> /dev/null; then
    fail "PHP non trovato"
else
    pass "PHP trovato ($(php -v | head -n 1 | awk '{print $2}'))"
fi

if ! command -v composer &> /dev/null; then
    warn "Composer non trovato nel PATH"
else
    pass "Composer trovato"
fi

if [ -d "vendor" ]; then
    pass "Cartella vendor presente (dipendenze installate)"
else
    warn "Cartella vendor mancante - esegui: composer install"
fi

echo ""
echo "9️⃣  Verificando database connessione (opzionale)..."
echo ""

if [ -f ".env" ]; then
    source .env
    if command -v mysql &> /dev/null; then
        if mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -e "USE $DB_NAME; SHOW TABLES;" 2>/dev/null | grep -q "Users"; then
            pass "Database connesso e schema presente"
        else
            warn "Database non accessibile o schema non importato - esegui: mysql -u $DB_USER -p < database.sql"
        fi
    else
        warn "mysql client non trovato"
    fi
fi

echo ""
echo "🔟 Verificando server PHP..."
echo ""

if netstat -tuln 2>/dev/null | grep -q ":8080 "; then
    warn "Porta 8080 già in uso"
else
    pass "Porta 8080 disponibile"
fi

echo ""
echo "========================================================"
echo "✨ Validazione completata!"
echo ""
echo "📋 Prossimi passi:"
echo ""
echo "1. Assicurati che MySQL sia in esecuzione"
echo "2. Importa il database:"
echo "   mysql -u <user> -p < database.sql"
echo ""
echo "3. Avvia il server PHP:"
echo "   php -S localhost:8080 -t public/"
echo ""
echo "4. Accedi a:"
echo "   http://localhost:8080/login.html"
echo ""
echo "5. Usa le credenziali di test:"
echo "   Admin: admin / password123"
echo "   Fornitore: acme_user / password123"
echo ""
echo "📚 Documentazione disponibile:"
echo "   - README.md - Guida rapida"
echo "   - IMPLEMENTAZIONE.md - Dettagli implementazione"
echo "   - API_REFERENCE.md - Riferimento API completo"
echo "   - DEPLOYMENT.md - Guide deployment produzione"
echo "   - CHANGELOG.md - Versioni e modifiche"
echo ""
echo "🎉 Buona fortune! 🚀"
echo ""
