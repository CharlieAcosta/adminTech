  <footer class="main-footer">
    <div class="float-right d-none d-sm-block">
    <b>Versión: </b><!--FECHA-AUTO--><span>260118-2003</span><!--/FECHA-AUTO-->
    </div>
    <strong>Copyright &copy; 2022 <a href="#">ECOTECHOS S.R.L</a>.</strong> Todos los derechos reservados
    <?php
  // Detecta entorno desde Apache (.htaccess) o variables de entorno
  $appEnv = getenv('APP_ENV') ?: ($_SERVER['APP_ENV'] ?? 'development');
  $esProd = strtolower($appEnv) === 'production';
?>

<?php if (!$esProd): ?>
  <span><?php echo '<br>BASE_URL= ' . BASE_URL; ?></span>
<?php endif; ?>

  </footer>
<!-- Inicialización de los tooltips -->
<script>
  window.onload = function() {
    if (typeof $ !== 'undefined') { // Verifica que jQuery está cargado
      $('[data-toggle="tooltip"]').tooltip();
    } else {
      console.error("jQuery no está cargado.");
    }
  };
</script>