import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { enableAutoUnmount, mount } from '@vue/test-utils';
import { reactive } from 'vue';

enableAutoUnmount(afterEach);

const hoisted = vi.hoisted(() => {
    const post = vi.fn();
    const put = vi.fn();
    const patch = vi.fn();
    const reset = vi.fn();
    const clearErrors = vi.fn();
    const routerGet = vi.fn();
    const routerPost = vi.fn();

    const page = {
        props: {
            flash: { success: null as string | null },
            errors: {} as Record<string, string>,
        },
    };

    return { post, put, patch, reset, clearErrors, routerGet, routerPost, page };
});

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div />' },
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
    useForm: (initial: Record<string, unknown>) => {
        const form: Record<string, unknown> = {
            ...initial,
            errors: {},
            processing: false,
            reset: hoisted.reset,
            clearErrors: hoisted.clearErrors,
        };

        const capture = (spy: typeof hoisted.post) => (url: string, options?: unknown) => {
            spy(url, options, JSON.parse(JSON.stringify(form)));
        };

        form.post = capture(hoisted.post);
        form.put = capture(hoisted.put);
        form.patch = capture(hoisted.patch);

        return reactive(form);
    },
    usePage: () => hoisted.page,
    router: { get: hoisted.routerGet, post: hoisted.routerPost },
}));

vi.mock('../../../Layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /></div>' },
}));

import Ordenes from './Index.vue';

const planificacion = { id: 1, nombre: 'Semana 12' };
const productoA = { id: 10, codigo_sku: 'PT-A', descripcion: 'Silla' };
const productoB = { id: 11, codigo_sku: 'PT-B', descripcion: 'Mesa' };
const operarioA = { id: 1, nombre: 'García, Ana' };
const operarioB = { id: 2, nombre: 'Fernández, Luis' };
const maquinariaDisponible = {
    id: 1,
    nombre: 'Lijadora orbital',
    estado: 'Disponible',
    seleccionable: true,
};
const maquinariaMantenimiento = {
    id: 2,
    nombre: 'Cepilladora',
    estado: 'En Mantenimiento',
    seleccionable: false,
};

const props = {
    ordenes: {
        data: [
            {
                id: 1,
                producto: { id: 10, codigo_sku: 'PT-A', descripcion: 'Silla' },
                planificacion: 'Semana 12',
                estado: 'Pendiente',
                cantidad_requerida: 10,
                cantidad_producida: 0,
                fecha_entrega: '2026-11-15',
                prioridad_actual: 1,
                operarios: ['García, Ana'],
                operario_ids: [1],
                maquinarias: ['Lijadora orbital'],
                maquinaria_ids: [1],
                etapas: ['Corte', 'Lijado'],
            },
        ],
        links: [],
        from: 1,
        to: 1,
        total: 1,
    },
    planificaciones: [planificacion],
    productos: [productoA, productoB],
    estados: ['Pendiente', 'Pendiente por Insumos', 'Asignada', 'En Curso', 'Cerrada', 'Anulada'],
    filtros: {},
    operariosDisponibles: [operarioA, operarioB],
    maquinariasDisponibles: [maquinariaDisponible, maquinariaMantenimiento],
};

describe('Produccion OrdenesDeProduccion page', () => {
    beforeEach(() => {
        hoisted.post.mockClear();
        hoisted.put.mockClear();
        hoisted.patch.mockClear();
        hoisted.routerGet.mockClear();
        hoisted.routerPost.mockClear();
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('renders the ordenes', () => {
        // Arrange / Act
        const wrapper = mount(Ordenes, { props });

        // Assert
        expect(wrapper.text()).toContain('Silla');
        expect(wrapper.text()).toContain('PT-A');
        expect(wrapper.text()).toContain('Pendiente');
    });

    it('renders assigned resources', () => {
        // Arrange / Act
        const wrapper = mount(Ordenes, { props });

        // Assert
        expect(wrapper.text()).toContain('García, Ana');
        expect(wrapper.text()).toContain('Lijadora orbital');
    });

    it('renders empty state when there are no orders', () => {
        // Arrange / Act
        const wrapper = mount(Ordenes, {
            props: { ...props, ordenes: { ...props.ordenes, data: [], total: 0 } },
        });

        // Assert
        expect(wrapper.text()).toContain('No hay órdenes de producción registradas.');
    });

    it('posts a batch order with both lines and the chosen mode', async () => {
        // Arrange
        const wrapper = mount(Ordenes, { props, attachTo: document.body });

        // Act
        const nuevo = wrapper.findAll('button').find((button) => button.text() === 'Nueva orden');
        await nuevo?.trigger('click');

        const form = wrapper.find('[data-testid="nueva-orden-form"]');
        await form.find('#fecha_entrega').setValue('2026-11-20');

        const selects = form.findAll('select');
        await selects[1].setValue(String(productoA.id));
        await form.find('input[type="number"]').setValue('5');

        const agregar = wrapper
            .findAll('button')
            .find((button) => button.text() === 'Agregar línea');
        await agregar?.trigger('click');

        const selectsConDos = form.findAll('select');
        await selectsConDos[2].setValue(String(productoB.id));
        const cantidades = form.findAll('input[type="number"]');
        await cantidades[1].setValue('3');

        await form.trigger('submit.prevent');

        // Assert
        expect(hoisted.post).toHaveBeenCalledWith(
            '/produccion/ordenes-de-produccion',
            expect.objectContaining({ preserveScroll: true }),
            expect.any(Object),
        );

        const payload = hoisted.post.mock.calls[0][2];
        expect(payload.lineas).toHaveLength(2);
        expect(payload.lineas[0].articulo_id).toBe(productoA.id);
        expect(Number(payload.lineas[0].cantidad)).toBe(5);
        expect(payload.lineas[1].articulo_id).toBe(productoB.id);
        expect(Number(payload.lineas[1].cantidad)).toBe(3);
        expect(payload.modo).toBe('solo_disponibles');
        expect(payload.planificacion_de_produccion_id).toBe(planificacion.id);
        expect(payload.fecha_entrega).toBe('2026-11-20');
    });

    it('preselects the assigned resources when opening the assignment drawer', async () => {
        // Arrange
        const wrapper = mount(Ordenes, { props, attachTo: document.body });

        // Act
        const asignar = wrapper
            .findAll('button')
            .find((button) => button.text() === 'Asignar recursos');
        await asignar?.trigger('click');

        // Assert
        const marcados = wrapper
            .findAll('input[type="checkbox"]')
            .filter((checkbox) => (checkbox.element as HTMLInputElement).checked);

        expect(marcados).toHaveLength(2);
    });

    it('opens the edit drawer and puts the changes', async () => {
        // Arrange
        const wrapper = mount(Ordenes, { props, attachTo: document.body });

        // Act
        const modificar = wrapper.findAll('button').find((button) => button.text() === 'Modificar');
        await modificar?.trigger('click');

        const form = wrapper.find('[data-testid="modificar-orden-form"]');
        await form.find('input[type="number"]').setValue('25');
        await form.find('input[type="date"]').setValue('2026-11-25');
        await form.trigger('submit.prevent');

        // Assert
        const payload = hoisted.put.mock.calls[0][2];
        expect(Number(payload.cantidad)).toBe(25);
        expect(payload.fecha_entrega).toBe('2026-11-25');
        expect(hoisted.put).toHaveBeenCalledWith(
            '/produccion/ordenes-de-produccion/1',
            expect.objectContaining({ preserveScroll: true }),
            expect.any(Object),
        );
    });

    it('opens the anular drawer and posts with motivo', async () => {
        // Arrange
        const wrapper = mount(Ordenes, { props, attachTo: document.body });

        // Act
        const anular = wrapper.findAll('button').find((button) => button.text() === 'Anular');
        await anular?.trigger('click');
        await wrapper.find('[data-testid="anular-orden-form"]').trigger('submit.prevent');

        // Assert
        expect(hoisted.post).toHaveBeenCalledWith(
            '/produccion/ordenes-de-produccion/1/anular',
            expect.objectContaining({ preserveScroll: true }),
            expect.any(Object),
        );
    });

    it('applies the filters through the router', async () => {
        // Arrange
        const wrapper = mount(Ordenes, { props });

        // Act
        await wrapper.find('[data-testid="filtros-form"]').trigger('submit.prevent');

        // Assert
        expect(hoisted.routerGet).toHaveBeenCalledWith(
            '/produccion/ordenes-de-produccion',
            expect.any(Object),
            expect.any(Object),
        );
    });

    it('opens the assignment drawer and puts the payload', async () => {
        // Arrange
        const wrapper = mount(Ordenes, { props, attachTo: document.body });

        // Act
        const asignar = wrapper
            .findAll('button')
            .find((button) => button.text() === 'Asignar recursos');
        await asignar?.trigger('click');

        const form = wrapper.find('[data-testid="asignar-recursos-form"]');
        const checkboxes = form.findAll('input[type="checkbox"]');
        await checkboxes[0].setValue(true);
        await checkboxes[2].setValue(true);
        await form.trigger('submit.prevent');

        // Assert
        const payload = hoisted.put.mock.calls[0][2];
        expect(payload.operarios).toContain(operarioA.id);
        expect(payload.maquinarias).toContain(maquinariaDisponible.id);
        expect(hoisted.put).toHaveBeenCalledWith(
            '/produccion/ordenes-de-produccion/1/recursos',
            expect.objectContaining({ preserveScroll: true }),
            expect.any(Object),
        );
    });

    it('posts iniciar through the router', async () => {
        // Arrange
        const asignadaProps = {
            ...props,
            ordenes: {
                ...props.ordenes,
                data: [{ ...props.ordenes.data[0], estado: 'Asignada' }],
            },
        };
        const wrapper = mount(Ordenes, { props: asignadaProps, attachTo: document.body });

        // Act
        const iniciar = wrapper.findAll('button').find((button) => button.text() === 'Iniciar');
        await iniciar?.trigger('click');

        // Assert
        expect(hoisted.routerPost).toHaveBeenCalledWith(
            '/produccion/ordenes-de-produccion/1/iniciar',
            {},
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('disables unavailable machinery in the assignment drawer', async () => {
        // Arrange
        const wrapper = mount(Ordenes, { props, attachTo: document.body });

        // Act
        const asignar = wrapper
            .findAll('button')
            .find((button) => button.text() === 'Asignar recursos');
        await asignar?.trigger('click');

        const form = wrapper.find('[data-testid="asignar-recursos-form"]');
        const checkboxes = form.findAll('input[type="checkbox"]');

        // Assert
        expect((checkboxes[2].element as HTMLInputElement).disabled).toBe(false);
        expect((checkboxes[3].element as HTMLInputElement).disabled).toBe(true);
    });
});
