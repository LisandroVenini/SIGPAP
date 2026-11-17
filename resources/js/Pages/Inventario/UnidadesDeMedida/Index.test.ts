import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { enableAutoUnmount, flushPromises, mount } from '@vue/test-utils';

enableAutoUnmount(afterEach);

const hoisted = vi.hoisted(() => {
    const post = vi.fn();
    const put = vi.fn();
    const routerDelete = vi.fn();
    const reset = vi.fn();
    const clearErrors = vi.fn();

    const page = {
        props: {
            flash: { success: null as string | null },
            errors: {} as Record<string, string>,
        },
    };

    return { post, put, routerDelete, reset, clearErrors, page };
});

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div />' },
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
    router: { delete: hoisted.routerDelete },
}));

vi.mock('../../../Layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /></div>' },
}));

vi.mock('@/composables/useConfirm', () => ({
    confirmar: vi.fn(async () => true),
}));

import Unidades from './Index.vue';

const props = {
    unidades: [
        { id: 1, nombre: 'Unidad', factor_conversion: 1, articulos: 2 },
        { id: 2, nombre: 'Litro', factor_conversion: 1, articulos: 0 },
    ],
};

describe('Inventario UnidadesDeMedida page', () => {
    beforeEach(() => {
        hoisted.post.mockClear();
        hoisted.put.mockClear();
        hoisted.routerDelete.mockClear();
    });

    it('renders the units with their usage count', () => {
        // Arrange / Act
        const wrapper = mount(Unidades, { props });

        // Assert
        expect(wrapper.text()).toContain('Unidad');
        expect(wrapper.text()).toContain('Litro');
        expect(
            wrapper.find('[data-testid="eliminar-unidad-1"]').attributes('disabled'),
        ).toBeDefined();
        expect(
            wrapper.find('[data-testid="eliminar-unidad-2"]').attributes('disabled'),
        ).toBeUndefined();
    });

    it('creates a unit from the drawer', async () => {
        // Arrange
        const wrapper = mount(Unidades, { props, attachTo: document.body });

        // Act
        await wrapper
            .findAll('button')
            .find((b) => b.text() === 'Nueva unidad')
            ?.trigger('click');
        await wrapper.find('[data-testid="unidad-form"]').trigger('submit.prevent');

        // Assert
        expect(hoisted.post).toHaveBeenCalledWith(
            '/inventario/unidades-de-medida',
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('edits a unit from the drawer', async () => {
        // Arrange
        const wrapper = mount(Unidades, { props, attachTo: document.body });

        // Act
        await wrapper.find('[data-testid="editar-unidad-1"]').trigger('click');
        await wrapper.find('[data-testid="unidad-form"]').trigger('submit.prevent');

        // Assert
        expect(hoisted.put).toHaveBeenCalledWith(
            '/inventario/unidades-de-medida/1',
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('deletes an unused unit after confirming', async () => {
        // Arrange
        const wrapper = mount(Unidades, { props, attachTo: document.body });

        // Act
        await wrapper.find('[data-testid="eliminar-unidad-2"]').trigger('click');
        await flushPromises();

        // Assert
        expect(hoisted.routerDelete).toHaveBeenCalledWith('/inventario/unidades-de-medida/2', {
            preserveScroll: true,
        });
    });
});
