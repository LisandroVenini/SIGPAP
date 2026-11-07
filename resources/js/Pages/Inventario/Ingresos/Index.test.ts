import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { enableAutoUnmount, mount } from '@vue/test-utils';

enableAutoUnmount(afterEach);

const hoisted = vi.hoisted(() => {
    const post = vi.fn();

    function makeForm(initial: Record<string, unknown>) {
        const form = {
            ...initial,
            errors: {} as Record<string, string>,
            processing: false,
            post: (...args: unknown[]) => post(form, ...args),
        };

        return form;
    }

    return { post, makeForm };
});

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div />' },
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
    useForm: (initial: Record<string, unknown>) => hoisted.makeForm(initial),
    usePage: () => ({ props: { flash: { success: null } } }),
}));

vi.mock('../../../Layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /></div>' },
}));

import Ingresos from './Index.vue';

const props = {
    ordenesRecepcionables: [
        {
            id: 1,
            numero_oc: 'OC-2026-0001',
            proveedor: 'Aceros del Sur',
            fecha_emision: '2026-11-01',
            fecha_confirmacion: '2026-11-02',
            lineas: [
                {
                    articulo_id: 10,
                    articulo: {
                        id: 10,
                        codigo_sku: 'MP-001',
                        descripcion: 'Tabla de pino',
                        unidad: 'Metro',
                    },
                    cantidad: 4,
                    precio_pactado: 120.5,
                },
            ],
        },
    ],
    filtros: {},
};

describe('Inventario Ingresos page', () => {
    beforeEach(() => {
        hoisted.post.mockClear();
    });

    it('renders the recepcionable orders and their lines', async () => {
        // Arrange / Act
        const wrapper = mount(Ingresos, { props });
        await wrapper.find('[data-testid="orden-select"]').setValue('OC-2026-0001');

        // Assert
        expect(wrapper.text()).toContain('OC-2026-0001');
        expect(wrapper.text()).toContain('Aceros del Sur');
        expect(wrapper.text()).toContain('Tabla de pino');
        expect(wrapper.text()).toContain('MP-001');
    });

    it('shows an empty state when there are no pending orders', () => {
        // Arrange / Act
        const wrapper = mount(Ingresos, { props: { ...props, ordenesRecepcionables: [] } });

        // Assert
        expect(wrapper.text()).toContain('No hay órdenes de compra pendientes de recepción.');
    });

    it('selects an order and submits the payload with the flags', async () => {
        // Arrange
        const wrapper = mount(Ingresos, { props });

        // Act
        await wrapper.find('[data-testid="orden-select"]').setValue('OC-2026-0001');
        await wrapper.find('[data-testid="numero-remito"]').setValue('REM-001');
        await wrapper.find('[data-testid="entrega-parcial"]').setValue(true);
        await wrapper.find('[data-testid="confirmar-excedente"]').setValue(true);
        await wrapper.find('[data-testid="ingreso-form"]').trigger('submit.prevent');

        // Assert
        expect(hoisted.post).toHaveBeenCalledTimes(1);
        const [form, url] = hoisted.post.mock.calls[0];
        expect(url).toBe('/inventario/ingresos');
        expect(form.numero_oc).toBe('OC-2026-0001');
        expect(form.numero_remito).toBe('REM-001');
        expect(form.entrega_parcial).toBe(true);
        expect(form.confirmar_excedente).toBe(true);
        expect(form.lineas).toEqual([{ articulo_id: 10, cantidad: 4 }]);
    });
});
