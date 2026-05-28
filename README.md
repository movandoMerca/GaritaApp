# GaritaApp

Aplicacion Laravel 8 preparada para correr en Docker con PHP 8.2, Nginx, MySQL 8.0 y almacenamiento de imagenes en S3 por carpeta de garita.

## Docker local

1. Copia el archivo de entorno:

```bash
cp .env.docker.example .env
```

2. Configura los valores reales de `APP_KEY`, `APP_URL`, credenciales de MySQL y credenciales S3. Si `APP_KEY` queda vacio, el contenedor genera una clave en el primer arranque.

3. Levanta todo el sistema:

```bash
docker compose up -d --build
```

La app queda disponible en `http://localhost:${APP_PORT}`. Por defecto usa `http://localhost:8080`.

## Base de datos

El servicio `mysql` importa automaticamente `database/migrations/data.sql` solo cuando el volumen `mysql_data` esta vacio. No se ejecutan migraciones Laravel automaticamente porque el esquema base depende del dump SQL.

Para reiniciar completamente la base local:

```bash
docker compose down -v
docker compose up -d --build
```

## S3 por garita

Todas las imagenes usan los discos Laravel existentes:

- `public`: brand y logo.
- `visits`: fotos de licencia, placa y visitante.

Cuando `IMAGE_STORAGE_DRIVER=s3`, ambos discos guardan en el mismo bucket usando `GARITA_FOLDER` como prefijo:

```env
IMAGE_STORAGE_DRIVER=s3
GARITA_FOLDER=garita-001
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=bucket-compartido
AWS_URL=
AWS_ENDPOINT=
```

Rutas esperadas en S3:

- `garita-001/public/<archivo>`
- `garita-001/visits/<archivo>`

Los objetos pueden permanecer privados porque la aplicacion los sirve por rutas Laravel.

## Produccion

El stack expone HTTP. Para HTTPS, usar un proxy externo como Nginx Proxy Manager, Cloudflare, ALB o un reverse proxy del servidor.

Valores recomendados en produccion:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com
APP_PORT=8080
DB_HOST=mysql
IMAGE_STORAGE_DRIVER=s3
GARITA_FOLDER=nombre-de-la-garita
```

## Verificacion

Comandos utiles:

```bash
docker compose ps
docker compose logs -f app
docker compose logs -f web
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

Healthcheck:

```bash
curl http://localhost:8080/healthz
```

Debe responder `ok`.
