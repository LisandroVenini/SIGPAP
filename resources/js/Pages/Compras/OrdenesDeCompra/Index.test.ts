import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { enableAutoUnmount, mount } from '@vue/test-utils';
import { reactive } from 'vue';

enableAutoUnmount(afterEach);

const hoisted = vi.hoisted(() => {
    const post = vi.fn();
    const put = vi.fn();
    const routerPost = vi.fn();
    const routerGet = vi.fn();
    const clearErrors = vi.fn();

    const page = {
        props: {
            flash: { success: null as string | null },
            errors: {} as Record<string, string>,
        },
    };

    return { post, put, routerPost, routerGet, clearErrors, page };
});

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div />' },
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
    useForm: (initial: Record<string, unknown>) => {
        const form: Record<string, unknown> = reactive({
            ...initial,
            errors: {},
            processing: false,
        });

        form.clearErrors = hoisted.clearErrors;
        form.post = (url: string, options?: unknown) => {
            hoisted.post(url, options, JSON.parse(JSON.stringify(form)));
        };
        form.put = (url: string, options?: unknown) => {
            hoisted.put(url, options, JSON.parse(JSON.stringify(form)));
        };

        return form;
    },
    usePage: () => hoisted.page,
    router: { post: hoisted.routerPost, get: hoisted.routerGet },
}));

vi.mock('../../../Layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /></div>' },
}));

import Ordenes from './Index.vue';

const orden = {
    id: 1,
    numero_oc: 'OC-2026-0001',
    proveedor: 'Aceros del Sur',
    estado: 'Emitida',
    fecha_emision: '2026-11-01',
    lineas: 2,
    total: 964,
};

const confirmada = {
    id: 2,
    numero_oc: 'OC-2026-0002',
    proveedor: 'Maderas Norte',
    estado: 'Confirmada',
    fecha_emision: '2026-11-02',
    lineas: 1,
    total: 100,
};

const props = {
    ordenes: {
        data: [orden, confirmada],
        links: [],
        from: 1,
        to: 2,
        total: 2,
    },
    solicitudesConCotizaciones: [
        {
            id: 9,
            fecha: '2026-10-20',
            fecha_vencimiento: '2026-11-20',
            cotizaciones: [
                {
                    id: 500,
                    proveedor: 'Aceros del Sur',
                    total: 964,
                    ultima_fecha_prometida: '2026-11-15',
                },
            ],
        },
    ],
    articulos: [{ id: 10, codigo_sku: 'MP-001', descripcion: 'Tabla de pino', unidad: 'Unidad' }],
};

describe('Compras OrdenesDeCompra page', () => {
    beforeEach(() => {
        hoisted.post.mockClear();
        hoisted.put.mockClear();
        hoisted.routerPost.mockClear();
        hoisted.routerGet.mockClear();
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('renders the ordenes with their totals', () => {
        // Arrange / Act
        const wrapper = mount(Ordenes, { props });

        // Assert
        expect(wrapper.text()).toContain('OC-2026-0001');
        expect(wrapper.text()).toContain('Aceros del Sur');
        expect(wrapper.text()).toContain('Emitida');
        expect(wrapper.text()).toContain('964');
        expect(wrapper.text()).toContain('OC-2026-0002');
        expect(wrapper.text()).toContain('Confirmada');
        expect(wrapper.text()).toContain('Mostrando 1–2 de 2');
    });

    it('opens the emit drawer and posts the selected quote', async () => {
        // Arrange
        const wrapper = mount(Ordenes, { props, attachTo: document.body });

        // Act
        const emitir = wrapper.findAll('button').find((button) => button.text() === 'Emitir OC');
        await emitir?.trigger('click');
        await wrapper.find('[data-testid="cotizacion-500"]').setValue();
        await wrapper.find('[data-testid="emitir-form"]').trigger('submit.prevent');

        // Assert
        expect(wrapper.text()).toContain('Aceros del Sur');
        expect(wrapper.text()).toContain('2026-11-15');
        expect(hoisted.post).toHaveBeenCalledWith(
            '/compras/ordenes-de-compra',
            expect.objectContaining({ preserveScroll: true }),
            expect.objectContaining({ cotizacion_id: 500 }),
        );
    });

    it('disables the modify action when the orden is not emitted', () => {
        // Arrange / Act
        const wrapper = mount(Ordenes, { props });
        const modificar = wrapper
            .findAll('button')
            .filter((button) => button.text() === 'Modificar');

        // Assert
        expect(modificar).toHaveLength(2);
        expect(modificar[0].attributes('disabled')).toBeUndefined();
        expect(modificar[1].attributes('disabled')).toBeDefined();
    });

    it('annuls an orden after confirming', async () => {
        // Arrange
        vi.stubGlobal(
            'confirm',
            vi.fn(() => true),
        );
        const wrapper = mount(Ordenes, { props });

        // Act
        const anular = wrapper.findAll('button').find((button) => button.text() === 'Anular');
        await anular?.trigger('click');

        // Assert
        expect(hoisted.routerPost).toHaveBeenCalledWith(
            '/compras/ordenes-de-compra/1/anular',
            {},
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('searches by number, state, provider, and date range', async () => {
        // Arrange
        const wrapper = mount(Ordenes, { props });

        // Act
        await wrapper.find('[data-testid="filtro-numero"]').setValue('0001');
        await wrapper.find('[data-testid="filtro-estado"]').setValue('Emitida');
        await wrapper.find('[data-testid="filtro-proveedor"]').setValue('Aceros');
        await wrapper.find('[data-testid="filtro-fecha-desde"]').setValue('2026-11-01');
        await wrapper.find('[data-testid="filtro-fecha-hasta"]').setValue('2026-11-30');
        await wrapper.find('[data-testid="ordenes-filtros"]').trigger('submit.prevent');

        // Assert
        expect(hoisted.routerGet).toHaveBeenCalledWith(
            '/compras/ordenes-de-compra',
            {
                numero_oc: '0001',
                estado: 'Emitida',
                proveedor: 'Aceros',
                fecha_desde: '2026-11-01',
                fecha_hasta: '2026-11-30',
            },
            expect.objectContaining({ preserveState: true, preserveScroll: true }),
        );
    });
});
