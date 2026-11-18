import type { Directive } from 'vue';

interface PanelConDialogo extends HTMLElement {
    _dialogoEscuchar?: (evento: KeyboardEvent) => void;
}

function enfocables(panel: HTMLElement): HTMLElement[] {
    return Array.from(
        panel.querySelectorAll<HTMLElement>(
            'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
        ),
    );
}

/**
 * Marks a side panel as a dialog: role, aria-modal, accessible name from its
 * heading, initial focus, Escape to close and a Tab focus trap.
 */
const dialog: Directive<PanelConDialogo, () => void> = {
    mounted(panel, binding) {
        panel.setAttribute('role', 'dialog');
        panel.setAttribute('aria-modal', 'true');
        panel.setAttribute('tabindex', '-1');

        const titulo = panel.querySelector<HTMLElement>('h2, h3');

        if (titulo !== null) {
            if (titulo.id === '') {
                titulo.id = `dialogo-${Math.random().toString(36).slice(2, 8)}`;
            }

            panel.setAttribute('aria-labelledby', titulo.id);
        }

        const escuchar = (evento: KeyboardEvent): void => {
            if (evento.key === 'Escape') {
                evento.preventDefault();
                binding.value();

                return;
            }

            if (evento.key !== 'Tab') {
                return;
            }

            const lista = enfocables(panel);

            if (lista.length === 0) {
                evento.preventDefault();

                return;
            }

            const primero = lista[0];
            const ultimo = lista[lista.length - 1];

            if (!panel.contains(document.activeElement)) {
                evento.preventDefault();
                primero.focus();

                return;
            }

            if (evento.shiftKey && document.activeElement === primero) {
                evento.preventDefault();
                ultimo.focus();
            } else if (!evento.shiftKey && document.activeElement === ultimo) {
                evento.preventDefault();
                primero.focus();
            }
        };

        panel._dialogoEscuchar = escuchar;
        document.addEventListener('keydown', escuchar);
        panel.focus();
    },
    unmounted(panel) {
        if (panel._dialogoEscuchar !== undefined) {
            document.removeEventListener('keydown', panel._dialogoEscuchar);
        }
    },
};

export default dialog;
