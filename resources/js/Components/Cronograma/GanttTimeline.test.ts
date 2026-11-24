import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import GanttTimeline from './GanttTimeline.vue';

const fila = {
    orden_id: 1,
    producto: 'Mesa de pino',
    estado: 'Asignada',
    prioridad: 1,
    fecha_entrega: '2026-11-30',
    reubicable: true,
    conflicto_entrega: false,
    cantidad_requerida: 8,
    etapa_actual: 'Corte',
    avance: 0,
    dias_totales: 2,
    etapas: [
        {
            etapa: 'Corte',
            inicio: '2026-11-02',
            fin: '2026-11-03',
            inicio_en: '2026-11-02 08:00:00',
            fin_en: '2026-11-03 12:00:00',
            dias: 2,
            minutos: 480,
            producida: 0,
            plan: { inicio: '2026-11-02 08:00:00', fin: '2026-11-02 12:00:00' },
        },
    ],
    historial: [],
};

describe('GanttTimeline', () => {
    it('draws the baseline of the plan next to the projection', () => {
        const wrapper = mount(GanttTimeline, {
            props: { filas: [fila], desde: '2026-11-02', dias: 7, hoy: '2026-11-02' },
        });

        expect(wrapper.find('[data-testid="plan-orden-1"]').exists()).toBe(true);
        expect(wrapper.find('[data-testid="barra-orden-1"]').exists()).toBe(true);
    });

    it('draws supply markers inside the visible window', () => {
        const wrapper = mount(GanttTimeline, {
            props: {
                filas: [fila],
                desde: '2026-11-02',
                dias: 7,
                hoy: '2026-11-02',
                marcas: [{ fecha: '2026-11-05', etiqueta: 'Pedir tabla', tono: 'riesgo' }],
            },
        });

        expect(wrapper.find('[data-testid="marca-2026-11-05"]').exists()).toBe(true);
    });

    it('ignores markers outside the visible window', () => {
        const wrapper = mount(GanttTimeline, {
            props: {
                filas: [fila],
                desde: '2026-11-02',
                dias: 7,
                hoy: '2026-11-02',
                marcas: [{ fecha: '2026-12-01', etiqueta: 'Fuera', tono: 'aviso' }],
            },
        });

        expect(wrapper.find('[data-testid="marca-2026-12-01"]').exists()).toBe(false);
    });
});
