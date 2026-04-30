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
registrarNavegacion('AUDITORIA Y CONFIGURACION');
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta name="robots" content="noindex">
  <meta name="googlebot" content="noindex">
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ADMINTECH | Auditoria &amp; Configuracion</title>

  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="../05-plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../05-plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="../05-plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="../05-plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../dist/css/custom.css">
  <script src='../05-plugins/pdfmake/pdfmake.min.js'></script>
  <script src='../05-plugins/pdfmake/vfs_fonts.js'></script>
</head>
<body class="hold-transition sidebar-collapse layout-navbar-fixed">
<div class="wrapper">

  <?php include '../01-views/layout/navbar_layout.php';?>

  <div class="content-wrapper" style="display: grid;">
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-8">
            <h1><strong>Auditoria &amp; Configuraci&oacute;n</strong></h1>
          </div>
        </div>
      </div>
    </section>

    <section class="content">
      <div class="col-10 offset-1 row justify-content-center">

        <div class="col-12 col-sm-6 col-md-4">
          <a href="../01-views/auditoria.php">
            <div class="info-box">
              <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-user-secret"></i></span>
              <div class="info-box-content">
                <h3 class="info-box-text d-flex align-items-center">Auditoria</h3>
              </div>
            </div>
          </a>
        </div>

        <div class="col-12 col-sm-6 col-md-4">
          <a href="../01-views/configuracion_mail_presupuestos.php">
            <div class="info-box">
              <span class="info-box-icon bg-secondary elevation-1"><i class="fas fa-envelope-open-text"></i></span>
              <div class="info-box-content">
                <h3 class="info-box-text d-flex align-items-center">Mail presupuestos</h3>
              </div>
            </div>
          </a>
        </div>

      </div>
    </section>
  </div>

  <?php include '../01-views/layout/footer_layout.php';?>

</div>

<script src="../05-plugins/jquery/jquery.min.js"></script>
<script src="../05-plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../05-plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../05-plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="../05-plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="../05-plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="../05-plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="../05-plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
<script src="../05-plugins/jszip/jszip.min.js"></script>
<script src="../05-plugins/pdfmake/pdfmake.min.js"></script>
<script src="../05-plugins/pdfmake/vfs_fonts.js"></script>
<script src="../05-plugins/datatables-buttons/js/buttons.html5.min.js"></script>
<script src="../05-plugins/datatables-buttons/js/buttons.print.min.js"></script>
<script src="../05-plugins/datatables-buttons/js/buttons.colVis.min.js"></script>
<script src="../dist/js/adminlte.min.js"></script>
<script src="../07-funciones_js/funciones.js"></script>
</body>
</html>
