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

import Mantenimiento from './Index.vue';

const props = {
    planes: {
        data: [
            {
                id: 1,
                maquinaria_id: 10,
                maquinaria: 'Lijadora orbital',
                umbral_uso: 1200,
                descripcion: 'Cambio de rodillos',
                maquina_bloqueada: false,
            },
        ],
        links: [],
        from: 1,
        to: 1,
        total: 1,
    },
    intervenciones: [
        {
            id: 1,
            maquinaria_id: 10,
            maquinaria: 'Lijadora orbital',
            fecha: '2026-11-01',
            tipo: 'Preventivo',
            responsable: 'Taller interno',
            observaciones: null,
        },
    ],
    maquinarias: [{ id: 10, nombre: 'Lijadora orbital' }],
    filtros: {},
};

describe('Produccion Mantenimiento page', () => {
    beforeEach(() => {
        hoisted.post.mockClear();
        hoisted.put.mockClear();
        hoisted.routerGet.mockClear();
        hoisted.routerDelete.mockClear();
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('renders the maintenance plans', () => {
        // Arrange / Act
        const wrapper = mount(Mantenimiento, { props });

        // Assert
        expect(wrapper.text()).toContain('Lijadora orbital');
        expect(wrapper.text()).toContain('1200');
        expect(wrapper.text()).toContain('Cambio de rodillos');
    });

    it('renders the interventions', () => {
        // Arrange / Act
        const wrapper = mount(Mantenimiento, { props });

        // Assert
        expect(wrapper.text()).toContain('Preventivo');
        expect(wrapper.text()).toContain('Taller interno');
    });

    it('opens the plan create drawer and posts a new plan', async () => {
        // Arrange
        const wrapper = mount(Mantenimiento, { props, attachTo: document.body });

        // Act
        const nuevo = wrapper.findAll('button').find((button) => button.text() === 'Nuevo plan');
        await nuevo?.trigger('click');
        await wrapper.find('[data-testid="plan-form"]').trigger('submit.prevent');

        // Assert
        expect(hoisted.post).toHaveBeenCalledWith(
            '/produccion/planes-de-mantenimiento',
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('opens the plan edit drawer and puts the changes', async () => {
        // Arrange
        const wrapper = mount(Mantenimiento, { props, attachTo: document.body });

        // Act
        const editar = wrapper.findAll('button').find((button) => button.text() === 'Editar plan');
        await editar?.trigger('click');
        await wrapper.find('[data-testid="plan-form"]').trigger('submit.prevent');

        // Assert
        expect(hoisted.put).toHaveBeenCalledWith(
            '/produccion/planes-de-mantenimiento/1',
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('opens the intervention drawer and posts the intervention', async () => {
        // Arrange
        const wrapper = mount(Mantenimiento, { props, attachTo: document.body });

        // Act
        const registrar = wrapper
            .findAll('button')
            .find((button) => button.text() === 'Registrar mantenimiento');
        await registrar?.trigger('click');
        await wrapper.find('[data-testid="intervencion-form"]').trigger('submit.prevent');

        // Assert
        expect(hoisted.post).toHaveBeenCalledWith(
            '/produccion/maquinarias/10/mantenimientos',
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('applies the search through the router', async () => {
        // Arrange
        const wrapper = mount(Mantenimiento, { props });

        // Act
        await wrapper.find('form').trigger('submit.prevent');

        // Assert
        expect(hoisted.routerGet).toHaveBeenCalledWith(
            '/produccion/planes-de-mantenimiento',
            expect.any(Object),
            expect.any(Object),
        );
    });
});
