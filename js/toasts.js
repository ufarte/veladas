// /js/toasts.js - Manejador de mensajes Flash con Toasts de Bootstrap

document.addEventListener('DOMContentLoaded', function () {
    // Buscar si PHP ha dejado mensajes flash en el HTML (en un script específico)
    const flashMessageScript = document.getElementById('flash-message-data');

    if (flashMessageScript) {
        try {
            const flashData = JSON.parse(flashMessageScript.textContent || '{}');

            if (flashData.message && flashData.type) {
                console.log("DEBUG Toast:", flashData); // Para depurar
                showToast(flashData.message, flashData.type);
            }
        } catch (e) {
            console.error("Error parseando datos flash message:", e);
        }
    }

    // Función para mostrar un Toast
    function showToast(message, type = 'info') { // type puede ser 'success', 'danger', 'warning', 'info'
        const toastContainer = document.querySelector('.toast-container');
        if (!toastContainer || typeof bootstrap === 'undefined' || !bootstrap.Toast) {
            console.error("Toast container o Bootstrap no disponible.");
            // Fallback a alert si no se puede mostrar toast
            alert(`${type.toUpperCase()}: ${message}`);
            return;
        }

        // Determinar clase de color de Bootstrap
        let bgClass = 'bg-primary'; // Default
        switch (type) {
            case 'success': bgClass = 'bg-success'; break;
            case 'danger':
            case 'error':   bgClass = 'bg-danger'; break;
            case 'warning': bgClass = 'bg-warning text-dark'; break; // Warning a menudo necesita texto oscuro
            case 'info':    bgClass = 'bg-info text-dark'; break;    // Info a menudo necesita texto oscuro
            default:        bgClass = 'bg-secondary';
        }

        // Crear el HTML del Toast dinámicamente
        const toastId = 'toast-' + Date.now(); // ID único
        const toastHTML = `
            <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="5000">
              <div class="d-flex">
                <div class="toast-body">
                  ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
              </div>
            </div>`;

        // Añadir el toast al contenedor
        toastContainer.insertAdjacentHTML('beforeend', toastHTML);

        // Inicializar y mostrar el toast
        const toastElement = document.getElementById(toastId);
        if(toastElement) {
            const toastInstance = new bootstrap.Toast(toastElement);
            toastInstance.show();

            // Opcional: Eliminar el toast del DOM después de ocultarse
            toastElement.addEventListener('hidden.bs.toast', function () {
                toastElement.remove();
            });
        }
    }

    // Exponer la función globalmente si necesitas llamarla desde otro JS (opcional)
    // window.showToast = showToast;

}); // Fin DOMContentLoaded