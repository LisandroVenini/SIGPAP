import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { enableAutoUnmount, mount } from '@vue/test-utils';
import { reactive } from 'vue';

enableAutoUnmount(afterEach);

const hoisted = vi.hoisted(() => {
    const post = vi.fn();
    const reset = vi.fn();
    const clearErrors = vi.fn();
    const routerDelete = vi.fn();
    const routerPut = vi.fn();

    const page = {
        props: {
            flash: { success: null as string | null },
            errors: {} as Record<string, string>,
        },
    };

    return { post, reset, clearErrors, routerDelete, routerPut, page };
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
    router: { delete: hoisted.routerDelete, put: hoisted.routerPut },
}));

vi.mock('../../../Layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /></div>' },
}));

import Detalle from './Detalle.vue';

const props = {
    producto: { id: 1, codigo_sku: 'PT-001', descripcion: 'Silla de pino', unidad: 'Unidad' },
    asignadas: [{ etapa_id: 10, etapa: 'Lijado', tiempo_por_unidad: 5, orden: 1 }],
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
        hoisted.routerPut.mockClear();
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

    it('renders the order of each assigned etapa', () => {
        // Arrange / Act
        const wrapper = mount(Detalle, { props });

        // Assert
        const ordenes = wrapper.findAll('[data-testid="orden-etapa"]').map((celda) => celda.text());

        expect(ordenes).toEqual(['1']);
    });

    it('moves an etapa up when the up button is clicked', async () => {
        // Arrange
        const wrapper = mount(Detalle, {
            props: {
                ...props,
                asignadas: [
                    { etapa_id: 10, etapa: 'Lijado', tiempo_por_unidad: 5, orden: 1 },
                    { etapa_id: 11, etapa: 'Pintado', tiempo_por_unidad: 8, orden: 2 },
                ],
            },
        });
        const subir = wrapper
            .findAll('button')
            .find(
                (button) =>
                    button.text() === 'Subir' && button.attributes('disabled') === undefined,
            );

        expect(subir).toBeDefined();

        // Act
        await subir!.trigger('click');

        // Assert
        expect(hoisted.routerPut).toHaveBeenCalledWith(
            '/produccion/hoja-de-ruta/1/etapas/11/mover',
            { etapa_id: 11, direccion: 'subir' },
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('disables the up button on the first etapa', () => {
        // Arrange / Act
        const wrapper = mount(Detalle, { props });
        const subir = wrapper.findAll('button').find((button) => button.text() === 'Subir');

        // Assert
        expect(subir!.attributes('disabled')).toBeDefined();
    });

    it('disables the down button on the last etapa', () => {
        // Arrange / Act
        const wrapper = mount(Detalle, { props });
        const bajar = wrapper.findAll('button').find((button) => button.text() === 'Bajar');

        // Assert
        expect(bajar!.attributes('disabled')).toBeDefined();
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
