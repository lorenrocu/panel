@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
@endpush

<!-- Componente personalizado -->
<div class="custom-component">
    <!-- Lista de valores -->
    <ul id="sortable-list" data-id="{{ $recordId ?? $this->record->id }}">
        @if($getState() && is_array(json_decode($getState())))
            @foreach(json_decode($getState()) as $index => $item)
                <li draggable="true" data-id="{{ $index }}">{{ $item }}</li>
            @endforeach
        @else
            <li>No hay elementos para mostrar.</li>
        @endif
    </ul>

    <!-- Campo de texto para agregar nuevo valor al final -->
    <input type="text" id="new-item" placeholder="Agregar nuevo valor">

    <!-- Enlace para agregar el nuevo valor a la lista -->
    <a href="#" id="add-item">Agregar Valor</a>

    <!-- Enlace para guardar el array de valores en la base de datos -->
    <a href="#" id="save-items">Guardar Valores</a>
</div>





<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectElement = document.querySelector('#data\\.atributo_seleccionado');
    let id = null;

    if (selectElement) {
        selectElement.addEventListener('change', function () {
            id = selectElement.value;
            console.log('ID seleccionado del select:', id);
        });
    }

    const listElement = document.getElementById('sortable-list');
    const addItemButton = document.getElementById('add-item');
    const newItemInput = document.getElementById('new-item');
    const saveItemsLink = document.getElementById('save-items');

    // Funcionalidad para agregar nuevo valor
    addItemButton.addEventListener('click', function (e) {
        e.preventDefault();  // Evitar comportamiento predeterminado del enlace

        const newValue = newItemInput.value;

        if (newValue.trim() === '') {
            alert('Por favor, ingresa un valor válido.');
            return;
        }

        // Crear un nuevo elemento <li> y agregarlo a la lista
        const newItem = document.createElement('li');
        newItem.innerText = newValue;
        newItem.setAttribute('draggable', true);
        listElement.appendChild(newItem);

        // Limpiar el campo de texto
        newItemInput.value = '';
    });

    // Reordenar la lista con SortableJS
    new Sortable(listElement, {
        animation: 150
    });

    // Guardar valores cuando el usuario haga clic en el enlace "Guardar Valores"
    saveItemsLink.addEventListener('click', function (e) {
        e.preventDefault();  // Evitar comportamiento predeterminado del enlace

        const newValuesArray = Array.from(listElement.querySelectorAll('li')).map((li) => {
            return li.innerText;
        });

        console.log('Array que se enviará:', newValuesArray);
        console.log('ID que se enviará (del select):', id);

        // Enviar el array actualizado al backend solo cuando el usuario haga clic en "Guardar Valores"
        if (id) {
            @this.call('saveNewArrayOrder', {
                id: id,
                new_values: newValuesArray
            }).then(response => {
                if (response.success) {
                    console.log('Datos guardados con éxito:', response.new_values);
                }
            });
        } else {
            console.error('No se pudo enviar el ID porque está vacío');
        }
    });
});



</script>



<style>
    /* Contenedor principal del componente */
    .custom-component {
        margin-top: 20px;
    }

    /* Estilo para la lista */
    .custom-component #sortable-list {
        list-style-type: none;
        padding: 0;
        margin: 10px 0;
    }

    .custom-component #sortable-list li {
        background-color: #f9f9f9;
        border: 1px solid #ddd;
        margin-bottom: 5px;
        padding: 10px;
        font-size: 16px;
        border-radius: 4px;
        cursor: move;
        transition: background-color 0.3s ease;
    }

    .custom-component #sortable-list li:hover {
        background-color: #f1f1f1;
    }

    /* Estilo para el campo de texto */
    .custom-component #new-item {
        padding: 10px;
        width: calc(100% - 22px);
        margin-bottom: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 16px;
    }

    /* Estilo para los enlaces */
    .custom-component a {
        display: inline-block;
        padding: 10px 15px;
        background-color: #007bff;
        color: white;
        text-decoration: none;
        border-radius: 4px;
        font-size: 16px;
        margin-right: 10px;
        transition: background-color 0.3s ease;
    }

    .custom-component a:hover {
        background-color: #0056b3;
    }

    /* Estilo para el enlace de guardar */
    .custom-component #save-items {
        background-color: #28a745;
    }

    .custom-component #save-items:hover {
        background-color: #218838;
    }
</style>
