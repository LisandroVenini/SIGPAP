import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { enableAutoUnmount, mount } from '@vue/test-utils';
import { reactive } from 'vue';

enableAutoUnmount(afterEach);

const hoisted = vi.hoisted(() => {
    const post = vi.fn();

    return { post };
});

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div />' },
    useForm: (initial: Record<string, unknown>) => {
        const form: Record<string, unknown> = reactive({
            ...initial,
            errors: {},
            processing: false,
        });

        form.post = (url: string, options?: unknown) => {
            hoisted.post(url, options, JSON.parse(JSON.stringify(form)));
        };

        return form;
    },
}));

import Orden from './Orden.vue';

const orden = {
    numero_oc: 'OC-2026-0001',
    proveedor: 'Aceros del Sur',
    fecha_emision: '2026-11-01',
    lineas: [
        { articulo: 'Tabla de pino', cantidad: 8, precio_pactado: 120.5 },
        { articulo: 'Tornillo', cantidad: 20, precio_pactado: 5.25 },
    ],
    total: 1069,
};

describe('Portal Orden page', () => {
    beforeEach(() => {
        hoisted.post.mockClear();
    });

    it('renders the orden and its lines', () => {
        // Arrange / Act
        const wrapper = mount(Orden, { props: { orden, token: 'token-123' } });

        // Assert
        expect(wrapper.text()).toContain('OC-2026-0001');
        expect(wrapper.text()).toContain('Aceros del Sur');
        expect(wrapper.text()).toContain('Tabla de pino');
        expect(wrapper.text()).toContain('Tornillo');
        expect(wrapper.text()).toContain('1.069');
        expect(wrapper.findAll('[data-testid^="linea-"]')).toHaveLength(2);
    });

    it('confirms the orden through the token', async () => {
        // Arrange
        const wrapper = mount(Orden, { props: { orden, token: 'token-123' } });

        // Act
        const confirmar = wrapper.findAll('button').find((button) => button.text() === 'Confirmar');
        await confirmar?.trigger('click');

        // Assert
        expect(hoisted.post).toHaveBeenCalledWith(
            '/portal/ordenes/token-123',
            undefined,
            expect.objectContaining({ aceptada: true }),
        );
    });

    it('rejects the orden through the token with a reason', async () => {
        // Arrange
        const wrapper = mount(Orden, { props: { orden, token: 'token-123' } });

        // Act
        const rechazar = wrapper.findAll('button').find((button) => button.text() === 'Rechazar');
        await rechazar?.trigger('click');
        await wrapper.find('[data-testid="motivo"]').setValue('No hay stock');
        await wrapper.find('[data-testid="rechazo-form"]').trigger('submit.prevent');

        // Assert
        expect(hoisted.post).toHaveBeenCalledWith(
            '/portal/ordenes/token-123',
            undefined,
            expect.objectContaining({ aceptada: false, motivo: 'No hay stock' }),
        );
    });

    it('shows the result after the supplier responds', () => {
        // Arrange / Act
        const wrapper = mount(Orden, {
            props: { orden, token: 'token-123', resultado: 'Confirmada' },
        });

        // Assert
        expect(wrapper.text()).toContain('confirmada');
        expect(wrapper.findAll('button')).toHaveLength(0);
    });
});
