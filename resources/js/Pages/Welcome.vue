<script setup lang="ts">
defineProps<{ appName: string }>();
</script>

<template>
    <div class="flex min-h-[100dvh] items-center justify-center px-4 py-10">
        <main
            class="w-full max-w-xl rounded-panel border border-hairline bg-surface p-4 shadow-diffused sm:p-10"
        >
            <p class="text-xs font-medium tracking-[0.18em] text-clay uppercase">
                Confort y Muebles · Posadas
            </p>
            <h1 class="mt-4 text-3xl font-semibold text-ink">{{ appName }}</h1>
            <p class="mt-3 text-base leading-relaxed text-steel">
                Sistema de Gestión de Producción y Análisis Predictivo
            </p>
        </main>
    </div>
</template>
