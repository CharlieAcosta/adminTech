<?php  
session_start();
define('BASE_URL', $_SESSION["base_url"]);
include_once '../06-funciones_php/funciones.php'; //funciones últiles
sesion(); //Verifica si hay usuario sesionado

include_once '../06-funciones_php/auditoria.php';
registrarNavegacion('PANEL');

controlarDepuracion();

$perfil = $_SESSION['usuario']['perfil'];
$vencidas = countColWhere('previsitas', array('columna' => 'estado_visita', 'condicion' => '=', 'valor' => "Vencida"), false);
$programadas = countColWhere('previsitas', array('columna' => 'estado_visita', 'condicion' => '=', 'valor' => "Programada"), false);
$reprogramadas = countColWhere('previsitas', array('columna' => 'estado_visita', 'condicion' => '=', 'valor' => "Reprogramada"), false);


$agentes = array('Super Administrador','Administrador','Administrativo');
$novedades = array('Super Administrador','Administrador','Administrativo');
$clientes = array('Super Administrador','Administrador','Administrativo');
$presupuestos = array('Super Administrador','Administrador','Administrativo','Técnico','Tecnico Administrativo');
$materiales = array('Super Administrador','Administrador', 'Técnico','Tecnico Administrativo');
$obras = array('Super Administrador','Administrador','Administrativo');
$AEO = array('Super Administrador','Administrador','Administrativo','Tecnico Administrativo');
$tipoJornales = array('Super Administrador','Administrador','Administrativo');
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta name="robots" content="noindex">
  <meta name="googlebot" content="noindex">
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ADMINTECH | Módulos</title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="../05-plugins/fontawesome-free/css/all.min.css">
  <!-- DataTables -->
  <link rel="stylesheet" href="../05-plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="../05-plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="../05-plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../dist/css/custom.css">
  <script src='../05-plugins/pdfmake/pdfmake.min.js'></script>
  <script src='../05-plugins/pdfmake/vfs_fonts.js'></script>
</head>
<body class="hold-transition sidebar-collapse layout-navbar-fixed">
<div class="wrapper">

  <!-- Navbar -->

  <!-- /.navbar -->
  <?php include '../01-views/layout/navbar_layout.php';?> 
  <!-- Main Sidebar Container -->

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper" style="display: grid;">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1><strong>Módulos</strong></h1>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
        <!-- boxes -->
    <div class=" col-10 offset-1 row justify-content-center">

        
         <?php if (in_array($perfil, $agentes)){ ?>
         <!-- /.info-box -->
          <div class="col-12 col-sm-6 col-md-4">
            <a href="../01-views/listado_personal.php">
               <div class="info-box">
                  <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-users"></i></span>
                  <!-- .info-box-content -->
                  <div class="info-box-content">
                    <h2 class="info-box-text">Agentes</h2>
                  </div>
                <!-- /.info-box-content -->
              </div>
            </a>
          </div>
          <!-- /.info-box -->
          <?php } ?>

         <?php if (in_array($perfil, $novedades)){ ?>
         <!-- /.info-box -->
          <div class="col-12 col-sm-6 col-md-4">
            <a href="../01-views/novedades_listado.php">
               <div class="info-box">
                  <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-calendar-check"></i></span>
                  <!-- .info-box-content -->
                  <div class="info-box-content">
                    <h2 class="info-box-text">Novedades</h2>
                  </div>
                <!-- /.info-box-content -->
              </div>
            </a>
          </div>
          <!-- /.info-box --> 
          <?php } ?>


          <?php if (in_array($perfil, $clientes)){ ?>
          <!-- /.info-box -->
          <div class="col-12 col-sm-6 col-md-4">
            <a href="../01-views/clientes_listado.php">
               <div class="info-box">
                  <span class="info-box-icon bg-info elevation-1"><i class="fas fa-handshake"></i></span>
                  <!-- .info-box-content -->
                  <div class="info-box-content">
                    <h2 class="info-box-text">Clientes</h2>
                  </div>
                <!-- /.info-box-content -->
              </div>
            </a>
          </div>
          <!-- /.info-box --> 
          <?php } ?>

         <?php if (in_array($perfil, $presupuestos)){ ?>
         <!-- /.info-box -->
          <div class="col-12 col-sm-6 col-md-4">
            <a href="../01-views/seguimiento_de_obra_listado.php">
               <div class="info-box">
                  <span class="info-box-icon bg-success elevation-1"><i class="fa-solid fa-warehouse"></i></span>
                  <!-- .info-box-content -->
                  <div class="info-box-content">
                    <h2 class="info-box-text d-flex align-items-center">Seguimiento de obra  
                      
                      <?php if ($vencidas !== false && $vencidas['total'] > 0){ ?>
                      <small><span class="right badge badge-danger ml-3 mt-2"><?php echo $vencidas['total'].'<small><small><br> Venc. </small></small>'; ?></span></small>
                      <?php } ?>

                      <?php if ($programadas !== false && $programadas['total'] > 0){ ?>
                      <small><span class="right badge badge-info ml-2 mt-2"><?php echo $programadas['total'].'<small><small><br> Prog. </small></small>'; ?></span></small>
                      <?php } ?>

                      <?php if ($reprogramadas !== false && $reprogramadas['total'] > 0){ ?>
                      <small><span class="right badge badge-primary ml-2 mt-2"><?php echo $reprogramadas['total'].'<small><small><br> Repr. </small></small>'; ?></span></small>
                      <?php } ?>
                  </h2>  
                  </div>
                <!-- /.info-box-content -->
              </div>
            </a>
          </div>
          <!-- /.info-box -->
          <?php } ?>

         <?php if (in_array($perfil, $materiales)){ ?>
         <!-- /.info-box -->
          <div class="col-12 col-sm-6 col-md-4">
            <a href="../01-views/materiales_listado.php">
               <div class="info-box">
                  <span class="info-box-icon bg-secondary elevation-1"><i class="fa-solid fa-dolly"></i></span>
                  <!-- .info-box-content -->
                  <div class="info-box-content">
                    <h2 class="info-box-text d-flex align-items-center">Materiales</h2>  
                  </div>
                <!-- /.info-box-content -->
              </div>
            </a>
          </div>
          <!-- /.info-box -->
          <?php } ?>

         <?php if (in_array($perfil, $obras)){ ?>
         <!-- /.info-box -->
          <div class="col-12 col-sm-6 col-md-4">
            <a href="../01-views/obras_listado.php">
               <div class="info-box">
                  <span class="info-box-icon bg-primary elevation-1"><i class="fa-solid fa-helmet-safety"></i></span>
                  <!-- .info-box-content -->
                  <div class="info-box-content">
                    <h2 class="info-box-text d-flex align-items-center">Obras</h2>  
                  </div>
                <!-- /.info-box-content -->
              </div>
            </a>
          </div>
          <!-- /.info-box -->
          <?php } ?>   

         <?php if (in_array($perfil, $AEO)){ ?>
         <!-- /.info-box -->
          <div class="col-12 col-sm-6 col-md-4">
            <a href="../01-views/aeo_listado.php">
               <div class="info-box">
                  <span class="info-box-icon v-bg-magenta elevation-1"><i class="fa-solid fa-people-roof"></i></span>
                  <!-- .info-box-content -->
                  <div class="info-box-content">
                    <h2 class="info-box-text d-flex align-items-center">AEO - Asistencia en obras</h2>  
                  </div>
                <!-- /.info-box-content -->
              </div>
            </a>
          </div>
          <!-- /.info-box -->
          <?php } ?> 

          <?php if (in_array($perfil, $tipoJornales)){ ?>
         <!-- /.info-box -->
          <div class="col-12 col-sm-6 col-md-4">
            <a href="../01-views/jornales_listado.php">
               <div class="info-box">
                  <span class="info-box-icon v-bg-verde-oscuro elevation-1"><i class="fa-solid fa-sack-dollar"></i></span>
                  <!-- .info-box-content -->
                  <div class="info-box-content">
                    <h2 class="info-box-text d-flex align-items-center">Tipos de Jornales</h2>  
                  </div>
                <!-- /.info-box-content -->
              </div>
            </a>
          </div>
          <!-- /.info-box -->
          <?php } ?>           

    </div>
        <!-- /.row -->
    </section>
    <!-- /.content -->

  </div>
  <!-- /.content-wrapper -->
  <?php include '../01-views/layout/footer_layout.php';?>

  <!-- Control Sidebar -->
  <aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
  </aside>
  <!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="../05-plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="../05-plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- DataTables  & Plugins -->
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
<!-- AdminLTE App -->
<script src="../dist/js/adminlte.min.js"></script>
<!-- Page specific script -->

</body>
</html>
<script src="../07-funciones_js/usuariosAcciones.js"></script>


