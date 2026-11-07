import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div />' },
}));

vi.mock('../../../Layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /></div>' },
}));

import Ranking from './Index.vue';

const parametros = {
    pesos: {
        perfecta: 1,
        retraso: 0.6,
        faltante: 0.3,
        rotura: 0,
    },
    ventana_meses: 12,
};

const filas = [
    {
        proveedor_id: 1,
        proveedor: 'Alfa',
        entregas: 4,
        perfectas: 4,
        retrasos: 0,
        faltantes: 0,
        roturas: 0,
        puntaje: 100,
    },
    {
        proveedor_id: 2,
        proveedor: 'Beta',
        entregas: 4,
        perfectas: 2,
        retrasos: 1,
        faltantes: 0,
        roturas: 1,
        puntaje: 70,
    },
    {
        proveedor_id: 3,
        proveedor: 'Gamma',
        entregas: 4,
        perfectas: 1,
        retrasos: 0,
        faltantes: 0,
        roturas: 3,
        puntaje: 40,
    },
];

describe('Compras Ranking page', () => {
    it('renders one row per supplier with its delivery counts and score', () => {
        // Arrange / Act
        const wrapper = mount(Ranking, {
            props: { parametros, filas, sinHistorial: [] },
        });

        // Assert
        const rows = wrapper.findAll('tbody tr');

        expect(rows).toHaveLength(3);
        expect(rows[0].text()).toContain('Alfa');
        expect(rows[0].text()).toContain('100.00');
        expect(rows[0].text()).toContain('4');
        expect(rows[1].text()).toContain('Beta');
        expect(rows[1].text()).toContain('70.00');
        expect(rows[2].text()).toContain('Gamma');
        expect(rows[2].text()).toContain('40.00');
    });

    it('paints the score badge green, amber, or red depending on the range', () => {
        // Arrange / Act
        const wrapper = mount(Ranking, {
            props: { parametros, filas, sinHistorial: [] },
        });

        // Assert
        const badges = wrapper.findAll('[data-testid="puntaje"]');

        expect(badges).toHaveLength(3);
        expect(badges[0].classes()).toContain('text-pine');
        expect(badges[1].classes()).toContain('text-amber');
        expect(badges[2].classes()).toContain('text-oxide');
    });

    it('renders the without-history section only when it has suppliers', () => {
        // Arrange / Act
        const conHistorialFaltante = mount(Ranking, {
            props: {
                parametros,
                filas,
                sinHistorial: [{ proveedor_id: 9, proveedor: 'Sin movimientos' }],
            },
        });
        const sinHistorialFaltante = mount(Ranking, {
            props: { parametros, filas, sinHistorial: [] },
        });

        // Assert
        expect(conHistorialFaltante.text()).toContain('Sin historial');
        expect(conHistorialFaltante.text()).toContain('Sin movimientos');
        expect(sinHistorialFaltante.text()).not.toContain('Sin historial');
    });

    it('shows the active weights and window', () => {
        // Arrange / Act
        const wrapper = mount(Ranking, {
            props: { parametros, filas, sinHistorial: [] },
        });

        // Assert
        expect(wrapper.find('[data-testid="nota-parametros"]').text()).toContain(
            'perfecta 1 · retraso 0.6 · faltante 0.3 · rotura 0 · Ventana: 12 meses',
        );
    });

    it('renders the empty state when there are no deliveries in the window', () => {
        // Arrange / Act
        const wrapper = mount(Ranking, {
            props: { parametros, filas: [], sinHistorial: [] },
        });

        // Assert
        expect(wrapper.text()).toContain('No hay entregas registradas en el período.');
    });
});
