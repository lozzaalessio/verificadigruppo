# API Reference - FornitoriPezziDB

## Quick Reference

### Base URL
```
http://localhost:8080
```

All endpoints return JSON and require authentication (except login).

---

## 🔐 Authentication Endpoints

### Login
```
POST /api/auth/login
Content-Type: application/x-www-form-urlencoded

username=admin&password=password123
```

**Response (200):**
```json
{
  "message": "Login effettuato con successo",
  "user": {
    "id": 1,
    "username": "admin",
    "email": "admin@fornitoridb.com",
    "role": "admin"
  }
}
```

### Logout
```
POST /api/auth/logout
```

**Response (200):**
```json
{
  "message": "Logout effettuato con successo"
}
```

### Get Current User
```
GET /api/auth/me
```

**Response (200 - Authenticated):**
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

**Response (200 - Not Authenticated):**
```json
{
  "authenticated": false
}
```

---

## 🏢 Fornitori Endpoints

### List Suppliers (Paginated)
```
GET /api/fornitori?page=1&per_page=20&search=acme
```

**Response (200):**
```json
{
  "data": [
    {
      "fid": "F01",
      "fnome": "Acme",
      "indirizzo": "Via Roma 1, Milano",
      "user_id": 2,
      "username": "acme_user",
      "email": "acme@example.com",
      "num_pezzi": 12,
      "created_at": "2026-03-02 10:30:45",
      "updated_at": "2026-03-02 10:30:45"
    }
  ],
  "meta": {
    "total": 7,
    "per_page": 20,
    "current_page": 1,
    "last_page": 1
  }
}
```

### Get Supplier Details
```
GET /api/fornitori/{fid}
```

**Response (200):**
```json
{
  "data": {
    "fid": "F01",
    "fnome": "Acme",
    "indirizzo": "Via Roma 1, Milano",
    "user_id": 2,
    "username": "acme_user",
    "email": "acme@example.com",
    "created_at": "2026-03-02 10:30:45",
    "updated_at": "2026-03-02 10:30:45",
    "catalogo": [
      {
        "pid": "P01",
        "pnome": "Bullone",
        "colore": "rosso",
        "descrizione": "Bullone M8x20mm in acciaio",
        "costo": "10.50",
        "quantita": 100,
        "note": "Disponibile in magazzino"
      }
    ]
  }
}
```

### Create Supplier (Admin Only)
```
POST /api/fornitori
Content-Type: application/x-www-form-urlencoded

fid=F08&fnome=NuovoFornitore&indirizzo=Via Torino 5&user_id=5
```

You can also create and associate a new supplier user in one request:

```
fid=F08&fnome=NuovoFornitore&register_user=true&username=f08_user&email=f08@example.com&password=password123
```

**Response (201):**
```json
{
  "message": "Fornitore creato con successo",
  "data": {
    "fid": "F08",
    "fnome": "NuovoFornitore",
    "indirizzo": "Via Torino 5",
    "user_id": 5
  }
}
```

### Update Supplier (Admin Only)
```
PUT /api/fornitori/{fid}
Content-Type: application/x-www-form-urlencoded

fnome=Acme Updated&indirizzo=Via Roma 2
```

### Delete Supplier (Admin Only)
```
DELETE /api/fornitori/{fid}
```

---

## ⚙️ Pezzi Endpoints

### List Parts (Paginated)
```
GET /api/pezzi?page=1&per_page=20&search=rosso
```

**Response (200):**
```json
{
  "data": [
    {
      "pid": "P01",
      "pnome": "Bullone",
      "colore": "rosso",
      "descrizione": "Bullone M8x20mm in acciaio",
      "num_fornitori": 7,
      "costo_minimo": "10.50",
      "costo_massimo": "11.00",
      "costo_medio": "10.71",
      "created_at": "2026-03-02 10:30:45",
      "updated_at": "2026-03-02 10:30:45"
    }
  ],
  "meta": {
    "total": 12,
    "per_page": 20,
    "current_page": 1,
    "last_page": 1
  }
}
```

### Get Part Details
```
GET /api/pezzi/{pid}
```

**Response (200):**
```json
{
  "data": {
    "pid": "P01",
    "pnome": "Bullone",
    "colore": "rosso",
    "descrizione": "Bullone M8x20mm in acciaio",
    "created_at": "2026-03-02 10:30:45",
    "updated_at": "2026-03-02 10:30:45",
    "fornitori": [
      {
        "fid": "F01",
        "fnome": "Acme",
        "indirizzo": "Via Roma 1, Milano",
        "costo": "10.50",
        "quantita": 100,
        "note": "Disponibile in magazzino"
      }
    ]
  }
}
```

### Create Part (Admin Only)
```
POST /api/pezzi
Content-Type: application/x-www-form-urlencoded

pid=P13&pnome=Nuova Vite&colore=blu&descrizione=Vite M5x25mm
```

### Update Part (Admin Only)
```
PUT /api/pezzi/{pid}
Content-Type: application/x-www-form-urlencoded

pnome=Bullone Aggiornato&colore=rosso
```

### Delete Part (Admin Only)
```
DELETE /api/pezzi/{pid}
```

---

## 📦 Catalogo Endpoints

### List Catalog
```
GET /api/catalogo?page=1&per_page=20&search=bullone
```

**For Admin:** Shows complete catalog
**For Fornitore:** Shows only their catalog

**Response (200):**
```json
{
  "data": [
    {
      "fid": "F01",
      "pid": "P01",
      "costo": "10.50",
      "quantita": 100,
      "note": "Disponibile in magazzino",
      "fnome": "Acme",
      "indirizzo": "Via Roma 1, Milano",
      "pnome": "Bullone",
      "colore": "rosso",
      "descrizione": "Bullone M8x20mm in acciaio",
      "created_at": "2026-03-02 10:30:45",
      "updated_at": "2026-03-02 10:30:45"
    }
  ],
  "meta": {
    "total": 45,
    "per_page": 20,
    "current_page": 1,
    "last_page": 3
  }
}
```

### Get Catalog Entry Details
```
GET /api/catalogo/{fid}/{pid}
```

### Add Part to Catalog
```
POST /api/catalogo
Content-Type: application/x-www-form-urlencoded

fid=F01&pid=P02&costo=5.50&quantita=250&note=Pronta consegna
```

**Response (201):**
```json
{
  "message": "Pezzo aggiunto al catalogo con successo",
  "data": {
    "fid": "F01",
    "pid": "P02",
    "costo": "5.50",
    "quantita": 250,
    "note": "Pronta consegna"
  }
}
```

**Restrictions:**
- Fornitore can only add to their own catalog
- Admin can add to any catalog

### Update Catalog Entry
```
PUT /api/catalogo/{fid}/{pid}
Content-Type: application/x-www-form-urlencoded

costo=5.75&quantita=200&note=Prezzo aggiornato
```

### Remove from Catalog
```
DELETE /api/catalogo/{fid}/{pid}
```

---

## 📊 Original Query Endpoints (10)

### 1. Distinct Parts in Catalog
```
GET /1?page=1&per_page=20
```

### 2. Suppliers with All Parts
```
GET /2
```

### 3. Suppliers by Color
```
GET /3?colore=rosso&page=1&per_page=20
```

### 4. Unique Parts per Supplier
```
GET /4?fnome=Acme
```

### 5. Suppliers Above Average Price
```
GET /5?page=1&per_page=20
```

### 6. Minimum Cost per Part
```
GET /6?page=1&per_page=20
```

### 7. Suppliers with Only Red Parts
```
GET /7
```

### 8. Suppliers with Red and Green Parts
```
GET /8?page=1&per_page=20
```

### 9. Suppliers by Color(s)
```
GET /9?colori=rosso,verde&page=1&per_page=20
```

### 10. Parts in N Suppliers
```
GET /10?min_fornitori=2&page=1&per_page=20
```

---

## Error Responses

### 400 Bad Request
```json
{
  "error": "Parametri mancanti",
  "message": "fid e fnome sono obbligatori"
}
```

### 401 Unauthorized
```json
{
  "error": "Non autenticato",
  "message": "Devi effettuare il login per accedere a questa risorsa"
}
```

### 403 Forbidden
```json
{
  "error": "Accesso negato",
  "message": "Solo gli amministratori possono accedere a questa risorsa"
}
```

### 404 Not Found
```json
{
  "error": "Fornitore non trovato"
}
```

### 409 Conflict
```json
{
  "error": "Voce già esistente",
  "message": "Questo pezzo è già nel catalogo del fornitore"
}
```

---

## Examples with curl

### Login as Admin
```bash
curl -c cookies.txt \
  -d "username=admin&password=password123" \
  http://localhost:8080/api/auth/login
```

### Get Suppliers List
```bash
curl -b cookies.txt \
  "http://localhost:8080/api/fornitori?page=1&per_page=10"
```

### Create New Part
```bash
curl -b cookies.txt -X POST \
  -d "pid=P14&pnome=Nuova Parte&colore=blu" \
  http://localhost:8080/api/pezzi
```

### Add to Catalog
```bash
curl -b cookies.txt -X POST \
  -d "fid=F01&pid=P01&costo=15.99&quantita=50" \
  http://localhost:8080/api/catalogo
```

### Update Catalog Entry
```bash
curl -b cookies.txt -X PUT \
  -d "costo=16.50&quantita=75" \
  http://localhost:8080/api/catalogo/F01/P01
```

### Delete from Catalog
```bash
curl -b cookies.txt -X DELETE \
  http://localhost:8080/api/catalogo/F01/P01
```

---

## Pagination

All paginated endpoints support:
- `?page=N` - Page number (default: 1)
- `?per_page=N` - Items per page (default: 20)

Response includes:
```json
{
  "meta": {
    "total": 100,
    "per_page": 20,
    "current_page": 1,
    "last_page": 5
  }
}
```

---

## Authentication Flow

1. Call `POST /api/auth/login` with credentials
2. Server sets `PHPSESSID` cookie in response
3. Include cookie in subsequent requests
4. Session persists until `POST /api/auth/logout`

---

## Test Users

| Username | Password | Role | Supplier |
|----------|----------|------|----------|
| admin | password123 | admin | - |
| acme_user | password123 | fornitore | F01 |
| widget_user | password123 | fornitore | F02 |
| supplies_user | password123 | fornitore | F03 |

---

## Rate Limiting

No rate limiting implemented. For production, consider adding:
- Token-based auth (JWT)
- Rate limiting middleware
- CORS headers
- HTTPS enforcement

---

## Changelog

### v2.0
- Added authentication system
- Added web dashboards (admin & fornitore)
- Extended API with CRUD operations
- Added catalog management for suppliers
- Added role-based access control
- Added pagination and search
- Added dialog details view
