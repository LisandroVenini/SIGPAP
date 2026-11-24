import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { enableAutoUnmount, mount } from '@vue/test-utils';

enableAutoUnmount(afterEach);

const hoisted = vi.hoisted(() => {
    const post = vi.fn();

    return {
        post,
        form: {
            cantidad_disponible: '50',
            precio_unitario: '',
            fecha_prometida: '',
            errors: {} as Record<string, string>,
            processing: false,
            post,
        },
    };
});

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div />' },
    useForm: () => hoisted.form,
}));

vi.mock('@/Components/DatePicker.vue', () => ({
    default: {
        props: ['modelValue', 'min'],
        emits: ['update:modelValue'],
        template: '<input type="date" data-testid="date-picker" />',
    },
}));

import Disponibilidad from './Disponibilidad.vue';

const solicitud = {
    solicitud_id: 1,
    proveedor: 'Aceros del Sur',
    articulo: 'Tabla de pino',
    cantidad_solicitada: 50,
    fecha_requerida: '2026-12-01',
};

describe('Portal Disponibilidad', () => {
    beforeEach(() => {
        hoisted.post.mockReset();
    });

    it('renders the requested item and sends the answer', async () => {
        const wrapper = mount(Disponibilidad, {
            props: { solicitud, token: 'abc-123' },
        });

        expect(wrapper.text()).toContain('Tabla de pino');
        expect(wrapper.text()).toContain('2026-12-01');

        await wrapper.get('form').trigger('submit');

        expect(hoisted.post).toHaveBeenCalledWith('/portal/disponibilidad/abc-123');
    });

    it('shows the confirmation after answering', () => {
        const wrapper = mount(Disponibilidad, { props: { enviada: true } });

        expect(wrapper.text()).toContain('Respuesta enviada');
        expect(wrapper.find('form').exists()).toBe(false);
    });
});
