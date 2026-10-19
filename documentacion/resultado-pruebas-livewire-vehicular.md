# Registro vehicular con Livewire: resultado de verificación

Fecha: 18 de octubre de 2026 (Guatemala).

## Cambio entregado

- Laravel continúa en 8.83.27. Composer agregó únicamente Livewire 2.12.8 y
  registró la dependencia en el lock.
- `/visit/create` monta `App\Http\Livewire\VehicleVisit`; los recursos Livewire
  se cargan únicamente en esa pantalla. Se conservaron los controles y el diseño.
- El formulario ejecuta la acción `VehicleVisit::save` mediante el protocolo
  Livewire, sin enviar el guardado a `/visit/save`. El servicio
  `VehicleVisitRegistration` separa validación, almacenamiento de fotografías,
  creación de la visita y recuperación del resultado de una transacción fallida.
- El endpoint anterior conserva su contrato y utiliza el mismo servicio. No se
  agregaron migraciones ni se convirtieron el registro peatonal, detalle, egreso,
  listados o reportes a Livewire.
- Select2 mantiene su buscador y tema. La consulta del residente ahora es una
  acción del componente; la cancelación de consultas anteriores y la protección
  de ediciones locales evitan aplicar datos desactualizados.
- Una licencia válida dispara las fotos habilitadas automáticamente. La captura
  espera hasta diez segundos por las cámaras; un cambio de visitante o ingreso
  manual cancela la captura pendiente. El botón permite repetir las fotos.
- Las imágenes se envían como argumentos de la acción, nunca como propiedades
  públicas serializadas del componente. Se conserva el borrador por usuario y
  pestaña, incluido el UUID y el envío congelado de un resultado incierto.

## Resultados finales

| Verificación | Resultado |
| --- | --- |
| PHPUnit del módulo, servicio y componente | 30 pruebas, 513 verificaciones: aprobadas |
| Interrupciones y errores HTTP con el runtime real de Livewire | 20 escenarios en Edge y 20 en Chrome: aprobados |
| Registros completos en Edge | 30 visitas con detalle, fotos, listado y egreso: aprobadas |
| Registros completos en Chrome | 30 visitas con detalle, fotos, listado y egreso: aprobadas |
| Select2 real, recuperación y respuestas tardías | Seis grupos de comprobaciones por navegador: aprobados |
| Cámaras compartidas, distintas, inicio lento y apertura fallida | Siete escenarios por navegador: aprobados |
| Escaneo inválido, cancelación pendiente, ingreso manual y repetición de fotos | Aprobados en ambos navegadores |
| Estado agregado después de las suites de navegador | 74 visitas, 74 bitácoras de ingreso, 172 fotos |
| Fotos faltantes o huérfanas después de las suites | Ninguna; la lista de archivos coincide con las referencias en las visitas |
| Errores JavaScript no capturados en páginas de visitas | Ninguno en los recorridos aprobados |

Las suites completas incluyen las ocho combinaciones de fotografías, ingreso
manual y escáner, repartidor, veinte registros consecutivos por navegador, una
respuesta perdida después del COMMIT, recuperación tras recargar y solicitudes
simultáneas/doble envío. Los reintentos conservan un solo registro y bitácora.

Los fallos HTTP incluyen espera antes de cabeceras y durante el cuerpo, sesión
vencida, cambio de usuario, respuesta incompleta, renovación CSRF y estados
401/403/409/413/419/422/503. Los límites de diez y sesenta segundos se verificaron
con el reloj virtual de Playwright y un servidor HTTP que deja respuestas abiertas
o interrumpe realmente la conexión. La fixture usa el JavaScript del paquete
Livewire instalado; las pruebas completas utilizan además el endpoint Laravel.

PHPUnit comprobó validación del servidor, configuración vigente de fotos,
residentes activos, separación de usuarios, inmutabilidad del propietario del
componente y ausencia de imágenes en su estado serializado. Las regresiones del
servicio comprueban fallos de almacenamiento, rollback y pérdida de confirmación
del COMMIT, incluidos resultados que inicialmente no pueden verificarse.

## Comparación visual

Se capturó el formulario antes y después a 1366×1000 y en una ventana móvil de
390×844 (captura completa de 390×1027). El formulario vacío y el buscador abierto
coinciden píxel a píxel. Los estados escaneados solo difieren en los segundos de
la hora de ingreso: 74 píxeles en escritorio y 69 en móvil. Fuera de ese texto no
se encontraron diferencias gráficas.

Las pruebas de render verificaron además que las transmisiones no se abren otra
vez, que las fotos siguen visibles y que placa, cono, repartidor, residente y UUID
del borrador permanecen intactos.

## Entorno y reproducción

Se utilizó exclusivamente `docker-compose.visits-test.yml`, con MySQL
`garita_visits_test`, cookie, clave y volúmenes propios y almacenamiento local.
Las identidades y las cámaras son sintéticas. Los comandos y su orden están en
el README; ejecutar las suites de navegador secuencialmente.

La app Docker local recibió también la dependencia y su descubrimiento de
paquetes para que pueda cargar el código compartido. Se comprobaron las rutas
Livewire; esa instalación no ejecutó migraciones ni modificó sus datos.

No se desplegó en CloudPanel ni se comprobaron cámaras físicas o el bucket S3
real. Los resultados anteriores corresponden al entorno local aislado.
