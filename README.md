# verificaasorpresa

REST API con **Slim Framework 4** sul database `FornitoriPezziDB` (tabelle `Fornitori`, `Pezzi`, `Catalogo`).

---

## Struttura del progetto

```
verificaasorpresa/
├── public/
│   └── index.php          # entry-point Slim (10 endpoint)
├── src/
│   ├── Database.php       # singleton PDO
│   └── Paginator.php      # paginazione query-string
├── tests/
│   └── QueryTest.php      # PHPUnit (SQLite in-memory)
├── database.sql           # dump MySQL completo
├── composer.json
├── phpunit.xml
├── .env.example
└── .gitignore
```

---

## Installazione

```bash
git clone https://github.com/<tuo-username>/verificaasorpresa.git
cd verificaasorpresa

# dipendenze
composer install

# configura DB
cp .env.example .env
# modifica .env con le tue credenziali MySQL

# importa il dump
mysql -u root -p < database.sql

# avvia il server di sviluppo
composer start        # http://localhost:8080
```

---

## I 10 Endpoint

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
