import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';

const hoisted = vi.hoisted(() => {
    const put = vi.fn();
    const form = {
        parametros: {} as Record<string, string>,
        put,
        processing: false,
    };
    const page = {
        props: {
            flash: { success: null as string | null },
        },
    };

    return { put, form, page };
});

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div />' },
    useForm: () => hoisted.form,
    usePage: () => hoisted.page,
}));

vi.mock('../../Layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /></div>' },
}));

import Configuracion from './Configuracion.vue';

const parametros = {
    Producción: [
        { id: 1, nombre: 'prod_turno_manana_inicio', valor: '06:00', categoria: 'Producción' },
    ],
    Compras: [
        { id: 2, nombre: 'compras_vigencia_cotizacion_dias', valor: '7', categoria: 'Compras' },
    ],
};

describe('Configuracion page', () => {
    beforeEach(() => {
        hoisted.put.mockClear();
        hoisted.page.props.flash.success = null;
        hoisted.form.parametros = {
            prod_turno_manana_inicio: '06:00',
            compras_vigencia_cotizacion_dias: '7',
        };
    });

    it('renders one section per categoria with its inputs', () => {
        // Arrange / Act
        const wrapper = mount(Configuracion, { props: { parametros, puedeEditar: true } });

        // Assert
        expect(wrapper.text()).toContain('Producción');
        expect(wrapper.text()).toContain('Compras');
        expect(wrapper.find('input#prod_turno_manana_inicio').exists()).toBe(true);
        expect(wrapper.find('input#compras_vigencia_cotizacion_dias').exists()).toBe(true);
    });

    it('submits the parameters with a PUT request', async () => {
        // Arrange
        const wrapper = mount(Configuracion, { props: { parametros, puedeEditar: true } });

        // Act
        await wrapper.find('form').trigger('submit.prevent');

        // Assert
        expect(hoisted.put).toHaveBeenCalledWith('/usuarios/configuracion', {
            preserveScroll: true,
        });
    });

    it('disables editing when the usuario cannot edit', () => {
        // Arrange / Act
        const wrapper = mount(Configuracion, { props: { parametros, puedeEditar: false } });

        // Assert
        expect(wrapper.find('input#prod_turno_manana_inicio').attributes('disabled')).toBeDefined();
        expect(wrapper.text()).toContain('Solo lectura');
        expect(wrapper.find('button[type="submit"]').exists()).toBe(false);
    });

    it('renders the success flash message', () => {
        // Arrange
        hoisted.page.props.flash.success = 'Parámetros actualizados.';

        // Act
        const wrapper = mount(Configuracion, { props: { parametros, puedeEditar: true } });

        // Assert
        expect(wrapper.text()).toContain('Parámetros actualizados.');
    });
});
