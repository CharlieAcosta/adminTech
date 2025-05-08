$(".v-documento-link").click(function () {
  let link = "";
  const fileName = $(this).closest('.input-group').find('.custom-file-label').text();

  switch ($(this).data('tipo')) {
    case 'site':
      link = $(this).closest('.input-group').find('input').val();
      break;

      case 'previsita':
        const isLocal = window.location.hostname === "127.0.0.1" || window.location.hostname === "localhost";
        const adminFolder = isLocal ? "/admintech" : "";
        link = window.location.origin + adminFolder + "/09-adjuntos/previsita/" + fileName;
        break;     

    default:
      link = fileName;
      break;
  }

  console.log("🔗 Link generado:", link);

  if (link !== "") {
    window.open(link, "_blank");
  }
});
