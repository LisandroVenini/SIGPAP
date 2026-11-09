import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';

const hoisted = vi.hoisted(() => ({ routerGet: vi.fn() }));

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div />' },
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
    router: { get: hoisted.routerGet },
}));

vi.mock('../../Layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /></div>' },
}));

import Index from './Index.vue';

interface FilaReporte {
    [key: string]: string | number | null;
}

interface PreviewReporte {
    tipo: string;
    titulo: string;
    columnas: string[];
    filas: FilaReporte[];
    total: number;
    desde: string;
    hasta: string;
}

const tipos = {
    produccion: 'Producción',
    inventario: 'Inventario',
};

function fila(numero: number): FilaReporte {
    return {
        orden_id: numero,
        producto: `Producto ${numero}`,
        estado: 'Pendiente',
    };
}

function preview(filas: FilaReporte[]): PreviewReporte {
    return {
        tipo: 'produccion',
        titulo: 'Producción',
        columnas: ['Orden', 'Producto', 'Estado'],
        filas,
        total: filas.length,
        desde: '2026-03-01',
        hasta: '2026-03-31',
    };
}

const props = {
    tipos,
    filtros: {},
    preview: null as PreviewReporte | null,
};

describe('Reportes Index page', () => {
    beforeEach(() => {
        hoisted.routerGet.mockClear();
    });

    it('renders only the available report types', () => {
        // Arrange / Act
        const wrapper = mount(Index, { props });

        // Assert
        const opciones = wrapper
            .findAll('[data-testid="tipo"] option')
            .map((opcion) => opcion.text());
        expect(opciones).toEqual(['Producción', 'Inventario']);
    });

    it('applies the selected filters through the router', async () => {
        // Arrange
        const wrapper = mount(Index, { props });

        // Act
        await wrapper.find('[data-testid="tipo"]').setValue('produccion');
        await wrapper.find('[data-testid="desde"]').setValue('2026-03-01');
        await wrapper.find('[data-testid="hasta"]').setValue('2026-03-31');
        await wrapper.find('[data-testid="estado"]').setValue('Pendiente');
        await wrapper.find('form[data-testid="filtros-reporte"]').trigger('submit.prevent');

        // Assert
        expect(hoisted.routerGet).toHaveBeenCalledWith(
            '/reportes',
            expect.objectContaining({
                tipo: 'produccion',
                desde: '2026-03-01',
                hasta: '2026-03-31',
                estado: 'Pendiente',
            }),
            expect.any(Object),
        );
    });

    it('only offers the estado filter for the production report', async () => {
        // Arrange
        const wrapper = mount(Index, { props });

        // Act / Assert
        await wrapper.find('[data-testid="tipo"]').setValue('produccion');
        expect(wrapper.find('[data-testid="estado"]').exists()).toBe(true);

        await wrapper.find('[data-testid="tipo"]').setValue('inventario');
        expect(wrapper.find('[data-testid="estado"]').exists()).toBe(false);
    });

    it('renders at most 50 preview rows and shows the total', () => {
        // Arrange
        const filas = Array.from({ length: 60 }, (_, indice) => fila(indice + 1));

        // Act
        const wrapper = mount(Index, { props: { ...props, preview: preview(filas) } });

        // Assert
        expect(wrapper.findAll('[data-testid="preview"] tbody tr')).toHaveLength(50);
        expect(wrapper.find('[data-testid="total"]').text()).toContain('60');
    });

    it('shows the exact empty state when the report has no rows', () => {
        // Arrange
        const wrapper = mount(Index, { props: { ...props, preview: preview([]) } });

        // Assert
        expect(wrapper.find('[data-testid="sin-datos"]').text()).toContain(
            'Sin datos para generar',
        );
        expect(wrapper.find('[data-testid="preview"]').exists()).toBe(false);
    });

    it('renders the PDF and Excel download links with the current filters', async () => {
        // Arrange
        const wrapper = mount(Index, {
            props: {
                ...props,
                filtros: {
                    tipo: 'produccion',
                    desde: '2026-03-01',
                    hasta: '2026-03-31',
                },
                preview: preview([fila(1)]),
            },
        });

        // Act / Assert
        const pdf = wrapper.find('[data-testid="exportar-pdf"]');
        const xlsx = wrapper.find('[data-testid="exportar-xlsx"]');

        expect(pdf.attributes('href')).toContain('formato=pdf');
        expect(pdf.attributes('href')).toContain('tipo=produccion');
        expect(xlsx.attributes('href')).toContain('formato=xlsx');
        expect(xlsx.attributes('href')).toContain('desde=2026-03-01');
    });
});
