{{-- Este componente conecta los selectores de dispositivo, marca y modelo con config/device_catalog.php. --}}
@once
<script>
document.addEventListener('DOMContentLoaded', () => {
    const deviceCatalog = @json($deviceCatalog);
    const customValue = '__other__';

    // Agrega las opciones de un selector y conserva el valor inicial cuando existe en el catálogo.
    function fillSelect(select, values, placeholder, initialValue = '') {
        select.innerHTML = '';
        select.add(new Option(placeholder, ''));

        values.forEach(value => select.add(new Option(value, value)));
        select.add(new Option('Otro (escribir manualmente)', customValue));

        if (initialValue && values.includes(initialValue)) {
            select.value = initialValue;
            return false;
        }

        if (initialValue) {
            select.value = customValue;
            return true;
        }

        return false;
    }

    // Muestra el campo manual únicamente cuando se elige "Otro" y lo conecta con la validación HTML.
    function toggleCustomInput(select, input, show, value = '') {
        input.hidden = !show;
        input.required = show;
        input.value = show ? value : '';

        if (show) {
            window.setTimeout(() => input.focus(), 0);
        }
    }

    // Obtiene todas las marcas conocidas para permitir una categoría manual sin perder sugerencias.
    function allKnownBrands() {
        return [...new Set(Object.values(deviceCatalog).flatMap(brands => Object.keys(brands)))].sort();
    }

    document.querySelectorAll('[data-device-catalog]').forEach(form => {
        const typeSelect = form.querySelector('[data-device-type-select]');
        const typeCustom = form.querySelector('[data-device-type-custom]');
        const typeValue = form.querySelector('[data-device-type-value]');
        const brandSelect = form.querySelector('[data-device-brand-select]');
        const brandCustom = form.querySelector('[data-device-brand-custom]');
        const brandValue = form.querySelector('[data-device-brand-value]');
        const modelSelect = form.querySelector('[data-device-model-select]');
        const modelCustom = form.querySelector('[data-device-model-custom]');
        const modelValue = form.querySelector('[data-device-model-value]');

        if (!typeSelect || !brandSelect || !modelSelect) {
            return;
        }

        const initial = {
            type: form.dataset.initialDeviceType || '',
            brand: form.dataset.initialDeviceBrand || '',
            model: form.dataset.initialDeviceModel || '',
        };

        // Copia el valor visible al campo real que Laravel guarda en ordenes_servicio.
        function syncStoredValues() {
            typeValue.value = typeSelect.value === customValue ? typeCustom.value.trim() : typeSelect.value;
            brandValue.value = brandSelect.value === customValue ? brandCustom.value.trim() : brandSelect.value;
            modelValue.value = modelSelect.value === customValue ? modelCustom.value.trim() : modelSelect.value;
        }

        // Filtra marcas usando el tipo seleccionado y reinicia el modelo para evitar combinaciones inválidas.
        function refreshBrands(selectedBrand = '', selectedModel = '') {
            const brands = deviceCatalog[typeSelect.value] || {};
            const brandNames = Object.keys(brands).length ? Object.keys(brands) : allKnownBrands();
            const customBrand = fillSelect(brandSelect, brandNames, 'Selecciona una marca', selectedBrand);
            toggleCustomInput(brandSelect, brandCustom, customBrand, customBrand ? selectedBrand : '');
            refreshModels(selectedModel);
        }

        // Filtra los modelos según el dispositivo y la marca elegidos.
        function refreshModels(selectedModel = '') {
            const models = deviceCatalog[typeSelect.value]?.[brandSelect.value] || [];
            const customModel = fillSelect(modelSelect, models, 'Selecciona un modelo', selectedModel);
            toggleCustomInput(modelSelect, modelCustom, customModel, customModel ? selectedModel : '');
            syncStoredValues();
        }

        const customType = fillSelect(
            typeSelect,
            Object.keys(deviceCatalog),
            'Selecciona el tipo de dispositivo',
            initial.type
        );
        toggleCustomInput(typeSelect, typeCustom, customType, customType ? initial.type : '');
        refreshBrands(initial.brand, initial.model);

        typeSelect.addEventListener('change', () => {
            toggleCustomInput(typeSelect, typeCustom, typeSelect.value === customValue);
            refreshBrands();
            syncStoredValues();
        });

        brandSelect.addEventListener('change', () => {
            toggleCustomInput(brandSelect, brandCustom, brandSelect.value === customValue);
            refreshModels();
            syncStoredValues();
        });

        modelSelect.addEventListener('change', () => {
            toggleCustomInput(modelSelect, modelCustom, modelSelect.value === customValue);
            syncStoredValues();
        });

        [typeCustom, brandCustom, modelCustom].forEach(input => {
            input.addEventListener('input', syncStoredValues);
        });

        form.addEventListener('submit', syncStoredValues);
        form.syncDeviceCatalogFields = syncStoredValues;
    });

    // El asistente de nueve pasos llama esta función antes de validar o enviar los campos.
    window.syncDeviceCatalogFields = () => {
        document.querySelectorAll('[data-device-catalog]').forEach(form => {
            form.syncDeviceCatalogFields?.();
        });
    };
});
</script>
@endonce
