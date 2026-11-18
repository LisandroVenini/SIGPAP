import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';

const hoisted = vi.hoisted(() => ({
    get: vi.fn(),
    patch: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div />' },
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
    router: { get: hoisted.get, patch: hoisted.patch },
    usePage: () => ({ props: { flash: { success: null } } }),
}));

vi.mock('../../Layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /></div>' },
}));

import Index from './Index.vue';

const props = {
    alertas: {
        data: [
            {
                id: 1,
                tipo: 'stock_bajo_minimo',
                severidad: 'Critica',
                descripcion: 'Tabla de pino bajo el mínimo',
                fecha: '2026-11-18 10:00',
                resuelta_en: null,
                contexto: { articulo: 'Tabla de pino', disponible: 2 },
                roles: [{ name: 'Encargado de Stock' }],
            },
        ],
        links: [],
        from: 1,
        to: 1,
        total: 1,
    },
    filtros: {},
    noLeidas: 1,
    tipos: [{ clave: 'stock_bajo_minimo', etiqueta: 'Stock bajo el mínimo' }],
    severidades: [{ valor: 'Critica', etiqueta: 'Crítica' }],
};

describe('Alertas Index page', () => {
    beforeEach(() => {
        hoisted.get.mockClear();
        hoisted.patch.mockClear();
    });

    it('renders the alert, its context and the unread counter', () => {
        // Act
        const wrapper = mount(Index, { props });

        // Assert
        expect(wrapper.text()).toContain('Tabla de pino bajo el mínimo');
        expect(wrapper.text()).toContain('Crítica');
        expect(wrapper.text()).toContain('1 sin leer');
        expect(wrapper.text()).toContain('Encargado de Stock');
    });

    it('marks an alert as read through the router', async () => {
        // Arrange
        const wrapper = mount(Index, { props });

        // Act
        await wrapper
            .findAll('button')
            .find((boton) => boton.text() === 'Marcar leída')
            ?.trigger('click');

        // Assert
        expect(hoisted.patch).toHaveBeenCalledWith(
            '/alertas/1/leida',
            {},
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('applies the filters through the router', async () => {
        // Arrange
        const wrapper = mount(Index, { props });

        // Act
        await wrapper.find('#severidad').setValue('Critica');
        await wrapper.find('form').trigger('submit.prevent');

        // Assert
        expect(hoisted.get).toHaveBeenCalledWith(
            '/alertas',
            expect.objectContaining({ severidad: 'Critica', estado: 'abiertas' }),
            expect.objectContaining({ preserveState: true }),
        );
    });
});
