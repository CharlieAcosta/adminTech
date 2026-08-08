$(document).on("click", ".v-icon-accion, .v-accion-cancelar, .v-btn-accion", function () {
  feriadosAcciones($(this));
});

function feriadoFechaEsHoyOPasada(fecha) {
  if (!/^\d{4}-\d{2}-\d{2}$/.test(String(fecha || ''))) {
    return false;
  }

  const hoy = new Date();
  const yyyy = hoy.getFullYear();
  const mm = String(hoy.getMonth() + 1).padStart(2, '0');
  const dd = String(hoy.getDate()).padStart(2, '0');
  return fecha <= `${yyyy}-${mm}-${dd}`;
}

function feriadoTextoFecha(fecha) {
  if (!/^\d{4}-\d{2}-\d{2}$/.test(String(fecha || ''))) {
    return fecha || '';
  }

  const partes = fecha.split('-');
  return `${partes[2]}/${partes[1]}/${partes[0]}`;
}

function feriadoMostrarAlerta(icono, titulo, texto, color) {
  const alertParams = [
    false,
    `<span class="text-white">${titulo}</span>`,
    `<span class="text-white">${texto}</span>`,
    3700,
    true,
    false,
    false,
    true,
    color
  ];
  sAlertAutoCloseV2(alertParams);
}

function feriadoGuardar() {
  const idFeriado = $("#id_feriado").val();
  const fecha = $("#fecha").val();
  const fechaOriginal = $("#id_feriado").data("fecha-original");
  const descripcion = $("#descripcion").val();
  const cambiaFechaHistorica = idFeriado !== "" && fecha !== fechaOriginal && feriadoFechaEsHoyOPasada(fechaOriginal);

  const ejecutarGuardado = function () {
    $.ajax({
      url: "../03-controller/feriadosController.php",
      type: "POST",
      dataType: "json",
      data: {
        ajax: "on",
        funcion: "guardarFeriado",
        id_feriado: idFeriado,
        fecha: fecha,
        descripcion: descripcion
      },
      success: function (response) {
        if (response && response.ok === true) {
          feriadoMostrarAlerta(
            "success",
            idFeriado !== "" ? "EDICION EXITOSA" : "ALTA EXITOSA",
            response.mensaje || "La informacion ha sido registrada correctamente",
            "#28a745"
          );
          if (idFeriado === "" && response.id_feriado) {
            setTimeout(function () {
              window.location.href = "feriados_form.php?acci=e&id=" + response.id_feriado;
            }, 900);
          } else {
            $("#id_feriado").data("fecha-original", fecha);
          }
        } else {
          feriadoMostrarAlerta("error", "NO SE PUEDE GUARDAR", (response && response.mensaje) || "Revise los datos ingresados", "#dc3545");
        }
      },
      error: function () {
        feriadoMostrarAlerta("error", "HA OCURRIDO UN ERROR", "Intentelo mas tarde o comunicate con el administrador", "#dc3545");
      }
    });
  };

  if (cambiaFechaHistorica) {
    Swal.fire({
      icon: "warning",
      title: "Cambio de fecha historica",
      text: "Este cambio afectara futuras consultas y procesamientos que utilicen la tabla de feriados. No modificara novedades ni asistencias ya registradas.",
      showCancelButton: true,
      confirmButtonText: "Continuar",
      cancelButtonText: "Cancelar",
      allowOutsideClick: false,
      allowEscapeKey: false
    }).then((result) => {
      if (result.isConfirmed) {
        ejecutarGuardado();
      }
    });
  } else {
    ejecutarGuardado();
  }
}

function feriadoCambiarEstado(elemento, estadoDestino) {
  const fila = elemento.closest("tr");
  const idFeriado = fila.data("id");
  const fecha = fila.data("fecha");
  const esHistorico = feriadoFechaEsHoyOPasada(fecha);
  const esDesactivacion = estadoDestino === "disabled";
  const titulo = esDesactivacion ? "Desactivar feriado" : "Reactivar feriado";
  let texto = `${esDesactivacion ? "Se desactivara" : "Se reactivara"} el feriado del ${feriadoTextoFecha(fecha)}.`;

  if (esHistorico) {
    texto += " Este cambio afectara futuras consultas y procesamientos que utilicen la tabla de feriados. No modificara novedades ni asistencias ya registradas.";
  }

  Swal.fire({
    icon: esDesactivacion ? "warning" : "question",
    title: titulo,
    text: texto,
    showCancelButton: true,
    confirmButtonText: esDesactivacion ? "Si, desactivar" : "Si, reactivar",
    cancelButtonText: "Cancelar",
    allowOutsideClick: false,
    allowEscapeKey: false
  }).then((result) => {
    if (!result.isConfirmed) {
      return;
    }

    $.ajax({
      url: "../03-controller/feriadosController.php",
      type: "POST",
      dataType: "json",
      data: {
        ajax: "on",
        funcion: "cambiarEstadoFeriado",
        id_feriado: idFeriado,
        estado: estadoDestino
      },
      success: function (response) {
        if (response && response.ok === true) {
          feriadoMostrarAlerta("success", "ESTADO ACTUALIZADO", response.mensaje || "Estado actualizado correctamente", "#28a745");
          setTimeout(function () {
            window.location.reload();
          }, 900);
        } else {
          feriadoMostrarAlerta("error", "NO SE PUEDE ACTUALIZAR", (response && response.mensaje) || "Revise la solicitud", "#dc3545");
        }
      },
      error: function () {
        feriadoMostrarAlerta("error", "HA OCURRIDO UN ERROR", "Intentelo mas tarde o comunicate con el administrador", "#dc3545");
      }
    });
  });
}

function feriadosAcciones(elemento) {
  switch ($(elemento).data("accion")) {
    case "visual":
      window.location.href = "feriados_form.php?acci=v&id=" + $(elemento).closest("tr").data("id");
      break;

    case "editar":
      window.location.href = "feriados_form.php?acci=e&id=" + $(elemento).closest("tr").data("id");
      break;

    case "desactivar":
      feriadoCambiarEstado($(elemento), "disabled");
      break;

    case "reactivar":
      feriadoCambiarEstado($(elemento), "enabled");
      break;

    case "cancelar":
      Swal.fire({
        icon: "warning",
        title: "Los datos ingresados o modificados no seran registrados",
        text: "Deseas cancelar de todas maneras?",
        showDenyButton: true,
        confirmButtonText: "Si",
        denyButtonText: "No",
        allowOutsideClick: false,
        allowEscapeKey: false
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = "feriados_listado.php";
        }
      });
      break;

    case "guardar":
      if ($('#currentForm').valid()) {
        feriadoGuardar();
      } else {
        feriadoMostrarAlerta("error", "NO SE PUEDE GUARDAR", "Hay campos sin completar o invalidos", "#dc3545");
      }
      break;

    case "volver":
      window.location.href = "panel.php";
      break;
  }
}
