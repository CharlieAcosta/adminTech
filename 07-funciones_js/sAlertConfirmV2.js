function sAlertConfirmV2(params = []) {
    // Desestructurar parámetros
    const [
      icono = 'info',         // 0
      titulo,                 // 1
      html = '',              // 2
      textoBoton = 'OK',      // 3 - texto del botón
      pie = false,            // 4
      clickFuera = false,     // 5
      escape = false,         // 6
      fondo = '#000',         // 7 - color de fondo
      colorTexto = '#fff',    // 8 - color del texto general
      colorBoton = '#3085d6', // 9 - color de fondo del botón (default azul SweetAlert)
      colorTextoBoton = '#fff'// 10 - color del texto del botón
    ] = params;
  
    return Swal.fire({
      icon: icono,
      title: titulo,
      html: html,
      showConfirmButton: true,
      confirmButtonText: textoBoton,
      footer: pie,
      allowOutsideClick: clickFuera,
      allowEscapeKey: escape,
      backdrop: true,
      background: fondo,
      didOpen: () => {
        const popup = Swal.getPopup();
        if (popup) {
          // Aplicar color al texto general
          const titleEl = popup.querySelector('.swal2-title');
          const htmlEl = popup.querySelector('.swal2-html-container');
          const footerEl = popup.querySelector('.swal2-footer');
          const confirmBtn = popup.querySelector('.swal2-confirm');
          
          if (titleEl) titleEl.style.color = colorTexto;
          if (htmlEl) htmlEl.style.color = colorTexto;
          if (footerEl) footerEl.style.color = colorTexto;
  
          // Estilizar botón de confirmación
          if (confirmBtn) {
            confirmBtn.style.backgroundColor = colorBoton;
            confirmBtn.style.color = colorTextoBoton;
            confirmBtn.style.border = 'none';
          }
        }
      }
    }).then((result) => {
      if (result.isConfirmed) {
        console.log('Usuario confirmó con OK');
        return true;
      }
      return false;
    });
  }
  
  /*
// Ejemplo de uso

// usar este bloque para alertas danger con confirmación
const alertDanger = [
          false, // icono
          '<H3><strong>VALORES DESACTUALIZADOS</H3>', // título
          'Los campos en color rojo presentan precios desactualizados, para poder guardar el presupuesto deberá actualizar los valores.', // html
          'OK', // texto del botón
          false, // pie
          false, // clic fuera
          true, // permitir Escape
          '#dc3545', // color de fondo
          '#fff',    // 8 - color del texto general
          '#198754', // 9 - color de fondo del botón (default azul SweetAlert)
          '#fff'// 10 - color del texto del botón
];
// usar este bloque para alertas danger con confirmación
  
  sAlertConfirmV4(alertParams).then((ok) => {
    if (ok) {
      console.log("El usuario confirmó la alerta");
    }
  });
  */
  