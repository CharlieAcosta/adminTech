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
