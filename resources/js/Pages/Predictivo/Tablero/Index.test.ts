import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { enableAutoUnmount, mount } from '@vue/test-utils';

enableAutoUnmount(afterEach);

const hoisted = vi.hoisted(() => {
    return { get: vi.fn() };
});

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div />' },
    router: { get: hoisted.get },
}));

vi.mock('@/Layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /></div>' },
}));

vi.mock('@/Components/Cronograma/GanttTimeline.vue', () => ({
    default: {
        props: ['filas', 'desde', 'dias', 'hoy'],
        template: '<div data-testid="gantt" />',
    },
}));

import Tablero from './Index.vue';

const fila = {
    orden_id: 1,
    producto: 'Silla de pino',
    estado: 'En Curso',
    prioridad: 1,
    fecha_entrega: '2026-11-30',
    reubicable: false,
    conflicto_entrega: false,
    cantidad_requerida: 10,
    etapa_actual: 'Corte',
    avance: 40,
    dias_totales: 3,
    etapas: [],
    historial: [],
};

function props(overrides: Record<string, unknown> = {}) {
    return {
        cronograma: {
            inicio: '2026-11-02',
            hoy: '2026-11-02',
            dias: 14,
            filas: [fila],
        },
        kpis: {
            ordenes_activas: 3,
            en_riesgo: 1,
            avance_promedio: 42.5,
            tasa_promedio: 0.875,
        },
        alertas: [],
        tasas: {},
        estados: ['Pendiente', 'En Curso'],
        filtros: { estado: null, solo_riesgo: false },
        sinDatos: false,
        calendarioConfigurado: true,
        ...overrides,
    };
}

describe('Tablero Predictivo', () => {
    beforeEach(() => {
        hoisted.get.mockReset();
    });

    it('renders the KPIs and the shared gantt', () => {
        const wrapper = mount(Tablero, { props: props() });

        expect(wrapper.get('[data-testid="kpi-ordenes"]').text()).toBe('3');
        expect(wrapper.get('[data-testid="kpi-riesgo"]').text()).toBe('1');
        expect(wrapper.get('[data-testid="kpi-avance"]').text()).toBe('42.5%');
        expect(wrapper.get('[data-testid="kpi-tasa"]').text()).toBe('87.5%');
        expect(wrapper.find('[data-testid="gantt"]').exists()).toBe(true);
    });

    it('shows the exact empty state when there is no data', () => {
        const wrapper = mount(Tablero, {
            props: props({
                sinDatos: true,
                cronograma: { inicio: '2026-11-02', hoy: '2026-11-02', dias: 14, filas: [] },
            }),
        });

        expect(wrapper.get('[data-testid="estado-vacio"]').text()).toContain(
            'Recolectando datos de planta para primeras predicciones',
        );
        expect(wrapper.find('[data-testid="gantt"]').exists()).toBe(false);
    });

    it('lists the bottleneck alerts', () => {
        const wrapper = mount(Tablero, {
            props: props({
                alertas: [
                    {
                        id: 7,
                        descripcion: 'La orden 4 se proyecta con 6 días de atraso.',
                        severidad: 'Atencion',
                        fecha: '2026-11-23 10:00:00',
                        orden_de_produccion_id: 4,
                    },
                ],
            }),
        });

        expect(wrapper.text()).toContain('La orden 4 se proyecta con 6 días de atraso.');
    });

    it('warns when the working calendar is not configured', () => {
        const wrapper = mount(Tablero, { props: props({ calendarioConfigurado: false }) });

        expect(wrapper.text()).toContain('El calendario laboral no está configurado');
    });

    it('sends the filters to the server', async () => {
        const wrapper = mount(Tablero, { props: props() });

        await wrapper.find('input[type="checkbox"]').setValue(true);
        await wrapper.get('[data-testid="aplicar-filtros"]').trigger('click');

        expect(hoisted.get).toHaveBeenCalledWith(
            '/predictivo/tablero',
            { estado: undefined, solo_riesgo: 1 },
            { preserveState: true, preserveScroll: true },
        );
    });
});
