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
registrarNavegacion('SIMULADOR DE ACCESO');

$disfrazActivo   = isset($_SESSION['disfraz']['activo']) && $_SESSION['disfraz']['activo'];
$perfilDisfrazActual = $disfrazActivo ? ($_SESSION['disfraz']['perfil_disfraz'] ?? '') : '';

$perfilesSistema = [
    [
        'nombre'      => 'Administrador',
        'icono'       => 'fas fa-user-tie',
        'color'       => 'primary',
        'descripcion' => 'Agentes, Novedades, Clientes, Presupuestos, Materiales, Obras, AEO y Jornales.',
    ],
    [
        'nombre'      => 'Administrativo',
        'icono'       => 'fas fa-file-invoice',
        'color'       => 'warning',
        'descripcion' => 'Acceso exclusivo a Órdenes de Compra.',
    ],
    [
        'nombre'      => 'Técnico',
        'icono'       => 'fas fa-helmet-safety',
        'color'       => 'success',
        'descripcion' => 'Presupuestos y Materiales.',
    ],
    [
        'nombre'      => 'Tecnico Administrativo',
        'icono'       => 'fas fa-tools',
        'color'       => 'info',
        'descripcion' => 'Presupuestos, Materiales, Seguimiento de obra, AEO y Jornales.',
    ],
    [
        'nombre'      => 'Operario',
        'icono'       => 'fas fa-hard-hat',
        'color'       => 'secondary',
        'descripcion' => 'Sin módulos asignados actualmente.',
    ],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta name="robots" content="noindex">
  <meta name="googlebot" content="noindex">
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ADMINTECH | Simulador de acceso</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="../05-plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../dist/css/custom.css">
  <style>
    .perfil-card { transition: box-shadow .2s; }
    .perfil-card:hover { box-shadow: 0 4px 18px rgba(0,0,0,.18); }
    .perfil-card.activo { border: 2px solid #dc3545; }
    .badge-disfraz-activo {
      background: #dc3545;
      color: #fff;
      font-size: .72rem;
      padding: 2px 7px;
      border-radius: 3px;
      vertical-align: middle;
    }
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
            <h1><strong>Simulador de acceso</strong></h1>
            <p class="text-muted mb-0">Seleccioná un perfil para visualizar el sistema como lo vería ese rol.</p>
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

        <?php if ($disfrazActivo): ?>
        <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
          <i class="fas fa-user-secret fa-lg mr-3"></i>
          <div>
            <strong>Disfraz activo:</strong> estás viendo el sistema como <strong><?= htmlspecialchars($perfilDisfrazActual, ENT_QUOTES, 'UTF-8') ?></strong>.
            Podés activar otro disfraz desde acá o quitarlo con el botón en la barra superior.
          </div>
        </div>
        <?php endif; ?>

        <div class="row">
          <?php foreach ($perfilesSistema as $p):
            $esActivo = ($disfrazActivo && $perfilDisfrazActual === $p['nombre']);
          ?>
          <div class="col-12 col-sm-6 col-md-4 col-lg-3 mb-3">
            <div class="card perfil-card h-100 <?= $esActivo ? 'activo' : '' ?>" style="font-size:.88rem;display:flex;flex-direction:column;">
              <div class="card-header bg-<?= $p['color'] ?> text-white d-flex align-items-center py-2 px-3">
                <i class="<?= $p['icono'] ?> mr-2"></i>
                <strong><?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8') ?></strong>
                <?php if ($esActivo): ?>
                  <span class="badge-disfraz-activo ml-2">Activo</span>
                <?php endif; ?>
              </div>
              <div class="card-body py-2 px-3" style="flex:1 1 auto;">
                <p class="text-muted mb-0" style="font-size:.82rem;"><?= htmlspecialchars($p['descripcion'], ENT_QUOTES, 'UTF-8') ?></p>
              </div>
              <div class="card-footer bg-transparent" style="padding:8px 12px;">
                <form method="POST" action="../03-controller/disfrazController.php">
                  <input type="hidden" name="accion" value="activar">
                  <input type="hidden" name="perfil_disfraz" value="<?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8') ?>">
                  <button type="submit" class="btn btn-<?= $p['color'] ?> btn-block <?= $p['color'] === 'warning' ? 'text-dark' : '' ?>" style="height:38px;line-height:1;">
                    <i class="fas fa-user-secret mr-1"></i>
                    <?= $esActivo ? 'Reactivar' : 'Disfrazarse' ?>
                  </button>
                </form>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

      </div>
    </section>
  </div>

  <?php include '../01-views/layout/footer_layout.php'; ?>
</div>

<script src="../05-plugins/jquery/jquery.min.js"></script>
<script src="../05-plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../dist/js/adminlte.min.js"></script>
<script src="../07-funciones_js/funciones.js"></script>
</body>
</html>
