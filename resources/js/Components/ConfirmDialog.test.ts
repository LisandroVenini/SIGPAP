import { afterEach, describe, expect, it } from 'vitest';
import { enableAutoUnmount, flushPromises, mount } from '@vue/test-utils';
import ConfirmDialog from './ConfirmDialog.vue';
import { confirmar, estadoDeConfirmacion, reiniciarConfirmacion } from '../composables/useConfirm';

enableAutoUnmount(afterEach);

const estado = estadoDeConfirmacion();

function montar() {
    return mount(ConfirmDialog, { global: { stubs: { teleport: true } } });
}

describe('ConfirmDialog', () => {
    afterEach(() => {
        reiniciarConfirmacion();
    });

    it('stays hidden while there is nothing to confirm', () => {
        // Arrange / Act
        const wrapper = montar();

        // Assert
        expect(wrapper.find('[data-testid="confirm-dialog"]').exists()).toBe(false);
    });

    it('renders the confirmation and resolves true on accept', async () => {
        // Arrange
        const wrapper = montar();
        const respuesta = confirmar({
            titulo: 'Anular orden',
            mensaje: '¿Anular la OC?',
            confirmar: 'Anular',
            peligro: true,
        });
        await flushPromises();

        // Assert
        expect(wrapper.text()).toContain('Anular orden');
        expect(wrapper.find('[data-testid="confirm-message"]').text()).toBe('¿Anular la OC?');
        expect(wrapper.find('[data-testid="confirm-accept"]').text()).toBe('Anular');
        expect(wrapper.find('[data-testid="confirm-accept"]').classes().join(' ')).toContain(
            'bg-oxide',
        );

        // Act
        await wrapper.find('[data-testid="confirm-accept"]').trigger('click');

        // Assert
        await expect(respuesta).resolves.toBe(true);
        expect(estado.abierto).toBe(false);
    });

    it('resolves false when cancelled with the button or the escape key', async () => {
        // Arrange
        const wrapper = montar();
        const primera = confirmar({ titulo: 'Uno', mensaje: 'A' });
        await flushPromises();

        // Act
        await wrapper.find('[data-testid="confirm-cancel"]').trigger('click');

        // Assert
        await expect(primera).resolves.toBe(false);

        // Arrange
        const segunda = confirmar({ titulo: 'Dos', mensaje: 'B' });
        await flushPromises();

        // Act
        await wrapper.find('[data-testid="confirm-dialog"]').trigger('keydown', { key: 'Escape' });

        // Assert
        await expect(segunda).resolves.toBe(false);
        expect(estado.abierto).toBe(false);
    });
});
