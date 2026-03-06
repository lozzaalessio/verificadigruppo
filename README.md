# FornitoriPezziDB

Sistema completo di gestione **Fornitori, Pezzi e Cataloghi** con **Slim Framework 4**, autenticazione utenti e web dashboard.

### ✨ Novità v2.0
- ✅ **Sistema di Autenticazione**: Login/logout con sessioni PHP
- ✅ **Dashboard Web**: Per amministratori e fornitori
- ✅ **API REST Estesa**: CRUD completo per tutte le risorse
- ✅ **Gestione Catalogo**: Fornitori possono gestire il proprio catalogo
- ✅ **Controllo Accessi**: Ruoli differenziati (admin/fornitore)
- ✅ **Dialog Interattivi**: Visualizzazione dettagli di ogni risorsa
- ✅ **Paginazione**: Su tutte le liste API

---

## 🗂️ Struttura del progetto

```
verificadigruppo/
├── public/
│   ├── index.php                    # entry-point Slim API
│   ├── login.html                   # Pagina login
│   ├── dashboard-admin.html         # Dashboard amministratori
│   ├── dashboard-fornitore.html     # Dashboard fornitori
│   ├── styles.css                   # Stili CSS comuni
│   ├── dashboard-common.js          # JS comuni
│   ├── dashboard-admin.js           # Logica dashboard admin
│   └── dashboard-fornitore.js       # Logica dashboard fornitore
├── src/
│   ├── Database.php                 # Singleton PDO
│   ├── Paginator.php                # Paginazione
│   ├── Auth.php                     # Autenticazione
│   └── Middleware/
│       ├── AuthMiddleware.php       # Verifica autenticazione
│       └── AdminMiddleware.php      # Verifica ruolo admin
├── tests/
│   └── QueryTest.php                # PHPUnit (SQLite in-memory)
├── database.sql                     # Schema MySQL completo
├── IMPLEMENTAZIONE.md               # Documentazione dettagliata
├── composer.json
├── phpunit.xml
├── .env.example
└── .gitignore
```

---

## 🚀 Avvio Rapido

```bash
# Clone e setup
git clone https://github.com/<tuo-username>/verificadigruppo.git
cd verificadigruppo
composer install

# Configura database
cp .env.example .env
# Modifica .env con credenziali MySQL

# Importa schema
mysql -u root -p < database.sql

# Avvia server (PHP 7.4+)
php -S localhost:8080 -t public/
# Oppure: composer start

# Accedi
# Login: http://localhost:8080/login.html
```

### 🔑 Credenziali di Test
- **Admin**: `admin` / `password123`
- **Fornitore Acme**: `acme_user` / `password123`
- **Fornitore Widget**: `widget_user` / `password123`
- **Fornitore Supplies**: `supplies_user` / `password123`

---

## 📚 API Endpoints

### Autenticazione
- `POST /api/auth/login` - Login utente
- `POST /api/auth/logout` - Logout
- `GET /api/auth/me` - Info utente corrente

### Fornitori (Admin: CRUD completo, Fornitore: lettura)
- `GET /api/fornitori` - Lista fornitori (paginata)
- `GET /api/fornitori/{fid}` - Dettagli fornitore
- `POST /api/fornitori` - Crea fornitore *(admin only)*
- `PUT /api/fornitori/{fid}` - Aggiorna *(admin only)*
- `DELETE /api/fornitori/{fid}` - Elimina *(admin only)*

### Pezzi (Admin: CRUD, Fornitore: lettura)
- `GET /api/pezzi` - Lista pezzi (paginata)
- `GET /api/pezzi/{pid}` - Dettagli pezzo
- `POST /api/pezzi` - Crea pezzo *(admin only)*
- `PUT /api/pezzi/{pid}` - Aggiorna *(admin only)*
- `DELETE /api/pezzi/{pid}` - Elimina *(admin only)*

### Catalogo (Admin: CRUD, Fornitore: CRUD solo proprio)
- `GET /api/catalogo` - Lista catalogo (admin: tutto, fornitore: suo catalogo)
- `POST /api/catalogo` - Aggiungi pezzo al catalogo
- `PUT /api/catalogo/{fid}/{pid}` - Aggiorna voce catalogo
- `DELETE /api/catalogo/{fid}/{pid}` - Rimuovi dal catalogo

### Query Originali (10 Endpoint)
- `GET /1` - Pezzi distinti
- `GET /2` - Fornitori completi
- `GET /3` - Fornitori per colore
- `GET /4` - Pezzi unici per fornitore
- `GET /5` - Fornitori prezzo sopra media
- `GET /6` - Costo minimo per pezzo
- `GET /7` - Fornitori solo rossi
- `GET /8` - Fornitori rossi e verdi
- `GET /9` - Fornitori per colore
- `GET /10` - Pezzi in N fornitori

Vedi [IMPLEMENTAZIONE.md](IMPLEMENTAZIONE.md) per documentazione completa.

---

## I 10 Endpoint Originali

Tutti rispondono in `application/json`.  
La paginazione si attiva con `?page=N&per_page=N` (default: pagina 1, 20 righe).

| # | Metodo | Path | Descrizione | Parametri |
|---|--------|------|-------------|-----------|
| 1 | GET | `/1` | Pezzi distinti presenti in almeno un catalogo | `page`, `per_page` |
| 2 | GET | `/2` | Fornitori che forniscono **tutti** i pezzi esistenti | — |
| 3 | GET | `/3` | Fornitori che forniscono **tutti** i pezzi di un colore | `colore` (default: `rosso`) |
| 4 | GET | `/4` | Pezzi venduti da **un solo** fornitore con quel nome | `fnome` (default: `Acme`) |
| 5 | GET | `/5` | Fornitori con ≥1 pezzo sopra la media di quel pezzo | `page`, `per_page` |
| 6 | GET | `/6` | Per ogni pezzo: il fornitore col costo minimo | `page`, `per_page` |
| 7 | GET | `/7` | Fornitori che vendono **solo** pezzi rossi | — |
| 8 | GET | `/8` | Fornitori con pezzi sia **rossi** che **verdi** | `page`, `per_page` |
| 9 | GET | `/9` | Fornitori con ≥1 pezzo rosso **o** verde | `colori` (default: `rosso,verde`), `page`, `per_page` |
| 10 | GET | `/10` | Pezzi nel catalogo di ≥ N fornitori distinti | `min_fornitori` (default: `2`), `page`, `per_page` |

### Esempi

```bash
# /1 – tutti i pezzi in catalogo, pagina 1
curl http://localhost:8080/1

# /3 – fornitori che coprono tutti i pezzi BLU
curl "http://localhost:8080/3?colore=blu"

# /4 – pezzi venduti solo da WidgetCorp
curl "http://localhost:8080/4?fnome=WidgetCorp"

# /9 – fornitori con pezzi rossi o blu
curl "http://localhost:8080/9?colori=rosso,blu"

# /10 – pezzi in catalogo di almeno 3 fornitori
curl "http://localhost:8080/10?min_fornitori=3"

# paginazione su /1
curl "http://localhost:8080/1?page=2&per_page=5"
```

### Formato risposta

```json
{
    "data": [ { "pid": "P01", "pnome": "Bullone", "colore": "rosso" } ],
    "meta": {
        "total": 12,
        "per_page": 20,
        "current_page": 1,
        "last_page": 1
    }
}
```

---

## Test

I test usano **SQLite in-memory**: nessuna dipendenza da MySQL.

```bash
composer test
```

Output atteso:

```
FornitoriPezziDB API
 ✔ Q1 pezzi nel catalogo
 ✔ Q2 fornitori tutti i pezzi
 ✔ Q3 fornitori tutti pezzi rossi
 ✔ Q4 pezzi solo acme
 ✔ Q5 fornitori sopra media
 ✔ Q6 costo min per pezzo
 ✔ Q7 solo rossi
 ✔ Q8 rossi e verdi
 ✔ Q9 rossi o verdi
 ✔ Q10 pezzi multi fornitori
 ✔ Paginator slices correctly
```

---

## Schema DB

```
Fornitori(fid PK, fnome, indirizzo)
Pezzi(pid PK, pnome, colore)
Catalogo(fid FK, pid FK, costo)  ← PK composita (fid, pid)
```
