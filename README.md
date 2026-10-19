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

Después de esa comprobación, Docker ejecuta `php artisan migrate --force` en cada
arranque para aplicar únicamente las migraciones pendientes.

Importacion manual:

```bash
docker compose exec app php artisan import:data
docker compose exec app php artisan migrate --force
```

En una instalación existente con Laragon, desde la carpeta del proyecto:

```bash
php artisan migrate
```

Ejecute las migraciones antes de desplegar la página de registro vehicular y su
JavaScript actualizado; el formulario necesita la nueva columna para impedir
visitas duplicadas al reintentar.

Este comando aplica las migraciones pendientes, agrega el tipo de visita y la
marca de repartidor, y agrega una clave única opcional a cada envío vehicular
sin modificar las visitas existentes. `import:data` recrea tablas: se utiliza
solamente para la instalación inicial, nunca para actualizar una base con datos.

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

## Visitas peatonales y repartidores

En **Visita peatonal**, abra la cámara dentro de la página (también admite
DroidCam en PC) o tome la foto con el teléfono/tablet. Ajuste y gire el DPI si hace falta; luego
revise nombres, apellidos, CUI y nacimiento, seleccione al residente por nombre
o código de casa y guarde. Puede repetir la captura o completar los datos
manualmente si la lectura falla. Se conserva la foto del DPI.

La vista en vivo requiere HTTPS o localhost. Si se abre por HTTP mediante una
IP local, use **Tomar foto del DPI**; el navegador abrirá la cámara nativa o el
selector de imágenes y el OCR seguirá ejecutándose en esta página. Tesseract.js
7.0.0, el modelo de español y Cropper.js 1.6.2 están incluidos en
`public/vendor`: no se envía el documento a servicios externos ni se requiere
recompilar assets.

La captura en vivo toma cinco fotogramas y elige el más nítido. Se muestra la
resolución real de la cámara y del DPI: si DroidCam entrega una imagen pequeña,
aumente su resolución y acerque la tarjeta al marco. El OCR conserva la imagen
sin la compresión del archivo de guardado, localiza las secciones por sus
rótulos y compara lecturas por campo. Hace hasta nueve pasadas, con un límite
de 30 segundos después de cargar el motor.

**Reintentar lectura** usa la misma foto y respeta los campos editados a mano.
**Ajustar encuadre** permite recortar, girar y enderezar en pasos de un grado.
Una foto nueva limpia los datos del DPI anterior y conserva el residente.
Los campos dudosos o pendientes se señalan en el formulario; una lectura
parcial no se presenta como completa y permite corregir los datos antes de
guardar.

Para generar PDF con fotografías PNG, PHP debe tener GD habilitado (Docker ya
lo incluye). En Laragon configure `upload_max_filesize=8M` y `post_max_size=20M`
en `php.ini` y reinicie PHP; Docker incluye esos límites. El disco `visits`
conserva la configuración local/S3 existente.

En el ingreso vehicular, **Es repartidor** identifica ese ingreso. Las visitas
activas y los reportes por fecha y residente permiten filtrar tipo de visita y
repartidor; los filtros se conservan al exportar Excel y PDF.

## Pruebas del módulo de visitas

La suite MySQL importa el esquema inicial en una base **descartable** llamada
`garita_visits_test`. No ejecutarla contra una base con datos que se quieran
conservar. El nombre de base está fijado y verificado por la suite.

Ejemplo de instancia aislada (puerto local 13316):

```bash
docker run -d --rm --name garita-visits-test -e MYSQL_ALLOW_EMPTY_PASSWORD=yes -e MYSQL_DATABASE=garita_visits_test -p 127.0.0.1:13316:3306 --tmpfs /var/lib/mysql mysql:8.0
php vendor/bin/phpunit -c phpunit.visits.xml
node tests/js/dpi-parser.test.cjs
docker stop garita-visits-test
```

Espere a que MySQL esté listo antes de ejecutar PHPUnit. Requiere PHP con
`pdo_mysql`, GD y las dependencias de Composer. `DB_HOST`, `DB_PORT`,
`DB_USERNAME` y `DB_PASSWORD` se pueden proporcionar como variables de entorno
para conectar un contenedor PHP a esa misma base temporal.

La prueba de navegador `tests/browser/pedestrian-visit.cjs` requiere
`playwright-core` y Chromium instalado. Puede seleccionar WebKit con
`VISITS_BROWSER=webkit` para probar la captura nativa, el recorte, el OCR local y
los tamaños de teléfono/tablet. Después de la suite MySQL, sirva la
aplicación localmente con `APP_ENV=testing`, `DB_DATABASE=garita_visits_test`,
`DB_HOST=127.0.0.1`, `DB_PORT=13316`, `DB_USERNAME=root`, `DB_PASSWORD=` y
`IMAGE_STORAGE_DRIVER=local`. Use un servidor separado del de desarrollo, por
ejemplo `php -S 127.0.0.1:18880 -t public server.php`, y ejecute:

```bash
node tests/browser/pedestrian-visit.cjs
```

`VISITS_BASE_URL` permite cambiar la URL local; `PLAYWRIGHT_MODULE` permite
señalar una instalación externa de Playwright. La prueba utiliza una cámara
simulada con un documento de prueba, ejecuta el OCR real y crea una visita en la
base temporal. También prueba correcciones, captura repetida, reintento de
guardado, recuperación de sesión y respuesta vehicular perdida después de que
el servidor confirmó el registro; comprueba que el reintento crea una sola visita.
Para usar los navegadores instalados, defina `VISITS_BROWSER=chrome` o
`VISITS_BROWSER=edge`; `chromium` es la opción predeterminada y `webkit` conserva
las pruebas de captura nativa y diseño adaptable.

La prueba independiente de OCR no requiere Laravel ni MySQL:

```bash
node tests/browser/dpi-ocr.cjs
```

Requiere `playwright-core` y Chromium. `PLAYWRIGHT_MODULE` permite usar una
instalación externa de Playwright y `CHROMIUM_PATH` un ejecutable específico.
La suite sirve archivos locales temporalmente, ejecuta Tesseract real con
documentos sintéticos de formatos antiguo y bilingüe, menor resolución,
desenfoque y desplazamientos. También verifica selección de fotogramas,
límites de tiempo/pasadas, correcciones manuales, recorte y el contrato de
guardado mediante respuestas simuladas. No escribe visitas en una base de
datos y bloquea las solicitudes a servicios externos.

Para validar DroidCam físicamente, realice 20 capturas del mismo DPI con
pequeñas variaciones de distancia y posición, buena luz y enfoque. Anote la
resolución y los aciertos antes de corregir cada campo en
`documentacion/validacion_lectura_dpi.csv` (`1` correcto, `0` incorrecto).
`obligatorios_correctos` vale `1` solo si primer nombre, primer apellido,
CUI y nacimiento son correctos. El objetivo es al menos 18 de 20 capturas;
la tasa por campo es su suma dividida entre 20. Esta medición requiere el
equipo con DroidCam y es independiente de los resultados sintéticos.

## Guardado vehicular y CloudPanel

El guardado vehicular limita el intento completo (sesión, POST, lectura de la
respuesta y una renovación CSRF) a 60 segundos. Cada comprobación de sesión
admite 10 segundos. Las respuestas incompletas y las conexiones perdidas dejan
el mismo envío pendiente de verificación, conservando su UUID y sus fotos.
No se elimina una foto si la confirmación de la transacción es incierta.

Para producción sin Docker, seguir [la guía de CloudPanel](documentacion/despliegue-cloudpanel.md).
El disco S3 de visitas usa conexión máxima de 3 segundos, operación máxima de
10 segundos y cero reintentos internos. PHP y Nginx deben admitir 20 MB por POST.

### Pruebas vehiculares aisladas

Este Compose es **independiente**: no combinar con `docker-compose.yml`.
No lee el `.env` de desarrollo ni accede al S3 real. Usa MySQL temporal,
volúmenes propios y un controlador de pruebas montado únicamente dentro de
estos contenedores. Los datos de identidad y las fotografías son sintéticos.

```bash
docker compose -p garita-vehicle-test -f docker-compose.visits-test.yml up -d --build --wait
docker compose -p garita-vehicle-test -f docker-compose.visits-test.yml exec -u www-data -T app php vendor/bin/phpunit -c phpunit.visits.xml --do-not-cache-result
docker compose -p garita-vehicle-test -f docker-compose.visits-test.yml exec -u www-data -T app php tests/docker/prepare-visits.php
VISITS_BROWSER=edge node tests/browser/vehicle-visit.cjs
VISITS_BROWSER=chrome node tests/browser/vehicle-visit.cjs
```

Requiere `playwright-core` y Edge/Chrome instalados. `PLAYWRIGHT_MODULE` admite
una instalación externa de Playwright. Las pruebas rechazan un destino distinto
de localhost:18880 y comprueban la base `garita_visits_test` y almacenamiento
local antes de crear visitas. No ejecutar `prepare-visits.php` fuera de esta
instancia: reinicia exclusivamente sus datos descartables.

La suite reproduce respuestas HTTP realmente truncadas o detenidas, antes y
después de las cabeceras, con relojes virtuales del navegador para verificar los
límites de 10/60 segundos. También prueba sesión, CSRF, errores HTTP, cuota del
borrador, recarga y resultado incierto. Contra Laravel comprueba las ocho
configuraciones de fotos, 20 ciclos completos consecutivos por navegador,
respuesta perdida después del commit y envíos simultáneos/doble clic.

Para detener y retirar solo esta instancia y sus volúmenes descartables:

```bash
docker compose -p garita-vehicle-test -f docker-compose.visits-test.yml down -v
```

### Una cámara para las tres fotos vehiculares

Se puede configurar la misma cámara para licencia, placa y visitante. La página
comparte una sola transmisión entre las vistas y la libera una sola vez al salir.
Si una cámara configurada ya no está disponible, reutiliza una disponible cuando
no hay suficientes dispositivos distintos. Al capturar las tres fotos juntas con
una cámara compartida, las tres corresponden al encuadre de ese momento.

La prueba específica verifica una cámara por ID o nombre, cámaras configuradas
no disponibles, dos cámaras con una asignación compartida, tres cámaras distintas
y fallo de apertura. Ejecuta Chrome y Edge contra la instancia aislada anterior:

```bash
node tests/browser/vehicle-shared-camera.cjs
```

### Datos de la casa al elegir acceso telefónico

El formulario escucha el cambio de Select2 y actualiza No. Casa y Dirección.
Descarta respuestas de una selección anterior y muestra un error recuperable
si no puede consultar la casa. Con la instancia aislada preparada, ejecutar:

```bash
node tests/browser/vehicle-resident-selection.cjs
```

La regresión pulsa el selector real en Edge y Chrome, cambia de casa, recupera
el borrador, simula respuestas fuera de orden y errores, y guarda con el
residente elegido. Esta prueba nueva está pendiente de ejecución.
