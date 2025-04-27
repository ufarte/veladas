// /js/evento_form.js - Lógica para desplegables dependientes Municipio/Recinto

document.addEventListener('DOMContentLoaded', function() {
    // Buscar elementos SOLO si estamos en una página que los contiene
    const municipioSelect = document.getElementById('id_municipio');
    const recintoSelect = document.getElementById('id_recinto');
    const initialDataElement = document.getElementById('evento-initial-data'); // Para la página de edición

    // Si no encontramos los selects necesarios, no hacemos nada en esta página
    if (!municipioSelect || !recintoSelect) {
        // console.log("DEBUG: Selects de municipio/recinto no encontrados en esta página.");
        return;
    }

    console.log("DEBUG: evento_form.js - Selects encontrados, inicializando...");

    let initialRecintoId = null;

    // Leer datos iniciales si existen (para editar_evento.php)
    if (initialDataElement) {
        try {
            const initialData = JSON.parse(initialDataElement.textContent || '{}');
            initialRecintoId = initialData.id_recinto || null;
             console.log("DEBUG: Datos iniciales para editar evento:", initialData);
        } catch (e) {
            console.error("Error parseando datos iniciales de evento:", e);
        }
    }

    // --- Función para cargar recintos ---
    function cargarRecintos(municipioId, recintoIdParaSeleccionar = null) {
        console.log(`DEBUG: Cargando recintos para Municipio ID: ${municipioId}, Preseleccionar Recinto ID: ${recintoIdParaSeleccionar}`);
        // Estado inicial mientras carga
        recintoSelect.disabled = true;
        recintoSelect.innerHTML = '<option value="">--- Cargando Recintos... ---</option>';

        if (!municipioId || municipioId === "") {
            recintoSelect.innerHTML = '<option value="">--- Seleccione Municipio Primero ---</option>';
            // Mantenemos disabled=true
            return; // Salir si no hay municipio válido
        }

        // Llamada AJAX
        fetch(`../ajax_handler.php?action=get_recintos_por_municipio&id_municipio=${municipioId}`)
            .then(response => {
                if (!response.ok) { throw new Error(`HTTP error ${response.status}`); }
                return response.json();
            })
            .then(data => {
                console.log("DEBUG: Respuesta AJAX recintos:", data);
                recintoSelect.innerHTML = ''; // Limpiar opciones anteriores (incluso el 'cargando')

                if (data.success && Array.isArray(data.data)) {
                    if (data.data.length > 0) {
                        // Añadir opción por defecto
                        recintoSelect.innerHTML += '<option value="">--- Seleccionar Recinto ---</option>';
                        // Añadir recintos recibidos
                        data.data.forEach(recinto => {
                            const option = document.createElement('option');
                            option.value = recinto.id;
                            option.textContent = recinto.text;
                            // Marcar como seleccionado si coincide con el ID inicial o el que se busca
                            if (recinto.id == recintoIdParaSeleccionar) {
                                option.selected = true;
                                console.log(`DEBUG: Preseleccionado Recinto ID: ${recinto.id}`);
                            }
                            recintoSelect.appendChild(option);
                        });
                        recintoSelect.disabled = false; // Habilitar el select
                    } else {
                        // No hay recintos para este municipio
                        recintoSelect.innerHTML = '<option value="">--- No hay recintos para este municipio ---</option>';
                        recintoSelect.disabled = true; // Deshabilitar si no hay opciones
                    }
                } else {
                    // Error en la respuesta del servidor
                    console.error("Error en respuesta del servidor al cargar recintos:", data.message || "Respuesta no exitosa");
                    recintoSelect.innerHTML = '<option value="">--- Error al cargar recintos ---</option>';
                    recintoSelect.disabled = true;
                }
            })
            .catch(error => {
                console.error('Error en fetch para cargar recintos:', error);
                recintoSelect.innerHTML = '<option value="">--- Error de Red/Proceso ---</option>';
                recintoSelect.disabled = true;
            });
    }

    // --- Event Listener para el cambio de Municipio ---
    municipioSelect.addEventListener('change', function() {
        const selectedMunicipioId = this.value;
        cargarRecintos(selectedMunicipioId); // Llamar a cargar, sin preseleccionar nada específico
    });

    // --- Carga Inicial para Editar ---
    // Si estamos en editar y hay un municipio preseleccionado, cargar sus recintos
    const initialMunicipioId = municipioSelect.value;
    if (initialMunicipioId && initialRecintoId) { // Solo si tenemos AMBOS IDs iniciales
        console.log(`DEBUG: Carga inicial - Municipio ID: ${initialMunicipioId}, Recinto ID: ${initialRecintoId}`);
        cargarRecintos(initialMunicipioId, initialRecintoId);
    } else if (initialMunicipioId) {
         console.log(`DEBUG: Carga inicial - Solo Municipio ID: ${initialMunicipioId}. Cargando recintos sin preselección.`);
         cargarRecintos(initialMunicipioId); // Cargar recintos pero sin preseleccionar
    } else {
         console.log("DEBUG: Carga inicial - No hay municipio inicial seleccionado.");
         recintoSelect.disabled = true; // Asegurar que esté deshabilitado
         recintoSelect.innerHTML = '<option value="">--- Seleccione Municipio Primero ---</option>';
    }

     console.log("DEBUG: evento_form.js - Inicialización completada.");

}); // Fin DOMContentLoaded