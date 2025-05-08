function sAlertAutoCloseV2(params = []) {
  // Desestructurar el array para obtener los valores de cada parámetro
  const [
    icono = false,          // 0
    titulo,                 // 1
    html = '',              // 2
    duracion = 2000,        // 3
    barraProgreso = true,   // 4
    pie = false,            // 5
    clickFuera = false,     // 6
    escape = false,         // 7
    fondo = '#fff'          // 8 - nuevo parámetro para el color de fondo
  ] = params;

  let timerInterval;
  Swal.fire({
    icon: icono,
    title: titulo,
    html: html,
    timer: duracion,
    timerProgressBar: barraProgreso,
    showConfirmButton: false,
    footer: pie,
    allowOutsideClick: clickFuera,
    allowEscapeKey: escape,
    backdrop: true,
    background: fondo // <- se aplica el color de fondo personalizado
  }).then((result) => {
    // Manejar el cierre por el temporizador
    if (result.dismiss === Swal.DismissReason.timer) {
      console.log('I was closed by the timer');
    }
  });
}


/*
const alertParams = [
  'success',               // icono
  'Todo OK!',              // título
  '<strong>Guardado con éxito</strong>', // html
  3000,                    // duración
  true,                    // barra de progreso
  false,                   // pie
  false,                   // clic fuera para cerrar
  true,                    // permitir cerrar con Escape
  '#2e2e3a'                // color de fondo personalizado
];

sAlertAutoCloseV2(alertParams);
*/