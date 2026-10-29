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
    useForm: (initial: Record<string, unknown>) => ({
        ...initial,
        errors: {},
        processing: false,
        post: hoisted.post,
        put: hoisted.put,
        reset: hoisted.reset,
        clearErrors: hoisted.clearErrors,
    }),
    usePage: () => hoisted.page,
    router: { get: hoisted.routerGet, delete: hoisted.routerDelete },
}));

vi.mock('../../../Layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /></div>' },
}));

import Planificaciones from './Index.vue';

const props = {
    planificaciones: {
        data: [
            {
                id: 1,
                nombre: 'Semana 12',
                fecha_inicio: '2026-11-02',
                fecha_fin: '2026-11-08',
                observaciones: 'Turno completo',
                ordenes: 0,
            },
        ],
        links: [],
        from: 1,
        to: 1,
        total: 1,
    },
    filtros: {},
};

describe('Produccion Planificaciones page', () => {
    beforeEach(() => {
        hoisted.post.mockClear();
        hoisted.put.mockClear();
        hoisted.routerGet.mockClear();
        hoisted.routerDelete.mockClear();
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('renders the planificaciones', () => {
        // Arrange / Act
        const wrapper = mount(Planificaciones, { props });

        // Assert
        expect(wrapper.text()).toContain('Semana 12');
        expect(wrapper.text()).toContain('2026-11-02');
        expect(wrapper.text()).toContain('Mostrando 1–1 de 1');
    });

    it('opens the create drawer and posts a new planificacion', async () => {
        // Arrange
        const wrapper = mount(Planificaciones, { props, attachTo: document.body });

        // Act
        const nuevo = wrapper
            .findAll('button')
            .find((button) => button.text() === 'Nueva planificación');
        await nuevo?.trigger('click');
        await wrapper.find('[data-testid="drawer-form"]').trigger('submit.prevent');

        // Assert
        expect(hoisted.post).toHaveBeenCalledWith(
            '/produccion/planificaciones',
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('opens the edit drawer and puts the changes', async () => {
        // Arrange
        const wrapper = mount(Planificaciones, { props, attachTo: document.body });

        // Act
        const editar = wrapper.findAll('button').find((button) => button.text() === 'Editar');
        await editar?.trigger('click');
        await wrapper.find('[data-testid="drawer-form"]').trigger('submit.prevent');

        // Assert
        expect(hoisted.put).toHaveBeenCalledWith(
            '/produccion/planificaciones/1',
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('deletes a planificacion after confirming', async () => {
        // Arrange
        vi.stubGlobal(
            'confirm',
            vi.fn(() => true),
        );
        const wrapper = mount(Planificaciones, { props });

        // Act
        const eliminar = wrapper.findAll('button').find((button) => button.text() === 'Eliminar');
        await eliminar?.trigger('click');

        // Assert
        expect(hoisted.routerDelete).toHaveBeenCalledWith('/produccion/planificaciones/1', {
            preserveScroll: true,
        });
    });

    it('applies the search through the router', async () => {
        // Arrange
        const wrapper = mount(Planificaciones, { props });

        // Act
        await wrapper.find('form').trigger('submit.prevent');

        // Assert
        expect(hoisted.routerGet).toHaveBeenCalledWith(
            '/produccion/planificaciones',
            expect.any(Object),
            expect.any(Object),
        );
    });
});
