  <footer class="main-footer">
    <div class="float-right d-none d-sm-block">
    <b>Versión: </b><!--FECHA-AUTO--><span>250707-0021</span><!--/FECHA-AUTO-->
    </div>
    <strong>Copyright &copy; 2022 <a href="#">ECOTECHOS S.R.L</a>.</strong> Todos los derechos reservados
    <span><?php echo '<br>BASE_URL= '.BASE_URL;?></span>
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