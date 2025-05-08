/**
 * cookieValidCreate - Verifica si una cookie existe, y si no, la crea con un valor y vigencia proporcionados.
 *
 * Funcionalidad:
 * - Si la cookie con el nombre especificado ya existe, la función devuelve su valor.
 * - Si la cookie no existe, se crea con el nombre, valor y la vigencia especificados.
 * - En caso de crear una nueva cookie, la función devuelve el valor provisto.
 *
 * Parámetros:
 * @param {string} name - El nombre de la cookie que se desea verificar o crear.
 * @param {string} value - El valor que se asignará a la cookie si se crea.
 * @param {number} days - La cantidad de días que la cookie será válida. Si no se proporciona, la cookie será de sesión.
 *
 * @return {string|null} - Retorna el valor de la cookie si ya existe o el valor recién creado si no existía.
 *                         Si la cookie no existe y no se pudo crear, devuelve null.
 *
 * Ejemplo de uso:
 * 
 * // Verifica si existe la cookie 'usuario'. Si no existe, la crea con el valor 'JuanPerez' y una vigencia de 7 días.
 * let cookieValue = cookieValidCreate("usuario", "JuanPerez", 7);
 *
 * console.log("El valor de la cookie 'usuario' es: " + cookieValue);
 * 
 * - Si la cookie 'usuario' ya existe, se mostrará su valor.
 * - Si la cookie no existe, se creará con el valor 'JuanPerez' y vigencia de 7 días, devolviendo ese valor.
 */

function cookieValidCreate(name, value, days) {
  // Obtener todas las cookies en un array separadas por ";"
  let cookieArr = document.cookie.split(";");

  // Recorrer las cookies buscando la que coincida con el nombre
  for (let i = 0; i < cookieArr.length; i++) {
    let cookiePair = cookieArr[i].split("=");

    // Limpiar los espacios y verificar si el nombre coincide
    if (name === cookiePair[0].trim()) {
      // Si la cookie existe, devolver su valor
      return decodeURIComponent(cookiePair[1]);
    }
  }

  // Si la cookie no existe, crearla con el valor y vigencia provistos
  let expires = "";
  
  if (days) {
    let date = new Date();
    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000)); // Convertir los días en milisegundos
    expires = "; expires=" + date.toUTCString();
  }

  // Crear la cookie
  document.cookie = name + "=" + encodeURIComponent(value) + expires + "; path=/";

  // Devolver el valor provisto, ya que la cookie se acaba de crear
  return value;
}