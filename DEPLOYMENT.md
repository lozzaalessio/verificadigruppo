# Guida Deployment Produzione

## ⚠️ Considerazioni di Sicurezza

Prima di deployare in produzione, implementare:

### 1. Sessione PHP

```php
// .htaccess o apache config
php_value session.cookie_httponly 1
php_value session.cookie_secure 1
php_value session.cookie_samesite "Strict"
php_value session.gc_maxlifetime 3600
php_value session.use_strict_mode 1
```

### 2. HTTPS Obbligatoria

Implementare redirect HTTP→HTTPS:

```php
// public/index.php inizio
if ($_SERVER['HTTPS'] !== 'on') {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}
```

### 3. CORS (se frontend separato)

```php
// Aggiungere middleware
header('Access-Control-Allow-Origin: https://yourdomain.com');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
```

### 4. Rate Limiting

```php
// Implementare rate limiting middleware
$maxRequests = 100;
$timeWindow = 3600; // 1 ora
// Verificare $_SERVER['REMOTE_ADDR'] in Redis/Memcached
```

### 5. Validazione Input

```php
// Validare e sanitizzare sempre input
$username = filter_var($input, FILTER_SANITIZE_STRING);
$email = filter_var($input, FILTER_VALIDATE_EMAIL);
// etc.
```

---

## 📋 Checklist Pre-Produzione

- [ ] Database MySQL su server separato (non localhost)
- [ ] Password MySQL complessa (20+ caratteri, mix di caratteri)
- [ ] File .env con credenziali produzione
- [ ] .env NOT commitato in git (aggiungi a .gitignore)
- [ ] File di log con permessi appropriati
- [ ] HTTPS con certificato SSL/TLS valido
- [ ] PHP 7.4+ aggiornato con patch di sicurezza
- [ ] MySQL 5.7+ aggiornato
- [ ] Composer dependencies aggiornate
- [ ] PHPUnit tests passati
- [ ] SQL Injection protection verificata
- [ ] XSS protection verificata
- [ ] CSRF tokens implementati (se form tradizionali)
- [ ] Backup database automatici configurati
- [ ] Monitoring e logging configurati
- [ ] Error reporting disabilitato (display_errors = Off)
- [ ] Permessi file corretti (644 file, 755 dir)
- [ ] public_html separata dai file sensibili

---

## 🚀 Deployment Opzioni

### Option 1: Shared Hosting (cPanel/Plesk)

1. **Upload via FTP**
   ```bash
   ftp host.provider.com
   cd public_html
   put -r ./verificadigruppo/*
   ```

2. **Configura .env**
   - SSH/Terminal: `nano .env`
   - Credenziali host provider

3. **Crea database MySQL**
   - cPanel > MySQL Databases
   - Importa database.sql
   ```bash
   mysql -u user -p database < database.sql
   ```

4. **Configura file permissions**
   ```bash
   chmod 755 public
   chmod 644 public/index.php
   chmod 755 src
   ```

### Option 2: VPS (Debian/Ubuntu)

1. **Installa dipendenze**
   ```bash
   sudo apt update
   sudo apt install php7.4-cli \
       php7.4-mysql php7.4-mbstring \
       php7.4-dom mysql-server \
       composer nginx
   ```

2. **Clone repository**
   ```bash
   cd /var/www
   git clone https://github.com/user/verificadigruppo.git
   cd verificadigruppo
   composer install --no-dev --optimize-autoloader
   ```

3. **Setup Nginx**
   ```nginx
   server {
       listen 443 ssl http2;
       server_name yourdomain.com;
       
       ssl_certificate /etc/ssl/certs/cert.pem;
       ssl_certificate_key /etc/ssl/private/key.pem;
       
       root /var/www/verificadigruppo/public;
       
       location ~ \.php$ {
           fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
           fastcgi_index index.php;
           include fastcgi_params;
       }
       
       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }
       
       client_max_body_size 10M;
   }
   ```

4. **Database e .env**
   ```bash
   sudo mysql -u root -p < database.sql
   cp .env.example .env
   nano .env  # Aggiungi credenziali
   sudo chown www-data .env
   sudo chmod 600 .env
   ```

5. **Start services**
   ```bash
   sudo systemctl start php7.4-fpm
   sudo systemctl start nginx
   sudo systemctl start mysql
   ```

### Option 3: Docker

**Dockerfile:**
```dockerfile
FROM php:7.4-fpm-alpine

WORKDIR /app

RUN docker-php-ext-install pdo pdo_mysql mbstring

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install --no-dev --optimize-autoloader

RUN chmod -R 755 . && \
    chown -R www-data:www-data .

EXPOSE 9000

CMD ["php-fpm"]
```

**docker-compose.yml:**
```yaml
version: '3'
services:
  app:
    build: .
    volumes:
      - .:/app
    depends_on:
      - db
    environment:
      DB_HOST: db
      DB_NAME: FornitoriPezziDB
  
  db:
    image: mysql:5.7
    environment:
      MYSQL_ROOT_PASSWORD: ${MYSQL_PASSWORD}
      MYSQL_DATABASE: FornitoriPezziDB
    volumes:
      - ./database.sql:/docker-entrypoint-initdb.d/init.sql
      - db_data:/var/lib/mysql
    
  web:
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf
      - ./public:/app/public
    depends_on:
      - app

volumes:
  db_data:
```

### Option 4: Platform as a Service (Heroku, Render, etc)

1. **Crea Procfile**
   ```
   web: heroku-php-apache2 public/
   ```

2. **Push to Git**
   ```bash
   git push heroku main
   ```

3. **Database provisioning**
   ```bash
   heroku addons:create jawsdb:kitefin
   ```

---

## 🔧 Maintenance

### Backup Database

```bash
# Daily backup script
0 2 * * * mysqldump -u $USER -p$PASS FornitoriPezziDB | \
    gzip > /backups/db_$(date +\%Y\%m\%d).sql.gz
```

### Monitoring

```bash
# Check service status
sudo systemctl status nginx
sudo systemctl status php-fpm
sudo systemctl status mysql

# Monitor logs
tail -f /var/log/nginx/error.log
tail -f /var/log/php-fpm.log
```

### Updates

```bash
# Aggiorna dipendenze
composer update --no-dev

# Update PHP
sudo apt update && sudo apt upgrade php7.4*

# Update MySQL
sudo mysql_upgrade -u root -p
```

---

## 🔐 SSL Certificate

### Let's Encrypt (Gratuito)

```bash
sudo apt install certbot python3-certbot-nginx

sudo certbot certonly --standalone -d yourdomain.com

# Auto-renewal
sudo systemctl enable certbot.timer
```

---

## 📊 Monitoring & Logging

### Application Logs

```php
// Aggiungi logging in production
function logError($message) {
    $log_file = __DIR__ . '/../logs/error.log';
    $date = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$date] $message\n", FILE_APPEND);
}
```

### Database Monitoring

```sql
-- Monitor queries lente
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2;
```

### Server Monitoring

- **StatusCake**: https://www.statuscake.com
- **Uptime Robot**: https://uptimerobot.com
- **New Relic**: https://newrelic.com
- **Datadog**: https://www.datadoghq.com

---

## 🆘 Troubleshooting

### "Connection refused"
```bash
# Verifica MySQL
sudo systemctl status mysql
sudo mysql -u root -p

# Verifica PHP-FPM
sudo systemctl status php-fpm
```

### "Permission denied"
```bash
# Fix file permissions
sudo chown -R www-data:www-data /var/www/verificadigruppo
sudo chmod -R 755 /var/www/verificadigruppo
sudo chmod 600 .env
```

### "Out of memory"
```php
// Aumenta memory limit in production php.ini
memory_limit = 256M
max_execution_time = 30
```

### "Database not found"
```bash
# Verifica database import
mysql -u root -p -e "SHOW DATABASES;"
mysql -u root -p FornitoriPezziDB -e "SHOW TABLES;"
```

---

## 📞 Supporto

Per problemi di deployment:
1. Verificare logs del server
2. Controllare .env e credenziali
3. Testare connessione database
4. Verificare permessi file
5. Controllare firewall e porte

---

## ✅ Post-Deployment

1. Test login: http://yourdomain.com/login.html
2. Test API: `curl https://yourdomain.com/api/auth/me`
3. Monitora logs per errori
4. Verifica backup database
5. Configura email alerts
6. Documenta configurazione

---

Buon deployment! 🚀
