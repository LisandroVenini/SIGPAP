import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';

const hoisted = vi.hoisted(() => ({
    page: {
        props: {
            auth: {
                user: { nombre: 'Ana Pérez' },
            },
        },
    },
}));

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div />' },
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
    usePage: () => hoisted.page,
}));

vi.mock('../Layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /></div>' },
}));

import Dashboard from './Dashboard.vue';

type ProximaEntrega = {
    orden_id: number;
    producto: string;
    fecha_entrega: string | null;
    estado: string;
};

type MaquinaBloqueada = {
    id: number;
    nombre: string;
    desgaste: number;
};

type Resumen = {
    produccion: {
        activas: number;
        en_curso: number;
        pendientes_por_insumos: number;
        atrasadas: number;
        proximas_entregas: ProximaEntrega[];
    } | null;
    inventario: {
        activos: number;
        bajo_minimo: number;
        comprometido: number;
    } | null;
    compras: {
        pendientes_recepcion: number;
        solicitudes_abiertas: number;
        proveedores_activos: number;
    } | null;
    mantenimiento: {
        maquinas_bloqueadas: number;
        planes: number;
        bloqueadas: MaquinaBloqueada[];
    } | null;
};

const resumenCompleto: Resumen = {
    produccion: {
        activas: 7,
        en_curso: 2,
        pendientes_por_insumos: 1,
        atrasadas: 3,
        proximas_entregas: [
            {
                orden_id: 12,
                producto: 'Silla de pino',
                fecha_entrega: '2026-11-10',
                estado: 'Pendiente',
            },
        ],
    },
    inventario: {
        activos: 24,
        bajo_minimo: 4,
        comprometido: 1234.5,
    },
    compras: {
        pendientes_recepcion: 5,
        solicitudes_abiertas: 2,
        proveedores_activos: 9,
    },
    mantenimiento: {
        maquinas_bloqueadas: 1,
        planes: 3,
        bloqueadas: [{ id: 4, nombre: 'Lijadora principal', desgaste: 300 }],
    },
};

function resumenVacio(): Resumen {
    return {
        produccion: null,
        inventario: null,
        compras: null,
        mantenimiento: null,
    };
}

describe('Dashboard page', () => {
    it('greets the user and renders every available area with its indicators', () => {
        // Arrange / Act
        const wrapper = mount(Dashboard, { props: { resumen: resumenCompleto } });

        // Assert
        expect(wrapper.find('[data-testid="saludo"]').text()).toContain('Ana Pérez');
        expect(wrapper.find('[data-testid="produccion-activas"]').text()).toBe('7');
        expect(wrapper.find('[data-testid="produccion-atrasadas"]').text()).toBe('3');
        expect(wrapper.find('[data-testid="inventario-activos"]').text()).toBe('24');
        expect(wrapper.find('[data-testid="inventario-comprometido"]').text()).toBe('1.234,5');
        expect(wrapper.find('[data-testid="compras-pendientes-recepcion"]').text()).toBe('5');
        expect(wrapper.find('[data-testid="mantenimiento-maquinas-bloqueadas"]').text()).toBe('1');
        expect(wrapper.find('[data-testid="sin-areas"]').exists()).toBe(false);
    });

    it('renders the next deliveries and blocked machines lists with module links', () => {
        // Arrange / Act
        const wrapper = mount(Dashboard, { props: { resumen: resumenCompleto } });

        // Assert
        expect(wrapper.find('[data-testid="proximas-entregas"]').text()).toContain('Silla de pino');
        expect(wrapper.find('[data-testid="proximas-entregas"]').text()).toContain('10/11/2026');
        expect(wrapper.find('[data-testid="proximas-entregas"]').text()).toContain('Pendiente');
        expect(wrapper.find('[data-testid="maquinas-bloqueadas"]').text()).toContain(
            'Lijadora principal',
        );
        expect(wrapper.find('[data-testid="maquinas-bloqueadas"]').text()).toContain('300');
        expect(wrapper.find('[data-testid="link-maquinarias"]').attributes('href')).toBe(
            '/produccion/maquinarias',
        );
    });

    it('only renders the areas present in the summary', () => {
        // Arrange
        const resumen: Resumen = { ...resumenVacio(), inventario: resumenCompleto.inventario };

        // Act
        const wrapper = mount(Dashboard, { props: { resumen } });

        // Assert
        expect(wrapper.find('[data-testid="area-produccion"]').exists()).toBe(false);
        expect(wrapper.find('[data-testid="area-inventario"]').exists()).toBe(true);
        expect(wrapper.find('[data-testid="area-compras"]').exists()).toBe(false);
        expect(wrapper.find('[data-testid="area-mantenimiento"]').exists()).toBe(false);
    });

    it('shows the empty state when the user has no available areas', () => {
        // Act
        const wrapper = mount(Dashboard, { props: { resumen: resumenVacio() } });

        // Assert
        expect(wrapper.find('[data-testid="sin-areas"]').text()).toContain(
            'No tenés áreas disponibles',
        );
        expect(wrapper.find('[data-testid="area-produccion"]').exists()).toBe(false);
    });
});
