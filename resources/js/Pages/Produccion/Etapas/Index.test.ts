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

import Etapas from './Index.vue';

const props = {
    etapas: {
        data: [{ id: 1, nombre: 'Lijado', descripcion: 'Lijado de madera', asignaciones: 0 }],
        links: [],
        from: 1,
        to: 1,
        total: 1,
    },
    filtros: {},
};

describe('Produccion Etapas page', () => {
    beforeEach(() => {
        hoisted.post.mockClear();
        hoisted.put.mockClear();
        hoisted.routerGet.mockClear();
        hoisted.routerDelete.mockClear();
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('renders the etapas', () => {
        // Arrange / Act
        const wrapper = mount(Etapas, { props });

        // Assert
        expect(wrapper.text()).toContain('Lijado');
        expect(wrapper.text()).toContain('Lijado de madera');
        expect(wrapper.text()).toContain('Mostrando 1–1 de 1');
    });

    it('opens the create drawer and posts a new etapa', async () => {
        // Arrange
        const wrapper = mount(Etapas, { props, attachTo: document.body });

        // Act
        const nuevo = wrapper.findAll('button').find((button) => button.text() === 'Nueva etapa');
        await nuevo?.trigger('click');
        await wrapper.find('[data-testid="drawer-form"]').trigger('submit.prevent');

        // Assert
        expect(hoisted.post).toHaveBeenCalledWith(
            '/produccion/etapas',
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('opens the edit drawer and puts the changes', async () => {
        // Arrange
        const wrapper = mount(Etapas, { props, attachTo: document.body });

        // Act
        const editar = wrapper.findAll('button').find((button) => button.text() === 'Editar');
        await editar?.trigger('click');
        await wrapper.find('[data-testid="drawer-form"]').trigger('submit.prevent');

        // Assert
        expect(hoisted.put).toHaveBeenCalledWith(
            '/produccion/etapas/1',
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('deletes an etapa after confirming', async () => {
        // Arrange
        vi.stubGlobal(
            'confirm',
            vi.fn(() => true),
        );
        const wrapper = mount(Etapas, { props });

        // Act
        const eliminar = wrapper.findAll('button').find((button) => button.text() === 'Eliminar');
        await eliminar?.trigger('click');

        // Assert
        expect(hoisted.routerDelete).toHaveBeenCalledWith('/produccion/etapas/1', {
            preserveScroll: true,
        });
    });

    it('applies the search through the router', async () => {
        // Arrange
        const wrapper = mount(Etapas, { props });

        // Act
        await wrapper.find('form').trigger('submit.prevent');

        // Assert
        expect(hoisted.routerGet).toHaveBeenCalledWith(
            '/produccion/etapas',
            expect.any(Object),
            expect.any(Object),
        );
    });
});
