@if (isset($data))
    <script>
        window.filamentData = @js($data)
    </script>
<script>
    document.addEventListener('livewire:load', function () {
        console.log('JavaScript cargado correctamente con Livewire');
        const list = document.getElementById('sortable-list');
        let draggedItem = null;

        if (!list) {
            console.log('Lista no encontrada');
            return;
        }

        list.querySelectorAll('li').forEach(item => {
            item.addEventListener('dragstart', function () {
                draggedItem = item;
                setTimeout(() => item.style.display = 'none', 0);
            });

            item.addEventListener('dragend', function () {
                setTimeout(() => {
                    draggedItem.style.display = 'block';
                    draggedItem = null;
                }, 0);
            });

            item.addEventListener('dragover', function (e) {
                e.preventDefault();
            });

            item.addEventListener('dragenter', function (e) {
                e.preventDefault();
                this.style.border = '1px dashed #ccc';
            });

            item.addEventListener('dragleave', function () {
                this.style.border = 'none';
            });

            item.addEventListener('drop', function () {
                this.style.border = 'none';
                if (draggedItem !== this) {
                    list.insertBefore(draggedItem, this.nextSibling);
                }
                updateInputValue();
            });
        });

        function updateInputValue() {
            const newList = Array.from(list.querySelectorAll('li')).map(li => li.innerText);
            document.querySelector('input[name="valor_atributo"]').value = JSON.stringify(newList);
        }
    });
</script>
@endif

@foreach ($assets as $asset)
    @if (! $asset->isLoadedOnRequest())
        {{ $asset->getHtml() }}
    @endif
@endforeach

<style>
    :root {
        @foreach ($cssVariables ?? [] as $cssVariableName => $cssVariableValue) --{{ $cssVariableName }}:{{ $cssVariableValue }}; @endforeach
    }
</style>
