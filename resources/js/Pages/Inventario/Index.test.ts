import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { enableAutoUnmount, mount } from '@vue/test-utils';

enableAutoUnmount(afterEach);

const hoisted = vi.hoisted(() => {
    const post = vi.fn();
    const put = vi.fn();
    const patch = vi.fn();
    const reset = vi.fn();
    const clearErrors = vi.fn();
    const routerGet = vi.fn();
    const routerPatch = vi.fn();

    const page = {
        props: {
            flash: { success: null as string | null },
        },
    };

    return { post, put, patch, reset, clearErrors, routerGet, routerPatch, page };
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
        patch: hoisted.patch,
        reset: hoisted.reset,
        clearErrors: hoisted.clearErrors,
    }),
    usePage: () => hoisted.page,
    router: { get: hoisted.routerGet, patch: hoisted.routerPatch },
}));

vi.mock('../../Layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /></div>' },
}));

import Index from './Index.vue';

const props = {
    articulos: {
        data: [
            {
                id: 1,
                codigo_sku: 'MP-001',
                descripcion: 'Madera de pino',
                tipo: 'Materia Prima',
                origen: null,
                estado: 'Activo',
                unidad_de_medida_id: 1,
                unidad: 'Metro',
                stock_fisico: 5,
                stock_comprometido: 2,
                stock_disponible: 3,
                stock_minimo: 10,
                stock_maximo: 100,
            },
            {
                id: 2,
                codigo_sku: 'IN-001',
                descripcion: 'Barniz',
                tipo: 'Insumo Consumible',
                origen: null,
                estado: 'Activo',
                unidad_de_medida_id: 2,
                unidad: 'Litro',
                stock_fisico: 0,
                stock_comprometido: 0,
                stock_disponible: 0,
                stock_minimo: 5,
                stock_maximo: 50,
            },
        ],
        links: [],
        from: 1,
        to: 2,
        total: 2,
    },
    resumen: { total: 2, activos: 2, inactivos: 0 },
    tipos: ['Materia Prima', 'Insumo Consumible', 'Producto Terminado'],
    origenes: ['Fabricado', 'Tercero'],
    unidades: [
        { id: 1, nombre: 'Metro' },
        { id: 2, nombre: 'Litro' },
    ],
    filtros: {},
};

describe('Inventario Index page', () => {
    beforeEach(() => {
        hoisted.post.mockClear();
        hoisted.put.mockClear();
        hoisted.patch.mockClear();
        hoisted.routerGet.mockClear();
        hoisted.routerPatch.mockClear();
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('renders the articles with stock columns', () => {
        // Arrange / Act
        const wrapper = mount(Index, { props });

        // Assert
        expect(wrapper.text()).toContain('Madera de pino');
        expect(wrapper.text()).toContain('MP-001');
        expect(wrapper.text()).toContain('2 artículos');
    });

    it('opens the create drawer and posts a new articulo', async () => {
        // Arrange
        const wrapper = mount(Index, { props, attachTo: document.body });

        // Act
        const nuevo = wrapper
            .findAll('button')
            .find((button) => button.text().includes('Nuevo artículo'));
        await nuevo?.trigger('click');
        await wrapper.find('[data-testid="drawer-form"]').trigger('submit.prevent');

        // Assert
        expect(hoisted.post).toHaveBeenCalledWith(
            '/inventario/articulos',
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
            '/inventario/articulos/1',
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('confirms a baja when the article has stock and patches the state', async () => {
        // Arrange
        vi.stubGlobal(
            'confirm',
            vi.fn(() => true),
        );
        const wrapper = mount(Index, { props });

        // Act
        const baja = wrapper.findAll('button').find((button) => button.text() === 'Dar de baja');
        await baja?.trigger('click');

        // Assert
        expect(hoisted.routerPatch).toHaveBeenCalledWith(
            '/inventario/articulos/1/estado',
            { confirmar: true },
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
            '/inventario/articulos',
            expect.any(Object),
            expect.any(Object),
        );
    });
});
