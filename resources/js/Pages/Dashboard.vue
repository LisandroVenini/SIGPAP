<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

type SharedProps = {
    auth: {
        user: {
            nombre: string;
        };
    };
};

const page = usePage<SharedProps>();
</script>

<template>
    <AppLayout title="Tablero">
        <div class="rounded-panel border border-hairline bg-surface p-6 shadow-diffused">
            <p class="text-xs font-medium tracking-[0.18em] text-clay uppercase">Tablero ejecutivo</p>
            <h2 class="mt-2 text-2xl font-semibold text-ink">Bienvenido, {{ page.props.auth.user.nombre }}</h2>
            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-steel">
                Fase 0 fundacional completada. El tablero ejecutivo (BI-01) se implementa en la fase de Reportes.
            </p>
        </div>
    </AppLayout>
</template>
