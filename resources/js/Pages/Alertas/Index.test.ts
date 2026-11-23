import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';

const hoisted = vi.hoisted(() => ({
    get: vi.fn(),
    patch: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div />' },
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
    router: { get: hoisted.get, patch: hoisted.patch },
    usePage: () => ({ props: { flash: { success: null } } }),
}));

vi.mock('../../Layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /></div>' },
}));

import Index from './Index.vue';

const props = {
    alertas: {
        data: [
            {
                id: 1,
                tipo: 'stock_bajo_minimo',
                severidad: 'Critica',
                descripcion: 'Tabla de pino bajo el mínimo',
                fecha: '2026-11-18 10:00',
                resuelta_en: null,
                contexto: { articulo: 'Tabla de pino', disponible: 2, articulo_id: 10 },
                roles: [{ name: 'Encargado de Stock' }],
            },
        ],
        links: [],
        from: 1,
        to: 1,
        total: 1,
    },
    filtros: {},
    noLeidas: 1,
    tipos: [{ clave: 'stock_bajo_minimo', etiqueta: 'Stock bajo el mínimo', detalle: null }],
    severidades: [{ valor: 'Critica', etiqueta: 'Crítica' }],
};

describe('Alertas Index page', () => {
    beforeEach(() => {
        hoisted.get.mockClear();
        hoisted.patch.mockClear();
    });

    it('renders the alert, its context and the unread counter', () => {
        // Act
        const wrapper = mount(Index, { props });

        // Assert
        expect(wrapper.text()).toContain('Tabla de pino bajo el mínimo');
        expect(wrapper.text()).toContain('Crítica');
        expect(wrapper.text()).toContain('1 sin leer');
        expect(wrapper.text()).toContain('Encargado de Stock');
        expect(wrapper.text()).toContain('Artículo: Tabla de pino');
        expect(wrapper.text()).not.toContain('articulo_id');
    });

    it('marks an alert as read through the router', async () => {
        // Arrange
        const wrapper = mount(Index, { props });

        // Act
        await wrapper
            .findAll('button')
            .find((boton) => boton.text() === 'Marcar leída')
            ?.trigger('click');

        // Assert
        expect(hoisted.patch).toHaveBeenCalledWith(
            '/alertas/1/leida',
            {},
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('applies the filters through the router', async () => {
        // Arrange
        const wrapper = mount(Index, { props });

        // Act
        await wrapper.find('#severidad').setValue('Critica');
        await wrapper.find('form').trigger('submit.prevent');

        // Assert
        expect(hoisted.get).toHaveBeenCalledWith(
            '/alertas',
            expect.objectContaining({ severidad: 'Critica', estado: 'abiertas' }),
            expect.objectContaining({ preserveState: true }),
        );
    });

    it('renders a readable quality detail without internal ids', () => {
        // Arrange
        const propsConCalidad = {
            ...props,
            alertas: {
                ...props.alertas,
                data: [
                    {
                        id: 2,
                        tipo: 'calidad_baja',
                        severidad: 'Atencion',
                        descripcion:
                            '«Silla Misionera» tiene 6.25% de rechazo: 1 de 16 unidades en Corte.',
                        fecha: '2026-11-18 10:00',
                        resuelta_en: null,
                        contexto: {
                            orden: 4,
                            producto: 'Silla Misionera',
                            rechazo_pct: 6.25,
                            rechazadas: 1,
                            unidades: 16,
                            etapa: 'Corte',
                            motivo: 'Detalle de laca en el borde.',
                            orden_de_produccion_id: 4,
                        },
                        roles: [{ name: 'Supervisor de Producción' }],
                    },
                ],
            },
            tipos: [
                {
                    clave: 'calidad_baja',
                    etiqueta: 'Calidad baja',
                    detalle: 'Etapa: {{etapa}} · {{rechazadas}} de {{unidades}} rechazadas · Motivo: {{motivo}}',
                },
            ],
        };

        // Act
        const wrapper = mount(Index, { props: propsConCalidad });

        // Assert
        expect(wrapper.text()).toContain(
            'Etapa: Corte · 1 de 16 rechazadas · Motivo: Detalle de laca en el borde.',
        );
        expect(wrapper.text()).not.toContain('orden_de_produccion_id');
        expect(wrapper.text()).not.toContain('Orden #4');
    });

    it('falls back to the generic detail when a legacy quality alert lacks the template keys', () => {
        // Arrange
        const legado = {
            ...props,
            alertas: {
                ...props.alertas,
                data: [
                    {
                        id: 3,
                        tipo: 'calidad_baja',
                        severidad: 'Atencion',
                        descripcion: 'Alerta vieja',
                        fecha: '2026-11-18 10:00',
                        resuelta_en: '2026-11-18 11:00',
                        contexto: {
                            orden: 4,
                            producto: 'Orden #4',
                            rechazo_pct: 6.25,
                            orden_de_produccion_id: 4,
                        },
                        roles: [],
                    },
                ],
            },
            tipos: [
                {
                    clave: 'calidad_baja',
                    etiqueta: 'Calidad baja',
                    detalle: 'Etapa: {{etapa}} · {{rechazadas}} de {{unidades}} rechazadas · Motivo: {{motivo}}',
                },
            ],
        };

        // Act
        const wrapper = mount(Index, { props: legado });

        // Assert
        expect(wrapper.text()).not.toContain('de  rechazadas');
        expect(wrapper.text()).toContain('Producto: Orden #4');
        expect(wrapper.text()).not.toContain('orden_de_produccion_id');
    });
});
