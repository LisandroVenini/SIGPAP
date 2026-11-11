import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { enableAutoUnmount, mount } from '@vue/test-utils';
import { nextTick, reactive } from 'vue';

enableAutoUnmount(afterEach);

const hoisted = vi.hoisted(() => {
    const post = vi.fn();
    const reset = vi.fn();
    const clearErrors = vi.fn();

    const page = {
        props: {
            flash: { success: null as string | null },
            errors: {} as Record<string, string>,
        },
    };

    return { post, reset, clearErrors, page };
});

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div />' },
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
    useForm: (initial: Record<string, unknown>) => {
        const form = reactive<Record<string, unknown>>({
            ...initial,
            errors: {},
            processing: false,
            clearErrors: hoisted.clearErrors,
        });

        form.post = (url: string, options?: unknown) => {
            hoisted.post(url, options, JSON.parse(JSON.stringify(form)));
        };

        form.reset = (...campos: string[]) => {
            hoisted.reset(...campos);

            const objetivos = campos.length > 0 ? campos : Object.keys(initial);

            for (const campo of objetivos) {
                form[campo] = initial[campo];
            }
        };

        return form;
    },
    usePage: () => hoisted.page,
}));

vi.mock('../../../Layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /></div>' },
}));

import ParteDiario from './Index.vue';

const productoA = { id: 10, codigo_sku: 'PT-A', descripcion: 'Silla', unidad: 'u' };
const productoB = { id: 11, codigo_sku: 'PT-B', descripcion: 'Mesa', unidad: null };

const ordenA = {
    id: 1,
    producto: productoA,
    cantidad_requerida: 10,
    etapas: [
        { etapa_id: 10, nombre: 'Corte', producida: 4, rechazadas: 0, faltante: 6 },
        { etapa_id: 11, nombre: 'Lijado', producida: 0, rechazadas: 0, faltante: 10 },
    ],
    maquinarias: [{ id: 1, nombre: 'Lijadora orbital' }],
    partes_hoy: [
        {
            id: 100,
            etapa_id: 10,
            etapa: 'Corte',
            fecha: '2026-11-11',
            cantidad: 4,
            maquinaria: null,
            observaciones: 'Turno mañana',
        },
    ],
};

const ordenB = {
    id: 2,
    producto: productoB,
    cantidad_requerida: 5,
    etapas: [
        { etapa_id: 20, nombre: 'Armado', producida: 5, rechazadas: 0, faltante: 0 },
        { etapa_id: 21, nombre: 'Pintura', producida: 1, rechazadas: 0, faltante: 4 },
    ],
    maquinarias: [{ id: 2, nombre: 'Compresor' }],
    partes_hoy: [],
};

const props = {
    ordenes: [ordenA, ordenB],
    ordenSeleccionadaId: null,
    fechaHoy: '2026-11-11',
};

describe('Produccion ParteDiario page', () => {
    beforeEach(() => {
        hoisted.post.mockClear();
        hoisted.reset.mockClear();
        hoisted.clearErrors.mockClear();
        hoisted.page.props.flash.success = null;
        hoisted.page.props.errors = {};
    });

    it('renders the in-progress orders in the selector and the selected stages', () => {
        // Arrange / Act
        const wrapper = mount(ParteDiario, { props });

        // Assert
        const opciones = wrapper.findAll('[data-testid="orden-select"] option');

        expect(opciones).toHaveLength(2);
        expect(opciones[0].text()).toContain('Silla');
        expect(opciones[0].text()).toContain('PT-A');
        expect(opciones[1].text()).toContain('Mesa');
        expect(opciones[1].text()).toContain('PT-B');

        const etapas = wrapper.find('[data-testid="etapas-table"]');

        expect(etapas.text()).toContain('Corte');
        expect(etapas.text()).toContain('Lijado');
        expect(etapas.text()).toContain('En curso');
        expect(etapas.text()).toContain('Sin iniciar');

        const filas = etapas.findAll('tbody tr');

        expect(filas[0].text()).toContain('4');
        expect(filas[0].text()).toContain('6');
    });

    it('swaps the stage table and resets the form when the selected order changes', async () => {
        // Arrange
        const wrapper = mount(ParteDiario, { props });
        await wrapper.find('#cantidad').setValue('3');

        // Act
        await wrapper.find('[data-testid="orden-select"]').setValue('2');

        // Assert
        const etapas = wrapper.find('[data-testid="etapas-table"]');

        expect(etapas.text()).toContain('Armado');
        expect(etapas.text()).toContain('Pintura');
        expect(etapas.text()).not.toContain('Corte');

        expect((wrapper.find('#etapa_id').element as HTMLSelectElement).value).toBe('21');
        expect((wrapper.find('#cantidad').element as HTMLInputElement).value).toBe('');
    });

    it('posts the part to the registration endpoint with the expected payload', async () => {
        // Arrange
        const wrapper = mount(ParteDiario, { props });
        const form = wrapper.find('[data-testid="parte-form"]');

        // Act
        await form.find('#etapa_id').setValue('11');
        await form.find('#maquinaria_id').setValue('1');
        await form.find('#fecha').setValue('2026-11-10');
        await form.find('#cantidad').setValue('2.5');
        await form.find('#observaciones').setValue('Parte de prueba');
        await form.trigger('submit.prevent');

        // Assert
        expect(hoisted.post).toHaveBeenCalledWith(
            '/produccion/ordenes-de-produccion/1/partes',
            expect.objectContaining({ preserveScroll: true }),
            expect.any(Object),
        );

        const payload = hoisted.post.mock.calls[0][2];

        expect(Number(payload.etapa_id)).toBe(11);
        expect(Number(payload.maquinaria_id)).toBe(1);
        expect(payload.fecha).toBe('2026-11-10');
        expect(Number(payload.cantidad)).toBe(2.5);
        expect(payload.observaciones).toBe('Parte de prueba');
    });

    it('resets cantidad and observaciones on success', async () => {
        // Arrange
        const wrapper = mount(ParteDiario, { props });
        const form = wrapper.find('[data-testid="parte-form"]');
        await form.find('#cantidad').setValue('2');
        await form.find('#observaciones').setValue('Nota');
        await form.trigger('submit.prevent');

        // Act
        const options = hoisted.post.mock.calls[0][1] as { onSuccess?: () => void };
        options.onSuccess?.();
        await nextTick();

        // Assert
        expect(hoisted.reset).toHaveBeenCalledWith('cantidad', 'observaciones');
        expect((wrapper.find('#cantidad').element as HTMLInputElement).value).toBe('');
        expect((wrapper.find('#observaciones').element as HTMLTextAreaElement).value).toBe('');
    });

    it("renders today's registrations and the empty states", () => {
        // Arrange / Act
        const wrapper = mount(ParteDiario, { props });

        // Assert
        const partes = wrapper.find('[data-testid="partes-hoy-table"]');

        expect(partes.text()).toContain('Corte');
        expect(partes.text()).toContain('Turno mañana');
        expect(partes.text()).toContain('4');

        const sinPartes = mount(ParteDiario, {
            props: { ...props, ordenSeleccionadaId: ordenB.id },
        });

        expect(sinPartes.text()).toContain('No hay partes registrados hoy para esta orden.');

        const sinOrdenes = mount(ParteDiario, {
            props: { ...props, ordenes: [], ordenSeleccionadaId: null },
        });

        expect(sinOrdenes.text()).toContain('No hay órdenes En Curso');
    });

    it('renders the flash success banner', () => {
        // Arrange
        hoisted.page.props.flash.success = 'Parte diario registrado.';

        // Act
        const wrapper = mount(ParteDiario, { props });

        // Assert
        expect(wrapper.text()).toContain('Parte diario registrado.');
    });
});
