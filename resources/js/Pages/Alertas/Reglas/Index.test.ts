import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';

const hoisted = vi.hoisted(() => {
    const get = vi.fn();
    const patch = vi.fn();
    const routerDelete = vi.fn();
    const post = vi.fn();
    const put = vi.fn();
    const reset = vi.fn();
    const clearErrors = vi.fn();

    const form = {
        nombre: '',
        descripcion: '',
        tipo: '',
        severidad: 'Atencion',
        mensaje: '',
        activa: true,
        roles: [] as number[],
        condiciones: {} as Record<string, number>,
        errors: {} as Record<string, string>,
        processing: false,
        post,
        put,
        reset,
        clearErrors,
    };

    return { get, patch, routerDelete, post, put, reset, clearErrors, form };
});

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div />' },
    router: {
        get: hoisted.get,
        patch: hoisted.patch,
        delete: hoisted.routerDelete,
    },
    useForm: () => hoisted.form,
    usePage: () => ({ props: { flash: { success: null } } }),
}));

vi.mock('../../../Layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /></div>' },
}));

vi.mock('@/composables/useConfirm', () => ({
    confirmar: vi.fn(async () => false),
}));

import Index from './Index.vue';

const props = {
    reglas: {
        data: [
            {
                id: 1,
                nombre: 'Stock bajo',
                descripcion: null,
                tipo: 'stock_bajo_minimo',
                modulo: 'Inventario',
                severidad: 'Atencion',
                condiciones: {},
                mensaje: '{{articulo}} bajo mínimo.',
                activa: true,
                roles: [{ id: 3, name: 'Encargado de Stock' }],
            },
        ],
        links: [],
        from: 1,
        to: 1,
        total: 1,
    },
    filtros: {},
    tipos: [
        {
            clave: 'solicitud_por_vencer',
            etiqueta: 'Solicitud por vencer',
            descripcion: 'Una solicitud enviada vence pronto.',
            modulo: 'Compras',
            origen: 'condicion',
            severidad_por_defecto: 'Atencion',
            roles_por_defecto: ['Encargado de Compras'],
            condiciones: [
                {
                    clave: 'dias',
                    etiqueta: 'Días antes del vencimiento',
                    tipo: 'integer',
                    operador: 'lte',
                    variable: 'dias',
                    minimo: 1,
                    maximo: 30,
                    por_defecto: 3,
                },
            ],
            variables: ['numero', 'dias'],
        },
    ],
    roles: [
        { id: 4, name: 'Encargado de Compras' },
        { id: 3, name: 'Encargado de Stock' },
    ],
    modulos: ['Inventario', 'Compras', 'Produccion'],
    severidades: [{ valor: 'Atencion', etiqueta: 'Atención' }],
    puedeGestionar: true,
};

describe('Alertas Reglas page', () => {
    beforeEach(() => {
        hoisted.post.mockClear();
        hoisted.put.mockClear();
        hoisted.form.reset();
        hoisted.form.roles = [];
        hoisted.form.condiciones = {};
    });

    it('renders the configured rules', () => {
        // Act
        const wrapper = mount(Index, { props });

        // Assert
        expect(wrapper.text()).toContain('Stock bajo');
        expect(wrapper.text()).toContain('Encargado de Stock');
    });

    it('opens the drawer and loads the defaults of the chosen type', async () => {
        // Arrange
        const wrapper = mount(Index, { props, attachTo: document.body });

        // Act
        await wrapper
            .findAll('button')
            .find((boton) => boton.text() === 'Nueva regla')
            ?.trigger('click');
        await wrapper.find('#tipo').setValue('solicitud_por_vencer');

        // Assert
        expect(wrapper.text()).toContain('Nueva regla');
        expect(hoisted.form.condiciones.dias).toBe(3);
        expect(hoisted.form.roles).toEqual([4]);
    });

    it('submits a new rule through the form', async () => {
        // Arrange
        const wrapper = mount(Index, { props, attachTo: document.body });

        // Act
        await wrapper
            .findAll('button')
            .find((boton) => boton.text() === 'Nueva regla')
            ?.trigger('click');
        await wrapper.find('#tipo').setValue('solicitud_por_vencer');
        await wrapper.find('#nombre').setValue('Solicitud por vencer');
        await wrapper.find('[data-testid="regla-form"]').trigger('submit.prevent');

        // Assert
        expect(hoisted.post).toHaveBeenCalledWith('/alertas/reglas', expect.any(Object));
    });
});
