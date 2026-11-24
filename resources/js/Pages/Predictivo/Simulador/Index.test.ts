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
            fecha_limite: '',
            errors: {} as Record<string, string>,
            processing: false,
            post,
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

import Simulador from './Index.vue';

const productos = [{ id: 1, codigo_sku: 'SKU-1', descripcion: 'Mesa de pino', unidad: 'u' }];

function props(overrides: Record<string, unknown> = {}) {
    return {
        productos,
        resultado: null,
        filtros: { articulo_id: null, cantidad: null, fecha_limite: null },
        ...overrides,
    };
}

describe('Simulador Predictivo', () => {
    beforeEach(() => {
        hoisted.post.mockReset();
    });

    it('renders the form and sends the simulation', async () => {
        const wrapper = mount(Simulador, { props: props() });

        await wrapper.get('form').trigger('submit');

        expect(hoisted.post).toHaveBeenCalledWith('/predictivo/simulador', {
            preserveScroll: true,
        });
    });

    it('asks for parameters before showing a result', () => {
        const wrapper = mount(Simulador, { props: props() });

        expect(wrapper.text()).toContain('para ver el impacto');
        expect(wrapper.find('[data-testid="resultado"]').exists()).toBe(false);
    });

    it('shows a viable scenario with the delayed orders', () => {
        const wrapper = mount(Simulador, {
            props: props({
                resultado: {
                    escenario_id: 7,
                    viable: true,
                    motivo: null,
                    pedido: {
                        articulo_id: 1,
                        articulo: 'Mesa de pino',
                        cantidad: 1,
                        fecha_limite: '2026-11-05',
                        fecha_fin_proyectada: '2026-11-02',
                    },
                    retrasos: [
                        {
                            orden_id: 3,
                            producto: 'Silla',
                            fecha_entrega: '2026-11-10',
                            dias_retraso: 1,
                        },
                    ],
                    impacto_proyectado: 1,
                },
            }),
        });

        expect(wrapper.get('[data-testid="resultado"]').text()).toContain('Escenario viable');
        expect(wrapper.get('[data-testid="fila-retraso"]').text()).toContain('Silla');
        expect(wrapper.text()).toContain('Impacto acumulado: 1 días');
    });

    it('shows an inviable scenario with its reason', () => {
        const wrapper = mount(Simulador, {
            props: props({
                resultado: {
                    escenario_id: 8,
                    viable: false,
                    motivo: 'no_llega_a_la_fecha_limite',
                    pedido: {
                        articulo_id: 1,
                        articulo: 'Mesa de pino',
                        cantidad: 2,
                        fecha_limite: '2026-11-02',
                        fecha_fin_proyectada: '2026-11-03',
                    },
                    retrasos: [],
                    impacto_proyectado: 0,
                },
            }),
        });

        expect(wrapper.get('[data-testid="resultado"]').text()).toContain('Escenario inviable');
        expect(wrapper.text()).toContain('no llega a la fecha límite');
        expect(wrapper.text()).toContain('No se retrasa ninguna orden existente.');
    });
});
