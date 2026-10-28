<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    email: '',
    password: '',
});

function submit(): void {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <div class="flex min-h-[100dvh] items-center justify-center bg-canvas px-4 py-10">
        <Head title="Acceso" />

        <main class="w-full max-w-[400px]">
            <div
                class="rounded-panel border border-hairline bg-surface p-8 shadow-diffused sm:p-10"
            >
                <div class="mb-8 text-left">
                    <h1 class="text-2xl font-bold tracking-tight text-ink">SIGPAP</h1>
                    <p class="mt-1.5 text-[0.8125rem] text-clay">
                        Confort y Muebles · Sistema de Gestión
                    </p>
                </div>

                <form class="space-y-5" @submit.prevent="submit">
                    <div>
                        <label
                            for="email"
                            class="mb-2 block text-[0.8125rem] font-medium text-ink/80"
                        >
                            Correo electrónico
                        </label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            autocomplete="email"
                            required
                            placeholder="nombre@empresa.com"
                            class="w-full rounded-control border border-hairline bg-surface px-3.5 py-2.5 text-sm text-ink transition-all duration-150 placeholder:text-clay/70 focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        />
                        <p v-if="form.errors.email" class="mt-1.5 text-xs text-oxide">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="password"
                            class="mb-2 block text-[0.8125rem] font-medium text-ink/80"
                        >
                            Contraseña
                        </label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            autocomplete="current-password"
                            required
                            placeholder="••••••••"
                            class="w-full rounded-control border border-hairline bg-surface px-3.5 py-2.5 text-sm text-ink transition-all duration-150 placeholder:text-clay/70 focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        />
                        <p v-if="form.errors.password" class="mt-1.5 text-xs text-oxide">
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <div class="pt-1">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex w-full items-center justify-center rounded-control bg-teja px-4 py-2.5 text-sm font-medium text-white transition-colors duration-150 hover:bg-teja-deep active:scale-[0.98] disabled:opacity-60"
                        >
                            {{ form.processing ? 'Ingresando…' : 'Iniciar sesión' }}
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</template>
