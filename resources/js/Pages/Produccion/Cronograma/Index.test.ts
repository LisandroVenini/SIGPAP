import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { enableAutoUnmount, mount } from '@vue/test-utils';

enableAutoUnmount(afterEach);

const hoisted = vi.hoisted(() => {
    const put = vi.fn();

    const page = {
        props: {
            flash: { success: null as string | null },
            errors: {} as Record<string, string>,
        },
    };

    return { put, page };
});

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div />' },
    usePage: () => hoisted.page,
    router: { put: hoisted.put },
}));

vi.mock('../../../Layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /></div>' },
}));

import Cronograma from './Index.vue';

const props = {
    cronograma: {
        inicio: '2026-11-11',
        hoy: '2026-11-11',
        dias: 14,
        filas: [
            {
                orden_id: 1,
                producto: 'Silla de pino',
                estado: 'Pendiente',
                prioridad: 2,
                fecha_entrega: '2026-11-10',
                reubicable: true,
                conflicto_entrega: true,
                cantidad_requerida: 10,
                etapa_actual: 'Corte',
                avance: 0,
                dias_totales: 5,
                historial: [
                    {
                        prioridad_anterior: 2,
                        prioridad_nueva: 1,
                        motivo: 'Urgencia comercial',
                        detalle: null,
                        fecha: '2026-11-10 09:30:00',
                        usuario: 'Supervisor Demo',
                    },
                ],
                etapas: [
                    {
                        etapa: 'Corte',
                        inicio: '2026-11-10',
                        fin: '2026-11-12',
                        dias: 3,
                        producida: 0,
                    },
                    {
                        etapa: 'Lijado',
                        inicio: '2026-11-13',
                        fin: '2026-11-14',
                        dias: 2,
                        producida: 0,
                    },
                ],
            },
            {
                orden_id: 2,
                producto: 'Mesa de algarrobo',
                estado: 'En Curso',
                prioridad: 1,
                fecha_entrega: '2026-11-20',
                reubicable: false,
                conflicto_entrega: false,
                cantidad_requerida: 4,
                etapa_actual: 'Armado',
                avance: 40,
                dias_totales: 3,
                historial: [],
                etapas: [
                    {
                        etapa: 'Armado',
                        inicio: '2026-11-13',
                        fin: '2026-11-15',
                        dias: 3,
                        producida: 2,
                    },
                ],
            },
            {
                orden_id: 3,
                producto: 'Aparador de guatambú',
                estado: 'Pendiente',
                prioridad: 3,
                fecha_entrega: '2026-12-20',
                reubicable: true,
                conflicto_entrega: false,
                cantidad_requerida: 6,
                etapa_actual: 'Encolado',
                avance: 0,
                dias_totales: 3,
                historial: [],
                etapas: [
                    {
                        etapa: 'Encolado',
                        inicio: '2026-12-01',
                        fin: '2026-12-03',
                        dias: 3,
                        producida: 0,
                    },
                ],
            },
        ],
    },
    motivos: ['Urgencia comercial', 'Otro'],
};

describe('Produccion Cronograma page', () => {
    beforeEach(() => {
        hoisted.put.mockClear();
    });

    it('renders the gantt rows and the delivery conflict banner', () => {
        // Arrange / Act
        const wrapper = mount(Cronograma, { props });

        // Assert
        expect(wrapper.text()).toContain('Silla de pino');
        expect(wrapper.text()).toContain('Corte');
        expect(wrapper.find('[data-testid="banner-conflictos"]').text()).toContain('#1');
    });

    it('draws a bar for every stage inside the window and drops the ones outside', () => {
        // Arrange / Act
        const wrapper = mount(Cronograma, { props });

        // Assert
        expect(wrapper.findAll('[data-testid="barra-orden-1"]')).toHaveLength(2);
        expect(wrapper.findAll('[data-testid="barra-orden-2"]')).toHaveLength(1);
        expect(wrapper.findAll('[data-testid="barra-orden-3"]')).toHaveLength(0);
    });

    it('blocks reordering for an in-progress order', () => {
        // Arrange / Act
        const wrapper = mount(Cronograma, { props });

        // Assert
        const reordenable = wrapper.find('[data-testid="reordenar-orden-1"]');
        const bloqueada = wrapper.find('[data-testid="reordenar-orden-2"]');

        expect(reordenable.attributes('disabled')).toBeUndefined();
        expect(bloqueada.attributes('disabled')).toBeDefined();
    });

    it('sends the priority change with its motive', async () => {
        // Arrange
        const wrapper = mount(Cronograma, { props, attachTo: document.body });

        // Act
        await wrapper.find('[data-testid="reordenar-orden-1"]').trigger('click');
        await wrapper.find('#prioridad').setValue('1');
        await wrapper.find('[data-testid="reordenar-form"]').trigger('submit.prevent');

        // Assert
        expect(hoisted.put).toHaveBeenCalledWith(
            '/produccion/cronograma/1/prioridad',
            expect.objectContaining({
                prioridad: 1,
                motivo: 'Urgencia comercial',
                confirmar_conflicto: false,
            }),
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('moves the visible window by week and returns to today', async () => {
        // Arrange
        const wrapper = mount(Cronograma, { props });

        expect(wrapper.find('[data-testid="rango-ventana"]').text()).toBe('09/11 → 22/11');

        // Act
        await wrapper.find('[aria-label="Semana siguiente"]').trigger('click');

        // Assert
        expect(wrapper.find('[data-testid="rango-ventana"]').text()).toBe('16/11 → 29/11');

        // Act
        await wrapper
            .findAll('button')
            .find((button) => button.text() === 'Hoy')
            ?.trigger('click');

        // Assert
        expect(wrapper.find('[data-testid="rango-ventana"]').text()).toBe('09/11 → 22/11');
    });

    it('narrows the window when the horizon changes', async () => {
        // Arrange
        const wrapper = mount(Cronograma, { props });

        // Act
        await wrapper.find('[data-testid="horizonte-7"]').trigger('click');

        // Assert
        expect(wrapper.find('[data-testid="rango-ventana"]').text()).toBe('09/11 → 15/11');
    });

    it('filters the rows by order estado', async () => {
        // Arrange
        const wrapper = mount(Cronograma, { props });

        // Act
        await wrapper.find('[data-testid="filtro-estado"]').setValue('En Curso');

        // Assert
        expect(wrapper.find('[data-testid="fila-orden-2"]').exists()).toBe(true);
        expect(wrapper.find('[data-testid="fila-orden-1"]').exists()).toBe(false);
        expect(wrapper.text()).not.toContain('Silla de pino');
    });

    it('shows the priority history of the selected order in the drawer', async () => {
        // Arrange
        const wrapper = mount(Cronograma, { props, attachTo: document.body });

        // Act
        await wrapper.find('[data-testid="reordenar-orden-1"]').trigger('click');

        // Assert
        expect(wrapper.find('[data-testid="historial-prioridad"]').text()).toContain(
            'Supervisor Demo',
        );
    });
});
