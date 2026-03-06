## FornitoriPezziDB - Sistema di Gestione Fornitori, Pezzi e Cataloghi

### 📋 Implementazione Completata

Ho implementato un sistema completo di gestione fornitori, cataloghi e pezzi con differenziazione tra amministratori e fornitori. Di seguito una panoramica dettagliata delle modifiche.

---

## 🗄️ Modifiche al Database

### Nuove Tabelle

#### `Users` - Gestione Autenticazione
```sql
CREATE TABLE Users (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  username   VARCHAR(50) NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL,  -- hash bcrypt
  email      VARCHAR(100) NOT NULL UNIQUE,
  role       ENUM('admin', 'fornitore') NOT NULL DEFAULT 'fornitore',
  active     BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)
```

**Credenziali di Test:**
- **Admin**: `admin` / `password123`
- **Fornitore Acme**: `acme_user` / `password123`
- **Fornitore WidgetCorp**: `widget_user` / `password123`
- **Fornitore Supplies Inc**: `supplies_user` / `password123`

### Tabelle Modificate

#### `Fornitori` - Associazione con Utenti
- Aggiunto campo `user_id` (FK a Users)
- Aggiunti campi `created_at` e `updated_at`
- I fornitori non registrati rimangono gestibili solo da admin

#### `Pezzi` - Campi Aggiuntivi
- Aggiunto campo `descrizione`
- Aggiunti campi `created_at` e `updated_at`

#### `Catalogo` - Gestione Inventario
- Aggiunto campo `quantita` (quantità disponibile)
- Aggiunto campo `note` (note/descrizione voce catalogo)
- Aggiunti campi `created_at` e `updated_at`

---

## 🔐 Sistema di Autenticazione

### Nuove Classi

#### `src/Auth.php`
Gestisce:
- **Autenticazione**: verifica credenziali con hash bcrypt
- **Sessioni**: creazione, gestione e distruzione
- **Autorizzazione**: verifica ruoli (admin/fornitore)
- **Registrazione**: creazione nuovi utenti
- **Associazione Fornitore**: ottiene il fornitore associato a un utente

```php
// Utilizzo
Auth::authenticate($username, $password);  // Verifica credenziali
Auth::login($user);                         // Crea sessione
Auth::check();                              // Verifica se autenticato
Auth::user();                               // Ottiene dati utente
Auth::getFornitoreId();                     // Ottiene fornitore associato
Auth::isAdmin();                            // Verifica se admin
Auth::logout();                             // Distrugge sessione
```

### Middleware

#### `src/Middleware/AuthMiddleware.php`
- Verifica autenticazione utente
- Ritorna 401 se non autenticato

#### `src/Middleware/AdminMiddleware.php`
- Verifica che l'utente sia amministratore
- Ritorna 403 se non admin

---

## 🔌 Nuove API REST

### Autenticazione

#### `POST /api/auth/login`
Login utente
```bash
curl -X POST http://localhost:8080/api/auth/login \
  -d "username=admin&password=password123"
```

Risposta:
```json
{
  "message": "Login effettuato con successo",
  "user": {
    "id": 1,
    "username": "admin",
    "email": "admin@fornitoridb.com",
    "role": "admin",
    "fornitore_id": null
  }
}
```

#### `POST /api/auth/logout`
Logout utente

#### `GET /api/auth/me`
Ottiene info utente corrente
```json
{
  "authenticated": true,
  "user": {
    "id": 1,
    "username": "admin",
    "role": "admin"
  }
}
```

### Fornitori

#### `GET /api/fornitori`
Lista fornitori (paginata)
- **Querystring**: `?page=1&per_page=20`
- **Richiede**: autenticazione

#### `GET /api/fornitori/{fid}`
Dettagli fornitore con relativo catalogo
- **Richiede**: autenticazione

#### `POST /api/fornitori`
Crea nuovo fornitore
- **Richiede**: autenticazione + ruolo admin
- **Body**: `fid`, `fnome`, `indirizzo`, `user_id` (opzionale)

#### `PUT /api/fornitori/{fid}`
Aggiorna fornitore
- **Richiede**: autenticazione + ruolo admin

#### `DELETE /api/fornitori/{fid}`
Elimina fornitore
- **Richiede**: autenticazione + ruolo admin

### Pezzi

#### `GET /api/pezzi`
Lista pezzi con statistiche di utilizzo
- **Querystring**: `?page=1&per_page=20`
- **Richiede**: autenticazione

#### `GET /api/pezzi/{pid}`
Dettagli pezzo con fornitori che lo vendono

#### `POST /api/pezzi`
Crea nuovo pezzo
- **Richiede**: autenticazione + ruolo admin
- **Body**: `pid`, `pnome`, `colore`, `descrizione` (opzionale)

#### `PUT /api/pezzi/{pid}`
Aggiorna pezzo
- **Richiede**: autenticazione + ruolo admin

#### `DELETE /api/pezzi/{pid}`
Elimina pezzo
- **Richiede**: autenticazione + ruolo admin

### Catalogo

#### `GET /api/catalogo`
Lista catalogo completo (admin) o catalogo personale (fornitore)
- **Querystring**: `?page=1&per_page=20`
- **Richiede**: autenticazione
- **Comportamento**: Admin vede tutto, fornitore vede solo il suo catalogo

#### `POST /api/catalogo`
Aggiungi pezzo al catalogo
- **Richiede**: autenticazione
- **Body**: `fid`, `pid`, `costo`, `quantita`, `note`
- **Restrizioni**: Fornitore può aggiungere solo al proprio catalogo

#### `PUT /api/catalogo/{fid}/{pid}`
Aggiorna voce catalogo
- **Richiede**: autenticazione
- **Restrizioni**: Fornitore può aggiornare solo il proprio catalogo

#### `DELETE /api/catalogo/{fid}/{pid}`
Rimuovi pezzo dal catalogo
- **Richiede**: autenticazione
- **Restrizioni**: Fornitore può eliminare solo dal proprio catalogo

---

## 💻 Web Dashboard

### File Principali

| File | Descrizione |
|------|-------------|
| `public/login.html` | Pagina di login |
| `public/dashboard-admin.html` | Dashboard amministratori |
| `public/dashboard-fornitore.html` | Dashboard fornitori |
| `public/styles.css` | Stili CSS comuni |
| `public/dashboard-common.js` | Funzioni JavaScript comuni |
| `public/dashboard-admin.js` | Logica dashboard admin |
| `public/dashboard-fornitore.js` | Logica dashboard fornitore |

### Dashboard Amministratore

**URL**: `http://localhost:8080/dashboard-admin.html`

**Funzionalità**:
- ✅ Gestione completa fornitori (CRUD)
- ✅ Gestione completa pezzi (CRUD)
- ✅ Gestione completa catalogo (CRUD)
- ✅ Visualizzazione dettagli in dialog
- ✅ Ricerca e paginazione
- ✅ Statistiche di utilizzo

**Sezioni**:
1. **Fornitori**: Lista con nome, indirizzo, utente associato, numero pezzi
2. **Pezzi**: Lista con statistiche costo (min/max/media) e numero fornitori
3. **Catalogo**: Lista completa di tutte le voci con costo, quantità, note

### Dashboard Fornitore

**URL**: `http://localhost:8080/dashboard-fornitore.html`

**Funzionalità**:
- ✅ Gestione catalogo personale
- ✅ Visualizzazione catalogo con statistiche (valore inventario, quantità totale)
- ✅ Aggiunta/modifica/eliminazione pezzi dal proprio catalogo
- ✅ Visualizzazione pezzi disponibili nel sistema per aggiungere nuovi

**Sezioni**:
1. **Il Mio Catalogo**: Mostra i pezzi nel catalogo personale
   - Statistiche: N. pezzi, valore inventario, quantità totale
   - Azioni: Visualizza dettagli, modifica, rimuovi
   
2. **Pezzi Disponibili**: Mostra tutti i pezzi del sistema
   - Filtrare e selezionare pezzi per aggiungerli al catalogo
   - Badge "Nel catalogo" per pezzi già presenti

### UI/UX Features

**Dialog Dettagli**:
- ✅ Visualizzazione completa di ogni risorsa
- ✅ Dati organizzati in grid
- ✅ Sottotabelle per relazioni (es. catalogo di un fornitore)
- ✅ Timestamp creazione e aggiornamento

**Paginazione**:
- ✅ Implementata su tutte le liste (20 elementi per pagina)
- ✅ Bottoni precedente/successivo
- ✅ Indicatore pagina corrente

**Ricerca**:
- ✅ Ricerca in tempo reale su tutti i campi
- ✅ Filtra istantaneamente mentre si digita

**Form**:
- ✅ Validazione lato client e server
- ✅ Campi obbligatori marcati con *
- ✅ Conferme per azioni critiche (eliminazione)
- ✅ Messaggi di successo/errore

---

## 🔄 Flusso di Utilizzo

### Per Amministratore

1. Accedi con `admin / password123`
2. Vedi dashboard amministratore
3. Puoi:
   - Creare/modificare/eliminare fornitori
   - Creare/modificare/eliminare pezzi
   - Gestire il catalogo completo
   - Associare fornitori a utenti
   - Visualizzare dettagli di ogni risorsa in dialog

### Per Fornitore

1. Accedi con `acme_user / password123` (o altro fornitore)
2. Vedi dashboard personale
3. Puoi:
   - Visualizzare il tuo catalogo personale
   - Aggiungere pezzi al catalogo
   - Modificare costo, quantità e note dei tuoi pezzi
   - Rimuovere pezzi dal catalogo
   - Vedere tutti i pezzi disponibili nel sistema
   - NON puoi: modificare altri fornitori o il catalogo di altri

---

## 📊 Utilizzo API con curl

### Login Admin
```bash
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "username=admin&password=password123"
```

### Ottenere lista fornitori
```bash
curl http://localhost:8080/api/fornitori \
  -H "Cookie: PHPSESSID=<session_id>"
```

### Creare nuovo fornitore
```bash
curl -X POST http://localhost:8080/api/fornitori \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "Cookie: PHPSESSID=<session_id>" \
  -d "fid=F08&fnome=NuovoFornitore&indirizzo=Via Roma 1"
```

### Aggiungere pezzo al catalogo (come fornitore)
```bash
curl -X POST http://localhost:8080/api/catalogo \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "Cookie: PHPSESSID=<session_id>" \
  -d "fid=F01&pid=P01&costo=12.50&quantita=100&note=Disponibile"
```

---

## 📁 Struttura File Aggiunta

```
/workspaces/verificadigruppo/
├── src/
│   ├── Auth.php                        # Gestione autenticazione
│   └── Middleware/
│       ├── AuthMiddleware.php          # Middleware autenticazione
│       └── AdminMiddleware.php         # Middleware admin
├── public/
│   ├── login.html                      # Pagina login
│   ├── dashboard-admin.html            # Dashboard admin
│   ├── dashboard-fornitore.html        # Dashboard fornitore
│   ├── styles.css                      # Stili CSS comuni
│   ├── dashboard-common.js             # JS comuni
│   ├── dashboard-admin.js              # JS admin
│   └── dashboard-fornitore.js          # JS fornitore
└── database.sql                        # Schema aggiornato
```

---

## 🚀 Avvio Applicazione

### Prerequisiti
- PHP 7.4+
- MySQL 5.7+
- Composer (per dipendenze)

### Configurazione
```bash
# Copia il file di configurazione
cp .env.example .env

# Modifica .env con le credenziali MySQL
nano .env
```

### Importare Database
```bash
# Importa lo schema aggiornato
mysql -u root -p -e "SOURCE database.sql;"
```

### Avviare Server
```bash
# Usando PHP built-in server
php -S localhost:8080 -t public/

# Oppure usando composer
composer start  # Vedi script in composer.json
```

### Accesso
- **Login**: http://localhost:8080/login.html
- **Admin**: http://localhost:8080/dashboard-admin.html
- **Fornitore**: http://localhost:8080/dashboard-fornitore.html

---

## ✨ Caratteristiche Implementate

✅ **Autenticazione**
- Login/logout con sessioni PHP
- Password hash bcrypt
- Verifica ruoli (admin/fornitore)

✅ **API REST**
- Endpoint CRUD completi
- Middleware di autenticazione e autorizzazione
- Risposta JSON paginata
- Validazione dati

✅ **Web Dashboard**
- Interfaccia responsive
- Dark/Light design moderno
- Dialog dettagli per ogni risorsa
- Ricerca e paginazione
- Tabelle con dati relazionali

✅ **Gestione Dati**
- CRUD completo per fornitori, pezzi e catalogo
- Differenziazione accesso per ruolo
- Timestamp di creazione e aggiornamento
- Statistiche di utilizzo

✅ **Sicurezza**
- Autenticazione obbligatoria
- Autorizzazione per ruolo
- Validazione form
- Escaping HTML per XSS prevention

---

## 🔍 Endpoint Originali Mantenuti

I 10 endpoint originali rimangono intatti e funzionali:
- `/1` - Pezzi distinti nei cataloghi
- `/2` - Fornitori che forniscono tutti i pezzi
- `/3` - Fornitori per colore
- `/4` - Pezzi unici per fornitore
- `/5` - Fornitori con prezzi sopra media
- `/6` - Costo minimo per pezzo
- `/7` - Fornitori solo pezzi rossi
- `/8` - Fornitori pezzi rossi e verdi
- `/9` - Fornitori per colori
- `/10` - Pezzi in N fornitori

---

## 📝 Note Sviluppo

1. **Sessioni PHP**: Usa sessioni built-in PHP, configurabili in php.ini
2. **CORS**: Se necessario il CORS, aggiungi headers nelle API
3. **Database**: Schema supports queries relazionali complesse tramite foreign keys
4. **Paginazione**: Lato server tramite Paginator.php (20 elementi di default)
5. **Password Hash**: Tutte le password di test usano bcrypt con cost 10

---

## 🐛 Testing

Credenziali di test disponibili:

| Username | Password | Ruolo | Fornitore |
|----------|----------|-------|-----------|
| admin | password123 | admin | N/A |
| acme_user | password123 | fornitore | F01 - Acme |
| widget_user | password123 | fornitore | F02 - WidgetCorp |
| supplies_user | password123 | fornitore | F03 - Supplies Inc |

---

## 📖 Documentazione Completa

Tutte le modifiche sono state implementate rispettando i requisiti:

✅ **Fornitori registrati possono gestire il proprio catalogo**
✅ **Amministratori gestiscono tutto tramite dashboard**
✅ **Dialogs per visualizzare dettagli di ogni risorsa**
✅ **Risposte paginate (20 elementi per pagina)**
✅ **API REST completa con autenticazione**
✅ **Web dashboard responsive e intuitiva**

Buona documentazione codice e struttura modulare per manutenzione futura.
