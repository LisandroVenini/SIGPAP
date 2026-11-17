import { describe, it, expect, vi } from 'vitest';
import { mount, type VueWrapper } from '@vue/test-utils';

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

function findMenuTrigger(wrapper: VueWrapper, label: string) {
    return wrapper
        .findAll('button')
        .find(
            (button) =>
                button.attributes('aria-haspopup') === 'true' && button.text().includes(label),
        );
}

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

    it('does not render the section bar nor the footer', () => {
        // Arrange / Act
        const wrapper = mount(AppLayout, { props: { title: 'Tablero' } });

        // Assert
        expect(wrapper.find('footer').exists()).toBe(false);
        expect(wrapper.text()).not.toContain('Planta Posadas');
        expect(wrapper.text()).not.toContain('Confort y Muebles S.A.');
    });

    it('reveals a group of links when its dropdown is opened', async () => {
        // Arrange
        const wrapper = mount(AppLayout, { props: { title: 'Tablero' } });
        const trigger = findMenuTrigger(wrapper, 'Producción');

        expect(trigger?.attributes('aria-expanded')).toBe('false');
        expect(wrapper.text()).not.toContain('Hoja de Ruta');

        // Act
        await trigger?.trigger('click');

        // Assert
        expect(trigger?.attributes('aria-expanded')).toBe('true');
        expect(wrapper.text()).toContain('Hoja de Ruta');

        // Act
        await trigger?.trigger('click');

        // Assert
        expect(trigger?.attributes('aria-expanded')).toBe('false');
        expect(wrapper.text()).not.toContain('Hoja de Ruta');
    });

    it('links the daily report to its own section and highlights it on that url', async () => {
        // Arrange
        page.url = '/produccion/partes-diarios';
        const wrapper = mount(AppLayout, { props: { title: 'Parte Diario' } });

        // Act
        await findMenuTrigger(wrapper, 'Producción')?.trigger('click');
        const parteDiario = wrapper.findAll('a').find((link) => link.text() === 'Parte Diario');

        // Assert
        expect(parteDiario?.attributes('href')).toBe('/produccion/partes-diarios');
        expect(parteDiario?.classes()).toContain('border-teja');

        page.url = '/dashboard';
    });

    it('opens the mobile navigation panel', async () => {
        // Arrange
        const wrapper = mount(AppLayout, { props: { title: 'Tablero' } });
        const toggle = wrapper.find('button[aria-controls="mobile-nav"]');

        expect(wrapper.find('#mobile-nav').exists()).toBe(false);

        // Act
        await toggle.trigger('click');

        // Assert
        expect(wrapper.find('#mobile-nav').exists()).toBe(true);
        expect(wrapper.find('#mobile-nav').text()).toContain('Inventario');
        expect(toggle.attributes('aria-expanded')).toBe('true');
    });

    it('renders the logout control', () => {
        // Arrange / Act
        const wrapper = mount(AppLayout, { props: { title: 'Tablero' } });

        // Assert
        expect(wrapper.text()).toContain('Cerrar sesión');
    });
});
