<div style="margin-top: 1rem;">
    <button 
        type="button" 
        onclick="insertarVariableEnTextarea()" 
        style="background-color: #4F46E5; color: white; padding: 0.5rem 1rem; border: none; border-radius: 5px; cursor: pointer;"
        data-variable="{{ '{' . '{nombre}' . '}' }}"
    >
        Insertar {{ '{' . '{nombre}' . '}' }}
    </button>
</div>

<script>
function insertarVariableEnTextarea() {
    console.log("La función insertarVariableEnTextarea fue llamada."); // Confirmación de que la función fue llamada

    // Obtener el textarea
    const textarea = document.getElementById("mensaje-textarea");

    // Verificar si el textarea existe antes de continuar
    if (!textarea) {
        console.error("No se encontró el elemento textarea con ID 'mensaje-textarea'");
        return; // Salimos de la función si el textarea no existe
    } else {
        console.log("Elemento textarea encontrado.");
    }

    // Asegurar que textarea.value es una cadena
    if (typeof textarea.value !== 'string') {
        textarea.value = '';
    }

    // Obtener el botón para acceder al atributo data-variable
    const button = document.querySelector('button[data-variable]');

    // Verificar si el botón existe antes de continuar
    if (!button) {
        console.error("No se encontró el botón para insertar la variable");
        return; // Salimos de la función si el botón no existe
    } else {
        console.log("Elemento botón encontrado.");
    }

    // Obtener la variable que se desea insertar desde el atributo data-variable
    const variable = button.getAttribute('data-variable');
    console.log("Variable a insertar:", variable); // Mostrar la variable que se quiere insertar

    // Verificar si la variable está definida y no está vacía
    if (!variable) {
        console.error("La variable a insertar no se definió correctamente en el atributo data-variable");
        return; // Salimos de la función si no hay una variable para insertar
    }

    // Asegurarse de que el textarea tenga el foco
    textarea.focus();

    // Intentar obtener la posición del cursor dentro del textarea
    let startPos = typeof textarea.selectionStart === 'number' ? textarea.selectionStart : textarea.value.length;
    let endPos = typeof textarea.selectionEnd === 'number' ? textarea.selectionEnd : textarea.value.length;

    console.log("Posición de inicio:", startPos, "Posición de fin:", endPos);

    // Insertar la variable en la posición actual del cursor o al final si no hay selección
    textarea.value = textarea.value.substring(0, startPos) +
                     variable +
                     textarea.value.substring(endPos);

    console.log("Valor del textarea después de la inserción:", textarea.value);

    // **Nuevo código: Desencadenar el evento input para notificar a Livewire**
    textarea.dispatchEvent(new Event('input'));

    // Mantener el foco en el textarea después de insertar la variable
    textarea.focus();
}

</script>
