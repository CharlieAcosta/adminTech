<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header('Location: login.php');
    exit;
}

if (($_SESSION["usuario"]["perfil"] ?? '') !== 'Super Administrador') {
    header('Location: panel.php');
    exit;
}

define('BASE_URL', $_SESSION["base_url"] ?? '');
include_once '../06-funciones_php/funciones.php';
include_once '../04-modelo/conectDB.php';

function generarSQLTabla(mysqli $db, string $tabla, bool $conDatos = true): string {
    $sql  = "-- --------------------------------------------------------\n";
    $sql .= "-- Estructura para la tabla `{$tabla}`\n";
    $sql .= "-- --------------------------------------------------------\n\n";
    $sql .= "DROP TABLE IF EXISTS `{$tabla}`;\n";

    $res = $db->query("SHOW CREATE TABLE `{$tabla}`");
    if ($res) {
        $row  = $res->fetch_row();
        $sql .= $row[1] . ";\n\n";
    }

    if ($conDatos) {
        $res = $db->query("SELECT * FROM `{$tabla}`");
        if ($res && $res->num_rows > 0) {
            $sql .= "-- Volcado de datos para la tabla `{$tabla}`\n";
            $filas = [];
            while ($fila = $res->fetch_row()) {
                $vals = array_map(
                    fn($v) => $v === null ? 'NULL' : "'" . $db->real_escape_string((string)$v) . "'",
                    $fila
                );
                $filas[] = '(' . implode(', ', $vals) . ')';
            }
            $sql .= "INSERT INTO `{$tabla}` VALUES\n" . implode(",\n", $filas) . ";\n\n";
        }
    }

    return $sql;
}

// Descargas antes de cualquier salida HTML
if (!empty($_GET['action'])) {
    $db     = conectDB();
    $accion = $_GET['action'];

    $res = $db->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME");
    $tablasValidas = [];
    while ($r = $res->fetch_assoc()) {
        $tablasValidas[] = $r['TABLE_NAME'];
    }

    if (in_array($accion, ['dump_tabla', 'dump_tabla_estructura']) && !empty($_GET['tabla']) && in_array($_GET['tabla'], $tablasValidas, true)) {
        $tabla     = $_GET['tabla'];
        $conDatos  = $accion === 'dump_tabla';
        $sufijo    = $conDatos ? '' : '_estructura';
        $sql       = generarSQLTabla($db, $tabla, $conDatos);
        $archivo   = DB_NAME . '_' . $tabla . $sufijo . '_' . date('Ymd_His') . '.sql';
        if (!empty($_GET['token'])) {
            setcookie('dl_token_' . preg_replace('/\W/', '', $_GET['token']), '1', time() + 60, '/');
        }
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $archivo . '"');
        header('Content-Transfer-Encoding: binary');
        header('Pragma: no-cache');
        echo $sql;
        exit;
    }

    if (in_array($accion, ['dump_db', 'dump_db_estructura'])) {
        $conDatos = $accion === 'dump_db';
        $sufijo   = $conDatos ? '_dump' : '_estructura';
        $sql  = "-- ======================================================\n";
        $sql .= "-- Base de datos: `" . DB_NAME . "`\n";
        $sql .= "-- Generado: " . date('Y-m-d H:i:s') . "\n";
        $sql .= ($conDatos ? "-- Incluye: estructura + datos\n" : "-- Incluye: solo estructura\n");
        $sql .= "-- ======================================================\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n";
        $sql .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
        $sql .= "SET time_zone = '+00:00';\n\n";
        foreach ($tablasValidas as $t) {
            $sql .= generarSQLTabla($db, $t, $conDatos);
        }
        $sql .= "\nSET FOREIGN_KEY_CHECKS = 1;\n";
        $archivo = DB_NAME . $sufijo . '_' . date('Ymd_His') . '.sql';
        if (!empty($_GET['token'])) {
            setcookie('dl_token_' . preg_replace('/\W/', '', $_GET['token']), '1', time() + 60, '/');
        }
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $archivo . '"');
        header('Content-Transfer-Encoding: binary');
        header('Pragma: no-cache');
        echo $sql;
        exit;
    }

    if ($accion === 'get_datos' && !empty($_GET['tabla']) && in_array($_GET['tabla'], $tablasValidas, true)) {
        $tabla     = $_GET['tabla'];
        $porPagina = min(max((int)($_GET['por_pagina'] ?? 50), 10), 200);
        $pagina    = max((int)($_GET['pagina'] ?? 1), 1);
        $offset    = ($pagina - 1) * $porPagina;
        header('Content-Type: application/json; charset=utf-8');

        $esc    = $db->real_escape_string($tabla);
        $colRes = $db->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$esc}' ORDER BY ORDINAL_POSITION");
        $cols   = [];
        while ($c = $colRes->fetch_assoc()) $cols[] = $c['COLUMN_NAME'];

        $total       = (int)$db->query("SELECT COUNT(*) AS cnt FROM `{$esc}`")->fetch_assoc()['cnt'];
        $totalPaginas = (int)ceil($total / $porPagina);
        $rows        = [];
        $resD        = $db->query("SELECT * FROM `{$esc}` LIMIT {$offset}, {$porPagina}");
        while ($row = $resD->fetch_row()) {
            $rows[] = $row;
        }

        echo json_encode([
            'cols'          => $cols,
            'rows'          => $rows,
            'total'         => $total,
            'pagina'        => $pagina,
            'por_pagina'    => $porPagina,
            'total_paginas' => $totalPaginas,
        ], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        exit;
    }
}

header('Content-Type: text/html; charset=utf-8');
include_once '../06-funciones_php/auditoria.php';
registrarNavegacion('BASE DE DATOS - Esquema');

$db          = conectDB();
$tablasDatos = [];

$resTabs = $db->query("
    SELECT
        TABLE_NAME,
        COALESCE(TABLE_ROWS, 0)                                     AS TABLE_ROWS,
        ROUND(COALESCE((DATA_LENGTH + INDEX_LENGTH), 0) / 1024, 2) AS size_kb,
        COALESCE(TABLE_COMMENT, '')                                 AS TABLE_COMMENT,
        DATE_FORMAT(CREATE_TIME, '%d/%m/%Y')                        AS creada
    FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
    ORDER BY TABLE_NAME
");

while ($t = $resTabs->fetch_assoc()) {
    $tn      = $db->real_escape_string($t['TABLE_NAME']);
    $resCols = $db->query("
        SELECT
            COLUMN_NAME,
            " . COLUMN_TYPE . " AS col_type,
            IS_NULLABLE,
            COLUMN_KEY,
            COLUMN_DEFAULT,
            EXTRA
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = '{$tn}'
        ORDER BY ORDINAL_POSITION
    ");
    $cols = [];
    while ($c = $resCols->fetch_assoc()) {
        $cols[] = $c;
    }
    $t['columns'] = $cols;
    $tablasDatos[] = $t;
}

$totalTablas = count($tablasDatos);
$totalFilas  = array_sum(array_column($tablasDatos, 'TABLE_ROWS'));
$totalTamano = array_sum(array_column($tablasDatos, 'size_kb'));

$jsonTablas = json_encode($tablasDatos, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta name="robots" content="noindex">
  <meta name="googlebot" content="noindex">
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ADMINTECH | Base de Datos — Esquema</title>

  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="../05-plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../dist/css/custom.css">

  <style>
    /* ── Paneles ──────────────────────────────────────── */
    #schema-layout {
      display: flex;
      gap: 6px;
      height: calc(100vh - 316px);
      min-height: 420px;
      margin-bottom: 6px;
    }

    /* Panel izquierdo */
    #panel-lista {
      width: 33%;
      min-width: 220px;
      display: flex;
      flex-direction: column;
      border: 1px solid #dee2e6;
      border-radius: .25rem;
      background: #fff;
      overflow: hidden;
    }
    #panel-lista-header {
      height: 46px;
      padding: 0 .75rem;
      background: #e4e8ee;
      border-bottom: 1px solid #d0d5dc;
      flex-shrink: 0;
      display: flex;
      align-items: center;
      gap: .5rem;
    }
    #panel-lista-header .header-count {
      font-size: .68rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .07em;
      color: #6c757d;
      white-space: nowrap;
    }
    #buscar-tabla {
      flex: 1;
      height: 28px;
      font-size: .78rem;
      border: 1px solid #ced4da;
      border-radius: .4rem;
      padding: 0 .5rem 0 1.6rem;
      background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23adb5bd' stroke-width='2.5'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'/%3E%3C/svg%3E") no-repeat .45rem center;
      outline: none;
      transition: border-color .15s;
    }
    #buscar-tabla:focus { border-color: #80bdff; }
    #lista-scroll {
      overflow-y: auto;
      flex: 1;
    }
    .tabla-item {
      display: flex;
      flex-direction: column;
      padding: .55rem .75rem;
      border-bottom: 1px solid #f0f0f0;
      cursor: pointer;
      transition: background .12s ease;
    }
    .tabla-item:last-child { border-bottom: none; }
    .tabla-item:nth-child(odd)  { background: #fff; }
    .tabla-item:nth-child(even) { background: #f0faf4; }
    .tabla-item:hover { background: #d6f0e0; }
    .tabla-item.activa,
    .tabla-item.activa:nth-child(odd),
    .tabla-item.activa:nth-child(even) {
      background: #007bff;
      border-color: #0069d9;
    }
    .tabla-item.activa .tabla-item-nombre { color: #fff; }
    .tabla-item.activa .badge { opacity: .88; }

    .tabla-item-nombre {
      font-size: 1rem;
      font-weight: 800;
      color: #1a1d20;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      letter-spacing: .01em;
    }
    .tabla-item-badges { margin-top: .22rem; display: flex; gap: .25rem; flex-wrap: wrap; }

    /* Panel derecho */
    #panel-detalle {
      flex: 1;
      display: flex;
      flex-direction: column;
      border: 1px solid #dee2e6;
      border-radius: .25rem;
      background: #fff;
      overflow: hidden;
    }
    #detalle-header {
      height: 46px;
      padding: 0 1rem;
      background: #e4e8ee;
      border-bottom: 1px solid #d0d5dc;
      flex-shrink: 0;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    #detalle-scroll {
      flex: 1;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }
    #detalle-body {
      flex: 1;
      overflow: auto;
    }
    #detalle-paginacion {
      flex-shrink: 0;
      border-top: 2px solid #dee2e6;
      background: #f8f9fa;
    }

    /* Placeholder */
    #detalle-placeholder {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #adb5bd;
    }

    /* Tabla de columnas */
    .table-schema thead th {
      background: #f4f6f9;
      font-size: .7rem;
      text-transform: uppercase;
      letter-spacing: .06em;
      color: #6c757d;
      border-bottom: 2px solid #dee2e6;
      white-space: nowrap;
      position: sticky;
      top: 0;
      z-index: 1;
    }
    .table-schema td {
      font-size: .82rem;
      vertical-align: middle;
      padding: .38rem .75rem;
    }
    .table-schema tbody tr:nth-child(even) { background: #f0faf4; }
    .table-schema tbody tr:nth-child(odd)  { background: #fff; }
    .table-schema tbody tr:hover           { background: #d6f0e0; }

    .col-name {
      font-family: 'Courier New', monospace;
      font-weight: 700;
      color: #343a40;
    }
    .col-type {
      font-family: 'Courier New', monospace;
      font-size: .78rem;
    }

    /* Stats compactas */
    .stats-compact .info-box {
      margin-bottom: 0;
      min-height: 56px;
    }
    .stats-compact .info-box-icon {
      width: 56px;
      line-height: 56px;
      font-size: 1.5rem;
    }
    .stats-compact .info-box-content {
      padding: 6px 10px;
    }
    .stats-compact .info-box-text { font-size: .78rem; }
    .stats-compact .info-box-number { font-size: 1.2rem; }

    /* Overlay de descarga */
    #descarga-overlay {
      display: none;
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0,0,0,.55);
      z-index: 9999;
    }
    #descarga-card {
      position: absolute;
      top: 50%; left: 50%;
      transform: translate(-50%, -50%);
      background: #fff;
      border-radius: .75rem;
      padding: 2rem 2.5rem;
      min-width: 360px;
      text-align: center;
      box-shadow: 0 15px 50px rgba(0,0,0,.35);
    }
    #descarga-card .progress {
      height: 10px;
      border-radius: 5px;
      background: #e9ecef;
    }
    #descarga-card .progress-bar {
      border-radius: 5px;
      transition: width .4s ease;
    }

    /* Botones de descarga */
    .btn-dump {
      border-radius: .5rem;
      min-width: 120px;
      text-align: center;
      font-weight: 600;
      font-size: .78rem;
      padding: .25rem .75rem;
      line-height: 1.4;
    }
    .btn-dump-datos {
      background: #3d8bcd;
      border-color: #3480c4;
      color: #fff;
    }
    .btn-dump-datos:hover { background: #2d7ab8; color: #fff; }
    .btn-dump-estructura {
      background: #6c757d;
      border-color: #626a71;
      color: #fff;
    }
    .btn-dump-estructura:hover { background: #5a6268; color: #fff; }
    .btn-ver-datos {
      background: #138496;
      border-color: #117a8b;
      color: #fff;
      border-radius: .5rem;
      font-weight: 600;
      font-size: .78rem;
      padding: .25rem .75rem;
      line-height: 1.4;
    }
    .btn-ver-datos:hover, .btn-ver-datos:focus { background: #0f6674; color: #fff; }
    .btn-ver-esquema {
      background: #5a6268;
      border-color: #4e555b;
      color: #fff;
      border-radius: .5rem;
      font-weight: 600;
      font-size: .78rem;
      padding: .25rem .75rem;
      line-height: 1.4;
    }
    .btn-ver-esquema:hover { background: #484f55; color: #fff; }
    .datos-info {
      background: #e8f4f8;
      border-left: 3px solid #17a2b8;
      padding: .35rem .75rem;
      font-size: .78rem;
      color: #0c5460;
    }
  </style>
</head>
<body class="hold-transition sidebar-collapse layout-navbar-fixed">
<div class="wrapper">

  <?php include '../01-views/layout/navbar_layout.php'; ?>

  <div class="content-wrapper">

    <section class="content-header">
      <div class="container-fluid">
        <div class="row align-items-center">
          <div class="col-sm-6">
            <h1>
              <i class="fas fa-database mr-2 text-primary"></i>
              <strong>Base de Datos</strong>
              <small class="text-muted ml-1">| Esquema</small>
            </h1>
          </div>
          <div class="col-sm-6 d-flex justify-content-sm-end">
            <button type="button" onclick="window.history.back()" class="btn btn-success" style="font-size:.78rem; padding:.25rem .75rem; line-height:1.4; border-radius:.5rem; font-weight:600">
              <i class="fa fa-arrow-left mr-1"></i>Volver
            </button>
          </div>
        </div>
      </div>
    </section>

    <section class="content">
      <div class="container-fluid">

        <!-- Stats + acciones -->
        <div class="row align-items-center stats-compact" style="margin-bottom:6px">
          <div class="col-md-3 col-sm-4 col-6 mb-2 mb-md-0">
            <div class="info-box shadow-none border">
              <span class="info-box-icon bg-primary"><i class="fas fa-table"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">Tablas</span>
                <span class="info-box-number"><?= $totalTablas ?></span>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-sm-4 col-6 mb-2 mb-md-0">
            <div class="info-box shadow-none border">
              <span class="info-box-icon bg-info"><i class="fas fa-list-ol"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">Filas estimadas</span>
                <span class="info-box-number"><?= number_format($totalFilas) ?></span>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-sm-4 col-6 mb-2 mb-md-0">
            <div class="info-box shadow-none border">
              <span class="info-box-icon bg-success"><i class="fas fa-hdd"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">Tamaño total</span>
                <span class="info-box-number" style="white-space:nowrap">
                  <?= number_format($totalTamano, 1) ?> <small>KB</small>
                  &nbsp;<?= number_format($totalTamano / 1024, 2) ?> <small>MB</small>
                  &nbsp;<?= number_format($totalTamano / 1024 / 1024, 3) ?> <small>GB</small>
                </span>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-sm-12 col-12 d-flex flex-column align-items-center justify-content-center mb-2 mb-md-0">
            <span class="text-muted mb-1" style="font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; text-align:center">
              <i class="fas fa-database mr-1"></i>Descargar base de datos
            </span>
            <div class="d-flex" style="gap:6px">
              <a href="?action=dump_db" class="btn btn-dump btn-dump-datos descarga-link"
                 data-titulo="Descargando base de datos"
                 data-subtitulo="Estructura + datos de todas las tablas">
                <i class="fas fa-download mr-1"></i>Con datos
              </a>
              <a href="?action=dump_db_estructura" class="btn btn-dump btn-dump-estructura descarga-link"
                 data-titulo="Descargando base de datos"
                 data-subtitulo="Solo estructura de todas las tablas">
                <i class="fas fa-code mr-1"></i>Estructura
              </a>
            </div>
          </div>
        </div>

        <!-- Layout dos paneles -->
        <div id="schema-layout">

          <!-- Panel izquierdo: lista de tablas -->
          <div id="panel-lista">
            <div id="panel-lista-header">
              <span class="header-count"><i class="fas fa-table mr-1"></i><?= $totalTablas ?> tablas</span>
              <input type="text" id="buscar-tabla" placeholder="Buscar...">
            </div>
            <div id="lista-scroll">
              <?php foreach ($tablasDatos as $i => $tabla): ?>
              <div class="tabla-item" data-idx="<?= $i ?>">
                <span class="tabla-item-nombre">
                  <i class="fas fa-table fa-xs mr-1 text-muted"></i>
                  <?= htmlspecialchars($tabla['TABLE_NAME']) ?>
                </span>
                <div class="tabla-item-badges">
                  <span class="badge badge-pill badge-light border" style="font-size:.66rem">
                    <?= count($tabla['columns']) ?> cols
                  </span>
                  <span class="badge badge-pill badge-info" style="font-size:.66rem">
                    <?= number_format((int)$tabla['TABLE_ROWS']) ?> filas
                  </span>
                  <span class="badge badge-pill badge-secondary" style="font-size:.66rem">
                    <?= $tabla['size_kb'] ?> KB
                  </span>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Panel derecho: detalle -->
          <div id="panel-detalle">
            <div id="detalle-header">
              <div id="detalle-titulo" class="text-muted small"></div>
              <div id="detalle-acciones"></div>
            </div>
            <div id="detalle-scroll">
              <div id="detalle-placeholder">
                <div class="text-center">
                  <i class="fas fa-hand-point-left fa-2x mb-2 d-block"></i>
                  <span style="font-size:.85rem">Seleccioná una tabla para ver su estructura</span>
                </div>
              </div>
              <div id="detalle-body" style="display:none"></div>
            </div>
            <div id="detalle-paginacion" style="display:none"></div>
          </div>

        </div><!-- /#schema-layout -->

      </div>
    </section>
  </div>

  <?php include '../01-views/layout/footer_layout.php'; ?>
  <aside class="control-sidebar control-sidebar-dark"></aside>

  <!-- Overlay descarga -->
  <div id="descarga-overlay">
    <div id="descarga-card">
      <i class="fas fa-database fa-2x text-primary mb-3 d-block"></i>
      <h6 id="descarga-titulo" class="mb-1 font-weight-bold">Generando archivo SQL...</h6>
      <p id="descarga-subtitulo" class="text-muted small mb-3"></p>
      <div class="progress mb-2">
        <div id="descarga-barra"
             class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
             style="width:0%"></div>
      </div>
      <small id="descarga-estado" class="text-muted">Preparando...</small>
    </div>
  </div>

</div>

<script src="../05-plugins/jquery/jquery.min.js"></script>
<script src="../05-plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../dist/js/adminlte.min.js"></script>
<script src="../07-funciones_js/funciones.js"></script>

<script>
var TABLAS = <?= $jsonTablas ?>;
var tablaActualIdx = null;

function esc(s) {
  if (s === null || s === undefined) return '';
  return String(s)
    .replace(/&/g, '&amp;').replace(/</g, '&lt;')
    .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function tipoColor(t) {
  t = (t || '').toLowerCase();
  if (/^(int|bigint|smallint|tinyint|mediumint|decimal|float|double|numeric)/.test(t)) return 'text-primary';
  if (/^(varchar|char|text|mediumtext|longtext|tinytext|enum|set)/.test(t))             return 'text-success';
  if (/^(date|datetime|timestamp|time|year)/.test(t))                                   return 'text-warning';
  if (/^(blob|binary|varbinary|bit)/.test(t))                                           return 'text-secondary';
  return 'text-muted';
}

function badgeClave(k) {
  switch (k) {
    case 'PRI': return '<span class="badge badge-danger"><i class="fas fa-key fa-xs mr-1"></i>PK</span>';
    case 'UNI': return '<span class="badge badge-warning">UNI</span>';
    case 'MUL': return '<span class="badge badge-info">IDX</span>';
    default:    return '<span class="text-muted">—</span>';
  }
}

function badgeNulo(n) {
  return n === 'YES'
    ? '<span class="badge badge-secondary">NULL</span>'
    : '<span class="badge badge-success">NOT NULL</span>';
}

function renderExtra(e) {
  if (!e) return '<span class="text-muted">—</span>';
  if (e.toLowerCase().indexOf('auto_increment') !== -1)
    return '<span class="badge badge-primary">AUTO_INC</span>';
  return '<small class="text-muted">' + esc(e) + '</small>';
}

function botonesAccion(t, idx, vista) {
  var btnVista = vista === 'datos'
    ? '<button type="button" class="btn btn-ver-esquema" onclick="mostrarTabla(' + idx + ')">' +
      '<i class="fas fa-columns mr-1"></i>Ver esquema</button>'
    : '<button type="button" class="btn btn-ver-datos" onclick="cargarDatos(' + idx + ')">' +
      '<i class="fas fa-th mr-1"></i>Ver datos</button>';

  return '<div class="d-flex" style="gap:6px">' +
    '<a href="?action=dump_tabla&tabla=' + encodeURIComponent(t.TABLE_NAME) + '" ' +
    'class="btn btn-sm btn-dump btn-dump-datos descarga-link" ' +
    'data-titulo="Descargando tabla" ' +
    'data-subtitulo="' + esc(t.TABLE_NAME) + ' — estructura + datos">' +
    '<i class="fas fa-download mr-1"></i>Con datos</a>' +
    '<a href="?action=dump_tabla_estructura&tabla=' + encodeURIComponent(t.TABLE_NAME) + '" ' +
    'class="btn btn-sm btn-dump btn-dump-estructura descarga-link" ' +
    'data-titulo="Descargando tabla" ' +
    'data-subtitulo="' + esc(t.TABLE_NAME) + ' — solo estructura">' +
    '<i class="fas fa-code mr-1"></i>Estructura</a>' +
    btnVista +
    '</div>';
}

function mostrarTabla(idx) {
  var t = TABLAS[idx];
  if (!t) return;
  tablaActualIdx = idx;

  // Header del panel derecho
  $('#detalle-titulo').html(
    '<i class="fas fa-table text-primary mr-2"></i>' +
    '<strong>' + esc(t.TABLE_NAME) + '</strong>' +
    (t.TABLE_COMMENT ? ' <small class="text-muted ml-1">' + esc(t.TABLE_COMMENT) + '</small>' : '') +
    ' <span class="badge badge-light border ml-2">' + t.columns.length + ' cols</span>' +
    ' <span class="badge badge-info ml-1">' + Number(t.TABLE_ROWS).toLocaleString() + ' filas</span>' +
    ' <span class="badge badge-secondary ml-1">' + t.size_kb + ' KB</span>' +
    (t.creada ? ' <small class="text-muted ml-2"><i class="fas fa-calendar-alt fa-xs mr-1"></i>' + t.creada + '</small>' : '')
  );

  $('#detalle-acciones').html(botonesAccion(t, idx, 'esquema'));

  // Tabla de columnas
  var rows = '';
  t.columns.forEach(function (c, j) {
    var def = c.COLUMN_DEFAULT !== null
      ? '<code>' + esc(c.COLUMN_DEFAULT) + '</code>'
      : '<em class="text-muted">NULL</em>';
    rows +=
      '<tr>' +
      '<td class="text-center text-muted small">' + (j + 1) + '</td>' +
      '<td><span class="col-name">' + esc(c.COLUMN_NAME) + '</span></td>' +
      '<td><span class="col-type ' + tipoColor(c.col_type) + '">' + esc(c.col_type) + '</span></td>' +
      '<td class="text-center">' + badgeNulo(c.IS_NULLABLE) + '</td>' +
      '<td class="text-center">' + badgeClave(c.COLUMN_KEY) + '</td>' +
      '<td class="small">' + def + '</td>' +
      '<td>' + renderExtra(c.EXTRA) + '</td>' +
      '</tr>';
  });

  var html =
    '<table class="table table-sm table-hover table-schema mb-0">' +
    '<thead><tr>' +
    '<th class="text-center" style="width:3%">#</th>' +
    '<th style="width:22%">Columna</th>' +
    '<th style="width:21%">Tipo</th>' +
    '<th class="text-center" style="width:10%">Nulo</th>' +
    '<th class="text-center" style="width:8%">Clave</th>' +
    '<th style="width:18%">Default</th>' +
    '<th>Extra</th>' +
    '</tr></thead>' +
    '<tbody>' + rows + '</tbody>' +
    '</table>';

  $('#detalle-placeholder').hide();
  $('#detalle-paginacion').hide().empty();
  $('#detalle-body').html(html).show();

  // Selección visual
  $('.tabla-item').removeClass('activa');
  $('.tabla-item[data-idx="' + idx + '"]').addClass('activa');
}

// ── Ver datos de tabla ────────────────────────────────────────────────────
function cargarDatos(idx, pagina, porPagina) {
  pagina    = pagina    || 1;
  porPagina = porPagina || 50;

  var t = TABLAS[idx];
  if (!t) return;
  tablaActualIdx = idx;

  $('#detalle-acciones').html(botonesAccion(t, idx, 'datos'));
  $('#detalle-placeholder').hide();
  $('#detalle-body').html(
    '<div class="text-center py-5 text-muted">' +
    '<i class="fas fa-spinner fa-spin fa-2x mb-2 d-block"></i>Cargando...</div>'
  ).show();

  $.ajax({
    url: 'dump.php',
    data: { action: 'get_datos', tabla: t.TABLE_NAME, pagina: pagina, por_pagina: porPagina },
    dataType: 'json',
    success: function (d) {
      var desde = (d.pagina - 1) * d.por_pagina + 1;
      var hasta = Math.min(d.pagina * d.por_pagina, d.total);

      var html = '<table class="table table-sm table-hover table-schema mb-0"><thead><tr>' +
        '<th class="text-center" style="width:3%">#</th>';
      d.cols.forEach(function (col) { html += '<th>' + esc(col) + '</th>'; });
      html += '</tr></thead><tbody>';

      d.rows.forEach(function (row, i) {
        html += '<tr><td class="text-center text-muted small">' + (desde + i) + '</td>';
        row.forEach(function (val) {
          html += '<td class="small">' +
            (val === null ? '<em class="text-muted">NULL</em>' : esc(String(val))) + '</td>';
        });
        html += '</tr>';
      });
      html += '</tbody></table>';

      $('#detalle-body').html(html);
      $('#detalle-paginacion').html(renderPaginacion(d, idx)).show();

      $('#select-por-pagina').on('change', function () {
        cargarDatos(idx, 1, parseInt($(this).val()));
      });
    },
    error: function () {
      $('#detalle-body').html(
        '<div class="text-center py-4 text-danger">' +
        '<i class="fas fa-exclamation-circle mr-1"></i>Error al cargar los datos.</div>'
      );
    }
  });
}

function renderPaginacion(d, idx) {
  var p  = d.pagina, pp = d.por_pagina, tp = d.total_paginas;
  var desde = (p - 1) * pp + 1;
  var hasta = Math.min(p * pp, d.total);

  var html = '<div class="d-flex align-items-center justify-content-between px-3 py-2">';

  // Selector filas + info
  html += '<div class="d-flex align-items-center" style="gap:6px">' +
    '<small class="text-muted">Filas:</small>' +
    '<select id="select-por-pagina" class="form-control form-control-sm" style="width:68px">';
  [25, 50, 100, 200].forEach(function (n) {
    html += '<option value="' + n + '"' + (n === pp ? ' selected' : '') + '>' + n + '</option>';
  });
  html += '</select>' +
    '<small class="text-muted">' + desde + '–' + hasta + ' de <strong>' +
    Number(d.total).toLocaleString() + '</strong></small></div>';

  // Paginación
  if (tp > 1) {
    html += '<nav><ul class="pagination pagination-sm mb-0">';

    var btnPag = function (pg, label, disabled, active) {
      if (disabled) return '<li class="page-item disabled"><span class="page-link">' + label + '</span></li>';
      if (active)   return '<li class="page-item active"><span class="page-link">' + label + '</span></li>';
      return '<li class="page-item"><a class="page-link" href="#" ' +
        'onclick="cargarDatos(' + idx + ',' + pg + ',' + pp + ');return false">' + label + '</a></li>';
    };

    html += btnPag(p - 1, '‹', p === 1);

    var ini = Math.max(1, p - 2), fin = Math.min(tp, p + 2);
    if (ini > 1) {
      html += btnPag(1, '1');
      if (ini > 2) html += btnPag(null, '…', true);
    }
    for (var pg = ini; pg <= fin; pg++) html += btnPag(pg, pg, false, pg === p);
    if (fin < tp) {
      if (fin < tp - 1) html += btnPag(null, '…', true);
      html += btnPag(tp, tp);
    }

    html += btnPag(p + 1, '›', p === tp);
    html += '</ul></nav>';
  }

  html += '</div>';
  return html;
}

// ── Descarga con barra de progreso ───────────────────────────────────────
function iniciarDescarga(url, titulo, subtitulo) {
  var token = 'dl' + Date.now();
  var sep   = url.indexOf('?') !== -1 ? '&' : '?';
  var fullUrl = url + sep + 'token=' + token;

  $('#descarga-titulo').text(titulo || 'Generando archivo SQL...');
  $('#descarga-subtitulo').text(subtitulo || '');
  $('#descarga-estado').text('Preparando...');
  $('#descarga-barra').css('width', '0%')
    .addClass('progress-bar-animated progress-bar-striped');
  $('#descarga-overlay').fadeIn(200);

  // Progreso simulado por tramos
  var progreso = 0;
  var tramos = [
    { hasta: 35, pausa: 60  },
    { hasta: 65, pausa: 130 },
    { hasta: 82, pausa: 280 },
    { hasta: 92, pausa: 600 },
  ];
  function avanzar() {
    var tramo = null;
    for (var i = 0; i < tramos.length; i++) {
      if (progreso < tramos[i].hasta) { tramo = tramos[i]; break; }
    }
    if (!tramo) return;
    progreso++;
    $('#descarga-barra').css('width', progreso + '%');
    $('#descarga-estado').text('Procesando... ' + progreso + '%');
    setTimeout(avanzar, tramo.pausa);
  }
  avanzar();

  // Iniciar descarga
  window.location = fullUrl;

  // Polling de cookie
  var cookieName = 'dl_token_' + token;
  var intentos = 0;
  var poll = setInterval(function () {
    intentos++;
    if (document.cookie.indexOf(cookieName + '=1') !== -1 || intentos > 120) {
      clearInterval(poll);
      document.cookie = cookieName + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/';
      $('#descarga-barra')
        .css('width', '100%')
        .removeClass('progress-bar-animated progress-bar-striped');
      $('#descarga-estado').text('¡Listo! Descarga iniciada.');
      setTimeout(function () { $('#descarga-overlay').fadeOut(400); }, 900);
    }
  }, 500);
}

$(document).on('click', '.descarga-link', function (e) {
  e.preventDefault();
  iniciarDescarga(
    $(this).attr('href'),
    $(this).data('titulo'),
    $(this).data('subtitulo')
  );
});

$(function () {

  // Buscador del panel izquierdo
  $('#buscar-tabla').on('input', function () {
    var q = $(this).val().toLowerCase().trim();
    var visible = 0;
    $('.tabla-item').each(function () {
      var nombre = $(this).find('.tabla-item-nombre').text().toLowerCase();
      var match  = !q || nombre.indexOf(q) !== -1;
      $(this).toggle(match);
      if (match) visible++;
    });
  });

  $(document).on('click', '.tabla-item', function () {
    mostrarTabla($(this).data('idx'));
  });
});
</script>

</body>
</html>
