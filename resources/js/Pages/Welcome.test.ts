import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import Welcome from './Welcome.vue';

describe('Welcome page', () => {
    it('renders the application name and subtitle', () => {
        // Arrange
        const wrapper = mount(Welcome, {
            props: { appName: 'SIGPAP' },
        });

        // Act
        const heading = wrapper.find('h1');

        // Assert
        expect(heading.text()).toBe('SIGPAP');
        expect(wrapper.text()).toContain('Sistema de Gestión de Producción y Análisis Predictivo');
    });
});
