# GaritaApp

Aplicacion Laravel 8 preparada para desarrollo con Docker: PHP 8.2 FPM, Nginx y MySQL 8.0.

## Arranque rapido

El archivo `.env` es opcional para el primer arranque. Si no existe, el contenedor lo crea desde `.env.docker.example` y genera `APP_KEY` si esta vacio.

```bash
docker compose up -d --build
```

La aplicacion queda disponible en `http://localhost:8080` por defecto. Para cambiar el puerto, copia el entorno y ajusta `APP_PORT` y `APP_URL`:

```bash
cp .env.docker.example .env
```

```env
APP_PORT=8081
APP_URL=http://localhost:8081
```

## Base de datos

MySQL no se expone al host. El servicio `app` espera a MySQL y ejecuta `php artisan import:data` solo cuando la base configurada no tiene tablas. Si la base ya contiene tablas, omite la importacion.

Importacion manual:

```bash
docker compose exec app php artisan import:data
```

Reset completo de una instancia:

```bash
docker compose down -v
docker compose up -d --build
```

## Multiples instancias en una VPS

Cada instancia debe vivir en una carpeta distinta. Docker Compose crea redes y volumenes separados por proyecto/carpeta, siempre que no se definan `container_name`.

Ejemplo:

```text
/srv/garita-a/.env  APP_PORT=8081  APP_URL=http://IP_VPS:8081  GARITA_FOLDER=garita-a
/srv/garita-b/.env  APP_PORT=8082  APP_URL=http://IP_VPS:8082  GARITA_FOLDER=garita-b
```

En cada carpeta:

```bash
docker compose up -d --build
```

`DB_DATABASE` puede repetirse entre carpetas porque cada instancia tiene su propio contenedor y volumen MySQL. Si varias instancias usan el mismo bucket S3, `GARITA_FOLDER` debe ser unico por instancia.

## Assets

Docker no compila assets ni ejecuta comandos npm. La aplicacion usa los archivos ya versionados en `public/`.

No ejecutes `npm run development`, `npm run watch-poll` ni `npm run production` como parte del arranque Docker si no quieres sobrescribir `public/css/app.css` o `public/js/app.js`.

Si en el futuro necesitas cambiar frontend, hazlo en una rama separada y revisa explicitamente los cambios generados en `public/` antes de desplegar.

## S3 por garita

Cuando `IMAGE_STORAGE_DRIVER=s3`, los discos `public` y `visits` guardan en el mismo bucket usando `GARITA_FOLDER` como prefijo:

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

Rutas esperadas:

```text
garita-001/public/<archivo>
garita-001/visits/<archivo>
```

## Verificacion

```bash
docker compose config
docker compose ps
docker compose logs -f app
curl http://localhost:8080/healthz
```

El healthcheck debe responder `ok`.
