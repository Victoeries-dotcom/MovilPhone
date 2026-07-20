{{-- Estilos compartidos: proporcionan la misma interfaz por pasos a todos los formularios de nuevos registros. --}}
<style>
    .registro-wizard {
        width: 100%;
        max-width: 680px;
        margin: 0 auto;
    }

    .registro-wizard-progress {
        display: grid;
        grid-template-columns: auto minmax(120px, 1fr);
        align-items: center;
        gap: 12px;
        margin: 0 0 1.25rem;
    }

    .registro-wizard-progress-label {
        color: #64748b;
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .registro-wizard-track {
        height: 5px;
        overflow: hidden;
        border-radius: 5px;
        background: #e2e8f0;
    }

    .registro-wizard-fill {
        width: 0;
        height: 100%;
        border-radius: inherit;
        background: #0f1f3d;
        transition: width 180ms ease;
    }

    .registro-wizard-card {
        min-height: 280px;
        padding: 2rem;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        box-shadow: 0 10px 28px rgba(15,31,61,0.08);
    }

    .registro-step {
        display: none;
    }

    .registro-step.is-active {
        display: block;
    }

    .registro-question {
        color: #0f1f3d;
        font-size: 26px;
        font-weight: 800;
        line-height: 1.2;
        margin-bottom: 1.25rem;
    }

    .registro-input {
        width: 100%;
        min-height: 54px;
        padding: 12px 16px;
        border: 2px solid #cbd5e1;
        border-radius: 8px;
        background: #ffffff;
        color: #0f172a;
        font: inherit;
        font-size: 17px;
        box-sizing: border-box;
        outline: none;
    }

    textarea.registro-input {
        min-height: 120px;
        resize: vertical;
    }

    .registro-input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
    }

    .registro-help {
        color: #64748b;
        font-size: 13px;
        margin-top: 8px;
    }

    .registro-actions {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-top: 2rem;
    }

    .registro-next,
    .registro-save,
    .registro-prev {
        min-height: 46px;
        padding: 10px 24px;
        border-radius: 8px;
        font: inherit;
        font-size: 15px;
        font-weight: 700;
        cursor: pointer;
    }

    .registro-next,
    .registro-save {
        flex: 1;
        border: 1px solid #0f1f3d;
        background: #0f1f3d;
        color: #ffffff;
    }

    .registro-next:hover,
    .registro-save:hover {
        background: #1e3a8a;
    }

    .registro-prev {
        border: 1px solid #dbe1ea;
        background: #ffffff;
        color: #64748b;
    }

    .registro-prev:hover {
        color: #0f1f3d;
        background: #f8fafc;
    }

    @media (max-width: 640px) {
        .registro-wizard-card { min-height: 0; padding: 1.25rem; }
        .registro-question { font-size: 22px; }
        .registro-actions { align-items: stretch; flex-direction: column-reverse; }
        .registro-prev, .registro-next, .registro-save { width: 100%; }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-registro-wizard]').forEach(function (wizard) {
        const steps = Array.from(wizard.querySelectorAll('.registro-step'));
        const progressLabel = wizard.querySelector('[data-progress-label]');
        const progressFill = wizard.querySelector('[data-progress-fill]');
        let currentStep = Math.max(1, Math.min(
            Number(wizard.dataset.initialStep || 1),
            steps.length
        ));

        /* Valida únicamente los campos obligatorios del paso visible antes de continuar. */
        function validateCurrentStep() {
            const fields = steps[currentStep - 1].querySelectorAll('input, select, textarea');

            for (const field of fields) {
                if (!field.checkValidity()) {
                    field.reportValidity();
                    field.focus();
                    return false;
                }
            }

            return true;
        }

        /* Muestra un paso, actualiza la barra y enfoca el primer campo de captura. */
        function showStep(stepNumber) {
            currentStep = Math.max(1, Math.min(stepNumber, steps.length));

            steps.forEach(function (step, index) {
                const isActive = index === currentStep - 1;
                step.classList.toggle('is-active', isActive);
                step.setAttribute('aria-hidden', isActive ? 'false' : 'true');
            });

            progressLabel.textContent = 'Paso ' + currentStep + ' de ' + steps.length;
            progressFill.style.width = ((currentStep / steps.length) * 100) + '%';

            const firstField = steps[currentStep - 1].querySelector('input:not([type="hidden"]), select, textarea');
            if (firstField) {
                window.setTimeout(function () { firstField.focus(); }, 80);
            }
        }

        /* Los botones Siguiente avanzan sin borrar la información capturada. */
        wizard.querySelectorAll('[data-next]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (validateCurrentStep()) showStep(currentStep + 1);
            });
        });

        /* Los botones Anterior permiten revisar los pasos previos. */
        wizard.querySelectorAll('[data-prev]').forEach(function (button) {
            button.addEventListener('click', function () {
                showStep(currentStep - 1);
            });
        });

        /* Enter avanza al siguiente paso, excepto dentro de campos de texto multilínea. */
        wizard.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter' || event.target.tagName === 'TEXTAREA' || currentStep === steps.length) {
                return;
            }

            event.preventDefault();
            if (validateCurrentStep()) showStep(currentStep + 1);
        });

        /* Los campos marcados se convierten a mayúsculas mientras el usuario escribe. */
        wizard.querySelectorAll('[data-uppercase]').forEach(function (field) {
            field.addEventListener('input', function () {
                this.value = this.value.toUpperCase();
            });
        });

        showStep(currentStep);
    });
});
</script>
