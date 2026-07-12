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
registrarNavegacion('CONFIGURACION MAIL PEDIDO MATERIALES');

require_once '../04-modelo/pedidoMaterialesConfigCorreoModel.php';

$idUsuarioSesion = (int)($_SESSION['usuario']['id_usuario'] ?? 0);
$flash = $_SESSION['pedido_materiales_mail_flash'] ?? null;
unset($_SESSION['pedido_materiales_mail_flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $accion = $_POST['accion_config_mail_pedido_materiales'] ?? '';
  $resultadoAccion = ['ok' => false, 'msg' => 'Accion no reconocida.'];

  if ($accion === 'guardar_config_mail_pedido_materiales') {
    $resultadoAccion = guardarConfiguracionCorreoPedidoMateriales($_POST, $idUsuarioSesion);
  }

  $_SESSION['pedido_materiales_mail_flash'] = $resultadoAccion;
  header('Location: configuracion_mail_pedido_materiales.php');
  exit;
}

$configCorreo = obtenerConfiguracionCorreoPedidoMateriales();
$puedeGuardarSecretosSmtp = runtimeCifradoMailPresupuestosDisponible() && hayClaveSecretaMailPresupuestos();

function escConfigCorreoPedidoMateriales($valor): string
{
  return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta name="robots" content="noindex">
  <meta name="googlebot" content="noindex">
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ADMINTECH | Configuracion mail pedido de materiales</title>

  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="../05-plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../dist/css/custom.css">
  <style>
    .password-toggle-wrap {
      position: relative;
    }

    .password-toggle-wrap .form-control {
      padding-right: 2.75rem;
    }

    .password-toggle-wrap__button {
      background: transparent;
      border: 0;
      color: #6c757d;
      cursor: pointer;
      padding: 0;
      position: absolute;
      right: .85rem;
      top: 50%;
      transform: translateY(-50%);
      z-index: 3;
    }

    .password-toggle-wrap__button:focus {
      color: #007bff;
      outline: 0;
    }

    .mail-config-eyebrow {
      color: #6c757d;
      font-size: .82rem;
      text-transform: uppercase;
      letter-spacing: .03em;
    }
  </style>
</head>
<body class="hold-transition sidebar-collapse layout-navbar-fixed">
<div class="wrapper">
  <?php include '../01-views/layout/navbar_layout.php';?>

  <div class="content-wrapper">
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-8">
            <h1><strong>Configuracion de correo | Pedido de Materiales</strong></h1>
          </div>
          <div class="col-sm-4 text-sm-right">
            <a href="../01-views/auditoria_configuracion.php" class="btn btn-outline-secondary btn-sm">
              <i class="fas fa-arrow-left mr-1"></i> Volver
            </a>
          </div>
        </div>
      </div>
    </section>

    <section class="content">
      <div class="container-fluid">
        <div class="alert alert-info">
          <strong>Etapa preparatoria.</strong> Esta pantalla deja lista la configuracion del correo del Pedido de Materiales. El envio real del email y el adjunto PDF se integraran mas adelante dentro de las acciones posteriores a la confirmacion del pedido.
        </div>

        <?php if (!$puedeGuardarSecretosSmtp) { ?>
          <div class="alert alert-warning">
            <strong>Proteccion de credenciales pendiente.</strong>
            Para guardar o actualizar la contrasena SMTP sin dejarla en claro, configurá la variable de entorno <code>MAIL_PRESUPUESTOS_SECRET</code> o <code>ADMINTECH_MAIL_SECRET</code> en el servidor, o el archivo externo no versionado <code>/admintech_secrets/mail_secret.php</code> fuera de <code>public_html</code>. Pedido de Materiales reutiliza ese secreto central para cifrar la contrasena.
          </div>
        <?php } ?>

        <div class="card card-primary">
          <div class="card-header">
            <h3 class="card-title">Configuracion activa de correo</h3>
          </div>
          <form method="post" autocomplete="off">
            <input type="hidden" name="accion_config_mail_pedido_materiales" value="guardar_config_mail_pedido_materiales">
            <div class="card-body">
              <div class="form-row">
                <div class="form-group col-md-4">
                  <label class="d-block">Estado</label>
                  <div class="custom-control custom-switch mt-2">
                    <input type="checkbox" class="custom-control-input" id="activo" name="activo" <?php echo !empty($configCorreo['activo']) ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="activo">Configuracion activa</label>
                  </div>
                  <small class="form-text text-muted">Permite dejar lista la configuracion sin que eso implique ejecutar todavia el envio real del pedido.</small>
                </div>
                <div class="form-group col-md-4">
                  <label class="d-block">Credencial SMTP</label>
                  <span class="mail-config-eyebrow">Estado actual</span>
                  <div class="font-weight-bold">
                    <?php echo !empty($configCorreo['smtp_password_configurada']) ? 'Contrasena protegida guardada' : 'Sin contrasena guardada'; ?>
                  </div>
                </div>
                <div class="form-group col-md-4">
                  <label class="d-block">Uso futuro</label>
                  <span class="mail-config-eyebrow">Adjunto previsto</span>
                  <div class="font-weight-bold">PDF del pedido de materiales</div>
                </div>
              </div>

              <hr>
              <h5 class="mb-3">SMTP</h5>

              <div class="form-row">
                <div class="form-group col-md-5">
                  <label for="smtp_host">Host SMTP</label>
                  <input type="text" class="form-control" id="smtp_host" name="smtp_host" value="<?php echo escConfigCorreoPedidoMateriales($configCorreo['smtp_host']); ?>" placeholder="c1234567.ferozo.com">
                </div>
                <div class="form-group col-md-2">
                  <label for="smtp_puerto">Puerto</label>
                  <input type="number" class="form-control" id="smtp_puerto" name="smtp_puerto" value="<?php echo escConfigCorreoPedidoMateriales($configCorreo['smtp_puerto']); ?>" min="1" max="65535" step="1" inputmode="numeric">
                </div>
                <div class="form-group col-md-3">
                  <label for="smtp_seguridad">Seguridad</label>
                  <select class="form-control" id="smtp_seguridad" name="smtp_seguridad">
                    <option value="ssl" <?php echo $configCorreo['smtp_seguridad'] === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                    <option value="tls" <?php echo $configCorreo['smtp_seguridad'] === 'tls' ? 'selected' : ''; ?>>TLS</option>
                    <option value="ninguna" <?php echo $configCorreo['smtp_seguridad'] === 'ninguna' ? 'selected' : ''; ?>>Ninguna</option>
                  </select>
                </div>
                <div class="form-group col-md-2">
                  <label class="d-block">Autenticacion</label>
                  <div class="custom-control custom-switch mt-2">
                    <input type="checkbox" class="custom-control-input" id="smtp_auth" name="smtp_auth" <?php echo !isset($configCorreo['smtp_auth']) || !empty($configCorreo['smtp_auth']) ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="smtp_auth">Requerida</label>
                  </div>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="smtp_usuario">Usuario SMTP</label>
                  <input type="text" class="form-control" id="smtp_usuario" name="smtp_usuario" value="<?php echo escConfigCorreoPedidoMateriales($configCorreo['smtp_usuario']); ?>" placeholder="pedidos@dominio.com">
                </div>
                <div class="form-group col-md-6">
                  <label for="smtp_password">Contrasena SMTP</label>
                  <div class="password-toggle-wrap">
                    <input type="password" class="form-control" id="smtp_password" name="smtp_password" value="" placeholder="<?php echo escConfigCorreoPedidoMateriales($configCorreo['smtp_password_placeholder'] ?: 'Ingresar nueva contrasena'); ?>" autocomplete="new-password">
                    <button type="button" class="password-toggle-wrap__button" id="toggleSmtpPasswordPedidoMateriales" aria-label="Mostrar contrasena SMTP" aria-pressed="false">
                      <i class="fas fa-eye" aria-hidden="true"></i>
                    </button>
                  </div>
                  <small class="form-text text-muted">
                    <?php if (!empty($configCorreo['smtp_password_configurada'])) { ?>
                      Ya hay una contrasena guardada. Dejá el campo vacio para conservarla o escribí una nueva para reemplazarla.
                    <?php } else { ?>
                      La contrasena se cifra antes de persistirse y nunca vuelve al frontend.
                    <?php } ?>
                  </small>
                </div>
              </div>

              <hr>
              <h5 class="mb-3">Remitente</h5>

              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="remitente_email">Email remitente</label>
                  <input type="text" class="form-control" id="remitente_email" name="remitente_email" value="<?php echo escConfigCorreoPedidoMateriales($configCorreo['remitente_email']); ?>" placeholder="pedidos@dominio.com">
                </div>
                <div class="form-group col-md-6">
                  <label for="remitente_nombre">Nombre visible remitente</label>
                  <input type="text" class="form-control" id="remitente_nombre" name="remitente_nombre" value="<?php echo escConfigCorreoPedidoMateriales($configCorreo['remitente_nombre']); ?>" placeholder="Pedido de Materiales AdminTech">
                </div>
              </div>

              <hr>
              <h5 class="mb-3">Destinatarios</h5>

              <div class="form-row">
                <div class="form-group col-md-4">
                  <label for="destinatarios_to">Para</label>
                  <textarea class="form-control" id="destinatarios_to" name="destinatarios_to" rows="5" placeholder="compras@dominio.com&#10;deposito@dominio.com"><?php echo escConfigCorreoPedidoMateriales($configCorreo['destinatarios_to']); ?></textarea>
                  <small class="form-text text-muted">Obligatorio. Acepta multiples emails separados por coma, punto y coma o salto de linea.</small>
                </div>
                <div class="form-group col-md-4">
                  <label for="destinatarios_cc">CC</label>
                  <textarea class="form-control" id="destinatarios_cc" name="destinatarios_cc" rows="5" placeholder="obra@dominio.com"><?php echo escConfigCorreoPedidoMateriales($configCorreo['destinatarios_cc']); ?></textarea>
                </div>
                <div class="form-group col-md-4">
                  <label for="destinatarios_bcc">CCO</label>
                  <textarea class="form-control" id="destinatarios_bcc" name="destinatarios_bcc" rows="5" placeholder="auditoria@dominio.com"><?php echo escConfigCorreoPedidoMateriales($configCorreo['destinatarios_bcc']); ?></textarea>
                </div>
              </div>

              <hr>
              <h5 class="mb-3">Mensaje base</h5>

              <div class="form-row">
                <div class="form-group col-md-12">
                  <label for="asunto_base">Asunto base</label>
                  <input type="text" class="form-control" id="asunto_base" name="asunto_base" value="<?php echo escConfigCorreoPedidoMateriales($configCorreo['asunto_base']); ?>" placeholder="Pedido de materiales | {obra}">
                </div>
              </div>

              <div class="form-row">
                <div class="form-group col-md-12">
                  <label for="cuerpo_base">Cuerpo base</label>
                  <textarea class="form-control" id="cuerpo_base" name="cuerpo_base" rows="8" placeholder="Buenas, adjuntamos el pedido de materiales correspondiente a la obra..."><?php echo escConfigCorreoPedidoMateriales($configCorreo['cuerpo_base']); ?></textarea>
                  <small class="form-text text-muted">Se pueden dejar placeholders de referencia para una etapa futura, por ejemplo <code>{numero_pedido}</code>, <code>{obra}</code> y <code>{fecha}</code>. En esta etapa todavia no se reemplazan ni se envia el correo real.</small>
                </div>
              </div>
            </div>
            <div class="card-footer d-flex justify-content-between">
              <a href="../01-views/auditoria_configuracion.php" class="btn btn-light">
                <i class="fas fa-times mr-1"></i> Cancelar
              </a>
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-save mr-1"></i> Guardar configuracion
              </button>
            </div>
          </form>
        </div>
      </div>
    </section>
  </div>

  <?php include '../01-views/layout/footer_layout.php';?>
</div>

<script src="../05-plugins/jquery/jquery.min.js"></script>
<script src="../05-plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../dist/js/adminlte.min.js"></script>
<script src="../07-funciones_js/funciones.js"></script>
<script>
  (function() {
    var flashPedidoMateriales = <?php echo json_encode($flash, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    var $password = $('#smtp_password');
    var $toggle = $('#toggleSmtpPasswordPedidoMateriales');
    var $smtpAuth = $('#smtp_auth');
    var $smtpUsuario = $('#smtp_usuario');

    function actualizarEstadoAuthSmtp() {
      var authActiva = $smtpAuth.is(':checked');
      $smtpUsuario.prop('disabled', !authActiva);
      $password.prop('disabled', !authActiva);
      $toggle.prop('disabled', !authActiva).toggleClass('text-muted', !authActiva);
    }

    $toggle.on('click', function() {
      var visible = $password.attr('type') === 'text';
      $password.attr('type', visible ? 'password' : 'text');
      $(this).attr('aria-pressed', visible ? 'false' : 'true');
      $(this).find('i')
        .toggleClass('fa-eye', visible)
        .toggleClass('fa-eye-slash', !visible);
    });

    $smtpAuth.on('change', actualizarEstadoAuthSmtp);
    actualizarEstadoAuthSmtp();

    if (flashPedidoMateriales && flashPedidoMateriales.msg) {
      if (flashPedidoMateriales.ok && typeof mostrarExito === 'function') {
        mostrarExito(flashPedidoMateriales.msg, 4);
      } else if (!flashPedidoMateriales.ok && typeof mostrarError === 'function') {
        mostrarError(flashPedidoMateriales.msg, 5);
      }
    }
  })();
</script>
</body>
</html>
