sudo service apache2 start
sudo service mariadb start
sudo ln -sfn /workspaces/verificadigruppo /var/www/html/verificadigruppo

echo "API disponibile su:"
echo "http://localhost/verificadigruppo/public/index.php"
echo "Esempio endpoint 1:"
echo "http://localhost/verificadigruppo/public/index.php/1"

echo "https://reimagined-adventure-7vw5rp9jq5q7fr97p-80.app.github.dev/phpmyadmin/index.php?route=/import"