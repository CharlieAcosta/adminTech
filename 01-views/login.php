<!DOCTYPE html>
<html lang="en">
<head>
  <meta name="robots" content="noindex">
  <meta name="googlebot" content="noindex">
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ADMINTECH | login</title>
  <!-- Google Captcha -->
  <script src="https://www.google.com/recaptcha/api.js"></script>
  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="../05-plugins/fontawesome-free/css/all.min.css">
  <!-- icheck bootstrap -->
  <link rel="stylesheet" href="../05-plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
  <style>
    .password-field-wrap {
      position: relative;
    }

    .password-field-wrap .form-control {
      padding-right: 5.25rem;
    }

    .password-toggle {
      align-items: center;
      background: transparent;
      border: 0;
      color: #495057;
      display: flex;
      height: 100%;
      justify-content: center;
      padding: 0;
      position: absolute;
      right: 2.75rem;
      top: 0;
      width: 2.75rem;
      z-index: 5;
    }

    .password-toggle:focus {
      color: #007bff;
      outline: 0;
    }
  </style>
</head>
<body class="hold-transition login-page v-fondo-01" style="background-image: url('../dist/img/fondo_01.jpg');">
<div class="login-box">
  <!-- /.login-logo -->
  <div class="card card-outline card-primary">
    <div class="card-header text-center">
      <a href="#" class="h2"><b>ADMIN</b>TECH</a>
    </div>
    <div class="card-body">
      <p class="login-box-msg">Ingresa tus credenciales para acceder</p>

      <form id="login" action="../03-controller/login.php" method="post">
        <div class="input-group mb-3">
          <input id="usuario" name="usuario" type="email" class="form-control" placeholder="Email">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-envelope"></span>
            </div>
          </div>
        </div>
        <div class="input-group mb-3 password-field-wrap">
          <input id="password" name="password" type="password" class="form-control" placeholder="Password">
          <button type="button" class="password-toggle" id="togglePassword" aria-label="Mostrar contraseña" aria-pressed="false">
            <span class="fas fa-eye" aria-hidden="true"></span>
          </button>
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-lock"></span>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-8">
            <div class="icheck-primary">
              <input type="checkbox" id="remember" name="recordarme" value="1">
              <label for="remember">
                Recordarme
              </label>
            </div>
          </div>
          <!-- /.col -->
          <div class="col-4">
            <button type="submit" class="btn btn-primary btn-block">Acceder</button>
          <!-- solo descomentar en producción -->          
            <!-- <button class="g-recaptcha btn btn-primary btn-block" data-sitekey="6LcHoDclAAAAAHEQZSS_k4w67PcslukIpMtBC3SE" data-callback="onSubmit"> Acceder </button> -->
          <!-- solo descomentar en producción -->
          </div>
          <!-- /.col -->
        </div>
      </form>
      <!-- solo descomentar en producción -->
        <!-- <script> function onSubmit(token) {document.getElementById("login").submit();	}</script> --> 
      <!-- solo descomentar en producción -->
      <!-- /.social-auth-links -->

    </div>
    <!-- /.card-body -->
  </div>
  <!-- /.card -->
</div>
<!-- /.login-box -->

<!-- jQuery -->
<script src="../05-plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="../05-plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="../dist/js/adminlte.min.js"></script>
<script>
  $('#togglePassword').on('click', function () {
    const $password = $('#password');
    const mostrar = $password.attr('type') === 'password';

    $password.attr('type', mostrar ? 'text' : 'password');
    $(this)
      .attr('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña')
      .attr('aria-pressed', mostrar ? 'true' : 'false')
      .find('.fas')
      .toggleClass('fa-eye', !mostrar)
      .toggleClass('fa-eye-slash', mostrar);
  });
</script>
</body>
</html>
