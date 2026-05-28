#!/bin/bash
set -e

if [ -n "$DB_USERNAME" ] && [ "$DB_USERNAME" != "root" ]; then
    mysql --protocol=socket -uroot -p"$MYSQL_ROOT_PASSWORD" <<-EOSQL
        CREATE USER IF NOT EXISTS '$DB_USERNAME'@'%' IDENTIFIED BY '$DB_PASSWORD';
        GRANT ALL PRIVILEGES ON \`$MYSQL_DATABASE\`.* TO '$DB_USERNAME'@'%';
        FLUSH PRIVILEGES;
EOSQL
fi
