<!-- funciones customizadas -->
<script src="../05-plugins/jquery/jquery.min.js?v=<?php echo time(); ?>"></script>
<script src="../07-funciones_js/funciones.js?v=<?php echo time(); ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11?v=<?php echo time(); ?>"></script>


<!-- funciones customizadas -->

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AEO | Asistencia en obra</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css?v=<?php echo time(); ?>" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f0f0f0;
            text-align: center;
        }

        #container {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 90%; /* Ajuste para el 90% del ancho de la pantalla */
            max-width: 600px; /* Máximo ancho para que no sea excesivo en pantallas grandes */
            margin: 5%;
        }

        h3 {
            font-size: 24px;
            text-transform: uppercase;
        }

        h3 span {
            font-weight: bold;
        }

        h4 {
            font-size: 18px;
            color: green;
            margin: 0;
        }

        form {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        input[type="number"] {
            padding: 10px;
            margin: 5px 0;
            width: 200px;
            border: 1px solid #ccc;
            border-radius: 4px;
            text-align: center;
        }

        input[type="submit"] {
            margin-top: 20px;
        }

        .desktop-message {
            display: none;
            text-transform: uppercase;
            color: black;
        }

        @media screen and (min-width: 768px) {
            #container {
                display: none;
            }

            .desktop-message {
                display: block;
                font-size: 24px;
                color: black;
                text-transform: uppercase;
            }
        }

        .info {
            margin-top: 10px;
            font-size: 14px;
            color: #333;
        }

        .error, .instructions {
            color: red;
            font-size: 14px;
            margin-top: 10px;
        }

        .message-box {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            display: inline-block;
            text-align: center;
        }

        .retry-button {
            display: none;
            margin-top: 10px;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-align: center;
            margin-left: 27%;
        }

        .retry-button:hover {
            background-color: #0056b3;
        }

        h3, h4 {
            margin: 0; /* Elimina el margen por defecto */
            padding: 0; /* Elimina el relleno por defecto */
        }
        h3 {
            font-size: 2rem; /* Ajusta el tamaño del texto si es necesario */
            margin-bottom: 0.1rem; /* Ajusta el espacio debajo del h1 */
        }
        h4 {
            font-size: 1.5rem; /* Ajusta el tamaño del texto si es necesario */
            margin-bottom: 0.9rem
        }

        strong {
            font-weight: bold;
        }

    </style>
</head>
<body>
    <div id="container">
        <h3><span><strong>ADMIN</strong></span>TECH</h3>
        <h4><strong>AEO</strong></h4>
        <form id="geo-form">
            <input type="number" name="codigo" placeholder="Código" required maxlength="4" id="codigo" class="form-control">
            <input type="number" name="dni" placeholder="DNI" required maxlength="8" id="dni" class="form-control">
            <input type="hidden" name="latitude" id="latitude">
            <input type="hidden" name="longitude" id="longitude">
            <input type="submit" value="Enviar" class="btn btn-primary">
        </form>
        <div id="error-message" class="error"></div>
        <div id="instructions-message" class="instructions message-box" style="display: none;">
            <p>Para habilitar los permisos de geolocalización, siga estos pasos:</p>
            <p>1. Abre Chrome (o el navegador que uses) en tu dispositivo</p>
            <p>2. Toca el icono de ajustes a la izquierda la barra de direcciones.</p>
            <p>3. Toca en "Ubicación".</p>
            <p>4. Selecciona "Permitir".</p>
            <p>5. Oprime el botón de "Recargar página".</p>
            <div style="width: 100%;"><button id="retry-button" class="retry-button">Recargar la página</button></div>
        </div>
    </div>
    <div class="desktop-message">
        PÁGINA NO DISPONIBLE
    </div>

<!-- customs -->
<script src="../07-funciones_js/cookieValidCreate.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const geoForm = document.getElementById('geo-form');
            const codigoInput = document.getElementById('codigo');
            const dniInput = document.getElementById('dni');
            const errorMessage = document.getElementById('error-message');
            const instructionsMessage = document.getElementById('instructions-message');
            const retryButton = document.getElementById('retry-button');

            // Verificar si el dispositivo es un móvil
            const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
            if (!isMobile) {
                document.getElementById('container').style.display = 'none';
                document.querySelector('.desktop-message').style.display = 'block';
                return;
            }

            function requestGeolocation() {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function(position) {
                        const latitude = position.coords.latitude;
                        const longitude = position.coords.longitude;

                        document.getElementById('latitude').value = latitude;
                        document.getElementById('longitude').value = longitude;

                        errorMessage.textContent = ''; // Limpiar mensaje de error si se concede el permiso
                        instructionsMessage.style.display = 'none'; // Ocultar instrucciones
                        retryButton.style.display = 'none'; // Ocultar botón de reintento

                    }, function(error) {
                        if (error.code === error.PERMISSION_DENIED) {
                            errorMessage.textContent = ''; // Limpiar mensaje de error si se deniega el permiso
                            instructionsMessage.style.display = 'block'; // Mostrar instrucciones
                            document.getElementById('geo-form').style.display = 'none';
                            retryButton.style.display = 'block'; // Mostrar botón de reintento
                        } else {
                            errorMessage.textContent = 'Ocurrió un error al intentar obtener la ubicación.';
                            console.error('Error al obtener la geolocalización:', error);
                        }
                    });
                } else {
                    alert('La geolocalización no es compatible con este navegador. El formulario no podrá ser procesado.');
                    console.error('La geolocalización no es compatible con este navegador.');
                }
            }

            requestGeolocation();

            retryButton.addEventListener('click', function() {
                location.reload(); // Recargar la página
            });

            geoForm.addEventListener('submit', function(event) {
                if (!document.getElementById('latitude').value || !document.getElementById('longitude').value) {
                    alert('Espere a que se obtenga la ubicación antes de enviar el formulario.');
                    event.preventDefault();
                }
            });

            // Validar entrada en el campo código
            codigoInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '').substring(0, 4);
            });

            // Validar entrada en el campo DNI
            dniInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '').substring(0, 8);
            });
        });
    </script>
</body>
</html>

<script>
document.getElementById('geo-form').addEventListener('submit', function(event) {
    event.preventDefault(); // Evita el envío del formulario por defecto

    const codigo = document.getElementById('codigo').value;
    let dni = String(document.getElementById('dni').value);
    dni = formatDNI(dni);
    const latitude = parseFloat(document.getElementById('latitude').value);
    const longitude = parseFloat(document.getElementById('longitude').value);

    procesaEnvio(codigo, dni, latitude, longitude);
});

async function procesaEnvio(codigo, dni, latitude, longitude) {

    try {
        let obraRequest = await validaObra(codigo);
        if (obraRequest.status !== false) {
            let usuarioRequest = await validaUsuario(dni);
            if (usuarioRequest.status !== false) {
                const obraLat = parseFloat(obraRequest.obra_lat);
                const obraLon = parseFloat(obraRequest.obra_lon);
                
                if (isNaN(obraLat) || isNaN(obraLon)) {
                    throw new Error('Las coordenadas de la obra no son válidas.');
                }

                const distancia = calcularDistancia(latitude, longitude, obraLat, obraLon);
                console.log("Distancia calculada:", distancia.toFixed(2), "metros");

                if (distancia <= 1500) { // 200 metros
                    var obas_fecha = fechaActual("AAAA-MM-DD"); 

                    existInDBByMultipleValues('../06-funciones_php/funciones.php',
                        'existInDBByMultipleValues',
                        'obras_asistencia',
                        ['obas_obra_id','obas_id_usuario', 'obas_fecha', 'obas_estado', 'obas_log_accion'],
                        [codigo, usuarioRequest.id_usuario, obas_fecha, 'Entrada', 'alta']
                        )
                    .then(data => {
                        let userDispositive;
                        if (!data.obas_id){
                            userDispositive = cookieValidCreate("userDispositive", usuarioRequest.apellidos, 365);
                            //alert(userDispositive);
                            simpleInsertInDB(
                                '../06-funciones_php/funciones.php',
                                'obras_asistencia',
                                ['obas_id_usuario', 'obas_obra_id', 'obas_fecha', 'obas_hora', 'obas_estado', 'obas_lat', 'obas_lon', 'obas_log_accion', 'obas_log_usuario_id','obas_dispositivo'],
                                [usuarioRequest.id_usuario, codigo, fechaActual("AAAA-MM-DD"), horaActual('HH:MM:SS'), 'Entrada', latitude, longitude, 'alta', usuarioRequest.id_usuario, userDispositive]
                            );
                                Swal.fire({
                                    title: 'Registro Exitoso',
                                    html: '<p>Su ingreso a la obra ha sido registrado.</p><p><strong>No olvide registrar su salida al retirarse.</strong></p>', // Usar 'html' para permitir múltiples líneas
                                    icon: 'success',
                                    confirmButtonText: 'OK',
                                    customClass: {
                                        confirmButton: 'btn btn-success',
                                        popup: 'text-center'
                                    },
                                    buttonsStyling: false
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        window.location.href = 'https://www.google.com'; // Redirigir a Google cuando se oprima OK
                                    }
                                })
                        } else {
                            existInDBByMultipleValues('../06-funciones_php/funciones.php',
                                'existInDBByMultipleValues',
                                'obras_asistencia',
                                ['obas_obra_id','obas_id_usuario', 'obas_fecha', 'obas_estado', 'obas_log_accion'],
                                [codigo, usuarioRequest.id_usuario, obas_fecha, 'Salida', 'alta']
                                )
                            .then(data => {
                                if (!data.obas_id){
                                    userDispositive = cookieValidCreate("userDispositive", usuarioRequest.apellidos, 365);
                                    simpleInsertInDB(
                                        '../06-funciones_php/funciones.php',
                                        'obras_asistencia',
                                        ['obas_id_usuario', 'obas_obra_id', 'obas_fecha', 'obas_hora', 'obas_estado', 'obas_lat', 'obas_lon', 'obas_log_accion', 'obas_log_usuario_id','obas_dispositivo'],
                                        [usuarioRequest.id_usuario, codigo, fechaActual("AAAA-MM-DD"), horaActual('HH:MM:SS'), 'Salida', latitude, longitude, 'alta', usuarioRequest.id_usuario, userDispositive]
                                    );
                                    Swal.fire({
                                        title: 'Registro Exitoso',
                                        html: '<p>Su salida de la obra ha sido registrada.</p><p><strong>Hasta mañana.</strong></p>', // Usar 'html' para permitir múltiples líneas
                                        icon: 'success',
                                        confirmButtonText: 'OK',
                                        customClass: {
                                            confirmButton: 'btn btn-success',
                                            popup: 'text-center'
                                        },
                                        buttonsStyling: false
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            window.location.href = 'https://www.google.com'; // Redirigir a Google cuando se oprima OK
                                        }
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'Error de Registro',
                                        text: 'Ya cuenta con dos registros de entrada y salida. Comuníquese con la oficina administrativa y mencione este mensaje.',
                                        icon: 'error',
                                        confirmButtonText: 'OK',
                                        customClass: {
                                            confirmButton: 'btn btn-danger',
                                            popup: 'text-center'
                                        },
                                        buttonsStyling: false
                                    });
                                }
                            })
                            .catch(error => {
                                Swal.fire({
                                    title: 'Error',
                                    text: `Error: ${error.message}`,
                                    icon: 'error',
                                    confirmButtonText: 'OK',
                                    customClass: {
                                        confirmButton: 'btn btn-danger',
                                        popup: 'text-center'
                                    },
                                    buttonsStyling: false
                                });
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            title: 'Error',
                            text: `Error: ${error.message}`,
                            icon: 'error',
                            confirmButtonText: 'OK',
                            customClass: {
                                confirmButton: 'btn btn-danger',
                                popup: 'text-center'
                            },
                            buttonsStyling: false
                        });
                    });
                } else {
                    Swal.fire({
                        title: `<span style="font-size: 20px; color: red; text-transform: uppercase;">La ubicación actual de su dispositivo está a una distancia de la obra de: ${distancia.toFixed(2)} metros</span>`,
                        html: `<strong style="font-size: 18px;">Rango permitido no mayor a 200 metros</strong>`,
                        icon: 'warning',
                        confirmButtonText: 'OK',
                        customClass: {
                            confirmButton: 'btn btn-success',
                            popup: 'text-center'
                        },
                        buttonsStyling: false
                    }).then(() => {
                        document.getElementById('geo-form').reset(); // Reinicia el formulario
                        requestGeolocation(); // Solicita nuevamente la geolocalización
                    });
                }
            } else {
                Swal.fire({
                    title: 'DNI INEXISTENTE',
                    text: '',
                    icon: 'error',
                    confirmButtonText: 'OK',
                    customClass: {
                        confirmButton: 'btn btn-success'
                    },
                    buttonsStyling: false
                });
            }
        } else {
            Swal.fire({
                title: 'CODIGO INEXISTENTE',
                text: '',
                icon: 'error',
                confirmButtonText: 'OK',
                customClass: {
                    confirmButton: 'btn btn-success'
                },
                buttonsStyling: false
            });
        }
    } catch (error) {
        Swal.fire({
            title: `ERROR: ${error.message.toUpperCase()}`,
            text: '',
            icon: 'error',
            confirmButtonText: 'OK',
            customClass: {
                confirmButton: 'btn btn-success'
            },
            buttonsStyling: false
        });
    }
}

function validaObra(codigo) {
    return existInDB('../06-funciones_php/funciones.php', 'existInDB', 'obras', 'obra_id', codigo, '')
        .then(response => {
            let parsedResponse = typeof response === 'string' ? JSON.parse(response) : response;
            return parsedResponse;
        })
        .catch(error => {
            return 'error';
        });
}

function validaUsuario(dni) {
    return existInDB('../06-funciones_php/funciones.php', 'existInDB', 'usuarios', 'nro_documento', dni)
        .then(response => {
            let parsedResponse = typeof response === 'string' ? JSON.parse(response) : response;

            // Verifica si la respuesta es 'false', tiene un 'status: false', o 'id_usuario' es 0
            if (!parsedResponse || parsedResponse.status === false || parsedResponse.id_usuario === 0) {
                console.log('DNI no encontrado, respuesta inválida, o id_usuario = 0');
                return { status: false, message: 'Usuario no encontrado o inválido' };
            }

            // Si la respuesta es válida y el id_usuario no es 0, la retorna
            return parsedResponse;
        })
        .catch(error => {
            console.error('Error en validaUsuario:', error);
            return { status: false, message: 'Error en la validación del usuario' };
        });
}


function formatDNI(dni) {
    let reversed = dni.split('').reverse().join('');
    let withDots = reversed.match(/.{1,3}/g).join('.');
    return withDots.split('').reverse().join('');
}

function calcularDistancia(lat1, lon1, lat2, lon2) {
    const R = 6371e3;
    const radianesLat1 = lat1 * Math.PI / 180;
    const radianesLat2 = lat2 * Math.PI / 180;
    const diferenciaLat = (lat2 - lat1) * Math.PI / 180;
    const diferenciaLon = (lon2 - lon1) * Math.PI / 180;

    const a = Math.sin(diferenciaLat / 2) * Math.sin(diferenciaLat / 2) +
              Math.cos(radianesLat1) * Math.cos(radianesLat2) *
              Math.sin(diferenciaLon / 2) * Math.sin(diferenciaLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

    return R * c;
}

</script>






