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
        const form: Record<string, unknown> = {
            ...initial,
            errors: {},
            processing: false,
        };

        form.post = (url: string, options?: unknown) => {
            hoisted.post(url, options, JSON.parse(JSON.stringify(form)));
        };

        return reactive(form);
    },
}));

import Cotizacion from './Cotizacion.vue';

const solicitud = {
    solicitud_id: 5,
    proveedor: 'Aceros del Sur',
    fecha_vencimiento: '2026-11-12',
    lineas: [
        { articulo_id: 10, articulo: 'Tabla de pino', cantidad_solicitada: 10 },
        { articulo_id: 11, articulo: 'Tornillo', cantidad_solicitada: 25 },
    ],
};

describe('Portal Cotizacion page', () => {
    beforeEach(() => {
        hoisted.post.mockClear();
    });

    it('renders the solicitud and one form per line', () => {
        // Arrange / Act
        const wrapper = mount(Cotizacion, {
            props: { solicitud, token: 'token-123' },
        });

        // Assert
        expect(wrapper.text()).toContain('Aceros del Sur');
        expect(wrapper.text()).toContain('2026-11-12');
        expect(wrapper.text()).toContain('Tabla de pino');
        expect(wrapper.text()).toContain('Tornillo');
        expect(wrapper.findAll('[data-testid^="precio-"]')).toHaveLength(2);
        expect(wrapper.findAll('[data-testid^="cantidad-"]')).toHaveLength(2);
        expect(wrapper.findAll('[data-testid^="fecha-"]')).toHaveLength(2);
    });

    it('submits the quoted prices for every line', async () => {
        // Arrange
        const wrapper = mount(Cotizacion, {
            props: { solicitud, token: 'token-123' },
        });

        // Act
        await wrapper.find('[data-testid="precio-0"]').setValue('120.50');
        await wrapper.find('[data-testid="cantidad-0"]').setValue('8');
        await wrapper.find('[data-testid="fecha-0"]').setValue('2026-11-20');
        await wrapper.find('[data-testid="precio-1"]').setValue('5.25');
        await wrapper.find('[data-testid="cantidad-1"]').setValue('20');
        await wrapper.find('[data-testid="fecha-1"]').setValue('2026-11-18');
        await wrapper.find('[data-testid="cotizacion-form"]').trigger('submit.prevent');

        // Assert
        expect(hoisted.post).toHaveBeenCalledWith(
            '/portal/cotizaciones/token-123',
            undefined,
            expect.objectContaining({
                lineas: [
                    {
                        articulo_id: 10,
                        precio_unitario: 120.5,
                        cantidad_ofertada: 8,
                        fecha_prometida: '2026-11-20',
                    },
                    {
                        articulo_id: 11,
                        precio_unitario: 5.25,
                        cantidad_ofertada: 20,
                        fecha_prometida: '2026-11-18',
                    },
                ],
            }),
        );
    });

    it('shows a confirmation after the quote is sent', () => {
        // Arrange / Act
        const wrapper = mount(Cotizacion, {
            props: { enviada: true, token: 'token-123' },
        });

        // Assert
        expect(wrapper.text()).toContain('Cotización enviada');
    });
});
