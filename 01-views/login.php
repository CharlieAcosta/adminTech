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
        <div class="input-group mb-3">
          <input id="password" name="password" type="password" class="form-control" placeholder="Password">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-lock"></span>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-8">
            <div class="icheck-primary">
              <input type="checkbox" id="remember">
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
</body>
</html>
