<?php
include_once __DIR__ . '/../../04-modelo/ordenCompraWorkflowModel.php';

if(!isset($_SESSION["usuario"])){echo "<script type='text/javascript'>  window.location='../01-views/login.php'; </script>";} 
$usuario = $_SESSION["usuario"];
$usuarioIdNavbar = (int) ($usuario['id_usuario'] ?? 0);

if ($usuarioIdNavbar > 0 && function_exists('conectaDB')) {
    $dbNavbar = conectaDB();
    if ($dbNavbar) {
        $dbNavbar->set_charset('utf8mb4');
        $resultadoNavbar = $dbNavbar->query("
            SELECT nombres, apellidos, perfil
            FROM usuarios
            WHERE id_usuario = " . $usuarioIdNavbar . "
            LIMIT 1
        ");

        if ($resultadoNavbar && $usuarioActualizadoNavbar = mysqli_fetch_assoc($resultadoNavbar)) {
            $usuario = array_merge($usuario, $usuarioActualizadoNavbar);
            $_SESSION["usuario"] = array_merge($_SESSION["usuario"], $usuarioActualizadoNavbar);
            // Si hay disfraz activo, la BD devuelve el perfil real — restaurar el perfil del disfraz
            if (isset($_SESSION['disfraz']['activo']) && $_SESSION['disfraz']['activo']) {
                $usuario['perfil'] = $_SESSION['disfraz']['perfil_disfraz'];
                $_SESSION['usuario']['perfil'] = $_SESSION['disfraz']['perfil_disfraz'];
            }
        }

        if ($resultadoNavbar) {
            mysqli_free_result($resultadoNavbar);
        }

        mysqli_close($dbNavbar);
    }
}

if (!function_exists('navbarTextoSeguro')) {
    function navbarTextoSeguro($valor) {
        $texto = (string) ($valor ?? '');

        if ($texto !== '' && function_exists('mb_check_encoding') && !mb_check_encoding($texto, 'UTF-8')) {
            $texto = mb_convert_encoding($texto, 'UTF-8', 'ISO-8859-1');
        }

        return htmlspecialchars($texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

$nombreCompletoNavbar = trim(($usuario['nombres'] ?? '') . ' ' . ($usuario['apellidos'] ?? ''));
$disfrazNavbarActivo  = isset($_SESSION['disfraz']['activo']) && $_SESSION['disfraz']['activo'];
$perfilNavbar         = $disfrazNavbarActivo
    ? ($_SESSION['disfraz']['perfil_original'] ?? ($usuario['perfil'] ?? ''))
    : ($usuario['perfil'] ?? '');
$perfilDisfrazNavbar  = $disfrazNavbarActivo ? ($_SESSION['disfraz']['perfil_disfraz'] ?? '') : null;
$perfil = $usuario['perfil'];

// Arrays de perfiles que se usan en el panel para controlar la visualización
$agentes = array('Super Administrador','Administrador','Administrativo');
$novedades = array('Super Administrador','Administrador','Administrativo');
$clientes = array('Super Administrador','Administrador','Administrativo');
$presupuestos = array('Super Administrador','Administrador','Administrativo','Técnico','Tecnico Administrativo');
$materiales = array('Super Administrador','Administrador', 'Técnico','Tecnico Administrativo');
$obras = array('Super Administrador','Administrador','Administrativo');
$AEO = array('Super Administrador','Administrador','Administrativo','Tecnico Administrativo');
$ocPendientesSeguimientoNavbar = perfilPuedeAccederSoloOrdenCompra($perfil) ? contarOrdenesCompraPendientes() : 0;
$ocHabilitadasBandejaAdministrativaNavbar = perfilPuedeAccederSoloOrdenCompra($perfil)
    ? contarOrdenesCompraHabilitadasBandejaAdministrativa()
    : 0;
$mostrarSeguimientoNavbar = in_array($perfil, $presupuestos, true)
    && (!perfilPuedeAccederSoloOrdenCompra($perfil) || $ocHabilitadasBandejaAdministrativaNavbar > 0);
$tituloSeguimientoNavbar = perfilPuedeAccederSoloOrdenCompra($perfil) ? '&Oacute;rdenes de compra' : 'Seguimiento de obra';
$iconoSeguimientoNavbar = perfilPuedeAccederSoloOrdenCompra($perfil) ? 'fa-solid fa-file-invoice' : 'fa-solid fa-warehouse';
?>
<script>
  window.ACTIVE_USER_ID = <?= $usuarioIdNavbar ?>;
</script>

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

        <?php if ($mostrarSeguimientoNavbar){ ?>
        <li class="nav-item">
            <a href="../01-views/seguimiento_de_obra_listado.php" class="nav-link custom-button bg-success" data-toggle="tooltip" title="<?php echo $tituloSeguimientoNavbar; ?>">
                <i class="<?php echo $iconoSeguimientoNavbar; ?>"></i>
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

        <?php if ($perfil === 'Super Administrador'): ?>
        <li class="nav-item">
            <a href="../01-views/auditoria_configuracion.php" class="nav-link custom-button bg-dark" data-toggle="tooltip" title="Super Administrador">
                <i class="fas fa-user-shield"></i>
            </a>
        </li>
        <?php endif; ?>

    </ul>

    <!-- Perfil del usuario y logout a la derecha -->
    <ul class="navbar-nav ml-auto">
        <li class="nav-item">
            <a class="nav-link">
                <i class="fas fa-user-circle fa-2x"></i>
            </a>
        </li>
        <li class="nav-item mr-1 mt-2 mb-1 v-li-he">
            <span class="nombre-completo"><?php echo navbarTextoSeguro($nombreCompletoNavbar); ?></span>
            <small class="perfil-usuario"><?php echo navbarTextoSeguro($perfilNavbar); ?></small>
            <?php if ($disfrazNavbarActivo && $perfilDisfrazNavbar): ?>
            <small style="color:#ff6b6b;font-weight:700;display:block;line-height:1.2;">
                Disfrazado como: <?php echo navbarTextoSeguro($perfilDisfrazNavbar); ?>
            </small>
            <?php endif; ?>
        </li>
        <?php if ($disfrazNavbarActivo): ?>
        <li class="nav-item">
            <form method="POST" action="../03-controller/disfrazController.php" style="margin:0;display:inline;">
                <input type="hidden" name="accion" value="quitar">
                <button type="submit" class="nav-link btn btn-link p-0" style="color:#ff6b6b;" data-toggle="tooltip" title="Quitar disfraz">
                    <i class="fas fa-user-slash fa-2x"></i>
                </button>
            </form>
        </li>
        <?php endif; ?>
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
