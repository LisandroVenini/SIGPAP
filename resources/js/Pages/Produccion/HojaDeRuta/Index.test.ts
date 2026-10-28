import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { enableAutoUnmount, mount } from '@vue/test-utils';

enableAutoUnmount(afterEach);

const hoisted = vi.hoisted(() => ({ routerGet: vi.fn() }));

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div />' },
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
    router: { get: hoisted.routerGet },
}));

vi.mock('../../../Layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /></div>' },
}));

import HojaDeRuta from './Index.vue';

const props = {
    productos: [
        { id: 1, codigo_sku: 'PT-001', descripcion: 'Silla de pino', unidad: 'Unidad', etapas: 3 },
    ],
    filtros: {},
};

describe('Produccion Hoja de Ruta index page', () => {
    beforeEach(() => {
        hoisted.routerGet.mockClear();
    });

    it('renders the fabricado products with their etapas count', () => {
        // Arrange / Act
        const wrapper = mount(HojaDeRuta, { props });

        // Assert
        expect(wrapper.text()).toContain('Silla de pino');
        expect(wrapper.text()).toContain('PT-001');
        expect(wrapper.text()).toContain('3');
    });

    it('links each product to its hoja de ruta', () => {
        // Arrange / Act
        const wrapper = mount(HojaDeRuta, { props });

        // Assert
        expect(wrapper.find('a[href="/produccion/hoja-de-ruta/1"]').exists()).toBe(true);
    });

    it('searches products by term', async () => {
        // Arrange
        const wrapper = mount(HojaDeRuta, { props });

        // Act
        await wrapper.find('#buscar').setValue('Silla');
        await wrapper.find('form').trigger('submit.prevent');

        // Assert
        expect(hoisted.routerGet).toHaveBeenCalledWith(
            '/produccion/hoja-de-ruta',
            { buscar: 'Silla' },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    });

    it('shows the empty state when there are no fabricado products', () => {
        // Arrange / Act
        const wrapper = mount(HojaDeRuta, { props: { productos: [], filtros: {} } });

        // Assert
        expect(wrapper.text()).toContain('No hay productos terminados de origen Fabricado');
    });
});
