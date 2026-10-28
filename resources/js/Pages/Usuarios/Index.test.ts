import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { enableAutoUnmount, mount } from '@vue/test-utils';

enableAutoUnmount(afterEach);

const hoisted = vi.hoisted(() => {
    const post = vi.fn();
    const put = vi.fn();
    const reset = vi.fn();
    const clearErrors = vi.fn();
    const routerGet = vi.fn();
    const routerPatch = vi.fn();

    const form = {
        nombre: '',
        email: '',
        rol: '',
        password: '',
        estado: 'Activo',
        errors: {} as Record<string, string>,
        processing: false,
        post,
        put,
        reset,
        clearErrors,
    };

    const page = {
        props: {
            auth: { user: { id: 1 } },
            flash: { success: null as string | null },
        },
    };

    return { post, put, reset, clearErrors, routerGet, routerPatch, form, page };
});

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div />' },
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
    useForm: () => hoisted.form,
    usePage: () => hoisted.page,
    router: { get: hoisted.routerGet, patch: hoisted.routerPatch },
}));

vi.mock('../../Layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /></div>' },
}));

import Index from './Index.vue';

const props = {
    usuarios: {
        data: [
            {
                id: 1,
                nombre: 'Admin Sistema',
                email: 'admin@test.local',
                estado: 'Activo',
                roles: [{ id: 1, name: 'Administrador' }],
            },
            {
                id: 2,
                nombre: 'Laura Cáceres',
                email: 'laura@confort.test',
                estado: 'Activo',
                roles: [{ id: 2, name: 'Gerente' }],
            },
        ],
        links: [],
        from: 1,
        to: 2,
        total: 2,
    },
    resumen: { total: 2, activos: 2, inhabilitados: 0 },
    roles: ['Administrador', 'Gerente'],
    filtros: {},
};

describe('Usuarios Index page', () => {
    beforeEach(() => {
        hoisted.post.mockClear();
        hoisted.put.mockClear();
        hoisted.routerGet.mockClear();
        hoisted.routerPatch.mockClear();
        hoisted.form.errors = {};
        hoisted.form.nombre = '';
        hoisted.form.email = '';
        hoisted.form.rol = '';
        hoisted.form.password = '';
        hoisted.form.estado = 'Activo';
    });

    it('renders the users with their role and state', () => {
        // Arrange / Act
        const wrapper = mount(Index, { props });

        // Assert
        expect(wrapper.text()).toContain('Laura Cáceres');
        expect(wrapper.text()).toContain('Gerente');
        expect(wrapper.text()).toContain('2 usuarios registrados');
    });

    it('opens the create drawer and posts a new usuario', async () => {
        // Arrange
        const wrapper = mount(Index, { props, attachTo: document.body });

        // Act
        const nuevo = wrapper
            .findAll('button')
            .find((button) => button.text().includes('Nuevo usuario'));
        await nuevo?.trigger('click');
        await wrapper.find('[data-testid="drawer-form"]').trigger('submit.prevent');

        // Assert
        expect(hoisted.post).toHaveBeenCalledWith(
            '/usuarios',
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('opens the edit drawer and puts the changes', async () => {
        // Arrange
        const wrapper = mount(Index, { props, attachTo: document.body });

        // Act
        const editar = wrapper.findAll('button').find((button) => button.text() === 'Editar');
        await editar?.trigger('click');
        await wrapper.find('[data-testid="drawer-form"]').trigger('submit.prevent');

        // Assert
        expect(hoisted.put).toHaveBeenCalledWith(
            '/usuarios/1',
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('toggles the state of another usuario', async () => {
        // Arrange
        const wrapper = mount(Index, { props });

        // Act
        const inhabilitar = wrapper
            .findAll('button')
            .find((button) => button.text() === 'Inhabilitar');
        await inhabilitar?.trigger('click');

        // Assert
        expect(hoisted.routerPatch).toHaveBeenCalledWith(
            '/usuarios/2/estado',
            {},
            { preserveScroll: true },
        );
    });

    it('applies the filters through the router', async () => {
        // Arrange
        const wrapper = mount(Index, { props });

        // Act
        await wrapper.findAll('form')[0].trigger('submit.prevent');

        // Assert
        expect(hoisted.routerGet).toHaveBeenCalledWith(
            '/usuarios',
            expect.any(Object),
            expect.any(Object),
        );
    });

    it('renders the success flash message', () => {
        // Arrange
        hoisted.page.props.flash.success = 'Usuario creado.';

        // Act
        const wrapper = mount(Index, { props });

        // Assert
        expect(wrapper.text()).toContain('Usuario creado.');

        hoisted.page.props.flash.success = null;
    });
});
