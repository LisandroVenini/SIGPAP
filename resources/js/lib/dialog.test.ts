import { describe, it, expect, vi, afterEach } from 'vitest';
import { enableAutoUnmount, mount } from '@vue/test-utils';
import { defineComponent, h, withDirectives } from 'vue';
import dialog from './dialog';

enableAutoUnmount(afterEach);

function montarPanel() {
    const cerrar = vi.fn();

    const Componente = defineComponent({
        setup() {
            return () =>
                withDirectives(
                    h('aside', [
                        h('h3', 'Editar proveedor'),
                        h('button', { id: 'guardar' }, 'Guardar'),
                        h('button', { id: 'cancelar' }, 'Cancelar'),
                    ]),
                    [[dialog, cerrar]],
                );
        },
    });

    const wrapper = mount(Componente, { attachTo: document.body });

    return { wrapper, cerrar };
}

describe('dialog directive', () => {
    it('marks the panel as a modal dialog named by its heading', () => {
        // Act
        const { wrapper } = montarPanel();

        // Assert
        const panel = wrapper.find('aside');
        const titulo = wrapper.find('h3');

        expect(panel.attributes('role')).toBe('dialog');
        expect(panel.attributes('aria-modal')).toBe('true');
        expect(panel.attributes('aria-labelledby')).toBe(titulo.attributes('id'));
        expect(titulo.attributes('id')).toMatch(/^dialogo-/);
    });

    it('closes on Escape', () => {
        // Arrange
        const { cerrar } = montarPanel();

        // Act
        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));

        // Assert
        expect(cerrar).toHaveBeenCalledTimes(1);
    });

    it('traps Tab inside the panel', async () => {
        // Arrange
        const { wrapper } = montarPanel();
        const botones = wrapper.findAll('button');
        const primero = botones[0].element as HTMLButtonElement;
        const ultimo = botones[botones.length - 1].element as HTMLButtonElement;

        // Act
        ultimo.focus();
        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Tab' }));

        // Assert
        expect(document.activeElement).toBe(primero);

        // Act
        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Tab', shiftKey: true }));

        // Assert
        expect(document.activeElement).toBe(ultimo);
    });

    it('stops listening after unmount', () => {
        // Arrange
        const { wrapper, cerrar } = montarPanel();

        // Act
        wrapper.unmount();
        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));

        // Assert
        expect(cerrar).not.toHaveBeenCalled();
    });
});
