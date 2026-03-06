# Changelog

All notable changes to FornitoriPezziDB will be documented in this file.

## [2.0.0] - 2026-03-02

### Added (Nuove Funzionalità)

#### Sistema di Autenticazione
- 🔐 Login/Logout con sessioni PHP e hash bcrypt
- 👤 Ruoli differenziati: `admin` e `fornitore`
- 📋 Class `App\Auth` per gestione completa autenticazione
- 🛡️ Middleware `AuthMiddleware` per verifica sessione
- 🛡️ Middleware `AdminMiddleware` per verifica ruolo admin

#### API REST Estesa
- 📝 Endpoint autenticazione (`/api/auth/login`, `/api/auth/logout`, `/api/auth/me`)
- 📋 CRUD Fornitori (`GET`, `POST`, `PUT`, `DELETE` `/api/fornitori`)
- ⚙️ CRUD Pezzi (`GET`, `POST`, `PUT`, `DELETE` `/api/pezzi`)
- 📦 CRUD Catalogo (`GET`, `POST`, `PUT`, `DELETE` `/api/catalogo`)
- 📄 Paginazione su tutti gli endpoint (20 elementi per pagina)

#### Web Dashboard - Amministratori
- 🎨 Dashboard responsiva con sidebar navigation
- 📊 Gestione completa fornitori, pezzi e catalogo
- 🔍 Ricerca in tempo reale
- 📄 Paginazione con navigazione
- 💬 Dialog dettagli per ogni risorsa
- 📈 Statistiche e badge visivi
- 🎯 Form per CRUD con validazione

#### Web Dashboard - Fornitori
- 📱 Dashboard personalizzata per fornitore
- 📊 Statistiche catalogo personale (N. pezzi, valore inventario, quantità)
- 🛒 Gestione catalogo personale (add/edit/delete)
- 📋 Visualizzazione pezzi disponibili nel sistema
- ➕ Aggiunta selettiva di pezzi al proprio catalogo
- 🔒 Limitazioni di accesso (solo catalogo proprio)

#### Modifiche al Database
- 👥 Nuova tabella `Users` per autenticazione
  - `id`, `username`, `password` (bcrypt), `email`, `role`, `active`
  - Timestamp: `created_at`, `updated_at`
  
- 🏢 Tabella `Fornitori` estesa
  - Aggiunto `user_id` (FK a Users)
  - Timestamp: `created_at`, `updated_at`
  - Fornitori senza user_id rimangono solo admin

- ⚙️ Tabella `Pezzi` estesa
  - Aggiunto `descrizione`
  - Timestamp: `created_at`, `updated_at`

- 📦 Tabella `Catalogo` estesa
  - Aggiunto `quantita` (inventario)
  - Aggiunto `note` (descrizione voce)
  - Timestamp: `created_at`, `updated_at`

#### Dati di Test
- 👤 1 utente admin
- 🏢 3 fornitori registrati (associati a utenti)
- 🏢 4 fornitori non registrati (gestiti solo da admin)
- ⚙️ 12 pezzi di test
- 📦 45 voci di catalogo

#### File Nuovo Aggiunto
- `src/Auth.php` - Logica autenticazione
- `src/Middleware/AuthMiddleware.php` - Verifica sessione
- `src/Middleware/AdminMiddleware.php` - Verifica ruolo
- `public/login.html` - Pagina login
- `public/dashboard-admin.html` - Dashboard admin
- `public/dashboard-fornitore.html` - Dashboard fornitore
- `public/styles.css` - CSS comune dashboard
- `public/dashboard-common.js` - JS comune
- `public/dashboard-admin.js` - Logica dashboard admin
- `public/dashboard-fornitore.js` - Logica dashboard fornitore
- `IMPLEMENTAZIONE.md` - Documentazione dettagliata
- `API_REFERENCE.md` - Riferimento API completo
- `quickstart.sh` - Script di avvio rapido
- `CHANGELOG.md` - Questo file

### Changed (Modifiche)

#### API Endpoints
- Tutti gli endpoint originali rimangono compatibili (GET /1-/10)
- Nuovi endpoint richiedono autenticazione
- Risposte JSON ora paginabili

#### Database
- Struttura schema completamente rivista con nuove relazioni
- Aggiunto audit trail (timestamp)
- Migrazione da no-auth a role-based access

#### Interfaccia Utente
- Da API-only a Web Dashboard completa
- Aggiunto supporto responsive design
- Aggiunto theme moderno gradient

### Security (Sicurezza)

- ✅ Hash password con bcrypt (cost 10)
- ✅ Sessioni PHP con regeneration su login
- ✅ CSRF CSRF protection tramite sessioni
- ✅ Input validation e sanitization
- ✅ XSS prevention con escaping HTML
- ✅ SQL injection prevention con prepared statements
- ✅ Authorization checks su tutte le operazioni modificanti

### Performance (Performance)

- ✅ Paginazione default 20 per ridurre carico
- ✅ Index su campi frequentemente cercati (username, email, colore, etc)
- ✅ Prepared statements per ottimizzazione DBQuery

### Backward Compatibility (Compatibilità)

- ✅ Tutti i 10 endpoint originali rimangono funzionanti
- ✅ Stesso database `FornitoriPezziDB`
- ✅ Stesse tabelle originali (Fornitori, Pezzi, Catalogo) + Users

## [1.0.0] - Database Iniziale

### Features
- 10 endpoint REST per query complesse
- Database FornitoriPezziDB con 3 tabelle
- Slim Framework 4
- Paginazione query-string
- PHPUnit tests

---

## Roadmap Futuro

- [ ] JWT Authentication (al posto di sessioni PHP)
- [ ] CORS support per frontend separato
- [ ] Rate limiting
- [ ] Audit logging
- [ ] Email notifications
- [ ] Multi-language support
- [ ] API versioning (v1, v2)
- [ ] GraphQL endpoint
- [ ] Mobile app support
- [ ] Advanced filtering/sorting
- [ ] Bulk operations
- [ ] File upload (catalogo PDF, foto pezzi)
- [ ] Export dati (Excel, PDF)
- [ ] Charts/Analytics
- [ ] User roles (oltre admin/fornitore)
- [ ] Permessi granulari
- [ ] API documentation (Swagger/OpenAPI)

---

## Supporto

Per problemi o suggerimenti, aprire un issue su GitHub.

## License

[Inserire licensa appropriata]
