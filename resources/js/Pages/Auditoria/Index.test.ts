import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';

const hoisted = vi.hoisted(() => ({ routerGet: vi.fn() }));

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div />' },
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
    router: { get: hoisted.routerGet },
}));

vi.mock('../../Layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /></div>' },
}));

import Index from './Index.vue';

const usuarios = [
    { id: 1, nombre: 'Laura Cáceres' },
    { id: 2, nombre: 'Pedro Gómez' },
];

const eventos = {
    data: [
        {
            id: 10,
            fecha_hora: '2026-02-10 14:35:09',
            usuario_id: 1,
            accion: 'crear_usuario',
            direccion_ip: '192.168.0.10',
            auditable_type: 'App\\Modules\\Usuarios\\Models\\Usuario',
            auditable_id: 7,
            estado_anterior: { estado: 'Inhabilitado' },
            estado_nuevo: { estado: 'Activo' },
        },
    ],
    links: [],
    from: 1,
    to: 1,
    total: 1,
};

const props = {
    eventos,
    usuarios,
    acciones: ['crear_usuario', 'aprobar_oc'],
    filtros: {},
};

describe('Auditoria Index page', () => {
    beforeEach(() => {
        hoisted.routerGet.mockClear();
    });

    it('renders each event with fecha, hora, usuario, accion and IP', () => {
        // Arrange / Act
        const wrapper = mount(Index, { props });

        // Assert
        const row = wrapper.find('tbody tr');
        expect(row.text()).toContain('2026-02-10');
        expect(row.text()).toContain('14:35');
        expect(row.text()).toContain('Laura Cáceres');
        expect(row.text()).toContain('crear_usuario');
        expect(row.text()).toContain('192.168.0.10');
    });

    it('applies the filters through the router', async () => {
        // Arrange
        const wrapper = mount(Index, { props });

        // Act
        await wrapper.find('[data-testid="fecha-desde"]').setValue('2026-02-01');
        await wrapper.find('[data-testid="direccion-ip"]').setValue('192.168');
        await wrapper.find('form[data-testid="filtros-auditoria"]').trigger('submit.prevent');

        // Assert
        expect(hoisted.routerGet).toHaveBeenCalledWith(
            '/auditoria',
            expect.objectContaining({ fecha_desde: '2026-02-01', direccion_ip: '192.168' }),
            expect.any(Object),
        );
    });

    it('shows the previous and new state as readable JSON in the detail drawer', async () => {
        // Arrange
        const wrapper = mount(Index, { props, attachTo: document.body });

        // Act
        await wrapper.find('[data-testid="ver-detalle"]').trigger('click');

        // Assert
        const drawer = wrapper.find('[data-testid="detalle-auditoria"]');
        expect(drawer.exists()).toBe(true);
        expect(drawer.text()).toContain('"estado": "Inhabilitado"');
        expect(drawer.text()).toContain('"estado": "Activo"');
    });

    it('renders the exact empty state when no records match', () => {
        // Arrange
        const vacio = {
            ...props,
            eventos: { ...eventos, data: [], from: null, to: null, total: 0 },
        };

        // Act
        const wrapper = mount(Index, { props: vacio });

        // Assert
        expect(wrapper.text()).toContain(
            'No se encontraron registros para los criterios especificados',
        );
    });
});
