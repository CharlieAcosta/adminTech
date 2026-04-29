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
registrarNavegacion('CONFIGURACION MAIL PRESUPUESTOS');

require_once '../04-modelo/presupuestoMailConfigModel.php';

$idUsuarioSesion = (int)($_SESSION['usuario']['id_usuario'] ?? 0);
$flash = $_SESSION['mail_presupuestos_flash'] ?? null;
unset($_SESSION['mail_presupuestos_flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $accionConfigMail = $_POST['accion_config_mail'] ?? '';
  $resultadoAccion = ['ok' => false, 'msg' => 'Acción no reconocida.'];

  if ($accionConfigMail === 'guardar_config_mail') {
    $resultadoAccion = guardarConfiguracionMailPresupuestos($_POST, $idUsuarioSesion);
  } elseif ($accionConfigMail === 'guardar_copia_mail') {
    $resultadoAccion = guardarCopiaConfiguracionMailPresupuestos($_POST, $idUsuarioSesion);
  } elseif ($accionConfigMail === 'eliminar_copia_mail') {
    $resultadoAccion = eliminarCopiaConfiguracionMailPresupuestos((int)($_POST['id_copia'] ?? 0));
  }

  $_SESSION['mail_presupuestos_flash'] = $resultadoAccion;
  header('Location: configuracion_mail_presupuestos.php');
  exit;
}

$configMail = obtenerConfiguracionMailPresupuestos();
$configMailPrivada = obtenerConfiguracionMailPresupuestos(true);
$copiasMail = listarCopiasConfiguracionMailPresupuestos();
$estadoTransporteSmtp = obtenerEstadoTransporteSmtpMailPresupuestos();
$smtpTransportDisponible = !empty($estadoTransporteSmtp['disponible']);
$puedeGuardarSecretosSmtp = runtimeCifradoMailPresupuestosDisponible() && hayClaveSecretaMailPresupuestos();
$validacionModoSmtp = validarConfiguracionSmtpMailPresupuestos($configMailPrivada, [
  'exigir_password' => $configMail['modo_envio'] === 'smtp',
]);
$ayudaHostSmtp = textoAyudaHostSmtpDonWebMailPresupuestos();

function escConfigMail($valor): string
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
  <title>ADMINTECH | Configuración mail presupuestos</title>

  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="../05-plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../dist/css/custom.css">
  <style>
    .mail-mode-switch {
      border: 1px solid #ced4da;
      border-radius: .25rem;
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      justify-content: space-between;
      gap: 1rem;
      padding: .75rem 1rem;
    }

    .mail-mode-switch__status {
      display: flex;
      flex-direction: column;
      gap: .15rem;
    }

    .mail-mode-switch__eyebrow {
      color: #6c757d;
      font-size: .8rem;
      line-height: 1;
      text-transform: uppercase;
    }

    .mail-mode-switch__value {
      font-size: 1rem;
      font-weight: 700;
      line-height: 1.2;
    }

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
            <h1><strong>Configuración de mail | Presupuestos</strong></h1>
          </div>
        </div>
      </div>
    </section>

    <section class="content">
      <div class="container-fluid">
        <?php if ($flash) { ?>
          <div class="alert <?php echo !empty($flash['ok']) ? 'alert-success' : 'alert-danger'; ?>">
            <?php echo escConfigMail($flash['msg'] ?? 'Operación finalizada.'); ?>
          </div>
        <?php } ?>

        <div class="alert <?php echo $configMail['modo_envio'] === 'smtp' ? 'alert-primary' : 'alert-info'; ?>">
          <?php if ($configMail['modo_envio'] === 'smtp') { ?>
            <strong>Modo activo:</strong> el sistema enviará correos reales usando la configuración SMTP activa.
          <?php } else { ?>
            <strong>Modo activo:</strong> el sistema registra el intento, pero no envía correo real y el presupuesto sigue en <strong>Emitido</strong>.
          <?php } ?>
        </div>

        <div class="alert <?php echo $smtpTransportDisponible ? 'alert-success' : 'alert-warning'; ?>">
          <?php echo escConfigMail(mensajeDisponibilidadTransporteSmtpMailPresupuestos()); ?>
        </div>

        <?php if (!$puedeGuardarSecretosSmtp) { ?>
          <div class="alert alert-warning">
            <strong>Protección de credenciales pendiente.</strong>
            Para guardar o actualizar la contraseña SMTP sin dejarla en claro, configurá la variable de entorno <code>MAIL_PRESUPUESTOS_SECRET</code> o <code>ADMINTECH_MAIL_SECRET</code> en el servidor, o el archivo externo no versionado <code>/admintech_secrets/mail_secret.php</code> fuera de <code>public_html</code>. El secreto debe tener al menos 32 caracteres.
            Si el secreto cambia, las contraseñas SMTP cifradas anteriormente pueden quedar ilegibles.
          </div>
        <?php } ?>

        <?php if ($configMail['modo_envio'] === 'smtp' && !empty($validacionModoSmtp['errores'])) { ?>
          <div class="alert alert-danger">
            <strong>SMTP real incompleto:</strong>
            <?php echo escConfigMail(implode(' ', $validacionModoSmtp['errores'])); ?>
          </div>
        <?php } ?>

        <?php if (!empty($validacionModoSmtp['advertencias'])) { ?>
          <div class="alert alert-warning">
            <strong>Recomendaciones DonWeb/Ferozo:</strong>
            <?php echo escConfigMail(implode(' ', $validacionModoSmtp['advertencias'])); ?>
          </div>
        <?php } ?>

        <div class="row">
          <div class="col-lg-7">
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title">Modo y credenciales SMTP</h3>
              </div>
              <form method="post" autocomplete="off">
                <input type="hidden" name="accion_config_mail" value="guardar_config_mail">
                <div class="card-body">
                  <div class="form-group">
                    <label for="modo_envio_switch">Modo de envío</label>
                    <input type="hidden" id="modo_envio" name="modo_envio" value="<?php echo escConfigMail($configMail['modo_envio']); ?>">
                    <div class="mail-mode-switch">
                      <div class="mail-mode-switch__status">
                        <span class="mail-mode-switch__eyebrow">Modo actual</span>
                        <span class="mail-mode-switch__value" id="modo_envio_estado"></span>
                      </div>
                      <div class="custom-control custom-switch mb-0">
                        <input type="checkbox" class="custom-control-input" id="modo_envio_switch" <?php echo $configMail['modo_envio'] === 'smtp' ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="modo_envio_switch">Usar SMTP real</label>
                      </div>
                    </div>
                    <small class="form-text text-muted" id="modo_envio_ayuda"></small>
                  </div>

                  <div class="form-row">
                    <div class="form-group col-md-6">
                      <label for="remitente_nombre">Nombre visible remitente</label>
                      <input type="text" class="form-control" id="remitente_nombre" name="remitente_nombre" value="<?php echo escConfigMail($configMail['remitente_nombre']); ?>">
                    </div>
                    <div class="form-group col-md-6">
                      <label for="remitente_email">Email remitente</label>
                      <input type="text" class="form-control" id="remitente_email" name="remitente_email" value="<?php echo escConfigMail($configMail['remitente_email']); ?>" placeholder="presupuestos@dominio.com">
                      <small class="form-text text-muted">En DonWeb/Ferozo el remitente debe coincidir con la cuenta autenticada del usuario SMTP.</small>
                    </div>
                  </div>

                  <div class="form-row">
                    <div class="form-group col-md-6">
                      <label for="smtp_host">SMTP host</label>
                      <input type="text" class="form-control" id="smtp_host" name="smtp_host" value="<?php echo escConfigMail($configMail['smtp_host']); ?>" placeholder="c1234567.ferozo.com">
                      <small class="form-text text-muted"><?php echo escConfigMail($ayudaHostSmtp); ?></small>
                    </div>
                    <div class="form-group col-md-2">
                      <label for="smtp_puerto">Puerto</label>
                      <input type="number" class="form-control" id="smtp_puerto" name="smtp_puerto" value="<?php echo escConfigMail($configMail['smtp_puerto']); ?>">
                    </div>
                    <div class="form-group col-md-4">
                      <label for="smtp_seguridad">Seguridad</label>
                      <select class="form-control" id="smtp_seguridad" name="smtp_seguridad">
                        <option value="tls" <?php echo $configMail['smtp_seguridad'] === 'tls' ? 'selected' : ''; ?>>TLS</option>
                        <option value="ssl" <?php echo $configMail['smtp_seguridad'] === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                        <option value="ninguna" <?php echo $configMail['smtp_seguridad'] === 'ninguna' ? 'selected' : ''; ?>>Ninguna</option>
                      </select>
                    </div>
                  </div>

                  <div class="form-row">
                    <div class="form-group col-md-6">
                      <label for="smtp_usuario">Usuario SMTP</label>
                      <input type="text" class="form-control" id="smtp_usuario" name="smtp_usuario" value="<?php echo escConfigMail($configMail['smtp_usuario']); ?>">
                      <small class="form-text text-muted">Usá la cuenta completa autenticada, por ejemplo <code>presupuestos@dominio.com</code>.</small>
                    </div>
                    <div class="form-group col-md-6">
                      <label for="smtp_password">Contraseña SMTP</label>
                      <div class="password-toggle-wrap">
                        <input type="password" class="form-control" id="smtp_password" name="smtp_password" value="" placeholder="<?php echo escConfigMail($configMail['smtp_password_placeholder'] ?: 'Ingresar nueva contraseña'); ?>" autocomplete="new-password">
                        <button type="button" class="password-toggle-wrap__button" id="toggleSmtpPassword" aria-label="Mostrar contraseña SMTP" aria-pressed="false">
                          <i class="fas fa-eye" aria-hidden="true"></i>
                        </button>
                      </div>
                      <small class="form-text text-muted">
                        <?php if (!empty($configMail['smtp_password_configurada'])) { ?>
                          Ya hay una contraseña guardada. Dejalo vacío para conservarla o escribí una nueva para reemplazarla.
                        <?php } else { ?>
                          Ingresá la contraseña real de la cuenta SMTP autenticada.
                        <?php } ?>
                      </small>
                    </div>
                  </div>
                </div>
                <div class="card-footer text-right">
                  <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar configuración
                  </button>
                </div>
              </form>
            </div>
          </div>

          <div class="col-lg-5">
            <div class="card card-secondary">
              <div class="card-header">
                <h3 class="card-title">Copias internas por defecto</h3>
              </div>
              <form method="post" id="formCopiaMailPresupuesto" autocomplete="off">
                <input type="hidden" name="accion_config_mail" value="guardar_copia_mail">
                <input type="hidden" name="id_copia" id="id_copia" value="">
                <div class="card-body">
                  <div class="form-row">
                    <div class="form-group col-md-5">
                      <label for="etiqueta">Etiqueta</label>
                      <input type="text" class="form-control" name="etiqueta" id="etiqueta" placeholder="Técnica">
                    </div>
                    <div class="form-group col-md-7">
                      <label for="email">Email</label>
                      <input type="text" class="form-control" name="email" id="email" placeholder="tecnica@dominio.com">
                    </div>
                  </div>
                  <div class="form-row">
                    <div class="form-group col-md-4">
                      <label for="tipo">Tipo</label>
                      <select class="form-control" name="tipo" id="tipo">
                        <option value="cco">CCO</option>
                        <option value="cc">CC</option>
                      </select>
                    </div>
                    <div class="form-group col-md-4">
                      <label for="orden">Orden</label>
                      <input type="number" class="form-control" name="orden" id="orden" value="10">
                    </div>
                    <div class="form-group col-md-4 d-flex flex-column justify-content-end">
                      <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="activo" name="activo" checked>
                        <label class="custom-control-label" for="activo">Activo</label>
                      </div>
                      <div class="custom-control custom-checkbox mt-2">
                        <input type="checkbox" class="custom-control-input" id="activo_por_defecto" name="activo_por_defecto" checked>
                        <label class="custom-control-label" for="activo_por_defecto">Tildado por defecto</label>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="card-footer d-flex justify-content-between">
                  <button type="button" class="btn btn-light" id="resetCopiaMail">
                    <i class="fas fa-eraser"></i> Limpiar
                  </button>
                  <button type="submit" class="btn btn-secondary" id="submitCopiaMail">
                    <i class="fas fa-plus"></i> Guardar copia
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Copias configuradas</h3>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-sm table-bordered mb-0">
                <thead>
                  <tr>
                    <th>Etiqueta</th>
                    <th>Email</th>
                    <th>Tipo</th>
                    <th>Activo</th>
                    <th>Por defecto</th>
                    <th>Orden</th>
                    <th style="width: 140px;">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!$copiasMail) { ?>
                    <tr>
                      <td colspan="7" class="text-center text-muted">Todavía no hay copias internas configuradas.</td>
                    </tr>
                  <?php } ?>
                  <?php foreach ($copiasMail as $copia) { ?>
                    <tr>
                      <td><?php echo escConfigMail($copia['etiqueta']); ?></td>
                      <td><?php echo escConfigMail($copia['email']); ?></td>
                      <td class="text-uppercase"><?php echo escConfigMail($copia['tipo']); ?></td>
                      <td><?php echo !empty($copia['activo']) ? 'Sí' : 'No'; ?></td>
                      <td><?php echo !empty($copia['activo_por_defecto']) ? 'Sí' : 'No'; ?></td>
                      <td><?php echo (int)$copia['orden']; ?></td>
                      <td class="text-center">
                        <button
                          type="button"
                          class="btn btn-sm btn-outline-primary editar-copia-mail"
                          data-id="<?php echo (int)$copia['id_copia']; ?>"
                          data-etiqueta="<?php echo escConfigMail($copia['etiqueta']); ?>"
                          data-email="<?php echo escConfigMail($copia['email']); ?>"
                          data-tipo="<?php echo escConfigMail($copia['tipo']); ?>"
                          data-activo="<?php echo !empty($copia['activo']) ? '1' : '0'; ?>"
                          data-activo-por-defecto="<?php echo !empty($copia['activo_por_defecto']) ? '1' : '0'; ?>"
                          data-orden="<?php echo (int)$copia['orden']; ?>"
                        >
                          <i class="fas fa-edit"></i>
                        </button>
                        <form method="post" class="d-inline-block mb-0">
                          <input type="hidden" name="accion_config_mail" value="eliminar_copia_mail">
                          <input type="hidden" name="id_copia" value="<?php echo (int)$copia['id_copia']; ?>">
                          <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar esta copia interna?');">
                            <i class="fas fa-trash-alt"></i>
                          </button>
                        </form>
                      </td>
                    </tr>
                  <?php } ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>

  <?php include '../01-views/layout/footer_layout.php';?>
</div>

<script src="../05-plugins/jquery/jquery.min.js"></script>
<script src="../05-plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../dist/js/adminlte.min.js"></script>
<script>
  (function () {
    function actualizarModoEnvioUi() {
      const esSmtp = $('#modo_envio_switch').is(':checked');
      $('#modo_envio').val(esSmtp ? 'smtp' : 'simulacion');
      $('#modo_envio_estado').text(esSmtp ? 'SMTP real' : 'Simulación');
      $('#modo_envio_ayuda').text(
        esSmtp
          ? 'Se usarán las credenciales SMTP guardadas para enviar el presupuesto.'
          : 'Solo se registra el intento y el presupuesto permanece en Emitido.'
      );
    }

    function actualizarEstadoPasswordVisible() {
      const $inputPassword = $('#smtp_password');
      const esVisible = $inputPassword.attr('type') === 'text';
      $('#toggleSmtpPassword')
        .attr('aria-label', esVisible ? 'Ocultar contraseña SMTP' : 'Mostrar contraseña SMTP')
        .attr('aria-pressed', esVisible ? 'true' : 'false')
        .find('i')
        .toggleClass('fa-eye', !esVisible)
        .toggleClass('fa-eye-slash', esVisible);
    }

    function resetFormularioCopia() {
      $('#id_copia').val('');
      $('#etiqueta').val('');
      $('#email').val('');
      $('#tipo').val('cco');
      $('#orden').val('10');
      $('#activo').prop('checked', true);
      $('#activo_por_defecto').prop('checked', true);
      $('#submitCopiaMail').html('<i class="fas fa-plus"></i> Guardar copia');
    }

    $('#modo_envio_switch').on('change', function () {
      actualizarModoEnvioUi();
    });

    $('#toggleSmtpPassword').on('click', function () {
      const $inputPassword = $('#smtp_password');
      const nuevoTipo = $inputPassword.attr('type') === 'password' ? 'text' : 'password';
      $inputPassword.attr('type', nuevoTipo).trigger('focus');
      actualizarEstadoPasswordVisible();
    });

    $('#resetCopiaMail').on('click', function () {
      resetFormularioCopia();
    });

    $('.editar-copia-mail').on('click', function () {
      const $btn = $(this);
      $('#id_copia').val($btn.data('id'));
      $('#etiqueta').val($btn.data('etiqueta'));
      $('#email').val($btn.data('email'));
      $('#tipo').val($btn.data('tipo'));
      $('#orden').val(String($btn.data('orden') || '10'));
      $('#activo').prop('checked', String($btn.data('activo')) === '1');
      $('#activo_por_defecto').prop('checked', String($btn.data('activoPorDefecto')) === '1');
      $('#submitCopiaMail').html('<i class="fas fa-save"></i> Actualizar copia');
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    actualizarModoEnvioUi();
    actualizarEstadoPasswordVisible();
  })();
</script>
</body>
</html>
