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
- Adjuntos legacy de pre-visita o documento fuente: `09-adjuntos/previsita/`.
- Adjuntos multiples de pre-visita: `09-adjuntos/previsita/{id_previsita}/`.

Referencias de implementacion:

- `04-modelo/presupuestoDocumentosEmitidosModel.php` arma la ruta de emision como base de presupuesto + `/emisiones`.
- `04-modelo/presupuestoGeneradoModel.php` guarda fotos del presupuesto por tarea dentro de `t1`, `t2`, etc.
- `04-modelo/presupuestoGeneradoModel.php` tambien sanea el HTML permitido del detalle de tarea antes de persistirlo; hoy solo admite formato basico como negrita, cursiva, subrayado y listas.
- `04-modelo/visitaModel.php`, `06-funciones_php/guardar_visita.php`, `07-funciones_js/accordionVisita.js` y `04-modelo/presupuestoGeneradoModel.php` mantienen coordinado el orden de materiales y mano de obra entre visita y presupuesto mediante la columna `orden`. El presupuesto debe renderizar y persistir los items en el mismo orden visual de la visita, y la mano de obra debe calcular jornales como `operarios x dias` tanto en frontend como en backend.
- `04-modelo/presupuestoIntervencionesModel.php` usa el ultimo `estado_resultante` del historial comercial como fallback del estado visible del modal cuando el presupuesto todavia no tiene persistido un estado comercial activo en sus columnas propias.
- `04-modelo/presupuestosModel.php` y `04-modelo/presupuestoIntervencionesModel.php` tambien usan el ultimo `estado_resultante` del historial comercial del modo activo como fallback para la columna `Presupuesto` del listado de seguimiento, de modo que la grilla refleje `Recibido`, `Resolicitado`, `Aprobado`, `Rechazado`, `Cancelado` y, despues de `Reabrir`, el ultimo estado restaurado en lugar de mostrar la accion `Reabierto`.
- `04-modelo/presupuestoIntervencionesModel.php` persiste y lee `comentarios` del historial comercial cuando la columna existe; para filas historicas de accion `Enviado` que hayan quedado sin comentario persistido, recompone el texto en lectura desde `presupuesto_documentos_emitidos_envios` usando `para_email` y `cco`, y sigue excluyendo las CCO configuradas por defecto.
- `04-modelo/presupuestoIntervencionesModel.php` incorpora la accion `Reabrir` sin crear un estado nuevo: cuando el circuito actual esta en `Cancelado` o `Rechazado`, un usuario autorizado puede registrar `reabierto` y restaurar el ultimo estado comercial no terminal previo (`Enviado`, `Recibido` o `Resolicitado`). Los perfiles habilitados por defecto son `Super Administrador` y `Administrador`, y existe una whitelist adicional editable en `idsUsuariosPermitidosReaperturaHistorialComercialPresupuesto()`.
- `04-modelo/presupuestoComercialLockModel.php` centraliza la regla de bloqueo comercial del seguimiento: `Resolicitado` sigue permitiendo edicion, mientras que `Aprobado`, `Rechazado` y `Cancelado` bloquean la edicion, el guardado y la generacion de documento de visita/presupuesto. La accion `Reabrir` levanta ese bloqueo al restaurar el ultimo estado comercial no terminal.
- `04-modelo/previsitaWorkflowModel.php` centraliza el workflow operativo de la pre-visita: solo `Programada`, `Reprogramada` y `Vencida` permiten editar la pre-visita; `Ejecutada` la deja en solo lectura y habilita la visita; `Cancelada` deja la pre-visita en solo lectura y corta cualquier avance adicional de visita/presupuesto/documento.
- `04-modelo/previsitaDocumentosModel.php` centraliza los adjuntos multiples de la pre-visita: valida tipos (`pdf`, `doc`, `docx`, `xls`, `xlsx`, `txt`, `jpg`, `jpeg`, `png`), guarda nuevos archivos por pre-visita dentro de `09-adjuntos/previsita/{id_previsita}/`, permite borrar adjuntos existentes y mantiene `previsitas.doc_previsita` sincronizado como fallback con el ultimo documento disponible.
- Ese mismo fallback legacy de `previsitas.doc_previsita` solo debe mostrarse si no duplica un adjunto ya presente en `previsita_documentos`; la deduplicacion compara ruta y nombre de archivo para evitar que el mismo documento aparezca dos veces cuando la tabla nueva y la columna legacy apuntan al mismo adjunto.
- `01-views/seguimiento_form.php` y `04-modelo/presupuestosGuardarModel.php` dejan el bloque `Documentos` como la unica edicion habilitada en etapas avanzadas del seguimiento: aun cuando la pre-visita ya este `Ejecutada` o el resto del flujo quede en solo lectura, se pueden agregar o quitar adjuntos desde `Guardar documentos`, mientras que el backend ignora cualquier intento de modificar otros campos cuando llega en modo `solo_documentos`.
- `04-modelo/schemaIntrospectionModel.php` expone `tabla_existe()` y `columna_existe()` como helpers compartidos para que el lock comercial y los flujos de guardado/listado no dependan del orden en que se incluyen otros modelos.
- `04-modelo/presupuestoDocumentosEmitidosEnviosModel.php` genera automaticamente el comentario del evento `Enviado` con los destinatarios `Para` y las `CCO` agregadas manualmente o por seleccion del usuario, excluyendo las copias ocultas configuradas por defecto en el sistema.
- `01-views/configuracion_mail_presupuestos.php` concentra la pantalla de configuracion del mail comercial de presupuestos: el modo activo se cambia desde un switch visual que sigue persistiendo `simulacion` o `smtp` en `modo_envio`, y el backend resuelve el default inicial segun `APP_ENV` definido en `00-config/configIni.php`: ambientes no productivos arrancan en `simulacion` y `production` arranca en `smtp` solo mientras no exista una configuracion personalizada previa.
- La configuracion SMTP de presupuestos esta alineada a DonWeb/Ferozo: el frontend sugiere `465` + `SSL`, el host se documenta como `c######.ferozo.com` o `l######.ferozo.com`, y el email remitente debe coincidir con la cuenta autenticada del usuario SMTP.
- La contraseña SMTP ya no debe renderizarse en HTML ni viajar por JSON. `04-modelo/presupuestoMailConfigModel.php` la devuelve vacia al frontend, conserva un placeholder `********` cuando ya existe una guardada y solo la reemplaza si el administrador ingresa una nueva.
- Para guardar o leer la contraseña SMTP de forma protegida, el servidor debe exponer la variable de entorno `MAIL_PRESUPUESTOS_SECRET` o `ADMINTECH_MAIL_SECRET`. Si falta esa clave, AdminTech bloquea el guardado de nuevas contraseñas SMTP para no persistir secretos en claro.
- `04-modelo/presupuestoDocumentosEmitidosEnviosModel.php` usa PHPMailer por SMTP autenticado, deduplica destinatarios entre `TO`, `CC` y `CCO`, sanea el cuerpo HTML/errores visibles y no marca el presupuesto como enviado si el transporte SMTP falla.
- `03-controller/presupuestosController.php` renderiza las columnas `Visita` y `Presupuesto` del listado de seguimiento como badges centrados y expone en cada fila `data-estado-visita` y `data-estado-presupuesto` para filtros rapidos sin recargar la grilla.
- En ese mismo listado, las pre-visitas con estado `Cancelada` no deben mostrar accion `Editar`; el frontend tambien respeta esa regla si la fila se actualiza dinamicamente.
- `06-funciones_php/guardar_visita.php` guarda fotos de visita en carpetas fechadas `YYYYMMDD`.
- Para validaciones locales del proyecto ya quedo probado un host Windows con `PHP 8.3 CLI`, `Node.js LTS`, `Composer` en `%LOCALAPPDATA%\Programs\Composer\composer.bat`, `jq`, `Chrome` y `7-Zip`; cuando estas herramientas se agregan por `winget` o modificando el `PATH` del usuario puede hacer falta abrir una terminal nueva para invocarlas sin ruta absoluta.
- En shells sandboxed que solo pueden escribir dentro del workspace, `Composer` puede necesitar `TMP`/`TEMP` apuntando a una carpeta temporal dentro del repo para evitar avisos de escritura sobre `D:\Temp`.
- `07-funciones_js/accordionPresupuesto.js` centraliza el editor enriquecido liviano del detalle de tarea, sincroniza el HTML saneado con el `textarea` oculto, conserva la seleccion al usar la toolbar y transforma HTML pegado con estilos inline a etiquetas seguras; visualmente el editor mantiene una altura controlada, una toolbar gris suave y scroll interno para no empujar el resto de la card.
- `07-funciones_js/accordionPresupuesto.js` y `07-funciones_js/accordionVisita.js` deben mantenerse coordinados: el `textarea.tarea-descripcion` oculto del editor enriquecido del presupuesto comparte clase con la visita, por lo que los handlers viejos de `accordionVisita.js` tienen que quedar acotados a `#accordionTareas` para no sobrescribir el encabezado del presupuesto con `undefined:`. El sanitizado del pegado del editor tambien descarta bloques completos como `style`, `script`, `meta` y markup de Office para evitar que se pegue “codigo” antes del texto, y la sincronizacion durante escritura no renormaliza el HTML visible hasta `blur` para que `Enter` no devuelva el cursor al inicio.
- `07-funciones_js/accordionVisita.js` genera el PDF emitido desde el frontend y para calle/localidad/partido/provincia debe leer primero el `<select>` real y solo usar Select2 como fallback visual; el detalle de cada tarea respeta formato basico saneado como negrita, cursiva, subrayado y listas.
- `01-views/seguimiento_form.php` aplica un ajuste estetico local al editor enriquecido del detalle dentro de la card de tarea del presupuesto: en esta vista el interlineado y el espacio entre bloques se comprimen al maximo estetico acordado para mostrar mas texto visible sin afectar ni el HTML guardado ni los estilos usados al generar el PDF.
- `01-views/seguimiento_form.php` y `07-funciones_js/accordionVisita.js` tambien mantienen coordinado el layout de la card de tarea del presupuesto: desde `md` en adelante, la columna izquierda reparte en mitades el espacio disponible entre el editor de detalle y el bloque de imagenes. La pila vertical de utilidades queda al pie de la columna derecha dentro de `tarea-total`, y `Util real final`, `% Utilidad` y `Subtotal Tarea` comparten el mismo ancho fijo visual que los botones superiores de esa columna para no desalinearse ni desbordar. La barra inferior completa de la card usa todo el ancho con `Guardar tarea` y `Traer tarea` a la izquierda; a la derecha, los impuestos grises viven en una subfila flexible pegada al borde del `Subtotal Tarea`, y ese `Subtotal Tarea` reserva su propia columna fija alineada exactamente debajo de la pila de utilidades, con la misma separacion vertical que existe entre los botones de la pila superior, con la misma altura visual fija que los botones grises de esa fila y con los botones grises apenas mas compactos para conservar aire sin romper la fila. En mobile siguen apilados con altura automatica.
- `07-funciones_js/presupuestosAcciones.js` concentra el modal `Historial de presupuesto`: consume el endpoint `obtenerHistorialComercialPresupuesto`, renderiza las acciones disponibles en filas separadas como el diseno original del modal, deja la tabla simplificada en `Fecha`, `Usuario`, `Accion` y `Comentarios`, y antes de ejecutar una accion del modal abre un SweetAlert con textarea para capturar el comentario manual de esa accion. La accion `Reabrir` se muestra solo cuando el estado actual queda en `Cancelado` o `Rechazado` y el usuario tiene permisos, mientras que `OC` se muestra solo cuando el estado comercial actual queda en `Aprobado`, se pinta en verde y por ahora solo abre una alerta de funcionalidad en desarrollo, sin registrar movimientos en el historial. Ese mismo archivo tambien mantiene sincronizado el listado: cuando el estado pasa a `Aprobado`, `Rechazado` o `Cancelado`, desaparece el icono `Editar` y el motivo del bloqueo queda en el tooltip de `Visualizar`; al `Reabrir`, `Editar` vuelve a mostrarse. Como ese alert vive encima de un modal Bootstrap, libera temporalmente `focusin.bs.modal` mientras el textarea esta abierto y restaura el focus trap al cerrarlo. Al cerrar el modal principal tambien se limpian los ids cacheados para evitar contexto residual entre aperturas.
- `01-views/seguimiento_de_obra_listado.php`, `03-controller/presupuestosController.php` y `04-modelo/presupuestosModel.php` hacen que el listado arranque cargando desde SQL solo `30 dias` sobre `Ingreso` y que la botonera de tiempo (`15 dias`, `30 dias`, `Trimestre`, `Semestre`, `Año`) recargue la grilla por AJAX contra backend en lugar de filtrar solo lo ya cargado en memoria; los filtros rapidos de `Visita` y `Presupuesto` corren sobre la instancia actual de DataTables y ahora se mantienen por separado para poder combinarse entre si, mientras que `Todos` deja de bajar toda la base por defecto: la grilla queda vacia hasta que `Buscar` tenga al menos 3 caracteres, momento en que la consulta global pasa a SQL usando ese termino. Si `Todos` sigue activo pero se prende al menos un filtro rapido, la vista ya no sale de ese modo ni vuelve a un rango temporal: recarga por AJAX todo el subconjunto que coincide con esos filtros rapidos, y desde ahi `Buscar` vuelve a comportarse como filtro cliente sobre esa grilla ya acotada; al apagar el ultimo filtro rapido, `Todos` conserva su modo original y `Buscar` vuelve a consultar toda la base. La recarga AJAX del listado tambien invalida respuestas viejas y aborta solicitudes pendientes para que clics rapidos entre rangos o cambios de busqueda no dejen visible un resultado desactualizado, y cada reconstruccion de la grilla vuelve a pasar por una unica rutina que restablece la busqueda cliente y reaplica los filtros rapidos antes del redraw para no perder resultados al cambiar de rango con filtros activos. En modo `Todos`, el input `Buscar` ya no debe vaciarse durante esas reconstrucciones: la vista restaura el valor visible y, si el usuario estaba tipeando, tambien devuelve foco y cursor al nuevo input de DataTables. El aviso para `Todos` ya no usa `SweetAlert`: se inyecta inline dentro del bloque central del toolbar de DataTables, justo entre `Mostrar` y `Buscar`, y se prende o apaga simplemente al activar o desactivar ese modo. Importante: el despacho AJAX directo de `03-controller/presupuestosController.php` debe ejecutarse al final del archivo, despues de que PHP haya corrido los bloques `if (!function_exists(...))` que declaran helpers usados por `poblarDatableAll()`; si se lo deja arriba, los cambios de rango y `Todos + Buscar` fallan con `Fatal error` y el frontend aparenta erróneamente que no hay datos. El listado ahora tambien muestra una alerta visible si una recarga AJAX falla, en vez de vaciar la tabla en silencio.
- `01-views/seguimiento_de_obra_listado.php`, `03-controller/presupuestosController.php`, `04-modelo/presupuestoGeneradoModel.php` y `dist/css/custom.css` agregan la columna visible `Requerimiento tecnico` del listado de seguimiento alimentada desde `previsitas.requerimiento_tecnico`; el texto visible usa la misma regla corta del presupuesto (primer delimitador entre punto, coma, guion medio, asterisco o dos puntos; si no aparece ninguno, primeras 12 palabras), se muestra en sentence case (todo minusculas salvo la primera letra) solo despues de intentar reparar bytes en `Windows-1252`/`ISO-8859-1` y mojibake clasico para no degradar registros historicos, la busqueda general conserva el texto completo mediante `data-search`, las primeras cuatro columnas del cuerpo de la grilla usan una tipografia apenas mas compacta para recuperar ancho con `ID` en negrita, `ID`/`Ingreso`/`CUIT` tienen tambien menor ancho asignado y padding lateral mas chico con `nowrap`, y `Fecha`/`Hora` quedaron mas compactas con menor ancho asignado, `nowrap` y padding lateral reducido.
- `01-views/seguimiento_form.php` renderiza el encabezado del accordion de presupuesto y el template que lo recrea; el bloque `Intervino` queda en una sola linea en escritorio y en pantallas angostas baja completo a una nueva fila para no cortar la hora.
- `01-views/seguimiento_form.php` y `04-modelo/presupuestosGuardarModel.php` manejan la direccion de pre-visita como domicilio estructurado por catalogos (`provincia_visita`, `partido_visita`, `localidad_visita`, `calle_visita`, `altura_visita`, `cp_visita`) y `04-modelo/presupuestosModel.php` la vuelve a resolver con joins contra provincias/partidos/localidades/calles; ademas, al cambiar el `CUIT` puede copiar el domicilio fiscal del cliente existente al bloque de visita usando `existInDB` + `dataByIdCalleLocalidad`. Desde abril de 2026 ese autocompletado ya no depende de demoras fijas entre selects: espera a que carguen partido/localidad/calle antes de seleccionarlos, copia `cp_visita` directamente desde `clientes.dirfis_cp`, el helper PHP del domicilio devuelve aliases explicitos (`calle`, `localidad`, `partido`, `provincia`) para evitar `undefined` en el modal, `razon_social` ahora ofrece sugerencias nativas con `datalist` usando clientes no eliminados en formato `RAZON SOCIAL | CUIT` y, si se elige una coincidencia exacta, reutiliza el mismo flujo de confirmacion para copiar `cuit` y domicilio, la alerta visual de “ACTUALIZANDO CAMPOS” se redujo a 1.2 segundos para no dar sensacion de bloqueo, el toast informativo de esa vista ahora usa fondo mas opaco, contraste reforzado y barra de progreso visible con `toastr.options.progressBar = true` y un timeout levemente mayor (`2600ms`), el encabezado verde de la pre-visita se sincroniza en vivo con `razon_social` para no seguir mostrando el cliente anterior si el usuario lo cambia, y al cambiar `estado_visita`, si `fecha_visita` esta vacia o quedo anterior a hoy, el formulario la mueve automaticamente a la fecha actual para evitar validaciones tardias por una fecha vencida.
- `01-views/obras_form.php` registra ubicacion de obra con Google Maps Places y solo persiste `obra_lat` y `obra_lon` junto con los datos basicos de la obra; hoy no guarda una direccion estructurada equivalente ni reutiliza el circuito de cliente. Por eso, una adaptacion de pre-visita "como obras" no es un reemplazo directo del modelo actual sino, como minimo, una capa adicional de ayuda/autocompletado o una migracion de datos.
- `09-adjuntos/previsita/` contiene adjuntos operativos de la pre-visita y no debe versionarse; el repo solo conserva un `.gitkeep`.
- Actualizacion abril 2026: en `01-views/seguimiento_form.php` la sugerencia de `razon_social` ya no usa el popup nativo del navegador; ahora renderiza un desplegable propio con estilo del sistema, filtro por razon social o CUIT, navegacion con teclado y seleccion por click para evitar el tooltip oscuro del `datalist`.
- Actualizacion abril 2026: la confirmacion de cliente reutiliza el mismo modal, pero el titulo ahora cambia segun el origen del dato: desde `CUIT` mantiene `CLIENTE YA REGISTRADO` y desde la sugerencia de `razon_social` muestra `DATOS DEL CLIENTE`.
- Actualizacion abril 2026: al confirmar un cliente sugerido desde `razon_social`, la vista cierra el desplegable, desenfoca ese campo y mueve el foco a `contacto_obra` para que no quede reabierta la sugerencia sobre el valor ya aplicado.
- Ajuste abril 2026: el reenfoque posterior al autocompletado de cliente se programa para despues del alert temporal `ACTUALIZANDO CAMPOS`, evitando que SweetAlert devuelva el foco a `razon_social` al cerrarse.
- Ajuste abril 2026: el modal de confirmacion de cliente ahora intenta obtener el domicilio expandido por `id_cliente` desde `04-modelo/clientesModel.php::modGetClientesById` antes de recurrir a `dataByIdCalleLocalidad`, porque hay clientes con domicilio correcto en base para los que la reconstruccion auxiliar podia fallar aunque la vista luego completara bien los selects.
- Ajuste abril 2026: en `01-views/aeo_listado.php`, el objeto JS `usuarioLogueado` expone solo `id_usuario`. Las acciones del AEO solo necesitan ese dato y asi se evita que valores de sesion con encoding invalido rompan el `json_encode` e impidan la inicializacion de DataTables, sus botones de exportacion, busqueda y selector de registros.
- Ajuste abril 2026: el DataTables del AEO usa textos y botones definidos localmente en espanol, igual que seguimiento, para no depender del JSON remoto de DataTables. El navbar refresca `nombres`, `apellidos` y `perfil` desde `usuarios` cuando `conectaDB()` esta disponible y escapa el texto como UTF-8, evitando sesiones antiguas con caracteres rotos como la `ñ` en `Guiñazu`.

## Migracion SQL requerida por ambiente

- Archivo versionado: `11-migraciones_sql/2026-04-19-A_previsita_documentos.sql`
- Objetivo: crear la tabla hija `previsita_documentos` para soportar multiples adjuntos por pre-visita y migrar los valores historicos de `previsitas.doc_previsita` sin mover archivos legacy. En ambientes donde `previsitas` siga en `utf8mb4_general_ci`, la migracion ya fuerza `previsita_documentos` a esa misma collation antes de comparar rutas para evitar el error `Illegal mix of collations`.

```sql
CREATE TABLE IF NOT EXISTS previsita_documentos (
    id_documento_previsita INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_previsita INT UNSIGNED NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    nombre_archivo_original VARCHAR(255) DEFAULT NULL,
    ruta_archivo VARCHAR(500) NOT NULL,
    mime_type VARCHAR(100) DEFAULT NULL,
    tamano_bytes BIGINT UNSIGNED DEFAULT NULL,
    id_usuario_alta INT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_documento_previsita),
    KEY idx_previsita_documentos_previsita (id_previsita),
    KEY idx_previsita_documentos_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE previsita_documentos
CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

INSERT INTO previsita_documentos (
    id_previsita,
    nombre_archivo,
    nombre_archivo_original,
    ruta_archivo,
    mime_type,
    tamano_bytes,
    id_usuario_alta,
    created_at
)
SELECT
    p.id_previsita,
    p.doc_previsita,
    p.doc_previsita,
    CONCAT('09-adjuntos/previsita/', p.doc_previsita),
    'application/pdf',
    NULL,
    p.log_usuario_id,
    COALESCE(p.log_edicion, p.log_alta, NOW())
FROM previsitas AS p
LEFT JOIN previsita_documentos AS pd
    ON pd.id_previsita = p.id_previsita
   AND CONVERT(pd.ruta_archivo USING utf8mb4) = CONVERT(CONCAT('09-adjuntos/previsita/', p.doc_previsita) USING utf8mb4)
WHERE CHAR_LENGTH(TRIM(COALESCE(p.doc_previsita, ''))) > 0
  AND pd.id_documento_previsita IS NULL;
```

- Verificacion sugerida:

```sql
SHOW TABLES LIKE 'previsita_documentos';
SELECT id_previsita, ruta_archivo FROM previsita_documentos ORDER BY id_documento_previsita DESC LIMIT 10;
```

- Archivo versionado: `11-migraciones_sql/2026-04-21-A_orden_detalle_visita_presupuesto.sql`
- Objetivo: agregar `orden` a materiales/mano de obra de visita y presupuesto, backfillear el orden historico, alinear presupuestos existentes contra el orden de la visita cuando coinciden por material/jornal, y recalcular los subtotales de mano de obra usando `operarios x dias x valor_jornal`.

```sql
ALTER TABLE visita_tarea_material ADD COLUMN orden INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE visita_tarea_mano_obra ADD COLUMN orden INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE presupuesto_tarea_material ADD COLUMN orden INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE presupuesto_tarea_mano_obra ADD COLUMN orden INT UNSIGNED NOT NULL DEFAULT 0;
```

- Verificacion sugerida:

```sql
SHOW COLUMNS FROM visita_tarea_material LIKE 'orden';
SHOW COLUMNS FROM visita_tarea_mano_obra LIKE 'orden';
SHOW COLUMNS FROM presupuesto_tarea_material LIKE 'orden';
SHOW COLUMNS FROM presupuesto_tarea_mano_obra LIKE 'orden';
SELECT COUNT(*) FROM visita_tarea_material WHERE orden = 0;
SELECT COUNT(*) FROM visita_tarea_mano_obra WHERE orden = 0;
SELECT COUNT(*) FROM presupuesto_tarea_material WHERE orden = 0;
SELECT COUNT(*) FROM presupuesto_tarea_mano_obra WHERE orden = 0;
```

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
- Esa misma regla de resumen tambien se reutiliza en los encabezados visuales de tarea y en la columna `Descripcion` del listado de seguimiento.

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
