import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { enableAutoUnmount, mount } from '@vue/test-utils';

enableAutoUnmount(afterEach);

const hoisted = vi.hoisted(() => {
    const post = vi.fn();
    const reset = vi.fn();
    const clearErrors = vi.fn();
    const routerDelete = vi.fn();

    const page = {
        props: {
            flash: { success: null as string | null },
            errors: {} as Record<string, string>,
        },
    };

    return { post, reset, clearErrors, routerDelete, page };
});

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div />' },
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
    useForm: (initial: Record<string, unknown>) => ({
        ...initial,
        errors: {},
        processing: false,
        post: hoisted.post,
        reset: hoisted.reset,
        clearErrors: hoisted.clearErrors,
    }),
    usePage: () => hoisted.page,
    router: { delete: hoisted.routerDelete },
}));

vi.mock('../../../Layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /></div>' },
}));

import Detalle from './Detalle.vue';

const props = {
    producto: { id: 1, codigo_sku: 'PT-001', descripcion: 'Silla de pino', unidad: 'Unidad' },
    asignadas: [{ etapa_id: 10, etapa: 'Lijado', tiempo_por_unidad: 5 }],
    disponibles: [{ id: 11, nombre: 'Pintado' }],
};

describe('Produccion Hoja de Ruta detail page', () => {
    beforeEach(() => {
        hoisted.post.mockClear();
        hoisted.routerDelete.mockClear();
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('renders the product and its assigned etapas', () => {
        // Arrange / Act
        const wrapper = mount(Detalle, { props });

        // Assert
        expect(wrapper.text()).toContain('Silla de pino');
        expect(wrapper.text()).toContain('Lijado');
        expect(wrapper.text()).toContain('Pintado');
    });

    it('posts a new etapa assignment', async () => {
        // Arrange
        const wrapper = mount(Detalle, { props });

        // Act
        await wrapper.find('[data-testid="asignar-form"]').trigger('submit.prevent');

        // Assert
        expect(hoisted.post).toHaveBeenCalledWith(
            '/produccion/hoja-de-ruta/1/etapas',
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('unassigns an etapa after confirming', async () => {
        // Arrange
        vi.stubGlobal(
            'confirm',
            vi.fn(() => true),
        );
        const wrapper = mount(Detalle, { props });
        const desasignar = wrapper
            .findAll('button')
            .find((button) => button.text() === 'Desasignar');

        expect(desasignar).toBeDefined();

        // Act
        await desasignar!.trigger('click');

        // Assert
        expect(hoisted.routerDelete).toHaveBeenCalledWith('/produccion/hoja-de-ruta/1/etapas/10', {
            preserveScroll: true,
        });
    });
});
