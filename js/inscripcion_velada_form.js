// /js/inscripcion_velada_form.js - Manejo Dinámico Formulario Inscripción Velada (v1)

document.addEventListener('DOMContentLoaded', function() {

    // === INICIO: IIFE para envolver toda la lógica del script ===
    (function() {

        // --- Elementos del Formulario ---
        const inscripcionForm = document.getElementById('inscripcion-form');
        if (!inscripcionForm) {
            console.log("DEBUG: Formulario de inscripción no encontrado, script inscripcion_velada_form.js no se ejecuta.");
            return;
        }

        const getElement = (id) => document.getElementById(id);
        const querySelector = (selector) => document.querySelector(selector);
        const querySelectorAll = (selector) => document.querySelectorAll(selector);


        // --- Selects principales (Declarados UNA VEZ al principio de la IIFE) ---
        // Nota: Este formulario no usa Cat/Peso/Sexo del combate, solo los datos del pugil
        const categoriaSelect = getElement('categoria'); // Podrían ser null en esta página
        const sexoSelect = getElement('sexo'); // Este es el sexo del *pugil* en la sección manual
        const pesoSelect = getElement('id_categoria_peso_combate'); // Podrían ser null en esta página
        const clubSelect = document.getElementById('id_club_pertenencia_inscripcion'); // Selector de Club principal
        const pugilSelect = document.getElementById('id_pugil_inscripcion'); // Selector de Púgil existente/nuevo
        const pugilDetailsSection = document.getElementById('pugil-details-section'); // Sección de entrada manual

        // Campos de entrada manual de púgil (solo si la sección existe)
        const manualNombre = pugilDetailsSection ? pugilDetailsSection.querySelector('#nombre_pugil') : null;
        const manualApellido = pugilDetailsSection ? pugilDetailsSection.querySelector('#apellido_pugil') : null;
        const manualFechaNac = pugilDetailsSection ? pugilDetailsSection.querySelector('#fecha_nacimiento') : null;
        const manualSexInputs = pugilDetailsSection ? pugilDetailsSection.querySelectorAll('input[name="sexo"]') : []; // Para manejar required en radios


        // --- Funciones AJAX Helper ---

        // Helper para buscar púgiles (amateur o profesional) basado en clubId o tipo
        // Rellena el selector de púgil proporcionado.
        function fetchAndPopulatePugilSelect(clubValue, selectElement, selectedPugilId = null) {
            if (!selectElement) return;

            selectElement.innerHTML = '<option value="">--- Cargando Púgiles... ---</option>';
            selectElement.disabled = true;
            selectElement.classList.remove('is-invalid', 'is-valid'); // Limpiar validación

            let url;
            // Determinar qué acción AJAX llamar según el valor del club
            if (clubValue === 'PROFESIONAL') {
                url = '../ajax_handler.php?action=get_profesionales'; // Obtener todos los profesionales
            } else if (clubValue && parseInt(clubValue, 10) > 0) {
                url = `../ajax_handler.php?action=get_pugiles_por_club&club_id=${clubValue}`; // Obtener amateurs por ID de club
            } else {
                // Club vacío o inválido
                selectElement.innerHTML = '<option value="">--- Selecciona un Club primero ---</option>';
                selectElement.disabled = true;
                return;
            }

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    selectElement.innerHTML = '<option value="">--- Seleccionar Púgil ---</option>'; // Limpiar y añadir opción por defecto

                    if (data.success && Array.isArray(data.data) && data.data.length > 0) {
                        data.data.forEach(pugil => {
                            const option = new Option(pugil.text, pugil.id);
                            // Intentar seleccionar el púgil si currentSelected coincide
                            if (selectedPugilId !== null && pugil.id == selectedPugilId) {
                                option.selected = true;
                            }
                            selectElement.add(option);
                        });
                        selectElement.disabled = false; // Habilitar el select si hay opciones
                    } else {
                        // No hay púgiles para este club/tipo
                        selectElement.innerHTML += '<option value="" disabled>--- No hay púgiles disponibles ---</option>';
                        // selectElement.disabled = true; // Dejamos que esté deshabilitado si no hay opciones para seleccionar
                    }

                    // Añadir la opción para crear nuevo púgil al final
                    selectElement.innerHTML += '<option value="--ADD_NEW--">** Añadir Nuevo Púgil... **</option>';
					selectElement.disabled = false;


                    // Intentar pre-seleccionar si selectedPugilId fue proporcionado y no se seleccionó automáticamente
                    if (selectedPugilId !== null && selectElement.value != selectedPugilId) {
                        console.warn(`DEBUG Inscripcion: Púgil ID inicial ${selectedPugilId} no encontrado en la lista cargada.`);
                        // Si el ID inicial no se encontró, puede que el púgil ya no pertenezca a este club
                        // o haya sido eliminado. Dejamos la opción por defecto seleccionada.
                         selectElement.value = "";
                    } else if (selectedPugilId === null) {
                         selectElement.value = ""; // Asegurar que la opción por defecto está seleccionada si no hay initial ID
                    }


                })
                .catch(error => {
                    console.error('Error al cargar púgiles para inscripción:', error);
                    selectElement.innerHTML = '<option value="">--- Error al cargar púgiles ---</option>';
                    selectElement.disabled = true;
                });
        }


        // --- Manejadores de Eventos ---

        // Manejar cambio en el selector de Club
        if (clubSelect) {
            clubSelect.addEventListener('change', function() {
                const selectedClubValue = this.value; // ID del club o 'PROFESIONAL' o ''

                // Limpiar y deshabilitar el selector de púgil mientras carga o si no aplica
                if (pugilSelect) {
                     pugilSelect.innerHTML = '<option value="">--- Cargando Púgiles... ---</option>';
                     pugilSelect.disabled = true;
                     pugilSelect.value = ""; // Resetear selección de púgil
                     pugilSelect.classList.remove('is-invalid', 'is-valid');
                 }


                // Ocultar y deshabilitar la sección de detalles manuales por defecto
                if (pugilDetailsSection) pugilDetailsSection.style.display = 'none';
                // Eliminar required de los campos manuales
                if (manualNombre) manualNombre.required = false;
                if (manualApellido) manualApellido.required = false;
                if (manualFechaNac) manualFechaNac.required = false;
                manualSexInputs.forEach(input => input.required = false);


                if (selectedClubValue) { // Si se seleccionó un Club (ID o 'PROFESIONAL')
                    // === Llamar a la función para cargar la lista de púgiles en el selector de púgil ===
                     // Si hubo un error POST y el usuario ya había seleccionado un club Y un púgil/ADD_NEW,
                     // el PHP ya pre-rellenó el valor del pugilSelect. Pasamos ese valor para preselección.
                     // Si el usuario había elegido "Añadir Nuevo", el valor del pugilSelect será "--ADD_NEW--" después del POST error.
                     const initialPugilValue = pugilSelect ? pugilSelect.value : null; // Obtener el valor actual (posiblemente pre-rellenado por PHP)
                     if (pugilSelect) { // Asegurarse de que el elemento pugilSelect existe
                        fetchAndPopulatePugilSelect(selectedClubValue, pugilSelect, initialPugilValue);
                     }
                    // === FIN ===

                } else { // Si se selecciona la opción vacía del Club
                    if (pugilSelect) {
                        pugilSelect.innerHTML = '<option value="">--- Selecciona un Club primero ---</option>';
                        pugilSelect.disabled = true;
                    }
                }
            });
        }

        // Manejar cambio en el selector de Púgil
        if (pugilSelect) {
            pugilSelect.addEventListener('change', function() {
                const selectedPugilValue = this.value; // ID del púgil, '' o '--ADD_NEW--'

                // Ocultar y deshabilitar la sección de detalles manuales por defecto
                if (pugilDetailsSection) pugilDetailsSection.style.display = 'none';
                 // Eliminar required de los campos manuales
                if (manualNombre) manualNombre.required = false;
                if (manualApellido) manualApellido.required = false;
                if (manualFechaNac) manualFechaNac.required = false;
                manualSexInputs.forEach(input => input.required = false);

                 // Limpiar campos manuales al cambiar la selección (excepto si es para re-mostrar después de error POST)
                 // Hacemos esto solo si seleccionamos "--ADD_NEW--" y los campos NO vienen pre-rellenados por un error de POST previo
                // if (selectedPugilValue !== '--ADD_NEW--') { // No limpiar si es --ADD_NEW-- porque queremos mostrar los valores pre-rellenados si los hay
                //      if (manualNombre) manualNombre.value = '';
                //      if (manualApellido) manualApellido.value = '';
                //      if (manualFechaNac) manualFechaNac.value = '';
                //      manualSexInputs.forEach(input => input.checked = false);
                // }


                if (selectedPugilValue === '--ADD_NEW--') {
                    // Si se selecciona la opción "Añadir Nuevo Púgil..."
                    if (pugilDetailsSection) pugilDetailsSection.style.display = 'block'; // Mostrar la sección de detalles manuales
                    // Hacer los campos manuales obligatorios
                    if (manualNombre) manualNombre.required = true;
                    if (manualApellido) manualApellido.required = true;
                    if (manualFechaNac) manualFechaNac.required = true;
                    manualSexInputs.forEach(input => input.required = true);

                    // Limpiar campos manuales solo si *no* están pre-rellenados por PHP/error POST.
                     // Si manualNombre tiene un valor, asumimos que PHP lo pre-rellenó por un error POST.
                     if (manualNombre && manualNombre.value === '') { // Check si el elemento existe Y está vacío
                         if (manualNombre) manualNombre.value = '';
                         if (manualApellido) manualApellido.value = '';
                         if (manualFechaNac) manualFechaNac.value = '';
                         manualSexInputs.forEach(input => input.checked = false);
                     } else if (!manualNombre && pugilDetailsSection) { // Si los elementos manuales no existen pero la sección sí (error en HTML?)
                          console.error("DEBUG Inscripcion: Campos manuales de púgil no encontrados aunque se seleccionó '--ADD_NEW--'.");
                     }

                    // Nota: Si quieres pre-rellenar los campos manuales con datos de un pugil existente
                    // al seleccionarlo, necesitarías otra llamada AJAX aquí (get_pugil_details) para obtener los datos
                    // y luego llenar los campos con esos datos. Por ahora, solo mostramos/ocultamos la sección
                    // y los campos se usan solo para AÑADIR un nuevo púgil.


                } else if (selectedPugilValue && selectedPugilValue !== '') {
                    // Si se selecciona un púgil existente (ID numérico)
                    // Ocultar la sección de detalles manuales (ya oculta arriba)
                    // No hacer los campos manuales obligatorios (ya no requeridos arriba)
                    // El ID del púgil seleccionado (selectedPugilValue) es el que se enviará en el formulario.
                } else {
                    // Si se selecciona la opción vacía ("--- Seleccionar Púgil ---")
                     // No hacer nada especial, ya está manejado arriba (sección oculta, campos no requeridos).
                }
            });
        }

        // --- Lógica de Carga Inicial ---
        // Si hay valores pre-rellenados por PHP (ej. después de un error POST),
        // necesitamos disparar los eventos change para que el JavaScript configure
        // el estado inicial correcto del formulario.
        setTimeout(() => { // Usar un pequeño retraso para asegurar que el DOM y otros scripts estén listos
             console.log("DEBUG Inscripcion: Ejecutando lógica de carga inicial.");

             // Obtener el valor inicial del club select (si PHP lo pre-rellenó)
             const initialClubValue = clubSelect ? clubSelect.value : null;

             if (initialClubValue) {
                  console.log("DEBUG Inscripcion: Club pre-seleccionado detectado (" + initialClubValue + "). Disparando change.");
                  // Disparar evento change en el selector de club para que se active la lógica
                  clubSelect.dispatchEvent(new Event('change', { bubbles: true }));

                  // La función handleClubChange ahora intentará poblar el pugilSelect y pre-seleccionar
                  // si initialPugilValue existe. Si initialPugilValue era '--ADD_NEW--',
                  // handleClubChange NO mostrará la sección manual por sí sola.
                  // Necesitamos manejar eso aquí.

                  const initialPugilValue = pugilSelect ? pugilSelect.value : null;
                   console.log("DEBUG Inscripcion: Valor inicial pre-rellenado en PugilSelect: " + initialPugilValue);


                  // Si el pugilSelect estaba pre-seleccionado con '--ADD_NEW--' (por error POST)
                  // y los campos manuales tienen valores pre-rellenados (también por error POST),
                  // necesitamos mostrar la sección manual manualmente aquí.
                  // Usamos manualNombre.value como indicador simple de si los campos manuales tienen datos pre-rellenados.
                  if (initialPugilValue === '--ADD_NEW--' && manualNombre && manualNombre.value !== '') {
                      console.log("DEBUG Inscripcion: Pugil select pre-seleccionado a ADD_NEW y campos manuales tienen datos. Mostrando sección manual.");
                      // Aunque handlePugilChange con '--ADD_NEW--' ya hace esto, lo aseguramos aquí
                      // y disparamos change para la validación.
                      if (pugilDetailsSection) pugilDetailsSection.style.display = 'block';
                      if (manualNombre) manualNombre.required = true;
                      if (manualApellido) manualApellido.required = true;
                      if (manualFechaNac) manualFechaNac.required = true;
                      manualSexInputs.forEach(input => input.required = true);

                      // Disparar change en el pugilSelect si es '--ADD_NEW--' para activar la validación BS
                      if (pugilSelect) {
                           console.log("DEBUG Inscripcion: Disparando change en PugilSelect (--ADD_NEW--) para validación.");
                           pugilSelect.dispatchEvent(new Event('change', { bubbles: true }));
                       }

                  } else if (initialPugilValue && initialPugilValue !== '' && initialPugilValue !== '--ADD_NEW--') {
                      // Si el pugilSelect estaba pre-seleccionado con un ID (por error POST)
                      // handleClubChange ya disparó la carga. Necesitamos asegurarnos de que
                      // handlePugilChange se dispare para configurar datosPugilRojo/Azul para updateCombateDetails
                      // (si es necesario, aunque este form no usa updateCombateDetails para categorías de peso).
                      // Pero es bueno disparar change para la validación BS.
                       console.log(`DEBUG Inscripcion: Pugil select pre-seleccionado a ID ${initialPugilValue}. Disparando change para validación.`);
                      if (pugilSelect) {
                           pugilSelect.dispatchEvent(new Event('change', { bubbles: true }));
                       }

                  } else if (clubSelect && clubSelect.value) {
                       // Si hay club pre-seleccionado pero no púgil pre-seleccionado (error POST o simplemente estado inicial válido)
                       // La función handleClubChange ya fue disparada arriba y dejó el pugilSelect en estado correcto.
                       console.log("DEBUG Inscripcion: Club pre-seleccionado, pero no pugil. Estado inicial manejado por handleClubChange.");
                  } else {
                       // Si no hay club pre-seleccionado en absoluto
                        console.log("DEBUG Inscripcion: No hay club pre-seleccionado. Configurando estado inicial del select de pugil y sección manual.");
                        if (pugilSelect) {
                             pugilSelect.innerHTML = '<option value="">--- Selecciona un Club primero ---</option>';
                             pugilSelect.disabled = true;
                         }
                         if (pugilDetailsSection) {
                             pugilDetailsSection.style.display = 'none';
                             if (manualNombre) manualNombre.required = false;
                             if (manualApellido) manualApellido.required = false;
                             if (manualFechaNac) manualFechaNac.required = false;
                             manualSexInputs.forEach(input => input.required = false);
                         }
                  }


             } else {
                 // Si no hay club pre-seleccionado en absoluto
                 console.log("DEBUG Inscripcion: No hay club pre-seleccionado. Configurando estado inicial del select de pugil y sección manual.");
                 if (pugilSelect) {
                      pugilSelect.innerHTML = '<option value="">--- Selecciona un Club primero ---</option>';
                      pugilSelect.disabled = true;
                 }
                  if (pugilDetailsSection) {
                      pugilDetailsSection.style.display = 'none';
                      if (manualNombre) manualNombre.required = false;
                      if (manualApellido) manualApellido.required = false;
                      if (manualFechaNac) manualFechaNac.required = false;
                      manualSexInputs.forEach(input => input.required = false);
                 }
             }
        }, 50); // Pequeño retraso inicial


        console.log("DEBUG: inscripcion_velada_form.js - Script cargado.");

        // === FIN: IIFE ===
        })();


}); // Fin DOMContentLoaded