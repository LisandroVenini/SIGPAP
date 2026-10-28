import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { enableAutoUnmount, mount } from '@vue/test-utils';

enableAutoUnmount(afterEach);

const hoisted = vi.hoisted(() => {
    const patch = vi.fn();
    const clearErrors = vi.fn();
    const routerGet = vi.fn();

    const page = {
        props: {
            flash: { success: null as string | null },
        },
    };

    return { patch, clearErrors, routerGet, page };
});

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div />' },
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
    useForm: (initial: Record<string, unknown>) => ({
        ...initial,
        errors: {},
        processing: false,
        patch: hoisted.patch,
        clearErrors: hoisted.clearErrors,
    }),
    usePage: () => hoisted.page,
    router: { get: hoisted.routerGet },
}));

vi.mock('../../Layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /></div>' },
}));

import Ajuste from './Ajuste.vue';

const props = {
    articulos: {
        data: [
            {
                id: 1,
                codigo_sku: 'MP-001',
                descripcion: 'Madera de pino',
                stock_fisico: 5,
                unidad: 'Metro',
            },
            {
                id: 2,
                codigo_sku: 'IN-001',
                descripcion: 'Barniz',
                stock_fisico: 8,
                unidad: 'Litro',
            },
        ],
        links: [],
        from: 1,
        to: 2,
        total: 2,
    },
    motivos: ['Rotura', 'Auditoría', 'Merma', 'Otro'],
    filtros: {},
};

describe('Inventario Ajuste page', () => {
    beforeEach(() => {
        hoisted.patch.mockClear();
        hoisted.clearErrors.mockClear();
        hoisted.routerGet.mockClear();
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('renders the articles with their current stock', () => {
        // Arrange / Act
        const wrapper = mount(Ajuste, { props });

        // Assert
        expect(wrapper.text()).toContain('Madera de pino');
        expect(wrapper.text()).toContain('MP-001');
        expect(wrapper.text()).toContain('Mostrando 1–2 de 2');
    });

    it('opens the adjust drawer and patches the adjustment', async () => {
        // Arrange
        const wrapper = mount(Ajuste, { props, attachTo: document.body });

        // Act
        const ajustar = wrapper.findAll('button').find((button) => button.text() === 'Ajustar');
        await ajustar?.trigger('click');
        await wrapper.find('[data-testid="ajuste-form"]').trigger('submit.prevent');

        // Assert
        expect(hoisted.patch).toHaveBeenCalledWith(
            '/inventario/articulos/1/ajuste',
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('applies the search through the router', async () => {
        // Arrange
        const wrapper = mount(Ajuste, { props });

        // Act
        await wrapper.find('form').trigger('submit.prevent');

        // Assert
        expect(hoisted.routerGet).toHaveBeenCalledWith(
            '/inventario/ajustes',
            expect.any(Object),
            expect.any(Object),
        );
    });
});
