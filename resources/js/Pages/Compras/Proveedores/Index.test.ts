import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { enableAutoUnmount, mount } from '@vue/test-utils';

enableAutoUnmount(afterEach);

const hoisted = vi.hoisted(() => {
    const post = vi.fn();
    const put = vi.fn();
    const reset = vi.fn();
    const clearErrors = vi.fn();
    const routerGet = vi.fn();
    const routerDelete = vi.fn();

    const page = {
        props: {
            flash: { success: null as string | null },
            errors: {} as Record<string, string>,
        },
    };

    return { post, put, reset, clearErrors, routerGet, routerDelete, page };
});

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div />' },
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
    useForm: (initial: Record<string, unknown>) => {
        const form = {
            ...initial,
            errors: {},
            processing: false,
            post: hoisted.post,
            put: hoisted.put,
            reset: hoisted.reset,
            clearErrors: hoisted.clearErrors,
            transform: () => form,
        };

        return form;
    },
    usePage: () => hoisted.page,
    router: { get: hoisted.routerGet, delete: hoisted.routerDelete },
}));

vi.mock('../../../Layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /></div>' },
}));

import Proveedores from './Index.vue';

const props = {
    proveedores: {
        data: [
            {
                id: 1,
                razon_social: 'Aceros del Sur S.A.',
                cuit: '30-71234567-8',
                email: 'compras@acerosdelsur.test',
                telefono: '11-4444-5555',
                estado: 'Activo',
                solicitudes: 2,
                ordenes: 3,
            },
        ],
        links: [],
        from: 1,
        to: 1,
        total: 1,
    },
    filtros: {},
};

describe('Compras Proveedores page', () => {
    beforeEach(() => {
        hoisted.post.mockClear();
        hoisted.put.mockClear();
        hoisted.routerGet.mockClear();
        hoisted.routerDelete.mockClear();
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('renders the suppliers', () => {
        // Arrange / Act
        const wrapper = mount(Proveedores, { props });

        // Assert
        expect(wrapper.text()).toContain('Aceros del Sur S.A.');
        expect(wrapper.text()).toContain('30-71234567-8');
        expect(wrapper.text()).toContain('compras@acerosdelsur.test');
        expect(wrapper.text()).toContain('11-4444-5555');
        expect(wrapper.text()).toContain('Activo');
        expect(wrapper.text()).toContain('2');
        expect(wrapper.text()).toContain('3');
        expect(wrapper.text()).toContain('Mostrando 1–1 de 1');
    });

    it('opens the create drawer and posts a new supplier', async () => {
        // Arrange
        const wrapper = mount(Proveedores, { props, attachTo: document.body });

        // Act
        const nuevo = wrapper
            .findAll('button')
            .find((button) => button.text() === 'Nuevo proveedor');
        await nuevo?.trigger('click');
        await wrapper.find('[data-testid="drawer-form"]').trigger('submit.prevent');

        // Assert
        expect(hoisted.post).toHaveBeenCalledWith(
            '/compras/proveedores',
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('opens the edit drawer and puts the changes', async () => {
        // Arrange
        const wrapper = mount(Proveedores, { props, attachTo: document.body });

        // Act
        const editar = wrapper.findAll('button').find((button) => button.text() === 'Editar');
        await editar?.trigger('click');
        await wrapper.find('[data-testid="drawer-form"]').trigger('submit.prevent');

        // Assert
        expect(hoisted.put).toHaveBeenCalledWith(
            '/compras/proveedores/1',
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('deactivates a supplier after confirming', async () => {
        // Arrange
        vi.stubGlobal(
            'confirm',
            vi.fn(() => true),
        );
        const wrapper = mount(Proveedores, { props });

        // Act
        const baja = wrapper.findAll('button').find((button) => button.text() === 'Dar de baja');
        await baja?.trigger('click');

        // Assert
        expect(hoisted.routerDelete).toHaveBeenCalledWith('/compras/proveedores/1', {
            preserveScroll: true,
        });
    });

    it('applies the search through the router', async () => {
        // Arrange
        const wrapper = mount(Proveedores, { props });

        // Act
        await wrapper.find('form').trigger('submit.prevent');

        // Assert
        expect(hoisted.routerGet).toHaveBeenCalledWith(
            '/compras/proveedores',
            expect.any(Object),
            expect.any(Object),
        );
    });
});
