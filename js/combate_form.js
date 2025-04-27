// /js/combate_form.js - v6.4 (Manejo de Select Procedencia + Corrección ReferenceError con IIFE)

document.addEventListener('DOMContentLoaded', function() {

    // === INICIO: IIFE para envolver toda la lógica del script ===
    (function() {

        // --- Elementos Comunes ---
        // Ahora todos los elementos y helpers se definen DENTRO de la IIFE
        const form = document.getElementById('form-combate') || document.getElementById('form-combate-edit');
        // Si el formulario no existe, salimos de la IIFE
        if (!form) {
             console.log("DEBUG: Formulario de combate no encontrado, script combate_form.js no se ejecuta.");
             return;
        }

        const getElement = (id) => document.getElementById(id);
        const querySelector = (selector) => document.querySelector(selector);
        const querySelectorAll = (selector) => document.querySelectorAll(selector);


        // --- Selects principales (Declarados UNA VEZ al principio de la IIFE) ---
        const categoriaSelect = getElement('categoria');
        const sexoSelect = getElement('sexo');
        const pesoSelect = getElement('id_categoria_peso_combate');
        const clubRojoSelect = getElement('id_club_rojo');
        const clubAzulSelect = getElement('id_club_azul');
        const pugilRojoSelect = getElement('id_pugil_rojo');
        const pugilAzulSelect = getElement('id_pugil_azul');
        const procedenciaRojoSelect = getElement('procedencia_rojo');
        const procedenciaAzulSelect = getElement('procedencia_azul');

        // --- Elementos Modales (igual que antes, declarados dentro de la IIFE) ---
        const addNewClubModalElement = getElement('addNewClubModal'); const newClubNameInput = getElement('newClubNameInput'); const saveNewClubBtn = getElement('saveNewClubBtn'); const modalAlertPlaceholder = getElement('modalAlertPlaceholder'); let addNewClubModal = null; if (addNewClubModalElement && bootstrap.Modal) { try { addNewClubModal = new bootstrap.Modal(addNewClubModalElement); } catch(e){}}
        const addNewProModalElement = getElement('addNewProModal'); const newProNombreInput = getElement('newProNombreInput'); const newProApellidoInput = getElement('newProApellidoInput'); const newProSexoSelect = getElement('newProSexoSelect'); const saveNewProBtn = getElement('saveNewProBtn'); const modalProAlertPlaceholder = getElement('modalProAlertPlaceholder'); let addNewProModal = null; if (addNewProModalElement && bootstrap.Modal) { try { addNewProModal = new bootstrap.Modal(addNewProModalElement); } catch(e){}}
        const addNewPugilModalElement = getElement('addNewPugilModal'); const newPugilNombreInput = getElement('newPugilNombreInput'); const newPugilApellidoInput = getElement('newPugilApellidoInput'); const newPugilDobInput = getElement('newPugilDobInput'); const newPugilSexoSelect = getElement('newPugilSexoSelect'); const saveNewPugilBtn = getElement('saveNewPugilBtn'); const modalPugilAlertPlaceholder = getElement('modalPugilAlertPlaceholder'); const modalPugilClubNameElement = getElement('modalPugilClubName'); const modalPugilClubIdElement = getElement('modalPugilClubId'); let addNewPugilModal = null; if (addNewPugilModalElement && bootstrap.Modal) { try { addNewPugilModal = new bootstrap.Modal(addNewPugilModalElement); } catch(e){}}

        let triggeringSelectElement = null; // Usado en modales
        let triggeringPugilSelect = null;   // Usado en modales

        // --- Variables de Estado ---
        let datosPugilRojo = null; let datosPugilAzul = null;

        // --- Valores Iniciales (Editar) ---
        let initialData = {}; const initialDataElement = getElement('initial-form-data'); if (initialDataElement) { try { initialData = JSON.parse(initialDataElement.textContent || '{}'); } catch(e) { initialData = {}; } }

         // --- Funciones Helper (igual que antes) ---
        function showModalAlert(message, type = 'danger') { if (!modalAlertPlaceholder) return; modalAlertPlaceholder.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">${message}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`;}
        function showModalProAlert(message, type = 'danger') { if (!modalProAlertPlaceholder) return; modalProAlertPlaceholder.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">${message}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`;}
        function showModalPugilAlert(message, type = 'danger') { if (!modalPugilAlertPlaceholder) return; modalPugilAlertPlaceholder.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">${message}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`;}


        // --- Funciones AJAX para cargar desplegables ---

      function fetchAndPopulatePugiles(clubId, pugilSelectElement, selectedPugilId = null, excludePugilId = null) {
  if (!pugilSelectElement) return;
  const currentSelected = selectedPugilId || pugilSelectElement.value;
  pugilSelectElement.innerHTML = '<option value="">--- Cargando... ---</option>';
  pugilSelectElement.disabled = true;
  const currentCategoria = categoriaSelect ? categoriaSelect.value : null;
  const currentSexo = sexoSelect ? sexoSelect.value : null;
  let url = `../ajax_handler.php?action=get_pugiles_por_club&club_id=${clubId}`;
  if (currentCategoria && currentSexo) { url += `&categoria=${encodeURIComponent(currentCategoria)}&sexo=${encodeURIComponent(currentSexo)}`;}
  if (excludePugilId) { url += `&exclude_id=${excludePugilId};`}
  fetch(url).then(response => response.json()).then(data => {
    pugilSelectElement.innerHTML = '<option value="">--- Seleccionar Púgil ---</option>';
    if (data.success && data.data && data.data.length > 0) {
      const opponentId = document.getElementById('oponente-select') ? document.getElementById('oponente-select').value : null;
      data.data.forEach(pugil => {
        if (pugil.id !== opponentId) {
          const option = new Option(pugil.text, pugil.id);
          if (pugil.id == currentSelected) option.selected = true;
          pugilSelectElement.add(option);
        }
      });
    } else { pugilSelectElement.innerHTML += '<option value="" disabled>--- No hay púgiles ---</option>';}
    pugilSelectElement.innerHTML += '<option value="--ADD_PUGIL--">** Añadir Nuevo Púgil... **</option>';
  }).catch(error => { console.error('Error al cargar púgiles:', error); pugilSelectElement.innerHTML = '<option value="">--- Error al cargar ---</option>';})
  .finally(() => { const clubSelect = document.getElementById(pugilSelectElement.id.replace('pugil', 'club')); pugilSelectElement.disabled = !(clubSelect && clubSelect.value && clubSelect.value !== 'PROFESIONAL'); });
}

function fetchAndPopulateProfesionales(pugilSelectElement, selectedPugilId = null, excludePugilId = null) {
  try {
    if (!pugilSelectElement) return Promise.resolve();
    const currentSelected = selectedPugilId || (pugilSelectElement.value || '');
    pugilSelectElement.innerHTML = '<option value="">--- Cargando... ---</option>';
    pugilSelectElement.disabled = true;
    const currentSexo = sexoSelect ? sexoSelect.value : null;
    let url = `../ajax_handler.php?action=get_profesionales`;
    if (currentSexo) { url += `&sexo=${encodeURIComponent(currentSexo)}`;}
    if (excludePugilId) { url += `&exclude_id=${excludePugilId};`}
    return fetch(url).then(response => response.json()).then(data => {
      pugilSelectElement.innerHTML = '<option value="">--- Seleccionar Profesional ---</option>';
      if (data.success && data.data && data.data.length > 0) {
        data.data.forEach(pro => {
          if (pro.id !== excludePugilId) {
            const option = new Option(pro.text, pro.id);
            if (pro.id == currentSelected) option.selected = true;
            pugilSelectElement.add(option);
          }
        });
      } else { pugilSelectElement.innerHTML += '<option value="" disabled>--- No hay profesionales ---</option>';}
      pugilSelectElement.innerHTML += '<option value="--ADD_PRO--">** Registrar Nuevo Pro... **</option>';
      return data;
    }).catch(error => { console.error('Error al cargar profesionales:', error); pugilSelectElement.innerHTML = '<option value="">--- Error al cargar ---</option>'; throw error;})
    .finally(() => { pugilSelectElement.disabled = false; });
  } catch (error) {
    console.error('Error al cargar profesionales:', error);
  }
}

        // === Función fetchAndPopulatePesos (igual que antes) ===
        function fetchAndPopulatePesos(categoria, sexo, pesoSelectElement, selectedPesoId = null) {
            if (!pesoSelectElement) return; const currentSelected = selectedPesoId || pesoSelectElement.value; pesoSelectElement.innerHTML = '<option value="">--- Cargando... ---</option>'; pesoSelectElement.disabled = true; if (!categoria || !sexo) { pesoSelectElement.innerHTML = '<option value="">--- S. Cat/Sexo ---</option>'; pesoSelectElement.disabled = true; return; } const validC = ['Profesional','Elite','Joven','Junior']; const validS = ['Masculino','Femenino']; if (!validC.includes(categoria) || !validS.includes(sexo)) { pesoSelectElement.innerHTML = '<option value="">--- Cat/Sexo Inv. ---</option>'; pesoSelectElement.disabled = true; return; } let raw=''; fetch(`../ajax_handler.php?action=get_pesos_por_categoria&categoria=${categoria}&sexo=${sexo}`).then(r => {if(!r.ok){return r.text().then(t=>{raw=t; throw new Error(r.statusText);});} return r.clone().text().then(t=>{raw=t; return r.json();});}).then(d=>{ pesoSelectElement.innerHTML = '<option value="">--- S. Peso ---</option>'; if (d.success && Array.isArray(d.data)){ if (d.data.length > 0){ d.data.forEach(p => { const o=document.createElement('option'); o.value=p.id; o.textContent=p.text; if (p.id == currentSelected) o.selected = true; pesoSelectElement.appendChild(o); }); pesoSelectElement.disabled = false; } else { pesoSelectElement.innerHTML += '<option value="" disabled>--- No hay pesos ---</option>'; pesoSelectElement.disabled = true; } } else { pesoSelectElement.innerHTML += '<option value="" disabled>--- Error Resp ---</option>'; pesoSelectElement.disabled = true; } if (currentSelected && pesoSelectElement.querySelector(`option[value="${currentSelected}"]`)) { pesoSelectElement.value = currentSelected; } }).catch(e=>{ console.error('Error fetch pesos:', e, "RAW:", raw); pesoSelectElement.innerHTML = '<option value="">--- Error Red/Proc ---</option>'; pesoSelectElement.disabled = true; }); }


        // === Cargar y Poblar Desplegable de Procedencias (igual que antes) ===
        function fetchAndPopulateProcedencias(selectElement, selectedId = null) {
            if (!selectElement) return;

            selectElement.innerHTML = '<option value="">--- Cargando Procedencias... ---</option>';
            selectElement.disabled = true;

            fetch('../ajax_handler.php?action=get_procedencias')
                .then(response => response.json())
                .then(data => {
                    selectElement.innerHTML = '<option value="">--- Seleccionar Procedencia ---</option>'; // Limpiar y añadir opción por defecto

                    if (data.success && Array.isArray(data.data)) {
                        const procedencias = data.data;

                        const groupedProcedencias = {};
                        procedencias.forEach(proc => {
                            const groupKey = proc.tipo === 'Comunidad Autónoma' ? 'Comunidades Autónomas' : (proc.grupo || 'Otros Países'); // Clave para agrupar
                            if (!groupedProcedencias[groupKey]) {
                                groupedProcedencias[groupKey] = [];
                            }
                            groupedProcedencias[groupKey].push(proc);
                        });

                        const groupOrder = ['Comunidades Autónomas', 'Europa', 'América del Norte', 'América Central y Caribe', 'América del Sur', 'Asia', 'África', 'Oceanía', 'Otros Países'];

                        groupOrder.forEach(groupKey => {
                            if (groupedProcedencias[groupKey] && groupedProcedencias[groupKey].length > 0) {
                                const optgroup = document.createElement('optgroup');
                                optgroup.label = groupKey;

                                groupedProcedencias[groupKey].forEach(proc => {
                                    const option = new Option(proc.text, proc.id);
                                    if (selectedId !== null && proc.id == selectedId) {
                                        option.selected = true;
                                    }
                                    optgroup.appendChild(option);
                                });
                                selectElement.appendChild(optgroup);
                            }
                        });

                        if (selectElement.options.length <= 1) {
                             selectElement.innerHTML = '<option value="">--- No hay Procedencias ---</option>';
                             selectElement.disabled = true;
                        } else {
                             selectElement.disabled = false;
                             if (selectedId !== null) {
                                  selectElement.value = selectedId;
                                  if (selectElement.value != selectedId && selectedId !== "") {
                                      console.warn(`DEBUG: Procedencia ID inicial ${selectedId} no encontrada en la lista cargada.`);
                                      // Opcionalmente, podrías resetear a la opción por defecto:
                                      // selectElement.value = "";
                                  }
                             } else {
                                  selectElement.value = "";
                             }
                        }

                    } else {
                        console.error('Error al cargar procedencias:', data.message || "Formato de datos inválido o vacío");
                        selectElement.innerHTML = '<option value="">--- Error al cargar ---</option>';
                        selectElement.disabled = true;
                    }
                })
                .catch(error => {
                    console.error('Error en fetch de procedencias:', error);
                    selectElement.innerHTML = '<option value="">--- Error Red/Proc ---</option>';
                    selectElement.disabled = true;
                });
        }


        // --- ============================================= ---
        // ---            MANEJADORES DE EVENTOS             ---
        // --- ============================================= ---

        // --- Manejador Cambio Club (ACTUALIZADO para Procedencia Select) ---
         function handleClubChange(clubSelectElement) {
            if (!clubSelectElement) return;
            const selectedValue = clubSelectElement.value;
            const isRojo = clubSelectElement.id.includes('rojo');
            const targetPugilSelector = clubSelectElement.dataset.targetPugil;
            const targetProcedenciaSelector = clubSelectElement.dataset.targetProcedencia;

            // === Buscar elementos de Rincón usando los selectores de datos ===
            const pugilSelect = querySelector(targetPugilSelector);
            const procedenciaDiv = querySelector(targetProcedenciaSelector);
            // Buscar el select de procedencia dentro del div de procedencia
            const procedenciaSelectElement = procedenciaDiv ? procedenciaDiv.querySelector('.procedencia-select') : null; // <-- Buscar la clase correcta
            // === FIN ===

            const otroClubSelectId = isRojo ? 'id_club_azul' : 'id_club_rojo';
            const otroClubSelect = getElement(otroClubSelectId);

            // === Verificar que todos los elementos necesarios existan ===
            if (!pugilSelect || !procedenciaDiv || !procedenciaSelectElement || !otroClubSelect) {
                 console.error("handleClubChange: Targets no encontrados para", clubSelectElement.id, {pugilSelect, procedenciaDiv, procedenciaSelectElement, otroClubSelect});
                 return; // Salir si falta algún elemento
            }
            // === FIN ===


            // Resetear estados de los elementos de Rincón
            pugilSelect.required = false; procedenciaSelectElement.required = false; // Ambos no requeridos por defecto
            procedenciaDiv.style.display = 'none'; // Ocultar procedencia por defecto
            pugilSelect.disabled = true; // Deshabilitar púgil por defecto


            // Limpiar y resetear selectores (excepto si es --ADD_NEW--)
            if (selectedValue !== '--ADD_NEW--') { pugilSelect.innerHTML = '<option value="">--- Seleccionar Púgil ---</option>'; }
            // Siempre resetear procedencia select
            procedenciaSelectElement.innerHTML = '<option value="">--- Cargando Procedencias... ---</option>'; // Placeholder inicial
            procedenciaSelectElement.disabled = true; // Deshabilitar procedencia


            // Limpiar estilos de validación
            pugilSelect.classList.remove('is-invalid', 'is-valid');
            procedenciaSelectElement.classList.remove('is-invalid', 'is-valid');


            // Aplicar lógica según el valor seleccionado
            if (selectedValue === 'PROFESIONAL') {
                console.log("DEBUG: Club PRO seleccionado. Mostrando procedencia...");
                procedenciaDiv.style.display = 'block'; // Mostrar div de procedencia

                // === Habilitar y cargar datos para SELECT de Procedencia ===
                procedenciaSelectElement.required = true; // Hacer el select de procedencia obligatorio
                // Cargar las opciones de procedencia y seleccionar la inicial si existe (para edición)
                const initialProcId = initialData[procedenciaSelectElement.id] || null;
                fetchAndPopulateProcedencias(procedenciaSelectElement, initialProcId);
                // === FIN ===

                // === Habilitar y cargar datos para SELECT de Púgil Profesional ===
                 pugilSelect.required = true; // Hacer el select de púgil obligatorio (un profesional debe ser seleccionado)
                 // Cargar la lista de profesionales y seleccionar el inicial si existe (para edición)
                 const initialPugilId = initialData[pugilSelect.id] || null;
                 fetchAndPopulateProfesionales(pugilSelect, initialPugilId);
                // === FIN ===


                // Sincronizar el otro rincón si estaba en Profesional
                if (otroClubSelect && otroClubSelect.value !== 'PROFESIONAL') {
                     otroClubSelect.value = 'PROFESIONAL';
                     // Disparar evento change en el otro select para que su manejador actualice su lado
                     otroClubSelect.dispatchEvent(new Event('change', { bubbles: true }));
                }


            } else if (selectedValue && selectedValue !== '--ADD_NEW--') { // Rincón Amateur con Club (ID numérico)
                console.log("DEBUG: Club Amateur seleccionado. Ocultando procedencia.");
                // Ocultar y deshabilitar procedencia para Amateur
                procedenciaDiv.style.display = 'none';
                procedenciaSelectElement.required = false;
                procedenciaSelectElement.innerHTML = '<option value="">--- No aplica (Amateur) ---</option>'; // Reset/placeholder
                procedenciaSelectElement.disabled = true;

                 // === Habilitar y cargar datos para SELECT de Púgil Amateur ===
                 const clubId = parseInt(selectedValue, 10);
                 if (!isNaN(clubId) && clubId > 0) {
                      pugilSelect.required = true; // Hacer el select de púgil obligatorio para Amateur con Club
                      // Cargar la lista de púgiles amateur de este club y seleccionar el inicial si existe
                      const initialPugilId = initialData[pugilSelect.id] || null;
                      fetchAndPopulatePugiles(selectedValue, pugilSelect, initialPugilId);
                 } else {
                      // Si el ID del club no es válido, no cargar púgiles y mantener deshabilitado/no requerido
                      pugilSelect.innerHTML = '<option value="">--- Club Inválido ---</option>';
                      pugilSelect.disabled = true; // Asegurarse de que está deshabilitado
                      pugilSelect.required = false;
                 }
                // === FIN ===

                // Sincronizar el otro rincón si estaba en Profesional
                if (otroClubSelect && otroClubSelect.value === 'PROFESIONAL') {
                     otroClubSelect.value = ''; // Reset other corner if it was Professional
                     // Disparar evento change en el otro select
                     otroClubSelect.dispatchEvent(new Event('change', { bubbles: true }));
                }

            } else if (selectedValue === '--ADD_NEW--') { // Acción "Añadir Nuevo Club..."
                console.log("DEBUG: Añadir Nuevo Club seleccionado.");
                 if (!addNewClubModal) return; triggeringSelectElement = clubSelectElement; if(newClubNameInput) newClubNameInput.value = ''; if(modalAlertPlaceholder) modalAlertPlaceholder.innerHTML = ''; addNewClubModal.show();

                 // Ocultar y deshabilitar los otros campos del rincón
                 if(pugilSelect) { pugilSelect.innerHTML = '<option value="">--- Seleccione Club ---</option>'; pugilSelect.disabled = true; pugilSelect.required = false; }
                 if(procedenciaDiv) procedenciaDiv.style.display = 'none';
                 if(procedenciaSelectElement) { procedenciaSelectElement.innerHTML = '<option value="">--- Seleccione Club ---</option>'; procedenciaSelectElement.disabled = true; procedenciaSelectElement.required = false; }


                // Sincronizar el otro rincón si estaba en Profesional
                 if (otroClubSelect && otroClubSelect.value === 'PROFESIONAL') {
                     otroClubSelect.value = '';
                     // Disparar evento change en el otro select
                     otroClubSelect.dispatchEvent(new Event('change', { bubbles: true }));
                }

            } else { // Opción vacía seleccionada ("--- Seleccionar Club ---")
                 console.log("DEBUG: Seleccionar Club vacío.");
                 // Ocultar y deshabilitar los otros campos del rincón
                 if(pugilSelect) { pugilSelect.innerHTML = '<option value="">--- Seleccione Club ---</option>'; pugilSelect.disabled = true; pugilSelect.required = false; }
                 if(procedenciaDiv) procedenciaDiv.style.display = 'none';
                 if(procedenciaSelectElement) { procedenciaSelectElement.innerHTML = '<option value="">--- Seleccione Club ---</option>'; procedenciaSelectElement.disabled = true; procedenciaSelectElement.required = false; }

                // Sincronizar el otro rincón si estaba en Profesional
                 if (otroClubSelect && otroClubSelect.value === 'PROFESIONAL') {
                     otroClubSelect.value = '';
                     // Disparar evento change en el otro select
                     otroClubSelect.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }

            // Después de actualizar los selectores del rincón, reevaluar los detalles del combate
            updateCombateDetails();
        } // Fin handleClubChange


        function handlePugilChange(pugilSelectElement) { /* ... igual que antes, llama a updateCombateDetails ... */
            if (!pugilSelectElement) return;

            const selectedValue = pugilSelectElement.value;
            const corner = pugilSelectElement.id.includes('rojo') ? 'Rojo' : 'Azul';

            // Resetear datos del púgil
            if (corner === 'Rojo') {
                datosPugilRojo = null;
            } else {
                datosPugilAzul = null;
            }

            // Manejar opciones especiales (--ADD_PRO--, --ADD_PUGIL--)
            if (selectedValue === '--ADD_PRO--') {
                if (addNewProModal) { triggeringPugilSelect = pugilSelectElement; newProNombreInput.value = ''; newProApellidoInput.value = ''; newProSexoSelect.value = ''; modalProAlertPlaceholder.innerHTML = ''; addNewProModal.show(); }
                pugilSelectElement.value = ''; // Reset select value after triggering modal
                updateCombateDetails(); // Update details based on empty selection
                return;
            } else if (selectedValue === '--ADD_PUGIL--') {
                const clubSelectId = pugilSelectElement.id.replace('pugil', 'club');
                const clubSelect = getElement(clubSelectId);
                if (clubSelect && clubSelect.value && parseInt(clubSelect.value) > 0 && addNewPugilModal) {
                    triggeringPugilSelect = pugilSelectElement;
                    modalPugilClubIdElement.value = clubSelect.value;
                    modalPugilClubNameElement.textContent = clubSelect.options[clubSelect.selectedIndex].text;
                    newPugilNombreInput.value = ''; newPugilApellidoInput.value = ''; newPugilDobInput.value = ''; newPugilSexoSelect.value = ''; modalPugilAlertPlaceholder.innerHTML = '';
                    addNewPugilModal.show();
                } else { alert('Primero seleccione un club válido'); }
                pugilSelectElement.value = ''; // Reset select value after triggering modal
                updateCombateDetails(); // Update details based on empty selection
                return;
            }


            // Obtener detalles del púgil seleccionado (solo si es un ID numérico válido)
            const pugilId = parseInt(selectedValue);
            // Dentro de handlePugilChange(pugilSelectElement) { ... }
// ... (código hasta la verificación if (isNaN(pugilId)...) y la llamada fetch(...get_pugil_details...))

if (isNaN(pugilId) || pugilId <= 0) {
    console.log("DEBUG: Selected pugil ID is invalid, skipping details fetch.");
    // Además de actualizar la otra lista, resetea los datos locales para ESTE púgil
    const corner = pugilSelectElement.id.includes('rojo') ? 'Rojo' : 'Azul';
    if (corner === 'Rojo') {
        datosPugilRojo = null;
    } else {
        datosPugilAzul = null;
    }
    // Llama a updateCombateDetails aquí, ya que la selección actual es inválida
    updateCombateDetails();
    return; // Sale de la función si el ID no es válido
}

// Si el ID del púgil es válido, procede a obtener detalles vía AJAX
fetch(`../ajax_handler.php?action=get_pugil_details&id_pugil=${pugilId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data) {
            const pugilData = {
                id: data.data.id_pugil,
                categoria_calculada: data.data.categoria_calculada,
                sexo: data.data.sexo,
                es_profesional: data.data.es_profesional
            };

            const corner = pugilSelectElement.id.includes('rojo') ? 'Rojo' : 'Azul';
            if (corner === 'Rojo') {
                datosPugilRojo = pugilData;
            } else {
                datosPugilAzul = pugilData;
            }
            // === LLAMA A updateCombateDetails AQUÍ DENTRO DEL .then() ===
            console.log("DEBUG: Detalles del púgil cargados. Actualizando detalles del combate.");
            updateCombateDetails();
            // === FIN LLAMADA ===
        } else {
            console.error(`Error al obtener detalles del púgil:`, data.message || "Formato de datos inválido o vacío");
            // Si falló la carga de detalles, resetea los datos locales para este púgil
            const corner = pugilSelectElement.id.includes('rojo') ? 'Rojo' : 'Azul';
            if (corner === 'Rojo') {
                datosPugilRojo = null;
            } else {
                datosPugilAzul = null;
            }
            // === LLAMA A updateCombateDetails AQUÍ TAMBIÉN ===
            console.log("DEBUG: Error al cargar detalles del púgil. Actualizando detalles del combate.");
            updateCombateDetails();
            // === FIN LLAMADA ===
        }
    })
    .catch(error => {
        console.error(`Error en fetch de detalles del púgil:`, error);
        // Si el fetch falló completamente, resetea los datos locales
        const corner = pugilSelectElement.id.includes('rojo') ? 'Rojo' : 'Azul';
        if (corner === 'Rojo') {
            datosPugilRojo = null;
        } else {
            datosPugilAzul = null;
        }
        // === LLAMA A updateCombateDetails AQUÍ TAMBIÉN ===
        console.log("DEBUG: Error de red/fetch en detalles del púgil. Actualizando detalles del combate.");
        updateCombateDetails();
        // === FIN LLAMADA ===
    });
        }


        // --- Función updateCombateDetails (igual que antes) ---
        function updateCombateDetails() { console.log("DEBUG: updateCombateDetails - Ejecutando..."); console.log("DEBUG: Estado actual -> datosPugilRojo:", datosPugilRojo, "datosPugilAzul:", datosPugilAzul); let targetCategoria = ''; let targetSexo = ''; let lockFields = false; let primerPugilData = null; let pesoIdPreselect = null; if (datosPugilRojo) { primerPugilData = datosPugilRojo; } else if (datosPugilAzul) { primerPugilData = datosPugilAzul; } if (datosPugilRojo && datosPugilAzul) { const catRojo = datosPugilRojo.categoria_calculada; const catAzul = datosPugilAzul.categoria_calculada; const sexoRojo = datosPugilRojo.sexo; const sexoAzul = datosPugilAzul.sexo; const proRojo = datosPugilRojo.es_profesional; const proAzul = datosPugilAzul.es_profesional; if(proRojo !== proAzul){ alert("¡Error! No se puede enfrentar un Profesional contra un Amateur."); lockFields = false; targetCategoria = ''; targetSexo = ''; primerPugilData = null; } else if (sexoRojo !== sexoAzul) { alert("¡Error! Los púgiles deben ser del mismo sexo."); lockFields = false; targetCategoria = ''; targetSexo = ''; primerPugilData = null; } else if (!proRojo && !proAzul && catRojo !== catAzul && catRojo !== 'Desconocida' && catAzul !== 'Desconocida') { alert("¡Error! Los púgiles amateur deben ser de la misma categoría de edad."); lockFields = false; targetCategoria = ''; targetSexo = ''; primerPugilData = null; } else if (primerPugilData) { targetCategoria = primerPugilData.categoria_calculada; targetSexo = primerPugilData.sexo; lockFields = true; } } else if (primerPugilData) { targetCategoria = primerPugilData.categoria_calculada; targetSexo = primerPugilData.sexo; lockFields = true; } const validCategorias = ['Profesional', 'Elite', 'Joven', 'Junior']; if (lockFields && !validCategorias.includes(targetCategoria)) { console.warn(`Cat. calculada '${targetCategoria}' inválida. Desbloqueando.`); lockFields = false; targetCategoria = ''; targetSexo = ''; } let needsPesoReload = false; if (categoriaSelect) { if (categoriaSelect.value !== targetCategoria) needsPesoReload = true; categoriaSelect.value = targetCategoria; categoriaSelect.disabled = lockFields; categoriaSelect.classList.remove('is-invalid', 'is-valid'); } if (sexoSelect) { if (sexoSelect.value !== targetSexo) needsPesoReload = true; sexoSelect.value = targetSexo; sexoSelect.disabled = lockFields; sexoSelect.classList.remove('is-invalid', 'is-valid'); } if (categoriaSelect?.value === (initialData.categoria || null) && sexoSelect?.value === (initialData.sexo || null)) { pesoIdPreselect = initialData.id_categoria_peso_combate || null; } if (categoriaSelect?.value && sexoSelect?.value && pesoSelect) { fetchAndPopulatePesos(categoriaSelect.value, sexoSelect.value, pesoSelect, pesoIdPreselect); } else if (pesoSelect) { pesoSelect.innerHTML = '<option value="">--- Seleccione Cat/Sexo ---</option>'; pesoSelect.disabled = true; } console.log(`DEBUG: updateCombateDetails Fin - Cat=${categoriaSelect?.value}, Sexo=${sexoSelect?.value}, Locked=${lockFields}`); }


        // --- Manejador Cambio Cat/Sexo (igual que antes) ---
        function handleCategoriaSexoChange() { if(!categoriaSelect || !sexoSelect || !pesoSelect) return; if (categoriaSelect.disabled || sexoSelect.disabled) { return; } const cat = categoriaSelect.value; const sexo = sexoSelect.value; const pesoIdParaSeleccionar = (cat === (initialData.categoria || null) && sexo === (initialData.sexo || null)) ? (initialData.id_categoria_peso_combate || null) : null; fetchAndPopulatePesos(cat, sexo, pesoSelect, pesoIdParaSeleccionar);}

        // --- Listener para Submit del Formulario (igual que antes, asegura que los selects habilitados envíen su valor) ---
        if (form) {
            form.addEventListener('submit', function(event) {
                console.log("DEBUG: Interceptando submit del formulario...");
                // Volver a habilitar selectores deshabilitados por JS para que sus valores se envíen en el POST
                form.querySelectorAll('select:disabled').forEach(select => {
                     select.disabled = false;
                     console.log(`DEBUG: Select '${select.id}' re-habilitado para submit.`);
                });

                // La validación de Bootstrap (en bs-validation.js) se ejecutará después.
            });
        }

        // --- Manejadores de Eventos para Modales (igual que antes) ---
        if (saveNewClubBtn) { saveNewClubBtn.addEventListener('click', function() { const newName = newClubNameInput.value.trim(); if (!newName) { showModalAlert('El nombre del club no puede estar vacío'); return;} saveNewClubBtn.disabled = true; saveNewClubBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Guardando...'; const formData = new FormData(); formData.append('action', 'add_club'); formData.append('nombre_club', newName); fetch('../ajax_handler.php', { method: 'POST', body: formData }).then(response => response.json()).then(data => { if (data.success && triggeringSelectElement) { const option = new Option(data.data.new_club_name, data.data.new_club_id); const addOption = triggeringSelectElement.querySelector('option[value="--ADD_NEW--"]'); if (addOption) { triggeringSelectElement.insertBefore(option, addOption);} else { triggeringSelectElement.add(option);} triggeringSelectElement.value = data.data.new_club_id; triggeringSelectElement.dispatchEvent(new Event('change')); addNewClubModal.hide(); newClubNameInput.value = ''; modalAlertPlaceholder.innerHTML = '';} else { throw new Error(data.message || 'Error al guardar el club');} }).catch(error => { showModalAlert(error.message);}).finally(() => { saveNewClubBtn.disabled = false; saveNewClubBtn.innerHTML = 'Guardar Club';});});}
        if (saveNewProBtn) { saveNewProBtn.addEventListener('click', function() { const nombre = newProNombreInput.value.trim(); const apellido = newProApellidoInput.value.trim(); const sexo = newProSexoSelect.value; if (!nombre || !apellido || !sexo) { showModalProAlert('Todos los campos son obligatorios'); return;} saveNewProBtn.disabled = true; saveNewProBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Guardando...'; const formData = new FormData(); formData.append('action', 'add_profesional'); formData.append('nombre_pugil', nombre); formData.append('apellido_pugil', apellido); formData.append('sexo', sexo); fetch('../ajax_handler.php', { method: 'POST', body: formData }).then(response => response.json()).then(data => { if (data.success && triggeringPugilSelect) { const option = new Option(data.data.new_pugil_name, data.data.new_pugil_id); const addOption = triggeringPugilSelect.querySelector('option[value="--ADD_PRO--"]'); if (addOption) { triggeringPugilSelect.insertBefore(option, addOption);} else { triggeringPugilSelect.add(option);} triggeringPugilSelect.value = data.data.new_pugil_id; triggeringPugilSelect.dispatchEvent(new Event('change')); addNewProModal.hide(); newProNombreInput.value = ''; newProApellidoInput.value = ''; newProSexoSelect.value = ''; modalProAlertPlaceholder.innerHTML = '';} else { throw new Error(data.message || 'Error al guardar el profesional');} }).catch(error => { showModalProAlert(error.message);}).finally(() => { saveNewProBtn.disabled = false; saveNewProBtn.innerHTML = 'Guardar Profesional';});});}
        if (saveNewPugilBtn) { saveNewPugilBtn.addEventListener('click', function() { const nombre = newPugilNombreInput.value.trim(); const apellido = newPugilApellidoInput.value.trim(); const dob = newPugilDobInput.value; const sexo = newPugilSexoSelect.value; const clubId = modalPugilClubIdElement.value; let dobError = ''; if (!dob) { dobError = 'La fecha de nacimiento es obligatoria'; } else if (!/^\d{4}-\d{2}-\d{2}$/.test(dob)) { dobError = 'Formato de fecha inválido (AAAA-MM-DD)'; } if (!nombre || !apellido || !sexo || !clubId || dobError) { showModalPugilAlert(dobError || 'Todos los campos son obligatorios'); return;} saveNewPugilBtn.disabled = true; saveNewPugilBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Guardando...'; const formData = new FormData(); formData.append('action', 'add_pugil_inline'); formData.append('nombre_pugil', nombre); formData.append('apellido_pugil', apellido); formData.append('fecha_nacimiento', dob); formData.append('sexo', sexo); formData.append('id_club_pertenencia', clubId); fetch('../ajax_handler.php', { method: 'POST', body: formData }).then(response => response.json()).then(data => { if (data.success && triggeringPugilSelect) { const option = new Option(data.data.new_pugil_name, data.data.new_pugil_id); const addOption = triggeringPugilSelect.querySelector('option[value="--ADD_PUGIL--"]'); if (addOption) { triggeringPugilSelect.insertBefore(option, addOption);} else { triggeringPugilSelect.add(option);} triggeringPugilSelect.value = data.data.new_pugil_id; triggeringPugilSelect.dispatchEvent(new Event('change')); addNewPugilModal.hide(); newPugilNombreInput.value = ''; newPugilApellidoInput.value = ''; newPugilDobInput.value = ''; newPugilSexoSelect.value = ''; modalPugilAlertPlaceholder.innerHTML = '';} else { throw new Error(data.message || 'Error al guardar el púgil');} }).catch(error => { showModalPugilAlert(error.message);}).finally(() => { saveNewPugilBtn.disabled = false; saveNewPugilBtn.innerHTML = 'Guardar Púgil';});});}


        // --- Asignar Listeners y Ejecutar Cargas Iniciales ---
        console.log("DEBUG: Asignando listeners y ejecutando cargas iniciales...");

        // Asignar Listeners a los selectores de Club y Púgil
        querySelectorAll('.club-select').forEach(select => { select.addEventListener('change', function() { handleClubChange(this); }); }); // Listener Club
        querySelectorAll('.pugil-select').forEach(select => { select.addEventListener('change', function() { handlePugilChange(this); }); }); // Listener Púgil
        if(categoriaSelect) categoriaSelect.addEventListener('change', handleCategoriaSexoChange); // Listener Categoría
        if(sexoSelect) sexoSelect.addEventListener('change', handleCategoriaSexoChange);       // Listener Sexo


        // --- Carga Inicial para Editar (Mejorada para pugiles y procedencias) ---
        // Disparar el evento 'change' en los selectores de club si hay datos iniciales.
        // Esto activa handleClubChange, que a su vez carga púgiles y procedencias si es necesario.
        // Usar un pequeño retraso para asegurar que todos los listeners estén asignados.
        setTimeout(() => { // <--- Outer setTimeout
            if (initialDataElement && Object.keys(initialData).length > 0 && initialData.id_club_rojo !== undefined) {
                console.log("DEBUG: Iniciando carga para edición, disparando eventos change iniciales...");

                // Disparar evento change en los selects de club para cargar datos dependientes
                // NOTA: Las variables clubRojoSelect y clubAzulSelect están disponibles aquí dentro de la IIFE
                 if (clubRojoSelect) {
                      console.log("DEBUG: Disparando evento change en clubRojoSelect...");
                      clubRojoSelect.dispatchEvent(new Event('change', { bubbles: true })); // Disparar evento
                 } else { console.warn("DEBUG: clubRojoSelect not found for initial dispatch."); } // Check si el elemento existe

                 if (clubAzulSelect) {
                      console.log("DEBUG: Disparando evento change en clubAzulSelect...");
                      clubAzulSelect.dispatchEvent(new Event('change', { bubbles: true })); // Disparar evento
                 } else { console.warn("DEBUG: clubAzulSelect not found for initial dispatch."); } // Check si el elemento existe


                 // También necesitamos disparar la carga inicial para la categoría de Peso si Cat/Sexo están preseleccionados
                 if (categoriaSelect && sexoSelect && (initialData.categoria || initialData.sexo)) {
                      console.log("DEBUG: Disparando carga inicial de peso por Cat/Sexo");
                      handleCategoriaSexoChange(); // Llamar al manejador directamente si los valores iniciales lo requieren
                 }

                 // Después de que las cargas AJAX de pugiles/procedencias se completen (lo cual es asíncrono),
                 // necesitamos re-seleccionar los valores iniciales de pugiles si no se seleccionaron correctamente
                 // durante la carga inicial (puede pasar por timing).
                 // La pre-selección de procedencia se maneja DENTRO de fetchAndPopulateProcedencias ahora.
                 setTimeout(() => { // <--- Inner setTimeout
                     console.log("DEBUG: Intentando re-selección final de Púgiles basado en initialData...");
                     // Re-seleccionar Púgil Rojo si el ID existe en initialData Y la opción está presente en el select poblado
                     // NOTA: Las variables pugilRojoSelect y pugilAzulSelect están disponibles aquí dentro de la IIFE
                     if (pugilRojoSelect && initialData.id_pugil_rojo && pugilRojoSelect.querySelector(`option[value="${initialData.id_pugil_rojo}"]`)) {
                          pugilRojoSelect.value = initialData.id_pugil_rojo;
                          console.log(`DEBUG: Pugil Rojo re-seleccionado a ID: ${initialData.id_pugil_rojo}`);
                           // Disparar change en el select de púgil si es necesario (ej: para updateCombateDetails si no se activó antes)
                          pugilRojoSelect.dispatchEvent(new Event('change', { bubbles: true }));
                     } else if (initialData.id_pugil_rojo) {
                         console.warn(`DEBUG: Initial pugil Rojo ID ${initialData.id_pugil_rojo} no encontrado en el select después de la carga.`);
                     }

                     // Re-seleccionar Púgil Azul si el ID existe en initialData Y la opción está presente
                      if (pugilAzulSelect && initialData.id_pugil_azul && pugilAzulSelect.querySelector(`option[value="${initialData.id_pugil_azul}"]`)) {
                           pugilAzulSelect.value = initialData.id_pugil_azul;
                           console.log(`DEBUG: Pugil Azul re-seleccionado a ID: ${initialData.id_pugil_azul}`);
                           pugilAzulSelect.dispatchEvent(new Event('change', { bubbles: true }));
                     } else if (initialData.id_pugil_azul) {
                         console.warn(`DEBUG: Initial pugil Azul ID ${initialData.id_pugil_azul} no encontrado en el select después de la carga.`);
                     }

                     // La pre-selección de procedencia se maneja dentro de fetchAndPopulateProcedencias.
                     // No necesitamos re-seleccionar aquí explícitamente a menos que haya un problema de timing muy específico.
                     // El fetchAndPopulateProcedencias ya recibió el ID inicial y lo intentó seleccionar.

                     // Llamada final para actualizar los detalles del combate mostrados, ahora que todos los selectores deberían tener sus valores finales
                      updateCombateDetails();
                      console.log("DEBUG: Actualización final de detalles completada");

                 }, 1000); // Retraso para permitir que las cargas AJAX se completen

            } else {
                 // Si no estamos en edición o no hay initialData (página de añadir combate),
                 // simplemente disparar el evento change en los selectores de club vacíos
                 // para establecer su estado inicial (deshabilitado/oculto/placeholder).
                 console.log("DEBUG: No initial data or not editing. Disparando eventos change iniciales en selects vacíos.");
                 // NOTA: Las variables clubRojoSelect y clubAzulSelect están disponibles aquí dentro de la IIFE
                 if (clubRojoSelect) clubRojoSelect.dispatchEvent(new Event('change', { bubbles: true }));
                 else { console.warn("DEBUG: clubRojoSelect not found for initial dispatch (else)."); } // Check si el elemento existe

                 if (clubAzulSelect) clubAzulSelect.dispatchEvent(new Event('change', { bubbles: true }));
                 else { console.warn("DEBUG: clubAzulSelect not found for initial dispatch (else)."); } // Check si el elemento existe
            }

        }, 100); // Pequeño retraso inicial para asegurar que DOMContentLoaded y listeners están listos


        console.log("DEBUG: combate_form.js - Fin de la configuración inicial (dentro de IIFE).");

    })(); // === FIN: IIFE ===


    console.log("DEBUG: combate_form.js - Fin de la configuración inicial (fuera de IIFE).");

}); // Fin DOMContentLoaded