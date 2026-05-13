<?php
session_start();
define('BASE_URL', $_SESSION["base_url"]);
include_once '../06-funciones_php/funciones.php';
sesion();

if (($_SESSION['usuario']['perfil'] ?? '') !== 'Super Administrador') {
    echo "<script type='text/javascript'>window.location='../01-views/panel.php';</script>";
    exit;
}

include_once '../06-funciones_php/auditoria.php';
include_once '../04-modelo/migracionesModel.php';
registrarNavegacion('MIGRACIONES');

// ---------------------------------------------------------------------------
// Detecta si el SQL de una migración ya está aplicado en la BD
// Retorna: 'ejecutada' | 'pendiente' | 'sin_verificar'
// ---------------------------------------------------------------------------
function detectarEstadoMigracion($sqlContent, $conn) {
    // Eliminar comentarios SQL
    $sql = preg_replace('/--[^\n]*/', ' ', $sqlContent);
    $sql = preg_replace('/\/\*.*?\*\//s', ' ', $sql);

    $checks = [];

    // CREATE TABLE [IF NOT EXISTS] `tabla`
    if (preg_match_all('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?[`"]?(\w+)[`"]?/i', $sql, $m)) {
        foreach ($m[1] as $tabla) {
            $t   = $conn->real_escape_string($tabla);
            $res = $conn->query("SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$t'");
            if ($res && $row = $res->fetch_assoc()) $checks[] = (int)$row['c'] > 0;
        }
    }

    // DROP TABLE [IF EXISTS] `tabla`
    if (preg_match_all('/DROP\s+TABLE\s+(?:IF\s+EXISTS\s+)?[`"]?(\w+)[`"]?/i', $sql, $m)) {
        foreach ($m[1] as $tabla) {
            $t   = $conn->real_escape_string($tabla);
            $res = $conn->query("SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$t'");
            if ($res && $row = $res->fetch_assoc()) $checks[] = (int)$row['c'] === 0;
        }
    }

    // ALTER TABLE `tabla` ADD [COLUMN] `columna`
    if (preg_match_all('/ALTER\s+TABLE\s+[`"]?(\w+)[`"]?\s+ADD\s+(?:COLUMN\s+)?[`"]?(\w+)[`"]?/i', $sql, $m)) {
        for ($i = 0; $i < count($m[1]); $i++) {
            $t   = $conn->real_escape_string($m[1][$i]);
            $c   = $conn->real_escape_string($m[2][$i]);
            $res = $conn->query("SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$t' AND COLUMN_NAME = '$c'");
            if ($res && $row = $res->fetch_assoc()) $checks[] = (int)$row['c'] > 0;
        }
    }

    // ALTER TABLE `tabla` DROP [COLUMN] `columna`
    if (preg_match_all('/ALTER\s+TABLE\s+[`"]?(\w+)[`"]?\s+DROP\s+(?:COLUMN\s+)?[`"]?(\w+)[`"]?/i', $sql, $m)) {
        for ($i = 0; $i < count($m[1]); $i++) {
            $t   = $conn->real_escape_string($m[1][$i]);
            $c   = $conn->real_escape_string($m[2][$i]);
            $res = $conn->query("SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$t' AND COLUMN_NAME = '$c'");
            if ($res && $row = $res->fetch_assoc()) $checks[] = (int)$row['c'] === 0;
        }
    }

    // ALTER TABLE `tabla` ADD INDEX/KEY `nombre`
    if (preg_match_all('/ALTER\s+TABLE\s+[`"]?(\w+)[`"]?\s+ADD\s+(?:UNIQUE\s+)?(?:INDEX|KEY)\s+[`"]?(\w+)[`"]?/i', $sql, $m)) {
        for ($i = 0; $i < count($m[1]); $i++) {
            $t   = $conn->real_escape_string($m[1][$i]);
            $idx = $conn->real_escape_string($m[2][$i]);
            $res = $conn->query("SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$t' AND INDEX_NAME = '$idx'");
            if ($res && $row = $res->fetch_assoc()) $checks[] = (int)$row['c'] > 0;
        }
    }

    // CREATE [UNIQUE] INDEX `nombre` ON `tabla`
    if (preg_match_all('/CREATE\s+(?:UNIQUE\s+)?INDEX\s+[`"]?(\w+)[`"]?\s+ON\s+[`"]?(\w+)[`"]?/i', $sql, $m)) {
        for ($i = 0; $i < count($m[1]); $i++) {
            $idx = $conn->real_escape_string($m[1][$i]);
            $t   = $conn->real_escape_string($m[2][$i]);
            $res = $conn->query("SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$t' AND INDEX_NAME = '$idx'");
            if ($res && $row = $res->fetch_assoc()) $checks[] = (int)$row['c'] > 0;
        }
    }

    if (empty($checks)) return 'sin_verificar';
    return in_array(false, $checks, true) ? 'pendiente' : 'ejecutada';
}

// ---------------------------------------------------------------------------
$conn = conectaDB();

// Cargar migraciones ya registradas
$ejecutadas = obtenerMigracionesRegistradas($conn);

// Escanear archivos
$dirMigraciones = '../11-migraciones_sql/';
$archivos = glob($dirMigraciones . '*.sql');
rsort($archivos);

// Auto-detección para las pendientes
$autoDetectadas = 0;
foreach ($archivos as $ruta) {
    $nombre = basename($ruta);
    if (isset($ejecutadas[$nombre])) continue; // ya registrada

    $sqlContent = file_get_contents($ruta);
    $resultado  = detectarEstadoMigracion($sqlContent, $conn);

    if ($resultado === 'ejecutada') {
        registrarMigracionAutoDetectada($conn, $nombre);

        $ejecutadas[$nombre] = [
            'estado'              => 'OK',
            'ejecutada_por_email' => 'auto-detectada',
            'fecha_ejecucion'     => date('Y-m-d H:i:s'),
            'error_mensaje'       => null,
        ];
        $autoDetectadas++;
    } elseif ($resultado === 'sin_verificar') {
        // Marcar para mostrar badge especial sin registrar en tabla
        $ejecutadas[$nombre . '__sin_verificar'] = true;
    }
}

mysqli_close($conn);

// Contadores
$totalPendientes   = 0;
$totalSinVerificar = 0;
foreach ($archivos as $ruta) {
    $nombre = basename($ruta);
    if (!isset($ejecutadas[$nombre])) {
        if (isset($ejecutadas[$nombre . '__sin_verificar'])) {
            $totalSinVerificar++;
        } else {
            $totalPendientes++;
        }
    }
}

// Flash message
$flash = $_SESSION['mig_flash'] ?? null;
unset($_SESSION['mig_flash']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta name="robots" content="noindex">
  <meta name="googlebot" content="noindex">
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ADMINTECH | Migraciones</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="../05-plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../dist/css/custom.css">
  <style>
    .fila-pendiente    { background-color: #fff8e1; }
    .fila-sin-verif    { background-color: #f5f5f5; }
    .fila-error        { background-color: #fdecea; }
    .fila-ok           { background-color: #f1f8f1; }
    .badge-estado      { font-size: .78rem; padding: 4px 8px; }
    .btns-accion       { display: none; }
    .switch-label      { font-size: .95rem; font-weight: 600; }
  </style>
</head>
<body class="hold-transition sidebar-collapse layout-navbar-fixed">
<div class="wrapper">

  <?php include '../01-views/layout/navbar_layout.php'; ?>

  <div class="content-wrapper" style="display:grid;">
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2 align-items-center">
          <div class="col-sm-8">
            <h1><strong>Migraciones SQL</strong></h1>
            <p class="text-muted mb-0">
              Estado de migraciones en este ambiente.
              <?php if ($totalPendientes > 0): ?>
                <span class="badge badge-warning ml-1"><?= $totalPendientes ?> pendiente<?= $totalPendientes > 1 ? 's' : '' ?></span>
              <?php endif; ?>
              <?php if ($totalSinVerificar > 0): ?>
                <span class="badge badge-secondary ml-1"><?= $totalSinVerificar ?> sin verificar</span>
              <?php endif; ?>
              <?php if ($totalPendientes === 0 && $totalSinVerificar === 0): ?>
                <span class="badge badge-success ml-1">Todo ejecutado</span>
              <?php endif; ?>
              <?php if ($autoDetectadas > 0): ?>
                <span class="badge badge-info ml-1"><?= $autoDetectadas ?> auto-detectada<?= $autoDetectadas > 1 ? 's' : '' ?></span>
              <?php endif; ?>
            </p>
          </div>
          <div class="col-sm-4 text-right">
            <a href="../01-views/auditoria_configuracion.php" class="btn btn-sm btn-outline-secondary">
              <i class="fas fa-arrow-left mr-1"></i> Volver
            </a>
          </div>
        </div>
      </div>
    </section>

    <section class="content">
      <div class="container-fluid">

        <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['tipo'], ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show">
          <?= htmlspecialchars($flash['mensaje'], ENT_QUOTES, 'UTF-8') ?>
          <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
        <?php endif; ?>

        <!-- Aviso general -->
        <div class="alert alert-secondary py-2 mb-3" style="font-size:.88rem;">
          <i class="fas fa-shield-alt text-danger mr-2"></i>
          Cada migración tiene su propio switch de seguridad. Activalo individualmente antes de ejecutar.
        </div>

        <!-- Leyenda -->
        <div class="mb-3 d-flex flex-wrap" style="gap:10px;font-size:.82rem;">
          <span><span class="badge badge-success badge-estado">Ejecutada</span> registrada por el panel</span>
          <span><span class="badge badge-info badge-estado">Auto-detectada</span> encontrada en la BD automáticamente</span>
          <span><span class="badge badge-warning badge-estado">Pendiente</span> no aplicada en la BD</span>
          <span><span class="badge badge-secondary badge-estado">Sin verificar</span> sin DDL checkeable (INSERT/UPDATE)</span>
          <span><span class="badge badge-danger badge-estado">Error</span> se ejecutó con error</span>
        </div>

        <!-- Tabla -->
        <div class="card">
          <div class="card-header bg-dark text-white">
            <i class="fas fa-database mr-2"></i>
            <strong>Archivos en <code style="color:#adb5bd;">11-migraciones_sql/</code></strong>
          </div>
          <div class="card-body p-0">
            <?php if (empty($archivos)): ?>
            <p class="text-muted p-3 mb-0">No hay archivos .sql en el directorio de migraciones.</p>
            <?php else: ?>
            <table class="table table-bordered table-sm mb-0" style="font-size:.88rem;">
              <thead class="thead-dark">
                <tr>
                  <th style="width:44%;">Archivo</th>
                  <th style="width:13%;" class="text-center">Estado</th>
                  <th style="width:14%;" class="text-center">Fecha ejecución</th>
                  <th style="width:15%;" class="text-center">Ejecutado por</th>
                  <th style="width:14%;" class="text-center">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($archivos as $ruta):
                  $nombre      = basename($ruta);
                  $exec        = $ejecutadas[$nombre] ?? null;
                  $sinVerif    = !$exec && isset($ejecutadas[$nombre . '__sin_verificar']);
                  $esAutodet   = $exec && ($exec['ejecutada_por_email'] === 'auto-detectada');

                  if (!$exec && !$sinVerif) $clsFila = 'fila-pendiente';
                  elseif ($sinVerif)         $clsFila = 'fila-sin-verif';
                  elseif ($exec['estado'] === 'ERROR') $clsFila = 'fila-error';
                  else                       $clsFila = 'fila-ok';
                ?>
                <tr class="<?= $clsFila ?>">
                  <td class="align-middle">
                    <i class="fas fa-file-code text-secondary mr-1"></i>
                    <code><?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?></code>
                    <button type="button" class="btn btn-link btn-sm p-0 ml-2 ver-codigo"
                            data-nombre="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>"
                            data-sql="<?= htmlspecialchars(file_get_contents($ruta), ENT_QUOTES, 'UTF-8') ?>"
                            style="font-size:.78rem;">
                      <i class="fas fa-eye mr-1"></i>Ver código
                    </button>
                    <?php if ($exec && $exec['error_mensaje']): ?>
                    <br><small class="text-danger"><i class="fas fa-exclamation-triangle mr-1"></i><?= htmlspecialchars($exec['error_mensaje'], ENT_QUOTES, 'UTF-8') ?></small>
                    <?php endif; ?>
                  </td>
                  <td class="align-middle text-center">
                    <?php if (!$exec && !$sinVerif): ?>
                      <span class="badge badge-warning badge-estado">Pendiente</span>
                    <?php elseif ($sinVerif): ?>
                      <span class="badge badge-secondary badge-estado">Sin verificar</span>
                    <?php elseif ($exec['estado'] === 'ERROR'): ?>
                      <span class="badge badge-danger badge-estado">Error</span>
                    <?php elseif ($esAutodet): ?>
                      <span class="badge badge-info badge-estado">Auto-detectada</span>
                    <?php else: ?>
                      <span class="badge badge-success badge-estado">Ejecutada</span>
                    <?php endif; ?>
                  </td>
                  <td class="align-middle text-center">
                    <?= $exec ? date('d/m/Y H:i', strtotime($exec['fecha_ejecucion'])) : '—' ?>
                  </td>
                  <td class="align-middle text-center">
                    <?php
                      $por = $exec['ejecutada_por_email'] ?? '—';
                      echo $por === 'auto-detectada'
                        ? '<em class="text-info" style="font-size:.8rem;">auto-detectada</em>'
                        : htmlspecialchars($por, ENT_QUOTES, 'UTF-8');
                    ?>
                  </td>
                  <td class="align-middle text-center">
                    <?php
                      $switchId = 'sw_' . md5($nombre);
                      if (!$exec || $exec['estado'] === 'ERROR'):
                    ?>
                    <div class="custom-control custom-switch mb-2">
                      <input type="checkbox" class="custom-control-input switch-fila" id="<?= $switchId ?>" data-target="btns_<?= $switchId ?>">
                      <label class="custom-control-label" for="<?= $switchId ?>" style="font-size:.78rem;cursor:pointer;">Habilitar</label>
                    </div>
                    <div id="btns_<?= $switchId ?>" style="display:none;">
                      <form method="POST" action="../03-controller/migracionesController.php" class="d-block mb-1" onsubmit="return confirmarEjecucion(this)">
                        <input type="hidden" name="accion" value="ejecutar">
                        <input type="hidden" name="archivo" value="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="btn btn-danger btn-sm btn-block">
                          <i class="fas fa-play mr-1"></i>Ejecutar
                        </button>
                      </form>
                      <form method="POST" action="../03-controller/migracionesController.php" class="d-block">
                        <input type="hidden" name="accion" value="marcar">
                        <input type="hidden" name="archivo" value="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="btn btn-outline-secondary btn-sm btn-block">
                          <i class="fas fa-check mr-1"></i>Ya ejecutada
                        </button>
                      </form>
                    </div>
                    <?php elseif ($sinVerif): ?>
                    <div class="custom-control custom-switch mb-2">
                      <input type="checkbox" class="custom-control-input switch-fila" id="<?= $switchId ?>" data-target="btns_<?= $switchId ?>">
                      <label class="custom-control-label" for="<?= $switchId ?>" style="font-size:.78rem;cursor:pointer;">Habilitar</label>
                    </div>
                    <div id="btns_<?= $switchId ?>" style="display:none;">
                      <form method="POST" action="../03-controller/migracionesController.php" class="d-block">
                        <input type="hidden" name="accion" value="marcar">
                        <input type="hidden" name="archivo" value="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="btn btn-outline-secondary btn-sm btn-block">
                          <i class="fas fa-check mr-1"></i>Marcar ejecutada
                        </button>
                      </form>
                    </div>
                    <?php else: ?>
                      <span class="text-muted" style="font-size:.8rem;">—</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <?php endif; ?>
          </div>
        </div>

      </div>
    </section>
  </div>

  <?php include '../01-views/layout/footer_layout.php'; ?>

  <!-- Modal Ver código -->
  <div class="modal fade" id="modalCodigo" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
      <div class="modal-content">
        <div class="modal-header bg-dark text-white py-2">
          <h6 class="modal-title mb-0">
            <i class="fas fa-file-code mr-2"></i>
            <span id="modalCodigoNombre"></span>
          </h6>
          <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body p-0">
          <pre id="modalCodigoContenido" style="margin:0;padding:16px;font-size:.82rem;background:#1e1e1e;color:#d4d4d4;border-radius:0;max-height:70vh;overflow-y:auto;"></pre>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="../05-plugins/jquery/jquery.min.js"></script>
<script src="../05-plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../dist/js/adminlte.min.js"></script>
<script src="../07-funciones_js/funciones.js"></script>
<script>
  document.querySelectorAll('.ver-codigo').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.getElementById('modalCodigoNombre').textContent   = this.dataset.nombre;
      document.getElementById('modalCodigoContenido').textContent = this.dataset.sql;
      $('#modalCodigo').modal('show');
    });
  });

  document.querySelectorAll('.switch-fila').forEach(function (sw) {
    sw.addEventListener('change', function () {
      const target = document.getElementById(this.dataset.target);
      if (target) target.style.display = this.checked ? 'block' : 'none';
    });
  });

  function confirmarEjecucion(form) {
    const archivo = form.querySelector('[name="archivo"]').value;
    return confirm('¿Ejecutar la migración "' + archivo + '" contra la base de datos?\n\nEsta acción no se puede deshacer.');
  }
</script>
</body>
</html>
