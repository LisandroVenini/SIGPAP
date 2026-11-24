import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { enableAutoUnmount, mount } from '@vue/test-utils';

enableAutoUnmount(afterEach);

const hoisted = vi.hoisted(() => {
    const post = vi.fn();

    return {
        post,
        form: {
            articulo_id: '',
            cantidad: '',
            proveedor_id: '',
            fecha_requerida: '',
            errors: {} as Record<string, string>,
            processing: false,
            post,
            reset: vi.fn(),
        },
    };
});

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div />' },
    useForm: () => hoisted.form,
}));

vi.mock('@/Layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /></div>' },
}));

import Disponibilidad from './Index.vue';

const baseProps = {
    bajos: [{ articulo_id: 1, articulo: 'Tabla de pino', disponible: 5, minimo: 20 }],
    solicitudes: [],
    proveedores: [{ id: 9, razon_social: 'Aceros del Sur' }],
    proveedorSugerido: { id: 9, razon_social: 'Aceros del Sur', puntaje: 95 },
};

describe('Disponibilidad de proveedores', () => {
    beforeEach(() => {
        hoisted.post.mockReset();
    });

    it('lists the inputs below the minimum and sends the request', async () => {
        const wrapper = mount(Disponibilidad, { props: baseProps });

        expect(wrapper.text()).toContain('Tabla de pino');

        await wrapper.get('form').trigger('submit');

        expect(hoisted.post).toHaveBeenCalledWith('/compras/disponibilidad', {
            preserveScroll: true,
            onSuccess: expect.any(Function),
        });
    });

    it('shows the recommendation of an answered request', () => {
        const wrapper = mount(Disponibilidad, {
            props: {
                ...baseProps,
                solicitudes: [
                    {
                        id: 1,
                        articulo: 'Tabla de pino',
                        proveedor: 'Aceros del Sur',
                        cantidad_solicitada: 50,
                        fecha: '2026-11-24',
                        fecha_requerida: '2026-12-01',
                        estado: 'Respondida',
                        respondida: true,
                        recomendacion: {
                            escenario: 'Variación Fuerte',
                            motivos: ['precio_sobre_umbral'],
                            desviacion_precio: 0.5,
                            dias_de_atraso: -3,
                            recomendacion: 'Frenar: el precio supera el umbral esperado.',
                            proveedor_sugerido: null,
                            precio_referencia: 100,
                            precio_actual: 150,
                            fecha_prometida: '2026-11-28',
                        },
                    },
                ],
            },
        });

        expect(wrapper.get('[data-testid="fila-solicitud"]').text()).toContain('Variación Fuerte');
        expect(wrapper.text()).toContain('el precio supera el umbral esperado');
    });

    it('says when there are no requests', () => {
        const wrapper = mount(Disponibilidad, { props: baseProps });

        expect(wrapper.text()).toContain('Todavía no hay consultas de disponibilidad.');
    });
});
