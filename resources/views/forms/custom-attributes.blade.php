@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
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
                <div class="flex items-center">
                    <div class="drag-handle cursor-move mr-2">
                        <!-- Código SVG del ícono -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                        </svg>
                    </div>
                    <label class="font-bold">{{ $atributo->nombre_atributo }}</label>
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
</div>
