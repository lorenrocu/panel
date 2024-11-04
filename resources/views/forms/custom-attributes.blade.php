@push('styles')
    <!-- Incluir el CSS de Toastr -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
@endpush

<div
    x-data
    x-init="
        new Sortable($refs.attributesContainer, {
            animation: 150,
            handle: '.drag-handle',
            onEnd: function (evt) {
                let order = Array.from($refs.attributesContainer.children).map((item, index) => {
                    return {
                        id: item.getAttribute('data-id'),
                        orden: index
                    };
                });
                @this.call('updateAttributeOrder', order);
            },
        });
    "
>
<div x-ref="attributesContainer">
    @foreach ($atributosPersonalizados as $atributo)
        <div data-id="{{ $atributo->id }}" class="mb-4 border p-2 bg-white dark:bg-gray-800">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="drag-handle cursor-move mr-2">
                        <!-- Código SVG del ícono -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                        </svg>
                    </div>
                    <label class="font-bold">{{ $atributo->nombre_atributo }}</label>
                </div>
                <!-- Botón de Eliminar con llamada Ajax -->
                <button x-on:click.prevent="eliminarAtributoAjax({{ $atributo->id }})" class="text-red-500 hover:text-red-700">
                    Eliminar
                </button>
            </div>
            <div class="mt-2">
                @if (is_array(json_decode($atributo->valor_atributo, true)))
                    @php
                        $options = json_decode($atributo->valor_atributo, true);
                    @endphp
                    <select wire:model.defer="atributos_personalizados.{{ $atributo->id }}" class="w-full border-gray-300 rounded">
                        <option value="">Seleccione una opción</option>
                        @foreach ($options as $value => $label)
                            <option value="{{ $value }}" @if($atributo->valor_por_defecto == $value) selected @endif>{{ $label }}</option>
                        @endforeach
                    </select>
                @else
                    <input type="text" wire:model.defer="atributos_personalizados.{{ $atributo->id }}" class="w-full border-gray-300 rounded" value="{{ $atributo->valor_por_defecto }}">
                @endif
            </div>
        </div>
    @endforeach
</div>
@push('scripts')
    <!-- Incluir el JS de Toastr -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <!-- Incluir el JS de Sortable -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
    
    <script>
function eliminarAtributoAjax(id) {
        fetch(`/api/delete-attribute/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Eliminar el elemento visualmente de la lista sin refrescar la página
                document.querySelector(`[data-id="${id}"]`).remove();

                // Mostrar notificación de éxito
                showNotification('Atributo eliminado exitosamente.', 'success');
            } else {
                // Mostrar notificación de error
                showNotification('Error al eliminar el atributo.', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error en la solicitud.', 'error');
        });
    }


        function showNotification(message, type = 'success') {
    // Crear el contenedor de la notificación si no existe
    let notification = document.getElementById('notification');
    if (!notification) {
        notification = document.createElement('div');
        notification.id = 'notification';
        notification.className = 'notification';
        document.body.appendChild(notification);
    }

    // Cambiar el estilo de acuerdo al tipo (éxito o error)
    notification.className = 'notification ' + (type === 'error' ? 'error' : 'success');
    
    // Establecer el mensaje de la notificación
    notification.innerHTML = message;

    // Mostrar la notificación
    notification.classList.add('show');

    // Después de 3 segundos, ocultar la notificación
    setTimeout(function() {
        notification.classList.remove('show');
    }, 3000);
}

    </script>
@endpush

<style>
    .notification {
        visibility: hidden; /* Inicialmente está oculta */
        min-width: 250px;
        margin-left: -125px;
        background-color: #44c767; /* Color de fondo */
        color: white;
        text-align: center;
        border-radius: 2px;
        padding: 16px;
        position: fixed;
        z-index: 1;
        left: 50%;
        bottom: 30px;
        font-size: 17px;
        opacity: 0; /* Opacidad inicial */
        transition: opacity 0.6s, visibility 0.6s;
    }

    .notification.show {
        visibility: visible;
        opacity: 1; /* Mostrar la notificación */
    }

    .notification.error {
        background-color: #e74c3c; /* Color rojo para errores */
    }
</style>
