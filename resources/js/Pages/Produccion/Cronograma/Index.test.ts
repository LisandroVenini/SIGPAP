import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { enableAutoUnmount, mount } from '@vue/test-utils';

enableAutoUnmount(afterEach);

const hoisted = vi.hoisted(() => {
    const put = vi.fn();

    const page = {
        props: {
            flash: { success: null as string | null },
            errors: {} as Record<string, string>,
        },
    };

    return { put, page };
});

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div />' },
    usePage: () => hoisted.page,
    router: { put: hoisted.put },
}));

vi.mock('../../../Layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /></div>' },
}));

import Cronograma from './Index.vue';

const props = {
    cronograma: {
        inicio: '2026-11-01',
        dias: 14,
        filas: [
            {
                orden_id: 1,
                producto: 'Silla de pino',
                estado: 'Pendiente',
                prioridad: 2,
                fecha_entrega: '2026-11-10',
                reubicable: true,
                conflicto_entrega: true,
                etapas: [{ etapa: 'Corte', inicio: '2026-11-01', fin: '2026-11-02', dias: 2 }],
            },
            {
                orden_id: 2,
                producto: 'Mesa de algarrobo',
                estado: 'En Curso',
                prioridad: 1,
                fecha_entrega: '2026-11-20',
                reubicable: false,
                conflicto_entrega: false,
                etapas: [{ etapa: 'Armado', inicio: '2026-11-03', fin: '2026-11-04', dias: 2 }],
            },
        ],
    },
    motivos: ['Urgencia comercial', 'Otro'],
};

describe('Produccion Cronograma page', () => {
    beforeEach(() => {
        hoisted.put.mockClear();
    });

    it('renders the orders and the delivery conflict', () => {
        // Arrange / Act
        const wrapper = mount(Cronograma, { props });

        // Assert
        expect(wrapper.text()).toContain('Silla de pino');
        expect(wrapper.text()).toContain('Corte');
        expect(wrapper.findAll('[data-testid="conflicto-entrega"]')).toHaveLength(1);
    });

    it('disables reordering for an in-progress order', () => {
        // Arrange / Act
        const wrapper = mount(Cronograma, { props });

        // Assert
        const botones = wrapper.findAll('button').filter((b) => b.text() === 'Reordenar');

        expect(botones).toHaveLength(2);
        expect(botones[0].attributes('disabled')).toBeUndefined();
        expect(botones[1].attributes('disabled')).toBeDefined();
    });

    it('sends the priority change with its motive', async () => {
        // Arrange
        const wrapper = mount(Cronograma, { props, attachTo: document.body });

        // Act
        const reordenar = wrapper.findAll('button').find((button) => button.text() === 'Reordenar');
        await reordenar?.trigger('click');

        await wrapper.find('#prioridad').setValue('1');
        await wrapper.find('[data-testid="reordenar-form"]').trigger('submit.prevent');

        // Assert
        expect(hoisted.put).toHaveBeenCalledWith(
            '/produccion/cronograma/1/prioridad',
            expect.objectContaining({
                prioridad: 1,
                motivo: 'Urgencia comercial',
                confirmar_conflicto: false,
            }),
            expect.objectContaining({ preserveScroll: true }),
        );
    });
});
