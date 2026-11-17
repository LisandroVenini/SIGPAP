import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { enableAutoUnmount, mount } from '@vue/test-utils';

enableAutoUnmount(afterEach);

const hoisted = vi.hoisted(() => {
    const post = vi.fn();
    const put = vi.fn();
    const reset = vi.fn();
    const clearErrors = vi.fn();
    const routerPost = vi.fn();
    const routerGet = vi.fn();

    const page = {
        props: {
            flash: { success: null as string | null },
            errors: {} as Record<string, string>,
        },
    };

    return { post, put, reset, clearErrors, routerPost, routerGet, page };
});

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div />' },
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
    useForm: (initial: Record<string, unknown>) => {
        const form = {
            ...initial,
            errors: {},
            processing: false,
            post: hoisted.post,
            put: hoisted.put,
            reset: hoisted.reset,
            clearErrors: hoisted.clearErrors,
        };

        return form;
    },
    usePage: () => hoisted.page,
    router: { post: hoisted.routerPost, get: hoisted.routerGet },
}));

vi.mock('../../../Layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /></div>' },
}));

vi.mock('@/composables/useConfirm', () => ({
    confirmar: vi.fn(async () => true),
}));

import Solicitudes from './Index.vue';

const articulo = { id: 10, codigo_sku: 'MP-001', descripcion: 'Tabla de pino', unidad: 'Unidad' };
const proveedor = { id: 3, razon_social: 'Aceros del Sur' };

const props = {
    solicitudes: {
        data: [
            {
                id: 1,
                codigo: 'SC-2026-0001',
                fecha: '2026-11-05',
                fecha_vencimiento: '2026-11-12',
                estado: 'Enviada',
                estado_visible: 'Enviada',
                lineas: 2,
                proveedores: 1,
                cotizaciones: 0,
                respuestas: { recibidas: 0, esperadas: 1 },
            },
            {
                id: 2,
                codigo: 'SC-2026-0002',
                fecha: '2026-11-04',
                fecha_vencimiento: '2026-11-10',
                estado: 'Adjudicada',
                estado_visible: 'Adjudicada',
                lineas: 1,
                proveedores: 2,
                cotizaciones: 2,
                respuestas: { recibidas: 2, esperadas: 2 },
            },
            {
                id: 3,
                codigo: 'SC-2026-0003',
                fecha: '2026-11-03',
                fecha_vencimiento: '2026-11-14',
                estado: 'Enviada',
                estado_visible: 'Respondida',
                lineas: 1,
                proveedores: 1,
                cotizaciones: 1,
                respuestas: { recibidas: 1, esperadas: 1 },
            },
        ],
        links: [],
        from: 1,
        to: 3,
        total: 3,
    },
    detalles: {
        '1': {
            id: 1,
            codigo: 'SC-2026-0001',
            fecha: '2026-11-05',
            fecha_vencimiento: '2026-11-12',
            estado: 'Enviada',
            estado_visible: 'Enviada',
            insumos: 'Tabla de pino +1',
            lineas: [
                {
                    articulo_id: 10,
                    articulo: 'Tabla de pino',
                    cantidad_solicitada: 4,
                },
            ],
            proveedores: [
                {
                    id: 3,
                    razon_social: 'Aceros del Sur',
                    enlace_vigente: true,
                    respondio: false,
                },
            ],
            cotizaciones: [],
        },
        '3': {
            id: 3,
            codigo: 'SC-2026-0003',
            fecha: '2026-11-03',
            fecha_vencimiento: '2026-11-14',
            estado: 'Enviada',
            estado_visible: 'Respondida',
            insumos: 'Cola vinílica',
            lineas: [
                {
                    articulo_id: 2,
                    articulo: 'Cola vinílica',
                    cantidad_solicitada: 3,
                },
            ],
            proveedores: [
                {
                    id: 1,
                    razon_social: 'maderera',
                    enlace_vigente: false,
                    respondio: true,
                },
            ],
            cotizaciones: [
                {
                    id: 9,
                    proveedor: 'maderera',
                    fecha: '2026-11-07',
                    total_ofertado: 20,
                    costo_pedido: 30,
                    fecha_prometida: '2026-11-12',
                    lineas: [
                        {
                            articulo: 'Cola vinílica',
                            precio_unitario: 10,
                            cantidad_solicitada: 3,
                            cantidad_ofertada: 2,
                            fecha_prometida: '2026-11-12',
                            subtotal: 20,
                        },
                    ],
                },
            ],
        },
    },
    informes: {
        '3': {
            parametros: {
                pesos: { precio: 0.4, entrega: 0.3, confiabilidad: 0.3 },
                sin_historial: 50,
                penalizar_cobertura: true,
            },
            cotizaciones: [
                {
                    cotizacion_id: 9,
                    proveedor: 'maderera',
                    precio: 30,
                    dias: 1,
                    cobertura: 0.6667,
                    vencida: false,
                    confiabilidad: 50,
                    puntaje: 56.67,
                },
            ],
            recomendada_id: null,
        },
    },
    articulos: [articulo],
    proveedores: [proveedor],
};

describe('Compras SolicitudesDeCotizacion page', () => {
    beforeEach(() => {
        hoisted.post.mockClear();
        hoisted.put.mockClear();
        hoisted.routerPost.mockClear();
        hoisted.routerGet.mockClear();
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('renders the solicitudes with their counts', () => {
        // Arrange / Act
        const wrapper = mount(Solicitudes, { props });

        // Assert
        expect(wrapper.text()).toContain('2026-11-05');
        expect(wrapper.text()).toContain('2026-11-12');
        expect(wrapper.text()).toContain('Enviada');
        expect(wrapper.text()).toContain('2026-11-04');
        expect(wrapper.text()).toContain('Adjudicada');
        expect(wrapper.text()).toContain('Respondida');
        expect(wrapper.find('[data-testid="respuestas-3"]').text()).toBe('1 / 1');
        expect(wrapper.text()).toContain('Mostrando 1–3 de 3');
    });

    it('opens the create drawer and posts a new solicitud', async () => {
        // Arrange
        const wrapper = mount(Solicitudes, { props, attachTo: document.body });

        // Act
        const nueva = wrapper
            .findAll('button')
            .find((button) => button.text() === 'Nueva solicitud');
        await nueva?.trigger('click');
        await wrapper.find('[data-testid="solicitud-form"]').trigger('submit.prevent');

        // Assert
        expect(hoisted.post).toHaveBeenCalledWith(
            '/compras/solicitudes-de-cotizacion',
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('disables the modify action when the solicitud already has quotes', () => {
        // Arrange / Act
        const wrapper = mount(Solicitudes, { props });
        const modificar = wrapper
            .findAll('button')
            .filter((button) => button.text() === 'Modificar');

        // Assert
        expect(modificar).toHaveLength(3);
        expect(modificar[0].attributes('disabled')).toBeUndefined();
        expect(modificar[1].attributes('disabled')).toBeDefined();
        expect(modificar[2].attributes('disabled')).toBeDefined();
    });

    it('annuls a solicitud after confirming', async () => {
        // Arrange
        const wrapper = mount(Solicitudes, { props });

        // Act
        const anular = wrapper.findAll('button').find((button) => button.text() === 'Anular');
        await anular?.trigger('click');

        // Assert
        expect(hoisted.routerPost).toHaveBeenCalledWith(
            '/compras/solicitudes-de-cotizacion/1/anular',
            {},
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('searches by provider, state, and date range', async () => {
        // Arrange
        const wrapper = mount(Solicitudes, { props });

        // Act
        await wrapper.find('[data-testid="filtro-proveedor"]').setValue('Aceros');
        await wrapper.find('[data-testid="filtro-estado"]').setValue('Enviada');
        await wrapper.find('[data-testid="filtro-fecha-desde"]').setValue('2026-11-01');
        await wrapper.find('[data-testid="filtro-fecha-hasta"]').setValue('2026-11-30');
        await wrapper.find('[data-testid="solicitud-filtros"]').trigger('submit.prevent');

        // Assert
        expect(hoisted.routerGet).toHaveBeenCalledWith(
            '/compras/solicitudes-de-cotizacion',
            {
                proveedor: 'Aceros',
                estado: 'Enviada',
                fecha_desde: '2026-11-01',
                fecha_hasta: '2026-11-30',
            },
            expect.objectContaining({ preserveState: true, preserveScroll: true }),
        );
    });

    it('opens the modify drawer and puts the changes', async () => {
        // Arrange
        const wrapper = mount(Solicitudes, { props, attachTo: document.body });

        // Act
        const modificar = wrapper.findAll('button').find((button) => button.text() === 'Modificar');
        await modificar?.trigger('click');
        await wrapper.find('[data-testid="solicitud-form"]').trigger('submit.prevent');

        // Assert
        expect(hoisted.put).toHaveBeenCalledWith(
            '/compras/solicitudes-de-cotizacion/1',
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('clears the filters through the router', async () => {
        // Arrange
        const wrapper = mount(Solicitudes, { props, attachTo: document.body });
        await wrapper.find('[data-testid="filtro-estado"]').setValue('Enviada');

        // Act
        const limpiar = wrapper.findAll('button').find((button) => button.text() === 'Limpiar');
        await limpiar?.trigger('click');

        // Assert
        expect(hoisted.routerGet).toHaveBeenCalledWith(
            '/compras/solicitudes-de-cotizacion',
            {
                proveedor: undefined,
                estado: undefined,
                fecha_desde: undefined,
                fecha_hasta: undefined,
            },
            expect.objectContaining({ preserveState: true }),
        );
    });

    it('shows the search input fields', () => {
        // Arrange / Act
        const wrapper = mount(Solicitudes, { props });

        // Assert
        expect(wrapper.find('[data-testid="filtro-proveedor"]').exists()).toBe(true);
        expect(wrapper.find('[data-testid="filtro-estado"]').exists()).toBe(true);
        expect(wrapper.find('[data-testid="filtro-fecha-desde"]').exists()).toBe(true);
        expect(wrapper.find('[data-testid="filtro-fecha-hasta"]').exists()).toBe(true);
    });

    it('opens the detail drawer with the received quote lines', async () => {
        // Arrange
        const wrapper = mount(Solicitudes, { props, attachTo: document.body });

        // Act
        await wrapper.find('[data-testid="detalle-3"]').trigger('click');

        // Assert
        const drawer = wrapper.find('[data-testid="detalle-drawer"]');

        expect(drawer.exists()).toBe(true);
        expect(drawer.text()).toContain('Cola vinílica');
        expect(drawer.text()).toContain('maderera');
        expect(drawer.text()).toContain('3');
        expect(drawer.text()).toContain('2');
        expect(drawer.find('[data-testid="detalle-informe"]').exists()).toBe(true);
        expect(drawer.text()).toContain('Adjudicar y emitir OC');
    });

    it('adjudicates a received quote from the detail drawer', async () => {
        // Arrange
        const wrapper = mount(Solicitudes, { props, attachTo: document.body });
        await wrapper.find('[data-testid="detalle-3"]').trigger('click');

        // Act
        await wrapper.find('[data-testid="adjudicar-9"]').trigger('click');

        // Assert
        expect(hoisted.routerPost).toHaveBeenCalledWith(
            '/compras/ordenes-de-compra',
            { cotizacion_id: 9 },
            expect.objectContaining({ preserveScroll: true }),
        );
    });
});
