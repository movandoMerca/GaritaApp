# Guardado vehicular: despliegue en CloudPanel

Producción usa Laravel/PHP-FPM/Nginx en CloudPanel. Docker se usa únicamente para desarrollo y pruebas. No ejecutar comandos Docker en producción.

## Antes de publicar

1. Respaldar la base de datos, el código actualmente publicado, `.env` y las configuraciones del sitio. Mantener las fotografías existentes; no cambiar `IMAGE_STORAGE_DRIVER`, el bucket ni `GARITA_FOLDER`.
2. Usar el usuario SSH del sitio y su versión de PHP. Verificar `php -v`, `php --ini`, las extensiones `pdo_mysql`, `mbstring` y `gd`, y `composer check-platform-reqs --no-dev`. El PHP de consola debe corresponder al PHP-FPM seleccionado en CloudPanel.
3. Preparar la nueva versión con las dependencias de `composer.lock`. No requiere recompilar los assets: `public/js/vehicle-visit.js` es el archivo servido y la vista añade su versión automáticamente.
4. Consultar `php artisan migrate:status`. Debe aplicarse `2026_10_16_000001_add_submission_id_to_visits`, junto con las demás migraciones pendientes, antes de volver a admitir registros.

No usar `import:data`, `migrate:fresh`, `migrate:refresh` ni el esquema SQL de pruebas en producción: recrean tablas.

## Límites del sitio

En **PHP Settings** de CloudPanel, conservar `upload_max_filesize=8M` y configurar `post_max_size=20M`. Comprobar que `memory_limit` permita recibir las tres imágenes; se recomienda `256M`. Las fotos vehiculares se envían como campos base64: `post_max_size` limita la suma, aunque ninguna sea un archivo multipart. La aplicación admite hasta 6.000.000 de caracteres por fotografía.

En el pool PHP-FPM de **este sitio**, configurar `request_terminate_timeout=70s`. Es una directiva del pool FPM, no de `php.ini` ni de `.user.ini`. No modificar pools de otros sitios. Comprobar la configuración con el ejecutable FPM correspondiente y recargar el servicio de esa versión, por ejemplo `sudo systemctl reload php8.2-fpm` para PHP 8.2. No usar `php artisan` para intentar reiniciar OPcache de FPM: la consola usa otro proceso.

En el **Vhost Editor**, ajustar las directivas existentes en su contexto correcto, sin duplicarlas:

```nginx
# En el bloque server que recibe la solicitud:
client_max_body_size 20m;

# En la ubicación PHP que contiene fastcgi_pass:
fastcgi_read_timeout 75s;

# Si el sitio incluye un proxy adicional, en su ubicación proxy_pass:
proxy_read_timeout 75s;
```

Validar Nginx antes de recargarlo. Comprobar los valores **efectivos** del vhost y del pool del sitio; `php --ini` solo confirma la consola. CloudPanel documenta [PHP Settings](https://www.cloudpanel.io/docs/v2/frontend-area/settings/) y el [Vhost Editor](https://www.cloudpanel.io/docs/v2/frontend-area/vhost/).

El navegador limita cada intento completo a 60 segundos y cada verificación de sesión a 10 segundos. Un resultado incierto puede continuar brevemente en el servidor: el reintento conserva el UUID, por lo que recupera la misma visita. El disco S3 de visitas limita cada operación a 10 segundos, conexión a 3 segundos y desactiva los reintentos internos del SDK. No altera las credenciales ni el almacenamiento configurado.

## Publicación

Desde la raíz Laravel del sitio, con el binario PHP correcto, instalar las dependencias bloqueadas y publicar la versión mediante el procedimiento habitual. Mantener el sitio en mantenimiento mientras se aplican las migraciones y se actualiza el código; no exponer el nuevo formulario antes de migrar.

```bash
set -e
php artisan down --retry=60
# Publicar aquí la versión preparada con sus dependencias de composer.lock.
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan view:cache
php artisan migrate:status
# Verificar columna/índice, configuración y recargar el PHP-FPM del sitio.
php artisan up
```

Si un paso falla, resolverlo antes de ejecutar `up`. Para comprobar el índice en la base correcta:

```sql
SHOW COLUMNS FROM visitas LIKE 'submission_id';
SHOW INDEX FROM visitas WHERE Key_name = 'visitas_submission_id_unique';
```

La columna debe ser nullable y el índice único (`Non_unique = 0`). Las visitas antiguas conservan sus datos y pueden tener UUID nulo. Si es necesario volver al código anterior, conservar esta columna compatible; no ejecutar rollback de las migraciones que conservan los datos del despliegue.

Recargar PHP-FPM para retirar el código antiguo de OPcache. En el navegador verificar que la vista carga `vehicle-visit.js?v=...` y que el archivo corresponde a la versión publicada. Evitar caché pública de páginas autenticadas y de `/visit/session`; la respuesta de sesión debe conservar `private`, `no-store` y `max-age=0`.

## Comprobación posterior

- Iniciar sesión con una cuenta operativa y registrar una visita vehicular autorizada, con las fotos requeridas. Confirmar el detalle, la visualización de las fotos y su presencia en el listado; comprobar el egreso cuando corresponda.
- Comprobar que el reintento del **mismo** envío devuelve el mismo ID y no añade otra visita o bitácora. Un nuevo formulario inicia otro UUID.
- Ante desconexión o respuesta incompleta, debe salir de “Guardando…” en 60 segundos como máximo, conservar el borrador y ofrecer **Verificar / reintentar guardado**. No abrir otro registro para resolver una visita cuyo resultado sea incierto.
- Si vence la sesión, iniciar sesión con la misma cuenta en otra pestaña y volver al formulario. No perder nombres, residente, placa, cono ni fotos.
- Revisar los registros Laravel del canal configurado y los registros PHP-FPM/Nginx del sitio. Buscar errores 413, 499, 502, 504, de S3 o `No se pudo guardar/verificar`. No copiar tokens, fotografías, credenciales ni SQL con datos personales al reporte.

Una visita puede haberse confirmado aunque su respuesta se haya perdido. En ese caso sus fotos no se borran. Si tampoco puede consultarse la base, se informa `outcome: unknown` y se conservan los archivos; reconciliar los archivos pendientes antes de una limpieza manual.

La comprobación local no certifica las credenciales, permisos S3, migraciones o configuración de producción. Esta entrega incluye la guía; no despliega ni ejecuta pruebas sobre el sitio real.
