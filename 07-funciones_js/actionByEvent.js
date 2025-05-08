/*
 * Función: actionByEvent
 * ----------------------
 * Ejecuta funciones específicas en respuesta a eventos definidos en elementos del DOM.
 * 
 * Parámetros:
 *  - elements: Array de selectores de elementos del DOM (por ejemplo, ['#miId', '.miClase', 'div']).
 *  - events: Array de arrays, donde cada subarray contiene eventos que deben ser escuchados en los elementos correspondientes (por ejemplo, [['click', 'change'], ['mouseover'], ['click']]).
 *  - actions: Array de arrays, donde cada subarray contiene las funciones a ejecutar para los eventos correspondientes. Las funciones pueden ser funciones anónimas o referencias a funciones existentes (por ejemplo, [[() => funcion1(), funcion2]]).

 * Ejemplo de uso:
 *  actionByEvent(
 *      ['#mielemento'],                      // Elemento del DOM
 *      [['click', 'change']],                // Eventos a escuchar: 'click' y 'change'
 *      [[                                    // Funciones a ejecutar:
 *          () => funcion1(),                 // 'click' ejecuta 'funcion1()'
 *          () => funcion2()                  // 'change' ejecuta 'funcion2()'
 *      ]]
 *  );
 *
 * Detalles:
 *  - Esta función asocia uno o más eventos a uno o más elementos del DOM.
 *  - Las acciones se ejecutan como funciones que reciben el objeto `event` del evento capturado.
 *  - Las acciones pueden ser funciones anónimas definidas en línea o referencias a funciones previamente definidas.
 *  - Se ha eliminado el uso de `eval` para mejorar la seguridad y el rendimiento.
 */


function actionByEvent(elements, events, actions) {
    elements.forEach((selector, index) => {
        let elementsToModify;

        // Determinamos el tipo de selector y obtenemos los elementos correspondientes del DOM
        if (selector.startsWith('#')) {
            elementsToModify = [document.getElementById(selector.slice(1))];
        } else if (selector.startsWith('.')) {
            elementsToModify = document.getElementsByClassName(selector.slice(1));
        } else {
            elementsToModify = document.getElementsByTagName(selector);
        }

        // Convertimos los elementos obtenidos en un array para poder iterar sobre ellos
        Array.from(elementsToModify).forEach(element => {
            if (element) {
                const eventList = events[index] || [];
                const actionList = actions[index] || [];

                eventList.forEach((eventType, eventIndex) => {
                    element.addEventListener(eventType, function(event) {
                        //console.log(`Evento ${eventType} detectado en:`, element);

                        // Obtenemos los atributos data-* del elemento
                        const dataAttributes = element.dataset;

                        // Obtenemos la función o array de funciones correspondientes a este evento
                        const actionFn = actionList[eventIndex];

                        if (typeof actionFn === 'function') {
                            try {
                                // Ejecutamos la función pasando los data-* como argumento
                                actionFn(event, dataAttributes);
                            } catch (error) {
                                console.error('Error ejecutando la acción:', error);
                            }
                        } else if (Array.isArray(actionFn)) {
                            actionFn.forEach(fn => {
                                if (typeof fn === 'function') {
                                    try {
                                        fn(event, dataAttributes);
                                    } catch (error) {
                                        console.error('Error ejecutando la acción:', error);
                                    }
                                }
                            });
                        }
                    });
                });
            }
        });
    });
}



