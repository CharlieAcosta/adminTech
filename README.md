# AdminTech
Aplicacion web interna en PHP para gestion operativa y comercial. Por la estructura actual del sistema y los modulos visibles en el panel, cubre al menos administracion de usuarios/agentes, clientes, materiales, presupuestos, obras, novedades y seguimiento de obra.

Este README deja contexto tecnico util para mantenimiento y onboarding. La version anterior era la plantilla default de GitLab y no reflejaba el estado real del proyecto.

## Stack

- PHP 8.1 sobre Apache
- MySQL 5.7
- Frontend server-rendered con PHP, jQuery, Bootstrap/AdminLTE y plugins JS locales
- Docker Compose para entorno local
- Composer presente para dependencias PHP

## Modulos funcionales detectados

- Login y panel principal
- Agentes / personal
- Novedades
- Clientes
- Seguimiento de obra / previsitas
- Materiales
- Obras
- Presupuestos
- AEO
- Jornales
- Configuracion de mails de presupuestos

## Estructura del repositorio

- `00-config/`: configuracion de entorno y conexion a base de datos.
- `01-views/`: pantallas PHP renderizadas del sistema.
- `03-controller/`: controladores y endpoints que reciben formularios y acciones.
- `04-modelo/`: consultas y operaciones de datos por modulo.
- `05-plugins/`: librerias frontend locales vendorizadas.
- `06-funciones_php/`: helpers y funciones compartidas de PHP.
- `07-funciones_js/`: helpers y acciones de frontend por modulo.
- `09-adjuntos/`: adjuntos y archivos auxiliares del sistema.
- `10-clases/`: clases PHP compartidas, por ejemplo auditoria.
- `11-migraciones_sql/`: migraciones versionadas de base de datos.
- `12-scripts_operativos_sql/`: scripts SQL manuales u operativos; revisar antes de ejecutar.
- `dist/`: assets estaticos del panel.
- `docker-local/`: entorno Docker local.
- `uploads/`: archivos subidos por usuarios o procesos.
- `log/`: respaldos, logs y archivos operativos locales.

## Rutas de archivos operativos

- PDFs emitidos de presupuesto: `uploads/presupuestos/{id_presupuesto}/emisiones/`.
- Fotos cargadas en la visita: `uploads/visitas/{YYYYMMDD}/`.
- Fotos cargadas dentro del presupuesto: `uploads/presupuestos/{id_presupuesto}/t{nro_tarea}/`.
- Adjuntos generales de pre-visita o documento fuente: `09-adjuntos/previsita/`.

Referencias de implementacion:

- `04-modelo/presupuestoDocumentosEmitidosModel.php` arma la ruta de emision como base de presupuesto + `/emisiones`.
- `04-modelo/presupuestoGeneradoModel.php` guarda fotos del presupuesto por tarea dentro de `t1`, `t2`, etc.
- `04-modelo/presupuestoGeneradoModel.php` tambien sanea el HTML permitido del detalle de tarea antes de persistirlo; hoy solo admite formato basico como negrita, cursiva, subrayado y listas.
- `04-modelo/presupuestoIntervencionesModel.php` usa el ultimo `estado_resultante` del historial comercial como fallback del estado visible del modal cuando el presupuesto todavia no tiene persistido un estado comercial activo en sus columnas propias.
- `04-modelo/presupuestosModel.php` y `04-modelo/presupuestoIntervencionesModel.php` tambien usan el ultimo `estado_resultante` del historial comercial del modo activo como fallback para la columna `Presupuesto` del listado de seguimiento, de modo que la grilla refleje `Recibido`, `Resolicitado`, `Aprobado`, `Rechazado`, `Cancelado` y, despues de `Reabrir`, el ultimo estado restaurado en lugar de mostrar la accion `Reabierto`.
- `04-modelo/presupuestoIntervencionesModel.php` persiste y lee `comentarios` del historial comercial cuando la columna existe; para filas historicas de accion `Enviado` que hayan quedado sin comentario persistido, recompone el texto en lectura desde `presupuesto_documentos_emitidos_envios` usando `para_email` y `cco`, y sigue excluyendo las CCO configuradas por defecto.
- `04-modelo/presupuestoIntervencionesModel.php` incorpora la accion `Reabrir` sin crear un estado nuevo: cuando el circuito actual esta en `Cancelado` o `Rechazado`, un usuario autorizado puede registrar `reabierto` y restaurar el ultimo estado comercial no terminal previo (`Enviado`, `Recibido` o `Resolicitado`). Los perfiles habilitados por defecto son `Super Administrador` y `Administrador`, y existe una whitelist adicional editable en `idsUsuariosPermitidosReaperturaHistorialComercialPresupuesto()`.
- `04-modelo/presupuestoComercialLockModel.php` centraliza la regla de bloqueo comercial del seguimiento: `Resolicitado` sigue permitiendo edicion, mientras que `Aprobado`, `Rechazado` y `Cancelado` bloquean la edicion, el guardado y la generacion de documento de visita/presupuesto. La accion `Reabrir` levanta ese bloqueo al restaurar el ultimo estado comercial no terminal.
- `04-modelo/presupuestoDocumentosEmitidosEnviosModel.php` genera automaticamente el comentario del evento `Enviado` con los destinatarios `Para` y las `CCO` agregadas manualmente o por seleccion del usuario, excluyendo las copias ocultas configuradas por defecto en el sistema.
- `03-controller/presupuestosController.php` renderiza las columnas `Visita` y `Presupuesto` del listado de seguimiento como badges centrados y expone en cada fila `data-estado-visita` y `data-estado-presupuesto` para filtros rapidos sin recargar la grilla.
- `06-funciones_php/guardar_visita.php` guarda fotos de visita en carpetas fechadas `YYYYMMDD`.
- `07-funciones_js/accordionPresupuesto.js` centraliza el editor enriquecido liviano del detalle de tarea, sincroniza el HTML saneado con el `textarea` oculto, conserva la seleccion al usar la toolbar y transforma HTML pegado con estilos inline a etiquetas seguras; visualmente el editor mantiene una altura fija, una toolbar gris suave y hace scroll interno para no empujar el resto de la card.
- `07-funciones_js/accordionVisita.js` genera el PDF emitido desde el frontend y para calle/localidad/partido/provincia debe leer primero el `<select>` real y solo usar Select2 como fallback visual; el detalle de cada tarea respeta formato basico saneado como negrita, cursiva, subrayado y listas.
- `07-funciones_js/presupuestosAcciones.js` concentra el modal `Historial de presupuesto`: consume el endpoint `obtenerHistorialComercialPresupuesto`, renderiza las acciones disponibles en filas separadas como el diseno original del modal, deja la tabla simplificada en `Fecha`, `Usuario`, `Accion` y `Comentarios`, y antes de ejecutar una accion del modal abre un SweetAlert con textarea para capturar el comentario manual de esa accion. La accion `Reabrir` se muestra solo cuando el estado actual queda en `Cancelado` o `Rechazado` y el usuario tiene permisos, mientras que `OC` se muestra solo cuando el estado comercial actual queda en `Aprobado`, se pinta en verde y por ahora solo abre una alerta de funcionalidad en desarrollo, sin registrar movimientos en el historial. Ese mismo archivo tambien mantiene sincronizado el listado: cuando el estado pasa a `Aprobado`, `Rechazado` o `Cancelado`, desaparece el icono `Editar` y el motivo del bloqueo queda en el tooltip de `Visualizar`; al `Reabrir`, `Editar` vuelve a mostrarse. Como ese alert vive encima de un modal Bootstrap, libera temporalmente `focusin.bs.modal` mientras el textarea esta abierto y restaura el focus trap al cerrarlo. Al cerrar el modal principal tambien se limpian los ids cacheados para evitar contexto residual entre aperturas.
- `01-views/seguimiento_de_obra_listado.php` agrega una barra de filtros rapidos arriba de la grilla con dos familias de estados (`Visita` y `Presupuesto`) y un boton `Todos` que limpia ambos filtros; el filtrado corre sobre la misma instancia de DataTables para combinarse con la busqueda general y las exportaciones existentes.
- `01-views/seguimiento_form.php` renderiza el encabezado del accordion de presupuesto y el template que lo recrea; el bloque `Intervino` queda en una sola linea en escritorio y en pantallas angostas baja completo a una nueva fila para no cortar la hora.
- `09-adjuntos/previsita/` contiene adjuntos operativos de la pre-visita y no debe versionarse; el repo solo conserva un `.gitkeep`.

## Migracion SQL requerida por ambiente

- Archivo versionado: `11-migraciones_sql/2026-04-11-A_presupuesto_historial_comercial_comentarios.sql`
- Consulta a ejecutar una sola vez en cada ambiente si `presupuesto_historial_comercial` todavia no tiene la columna `comentarios`:

```sql
ALTER TABLE presupuesto_historial_comercial
ADD COLUMN comentarios TEXT NULL AFTER estado_resultante;
```

- Verificacion sugerida:

```sql
SHOW COLUMNS FROM presupuesto_historial_comercial LIKE 'comentarios';
```

- Archivo versionado: `11-migraciones_sql/2026-04-12-A_presupuesto_historial_comercial_reabierto.sql`
- Consulta a ejecutar una sola vez en cada ambiente para agregar `reabierto` al `ENUM accion` del historial comercial:

```sql
ALTER TABLE presupuesto_historial_comercial
MODIFY accion ENUM(
    'enviado',
    'recibido',
    'resolicitado',
    'aprobado',
    'rechazado',
    'cancelado',
    'llamado',
    'mail_contacto',
    'mensaje_contacto',
    'pendiente_respuesta',
    'respondio',
    'reabierto'
) NOT NULL;
```

## Reglas del PDF emitido de presupuesto

- El titulo de cada tarea se toma desde la descripcion visible de la tarea en el presupuesto.
- El titulo corta en el primer delimitador que aparezca entre punto, coma, guion medio, asterisco o dos puntos.
- El delimitador encontrado no se conserva: se reemplaza por un punto final.
- Si no aparece ninguno de esos delimitadores, el titulo usa las primeras 12 palabras y agrega `...`.
- El detalle no muestra la etiqueta `Detalle:` y excluye el tramo de texto usado para construir el titulo.
- Si el detalle tiene HTML enriquecido, el PDF conserva ese formato solo en el cuerpo del detalle; la construccion del titulo sigue basandose en el texto plano con las reglas anteriores.
- Si al comienzo del detalle quedan espacios o delimitadores usados por la regla del titulo, se eliminan hasta llegar a la primera palabra o a otro simbolo valido.
- La fila `Otros` no se muestra en el PDF emitido, pero sus valores no se eliminan del calculo ni de los subtotales mostrados.
- Las primeras dos imagenes de cada tarea se muestran dentro de la misma pagina de la tarea, de a dos por fila, respetando proporcion.
- Cada una de esas dos imagenes no debe exceder el 50% del ancho util de la hoja ni la altura disponible que queda en la pagina despues del contenido de la tarea.
- Desde la tercera imagen en adelante se generan paginas extra para la misma tarea, sin texto, en una grilla de hasta cuatro bloques por pagina y manteniendo proporcion.
- La descripcion del encabezado del PDF normaliza espacios alrededor de signos de puntuacion para respetar mejor la ortografia visual.
- El titulo se renderiza en mayusculas, en negrita y sin subrayado.

## Levantar el entorno local

### Requisitos

- Docker Desktop o Docker Engine con Compose
- Puerto `8080` libre para Apache
- Puerto `3306` libre para MySQL

### 1. Crear `docker-local/.env`

Crear el archivo `docker-local/.env` con un contenido similar a este:

```env
MYSQL_ROOT_PASSWORD=root
MYSQL_DATABASE=techos
MYSQL_USER=root
TZ=America/Argentina/Buenos_Aires
```

### 2. Iniciar contenedores

Desde `docker-local/`:

```bash
docker compose up --build -d
```

Servicios definidos:

- `web`: Apache + PHP 8.1
- `db`: MySQL 5.7

### 3. Abrir la aplicacion

La app no esta montada como docroot directo del contenedor, sino bajo `/admintech`.

Ruta local de acceso:

```text
http://localhost:8080/admintech/01-views/login.php
```

Conexion local a MySQL:

- Host: `127.0.0.1`
- Puerto: `3306`
- Base: `techos`
- Usuario: `root`
- Password: el definido en `docker-local/.env`

### 4. Detener el entorno

```bash
docker compose down
```

## Base de datos

### Lo que hace Docker al iniciar

El archivo `docker-local/mysql/init/01-create-db.sql` solo crea la base `techos` con `utf8mb4`. No crea automaticamente todo el esquema funcional del sistema.

### Flujo recomendado para dejar una base utilizable

1. Levantar Docker para crear la base vacia.
2. Importar un dump base aprobado por el equipo o una copia local de referencia.
3. Aplicar las migraciones de `11-migraciones_sql/` en orden cronologico.
4. Ejecutar scripts de `12-scripts_operativos_sql/` solo cuando correspondan a una necesidad puntual.

### Convenciones actuales

- Las migraciones usan prefijos de fecha tipo `YYYY-MM-DD`.
- Hay scripts consolidados y scripts de continuacion; revisar dependencias antes de correrlos.
- El codigo local usa `APP_ENV=development` desde `docker-local/docker-compose.override.yml`.

## Configuracion y entorno

- `00-config/configIni.php` define comportamiento distinto para `development` y `production`.
- En Docker local, el host de base esperado es `db`.
- El login local publica la sesion con una base URL que asume la carpeta `/adminTech/`; si se cambia el nombre o la ruta publicada, conviene revisar ese comportamiento.

## Flujo de trabajo recomendado

- Cambios de interfaz: revisar `01-views/`, `07-funciones_js/`, `dist/` y `05-plugins/`.
- Cambios de logica de negocio: revisar `03-controller/`, `04-modelo/` y `06-funciones_php/`.
- Cambios de base de datos: agregar migracion nueva en `11-migraciones_sql/`.
- Cambios operativos puntuales: documentarlos y separarlos en `12-scripts_operativos_sql/`.

## Comandos utiles

Desde `docker-local/`:

```bash
docker compose ps
docker compose logs -f web
docker compose logs -f db
docker compose exec web bash
docker compose exec db mysql -uroot -p
```

## Estado actual del proyecto

- No se detectaron tests automatizados ni un pipeline de CI documentado dentro del repo.
- La verificacion hoy parece ser principalmente manual.
- Existen cambios SQL recientes en `11-migraciones_sql/`, por lo que conviene revisar el estado del working tree antes de hacer merges o deploys.

## Recomendaciones para seguir mejorando la documentacion

- Agregar un dump base anonimizando datos sensibles o documentar de donde obtenerlo.
- Documentar credenciales de acceso local no sensibles o el proceso para crear un usuario inicial.
- Separar documentacion tecnica de archivos operativos y respaldos locales.
- Incorporar una guia corta de deploy y una checklist de validacion manual por modulo.
