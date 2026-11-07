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

import Maquinarias from './Index.vue';

const props = {
    maquinarias: {
        data: [
            {
                id: 1,
                nombre: 'Lijadora orbital',
                tipo: 'Lijado',
                estado_operativo: 'Disponible',
                desgaste_acumulado: 12.5,
                planes: 1,
                intervenciones: 2,
            },
        ],
        links: [],
        from: 1,
        to: 1,
        total: 1,
    },
    filtros: {},
};

describe('Produccion Maquinarias page', () => {
    beforeEach(() => {
        hoisted.post.mockClear();
        hoisted.put.mockClear();
        hoisted.routerGet.mockClear();
        hoisted.routerDelete.mockClear();
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('renders the machines', () => {
        // Arrange / Act
        const wrapper = mount(Maquinarias, { props });

        // Assert
        expect(wrapper.text()).toContain('Lijadora orbital');
        expect(wrapper.text()).toContain('Lijado');
        expect(wrapper.text()).toContain('Disponible');
        expect(wrapper.text()).toContain('12.5');
        expect(wrapper.text()).toContain('Mostrando 1–1 de 1');
    });

    it('opens the create drawer and posts a new machine', async () => {
        // Arrange
        const wrapper = mount(Maquinarias, { props, attachTo: document.body });

        // Act
        const nuevo = wrapper
            .findAll('button')
            .find((button) => button.text() === 'Nueva maquinaria');
        await nuevo?.trigger('click');
        await wrapper.find('[data-testid="drawer-form"]').trigger('submit.prevent');

        // Assert
        expect(hoisted.post).toHaveBeenCalledWith(
            '/produccion/maquinarias',
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('opens the edit drawer and puts the changes', async () => {
        // Arrange
        const wrapper = mount(Maquinarias, { props, attachTo: document.body });

        // Act
        const editar = wrapper.findAll('button').find((button) => button.text() === 'Editar');
        await editar?.trigger('click');
        await wrapper.find('[data-testid="drawer-form"]').trigger('submit.prevent');

        // Assert
        expect(hoisted.put).toHaveBeenCalledWith(
            '/produccion/maquinarias/1',
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('deletes a machine after confirming', async () => {
        // Arrange
        vi.stubGlobal(
            'confirm',
            vi.fn(() => true),
        );
        const wrapper = mount(Maquinarias, { props });

        // Act
        const eliminar = wrapper.findAll('button').find((button) => button.text() === 'Eliminar');
        await eliminar?.trigger('click');

        // Assert
        expect(hoisted.routerDelete).toHaveBeenCalledWith('/produccion/maquinarias/1', {
            preserveScroll: true,
        });
    });

    it('applies the search through the router', async () => {
        // Arrange
        const wrapper = mount(Maquinarias, { props });

        // Act
        await wrapper.find('form').trigger('submit.prevent');

        // Assert
        expect(hoisted.routerGet).toHaveBeenCalledWith(
            '/produccion/maquinarias',
            expect.any(Object),
            expect.any(Object),
        );
    });
});
