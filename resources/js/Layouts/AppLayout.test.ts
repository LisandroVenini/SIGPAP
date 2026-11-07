import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';

const page = vi.hoisted(() => ({
    props: {
        auth: {
            user: {
                id: 1,
                nombre: 'Marcos Cáceres',
                email: 'marcos@confort.test',
                roles: ['Supervisor de Producción'],
            },
        },
    },
    url: '/dashboard',
}));

vi.mock('@inertiajs/vue3', () => ({
    Link: {
        props: ['href', 'method', 'as'],
        template: '<a :href="href"><slot /></a>',
    },
    usePage: () => page,
}));

import AppLayout from './AppLayout.vue';

describe('AppLayout', () => {
    it('renders the page title, the slot and the authenticated user chip', () => {
        // Arrange / Act
        const wrapper = mount(AppLayout, {
            props: { title: 'Tablero' },
            slots: { default: '<div>contenido de la página</div>' },
        });

        // Assert
        expect(wrapper.text()).toContain('Tablero');
        expect(wrapper.text()).toContain('Marcos Cáceres');
        expect(wrapper.text()).toContain('Supervisor de Producción');
        expect(wrapper.text()).toContain('contenido de la página');
    });

    it('links the daily report to the in-progress orders and highlights it on that url', () => {
        // Arrange
        page.url = '/produccion/ordenes-de-produccion?estado=En Curso';
        const wrapper = mount(AppLayout, { props: { title: 'Parte Diario' } });

        // Act
        const parteDiario = wrapper.findAll('a').find((link) => link.text() === 'Parte Diario');

        // Assert
        expect(parteDiario?.attributes('href')).toBe(
            '/produccion/ordenes-de-produccion?estado=En Curso',
        );
        expect(parteDiario?.classes()).toContain('border-teja');

        page.url = '/dashboard';
    });

    it('renders the logout control', () => {
        // Arrange / Act
        const wrapper = mount(AppLayout, { props: { title: 'Tablero' } });

        // Assert
        expect(wrapper.text()).toContain('Cerrar sesión');
    });
});
