#!/bin/bash
set -e

PORT="${PORT:-80}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_USER="${DB_USER:-ttkpro}"
DB_PASS="${DB_PASS:-ttkpro}"
DB_NAME="${DB_NAME:-ttkpro}"
MYSQL_ROOT_PASSWORD="${MYSQL_ROOT_PASSWORD:-rootpass}"
ADMIN_PASS="${ADMIN_PASS:-admin123}"

echo "[ttkpro] Ajustando Apache para porta ${PORT}..."
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s#<VirtualHost \*:.*>#<VirtualHost *:${PORT}>#" /etc/apache2/sites-enabled/000-default.conf

echo "[ttkpro] Iniciando MariaDB..."
if [ ! -d /var/lib/mysql/mysql ]; then
  mysql_install_db --user=mysql --datadir=/var/lib/mysql >/dev/null
fi

mysqld --user=mysql --datadir=/var/lib/mysql --bind-address=127.0.0.1 --skip-networking=0 &

echo "[ttkpro] Aguardando MariaDB..."
for i in $(seq 1 60); do
  if mysqladmin ping -h 127.0.0.1 --silent 2>/dev/null; then
    break
  fi
  sleep 1
done

if ! mysqladmin ping -h 127.0.0.1 --silent 2>/dev/null; then
  echo "[ttkpro] ERRO: MariaDB nao subiu"
  exit 1
fi

echo "[ttkpro] Configurando banco ${DB_NAME}..."
mysql -u root <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
CREATE USER IF NOT EXISTS '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASS}';
ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
ALTER USER '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL

TABLE_COUNT=$(mysql -N -u root -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_NAME}';")
if [ "$TABLE_COUNT" = "0" ]; then
  echo "[ttkpro] Importando database-v1.6.sql..."
  mysql -u root "${DB_NAME}" < /var/www/html/database-v1.6.sql
  echo "[ttkpro] Import concluido."
else
  echo "[ttkpro] Banco ja tem ${TABLE_COUNT} tabelas — pulando import."
fi

echo "[ttkpro] Definindo senha local do admin..."
ADMIN_PASS="$ADMIN_PASS" php -r '
$pass = getenv("ADMIN_PASS") ?: "admin123";
$hash = password_hash($pass, PASSWORD_DEFAULT);
$sql = "UPDATE subscribers SET password_hash=" . var_export($hash, true)
     . ", status=\"active\", subscription_expires_at=DATE_ADD(NOW(), INTERVAL 1 YEAR)"
     . " WHERE username=\"admin\";\n";
file_put_contents("/tmp/ttkpro_set_admin.sql", $sql);
'
mysql -u root "${DB_NAME}" < /tmp/ttkpro_set_admin.sql
echo "[ttkpro] Login local: usuario=admin"
mysql -u root "${DB_NAME}" <<'EOSQL' || true
ALTER TABLE projects MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE projects ADD PRIMARY KEY (id);
ALTER TABLE projects ADD UNIQUE KEY uniq_project_name (name);
ALTER TABLE project_images MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE project_images ADD PRIMARY KEY (id);
ALTER TABLE order_bumps MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE order_bumps ADD PRIMARY KEY (id);
ALTER TABLE reviews MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE reviews ADD PRIMARY KEY (id);
ALTER TABLE variation_groups MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE variation_groups ADD PRIMARY KEY (id);
ALTER TABLE variation_options MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE variation_options ADD PRIMARY KEY (id);
ALTER TABLE recommendations MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE recommendations ADD PRIMARY KEY (id);
ALTER TABLE recommendation_variations MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE recommendation_variations ADD PRIMARY KEY (id);
EOSQL

mkdir -p /var/www/html/payments/pending /var/www/html/payments/paid /var/www/html/exports /var/www/html/projects
chown -R www-data:www-data /var/www/html/payments /var/www/html/exports /var/www/html/projects

echo "[ttkpro] Subindo Apache na porta ${PORT}..."
exec apache2-foreground