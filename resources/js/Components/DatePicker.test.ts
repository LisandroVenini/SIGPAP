import { afterEach, describe, expect, it } from 'vitest';
import { enableAutoUnmount, mount } from '@vue/test-utils';
import DatePicker from './DatePicker.vue';

enableAutoUnmount(afterEach);

function montar(props: Record<string, unknown> = {}) {
    return mount(DatePicker, {
        props: { modelValue: '', ...props },
        attrs: { 'data-testid': 'fecha' },
    });
}

describe('DatePicker', () => {
    it('shows the current value formatted as dd/mm/aaaa on the inner input', () => {
        // Arrange / Act
        const wrapper = montar({ modelValue: '2026-11-09' });

        // Assert: test id is forwarded to the input so forms keep working
        const input = wrapper.find('[data-testid="fecha"]');

        expect(input.exists()).toBe(true);
        expect(input.element.tagName).toBe('INPUT');
        expect((input.element as HTMLInputElement).value).toBe('09/11/2026');
    });

    it('emits an ISO date when the user types it', async () => {
        // Arrange
        const wrapper = montar({ modelValue: '', min: '2026-11-11' });

        // Act
        await wrapper.find('[data-testid="fecha"]').setValue('2026-11-20');

        // Assert
        expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual(['2026-11-20']);
    });

    it('refuses a typed date below the minimum', async () => {
        // Arrange
        const wrapper = montar({ modelValue: '', min: '2026-11-11' });

        // Act
        await wrapper.find('[data-testid="fecha"]').setValue('2026-11-10');

        // Assert
        expect(wrapper.emitted('update:modelValue')).toBeUndefined();
    });

    it('opens the calendar, disables days out of range and emits the picked day', async () => {
        // Arrange
        const wrapper = montar({ modelValue: '2026-11-20', min: '2026-11-11' });

        // Act
        await wrapper.find('[aria-label="Elegir fecha en el calendario"]').trigger('click');

        // Assert: month of the current value is shown
        const calendario = wrapper.find('[data-testid="calendario"]');
        expect(calendario.exists()).toBe(true);
        expect(calendario.text()).toContain('noviembre 2026');

        const fueraDeRango = wrapper.find('[data-testid="dia-2026-11-10"]');
        expect(fueraDeRango.attributes('disabled')).toBeDefined();

        // Act
        await wrapper.find('[data-testid="dia-2026-11-15"]').trigger('click');

        // Assert
        expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual(['2026-11-15']);
        expect(wrapper.find('[data-testid="calendario"]').exists()).toBe(false);
    });

    it('closes the calendar with the escape key', async () => {
        // Arrange
        const wrapper = montar({ modelValue: '2026-11-20' });
        await wrapper.find('[aria-label="Elegir fecha en el calendario"]').trigger('click');
        expect(wrapper.find('[data-testid="calendario"]').exists()).toBe(true);

        // Act
        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
        await wrapper.vm.$nextTick();

        // Assert
        expect(wrapper.find('[data-testid="calendario"]').exists()).toBe(false);
    });
});
