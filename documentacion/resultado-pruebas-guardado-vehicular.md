# Resultado de verificación del guardado vehicular

Fecha: 18 de octubre de 2026 (Guatemala).

## Resultados finales

| Comprobación | Resultado |
| --- | --- |
| PHPUnit del módulo de visitas | 24 pruebas, 342 verificaciones: aprobadas |
| Fallos HTTP en Edge | 20 escenarios: aprobados |
| Fallos HTTP en Chrome | 20 escenarios: aprobados |
| Recorrido completo en Edge | 30 registros con detalle, fotos, listado y egreso: aprobados |
| Recorrido completo en Chrome | 30 registros con detalle, fotos, listado y egreso: aprobados |
| Registros consecutivos | 20 ciclos completos por navegador: aprobados |
| Totales consultados directamente en MySQL | 60 visitas, 60 egresos y 60 bitácoras de ingreso |
| Fotografías | 136 archivos; todos referenciados, sin faltantes ni huérfanos |
| Errores JavaScript no capturados en páginas de visitas | 0 en ambos navegadores |

Los 30 recorridos por navegador incluyen ocho configuraciones de fotografías,
20 ciclos consecutivos, un guardado confirmado cuya respuesta se pierde y un
caso de solicitudes simultáneas seguido de doble envío desde el formulario.
Los reintentos devolvieron el mismo ID, conservando las fotos y una sola bitácora.

## Casos comprobados

- Sesión y guardado detenidos antes de las cabeceras y durante el cuerpo HTTP.
- Renovación CSRF detenida y segundo POST interrumpido después de un error 419.
- Presupuesto total de 60 segundos compartido entre sesión y ambos POST, con
  límite de 10 segundos por comprobación de sesión.
- Sesión vencida, cambio de cuenta, JSON incompleto y errores 401, 403, 409,
  413, 419, 422 y 503; distinción entre fallo confirmado y resultado incierto.
- Almacenamiento del borrador bloqueado/cuota agotada, recarga de envíos
  pendientes y conservación del nombre manual antes de confirmarlo.
- Ingreso manual y escáner, repartidor, ocho configuraciones de fotografías,
  consulta de imágenes reales, listado y egreso desde los controles de la página.
- Error de almacenamiento, rollback de visita/bitácora/fotos, columna UUID
  ausente y confirmación de COMMIT perdida, incluso cuando tampoco puede
  comprobarse inmediatamente su resultado.
- Cliente real del SDK S3 con transporte simulado: conexión de 3 segundos,
  operación de 10 segundos y un solo intento de subida ante un fallo de conexión.

Las pruebas de interrupción usan un servidor HTTP local que realmente deja la
respuesta abierta o destruye la conexión. Los relojes virtuales de Playwright
avanzan los límites de espera; los registros, transacciones, fotos y egresos se
verifican contra Laravel, PHP-FPM, Nginx y MySQL reales en Docker.

## Docker local normal

Se respaldó su base antes de actualizar el contenedor. Quedaron aplicadas las
migraciones pendientes y se comprobó directamente el índice único
`visitas_submission_id_unique`. La app local continuó activa con healthcheck
saludable. Se verificaron `post_max_size=20M`, conexión S3 de 3 segundos,
operación de 10 segundos y cero reintentos internos. En la imagen actualizada
se comprobó el límite PHP-FPM de 70 segundos; Nginx configura 75 segundos.

## Alcance y reproducción

Solo se modificó el guardado vehicular y su soporte de despliegue/pruebas.
Las identidades y cámaras utilizadas son sintéticas. La instancia de pruebas
usa su propio archivo de entorno, cookie de sesión, base y volúmenes; se
verificó que su `.env` corresponde al archivo sintético. No utiliza el bucket
S3 real. Los comandos reproducibles están en el README.

Producción en CloudPanel, sus credenciales/permisos S3 y las cámaras físicas
no se comprobaron ni modificaron. Seguir la
[guía de despliegue y comprobación en CloudPanel](despliegue-cloudpanel.md).
Los resultados verifican estos escenarios locales; no constituyen una garantía
absoluta frente a fallos de infraestructura externos.

## Verificación adicional: cámara compartida

Se añadió `tests/browser/vehicle-shared-camera.cjs` para comprobar en Edge y
Chrome una cámara seleccionada en los tres roles por ID o por nombre, la
reutilización de la única cámara disponible ante IDs antiguos, dos cámaras con
una asignación compartida, tres cámaras distintas y un fallo de apertura.
Las vistas con el mismo dispositivo comparten el mismo `MediaStream`, se abre
una sola transmisión por dispositivo después de comprobar permisos y se detiene
una vez por salida de la página. Las pruebas usan cámaras sintéticas y guardan
las tres fotografías en la base/almacenamiento local desechable.

Resultado: 12 escenarios aprobados (seis por navegador), diez visitas y diez
bitácoras de ingreso, con sus 30 fotografías verificadas y sin errores JavaScript
no capturados en las páginas de visitas. Los casos de apertura fallida no crearon
visitas. Se retiró únicamente la instancia temporal de pruebas al terminar.

## Corrección de datos de casa: pendiente de prueba en navegador

El video aportado muestra No. Casa y Dirección vacíos al elegir Acceso
Telefónico. El usuario confirmó que Placa y Cono funcionan. Se encontró que
Select2 emite un cambio de jQuery y la página escuchaba únicamente eventos
nativos. Se cambió esa suscripción, conservando compatibilidad con eventos
nativos y sin duplicar consultas.

La consulta ahora tiene un límite de diez segundos incluida la lectura de la
respuesta, descarta respuestas de selecciones anteriores y limpia datos de
la casa anterior inmediatamente. No requiere migraciones.

Se prepararon dos casas ficticias y la regresión
`tests/browser/vehicle-resident-selection.cjs`, que pulsa Select2 y comprueba
cambio, borrador, respuestas fuera de orden, error recuperable y residente del
registro guardado. La sintaxis PHP de la preparación pasó la revisión local.
La ejecución en navegador no se completó: la revisión automática de permisos
agotó su tiempo y después falló por falta de capacidad. Los intentos rechazados
no se ejecutaron. No se atribuyen a esta corrección los resultados históricos
de Edge y Chrome descritos arriba. La instancia Docker aislada preparada sigue
pendiente de retirada al disponer de ejecución Docker autorizada.

No se ha publicado ni verificado esta corrección en CloudPanel.
