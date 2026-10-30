import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { enableAutoUnmount, mount } from '@vue/test-utils';
import { reactive } from 'vue';

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
    useForm: (initial: Record<string, unknown>) =>
        reactive({
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
    receta: {
        componentes: [
            {
                id: 100,
                articulo_id: 20,
                codigo_sku: 'MP-001',
                descripcion: 'Tablón de pino',
                unidad: 'Metro',
                cantidad: 2.5,
            },
        ],
        disponibles: [
            {
                id: 21,
                codigo_sku: 'MP-002',
                descripcion: 'Barniz natural',
                unidad: 'Litro',
            },
        ],
    },
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

    it('renders the recipe section with components and available supplies', () => {
        // Arrange / Act
        const wrapper = mount(Detalle, { props });

        // Assert
        expect(wrapper.text()).toContain('Receta');
        expect(wrapper.text()).toContain('Tablón de pino');
        expect(wrapper.text()).toContain('MP-001');
        expect(wrapper.text()).toContain('Metro');
        expect(wrapper.text()).toContain('2.50');
        expect(wrapper.text()).toContain('Barniz natural');
    });

    it('renders the empty recipe state when there are no components', () => {
        // Arrange / Act
        const wrapper = mount(Detalle, {
            props: {
                ...props,
                receta: { componentes: [], disponibles: [] },
            },
        });

        // Assert
        expect(wrapper.text()).toContain('Este producto no tiene receta definida.');
    });

    it('posts the recipe form to the correct endpoint', async () => {
        // Arrange
        const wrapper = mount(Detalle, { props });

        // Act
        await wrapper.find('[data-testid="receta-form"]').trigger('submit.prevent');

        // Assert
        expect(hoisted.post).toHaveBeenCalledWith(
            '/produccion/hoja-de-ruta/1/receta/componentes',
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('removes a recipe component after confirming', async () => {
        // Arrange
        vi.stubGlobal(
            'confirm',
            vi.fn(() => true),
        );
        const wrapper = mount(Detalle, { props });
        const quitar = wrapper.findAll('button').find((button) => button.text() === 'Quitar');

        expect(quitar).toBeDefined();

        // Act
        await quitar!.trigger('click');

        // Assert
        expect(hoisted.routerDelete).toHaveBeenCalledWith(
            '/produccion/hoja-de-ruta/1/receta/componentes/100',
            {
                preserveScroll: true,
            },
        );
    });

    it('updates the quantity unit label when a supply is selected', async () => {
        // Arrange
        const wrapper = mount(Detalle, { props });
        const select = wrapper.find('[data-testid="receta-insumo-select"]');

        // Act
        await select.setValue('21');

        // Assert
        expect(wrapper.find('[data-testid="receta-cantidad-label"]').text()).toContain('Litro');
    });
});
