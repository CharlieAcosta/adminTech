<?php
if(!isset($_SESSION["usuario"])){echo "<script type='text/javascript'>  window.location='../01-views/login.php'; </script>";} 
$usuario = $_SESSION["usuario"];
$perfil = $usuario['perfil'];

// Arrays de perfiles que se usan en el panel para controlar la visualización
$agentes = array('Super Administrador','Administrador','Administrativo');
$novedades = array('Super Administrador','Administrador','Administrativo');
$clientes = array('Super Administrador','Administrador','Administrativo');
$presupuestos = array('Super Administrador','Administrador','Administrativo','Técnico','Tecnico Administrativo');
$materiales = array('Super Administrador','Administrador', 'Técnico','Tecnico Administrativo');
$obras = array('Super Administrador','Administrador','Administrativo');
$AEO = array('Super Administrador','Administrador','Administrativo','Tecnico Administrativo');
$auditoria = array('Super Administrador');
?>

<nav class="main-header navbar navbar-expand navbar-dark navbar-navy">
    <!-- Ícono de ayuda a la izquierda con tooltip -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button" data-toggle="tooltip" title="Ayuda">
                <i class="fas fa-circle-question fa-lg"></i>
            </a>
        </li>
    </ul>

    <!-- Contenedor para centrar los botones -->
    <ul class="navbar-nav mx-auto">

        <?php if (in_array($perfil, $auditoria)){ ?>
        <li class="nav-item">
            <a href="../01-views/auditoria.php" class="nav-link custom-button bg-warning" data-toggle="tooltip" title="Auditoria">
                <i class="fas fa-user-secret"></i>
            </a>
        </li>
        <?php } ?>

        <!-- Botón para Módulos -->
        <li class="nav-item">
            <a href="../01-views/panel.php" class="nav-link custom-button bg-primary" data-toggle="tooltip" title="Módulos">
                <i class="fas fa-th"></i>
            </a>
        </li>

        <?php if (in_array($perfil, $agentes)){ ?>
        <li class="nav-item">
            <a href="../01-views/listado_personal.php" class="nav-link custom-button bg-warning" data-toggle="tooltip" title="Agentes">
                <i class="fas fa-users"></i>
            </a>
        </li>
        <?php } ?>

        <?php if (in_array($perfil, $novedades)){ ?>
        <li class="nav-item">
            <a href="../01-views/novedades_listado.php" class="nav-link custom-button bg-danger" data-toggle="tooltip" title="Novedades">
                <i class="fas fa-calendar-check"></i>
            </a>
        </li>
        <?php } ?>

        <?php if (in_array($perfil, $clientes)){ ?>
        <li class="nav-item">
            <a href="../01-views/clientes_listado.php" class="nav-link custom-button bg-info" data-toggle="tooltip" title="Clientes">
                <i class="fas fa-handshake"></i>
            </a>
        </li>
        <?php } ?>

        <?php if (in_array($perfil, $presupuestos)){ ?>
        <li class="nav-item">
            <a href="../01-views/seguimiento_de_obra_listado.php" class="nav-link custom-button bg-success" data-toggle="tooltip" title="Seguimiento de obra">
                <i class="fa-solid fa-warehouse"></i>
            </a>
        </li>
        <?php } ?>

        <?php if (in_array($perfil, $materiales)){ ?>
        <li class="nav-item">
            <a href="../01-views/materiales_listado.php" class="nav-link custom-button bg-secondary" data-toggle="tooltip" title="Materiales">
                <i class="fa-solid fa-dolly"></i>
            </a>
        </li>
        <?php } ?>

        <?php if (in_array($perfil, $obras)){ ?>
        <li class="nav-item">
            <a href="../01-views/obras_listado.php" class="nav-link custom-button bg-primary" data-toggle="tooltip" title="Obras">
                <i class="fa-solid fa-helmet-safety"></i>
            </a>
        </li>
        <?php } ?>

        <?php if (in_array($perfil, $AEO)){ ?>
        <li class="nav-item">
            <a href="../01-views/aeo_listado.php" class="nav-link custom-button v-bg-magenta" data-toggle="tooltip" title="AEO - Asistencia en obras">
                <i class="fa-solid fa-people-roof"></i>
            </a>
        </li>
        <?php } ?>

        <?php if (in_array($perfil, $AEO)){ ?>
        <li class="nav-item">
            <a href="../01-views/jornales_listado.php" class="nav-link custom-button v-bg-verde-oscuro" data-toggle="tooltip" title="Tipos de jornales">
                <i class="fa-solid fa-sack-dollar"></i>
            </a>
        </li>
        <?php } ?>


    </ul>

    <!-- Perfil del usuario y logout a la derecha -->
    <ul class="navbar-nav ml-auto">
        <li class="nav-item">
            <a class="nav-link">
                <i class="fas fa-user-circle fa-2x"></i>
            </a>
        </li>
        <li class="nav-item mr-1 mt-2 mb-1 v-li-he">
            <span class="nombre-completo"><?php echo $usuario['nombres'] . ' ' . $usuario['apellidos']; ?></span>
            <small class="perfil-usuario"><?php echo $usuario['perfil']; ?></small>
        </li>
        <li class="nav-item">
            <a href="../03-controller/logout.php" class="nav-link" data-toggle="tooltip" title="Cerrar sesión">
                <i class="fas fa-sign-out-alt fa-2x"></i>
            </a>
        </li>
    </ul>
</nav>

<aside class="main-sidebar elevation-0 bg-yellow-soft">
  <div class="ayuda text-center text-primary p-3 mr-4"></div>
</aside>  
<!-- <div class="ayuda text-center"></div> -->

