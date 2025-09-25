FROM mysql:8.0.33

ENV MYSQL_ROOT_PASSWORD="admin12"
ENV MYSQL_DATABASE="library_db"

ADD db_backup.sql /docker-entrypoint-initdb.d/db_backup.sql
EXPOSE 3306
