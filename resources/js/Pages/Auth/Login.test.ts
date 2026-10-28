import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';

const hoisted = vi.hoisted(() => {
    const post = vi.fn();
    const reset = vi.fn();
    const form = {
        email: '',
        password: '',
        errors: {} as Record<string, string>,
        processing: false,
        post,
        reset,
    };

    return { post, reset, form };
});

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div />' },
    useForm: () => hoisted.form,
}));

import Login from './Login.vue';

describe('Login page', () => {
    beforeEach(() => {
        hoisted.post.mockClear();
        hoisted.reset.mockClear();
        hoisted.form.errors = {};
    });

    it('renders the access form', () => {
        // Arrange / Act
        const wrapper = mount(Login);

        // Assert
        expect(wrapper.find('h1').text()).toBe('SIGPAP');
        expect(wrapper.find('input#email').exists()).toBe(true);
        expect(wrapper.find('input#password').exists()).toBe(true);
        expect(wrapper.text()).toContain('Iniciar sesión');
    });

    it('posts the credentials and resets the password on finish', async () => {
        // Arrange
        const wrapper = mount(Login);

        // Act
        await wrapper.find('form').trigger('submit.prevent');

        // Assert
        expect(hoisted.post).toHaveBeenCalledWith(
            '/login',
            expect.objectContaining({ onFinish: expect.any(Function) }),
        );

        const options = hoisted.post.mock.calls[0][1] as { onFinish: () => void };
        options.onFinish();

        expect(hoisted.reset).toHaveBeenCalledWith('password');
    });

    it('renders validation errors', () => {
        // Arrange
        hoisted.form.errors = { email: 'Credenciales inválidas' };

        // Act
        const wrapper = mount(Login);

        // Assert
        expect(wrapper.text()).toContain('Credenciales inválidas');
    });
});
