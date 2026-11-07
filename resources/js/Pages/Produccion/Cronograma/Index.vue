<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

interface Segmento {
    etapa: string;
    inicio: string;
    fin: string;
    dias: number;
}

interface Fila {
    orden_id: number;
    producto: string;
    estado: string;
    prioridad: number;
    fecha_entrega: string | null;
    reubicable: boolean;
    conflicto_entrega: boolean;
    etapas: Segmento[];
}

interface Cronograma {
    inicio: string;
    dias: number;
    filas: Fila[];
}

const props = defineProps<{
    cronograma: Cronograma;
    motivos: string[];
}>();

const page = usePage<{
    flash: { success: string | null };
    errors: Record<string, string>;
}>();

const drawerOpen = ref(false);
const filaActiva = ref<Fila | null>(null);
const prioridad = ref<number | string>('');
const motivo = ref('');
const detalle = ref('');
const confirmar = ref(false);

function offset(fecha: string): number {
    const inicio = new Date(`${props.cronograma.inicio}T00:00:00`);
    const actual = new Date(`${fecha}T00:00:00`);

    return Math.round((actual.getTime() - inicio.getTime()) / 86400000);
}

function claseDeEstado(estado: string): string {
    if (estado === 'En Curso') {
        return 'bg-teja text-white';
    }

    if (estado === 'Pospuesta') {
        return 'bg-oxide/20 text-oxide';
    }

    if (estado === 'Asignada') {
        return 'bg-pine-wash text-pine';
    }

    return 'bg-workshop text-steel';
}

function abrir(fila: Fila): void {
    filaActiva.value = fila;
    prioridad.value = fila.prioridad;
    motivo.value = props.motivos[0] ?? '';
    detalle.value = '';
    confirmar.value = false;
    drawerOpen.value = true;
}

function guardar(): void {
    if (!filaActiva.value) {
        return;
    }

    router.put(
        `/produccion/cronograma/${filaActiva.value.orden_id}/prioridad`,
        {
            prioridad: Number(prioridad.value),
            motivo: motivo.value,
            detalle: detalle.value || null,
            confirmar_conflicto: confirmar.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                drawerOpen.value = false;
            },
        },
    );
}
</script>

<template>
    <AppLayout title="Cronograma de Producción">
        <Head title="Cronograma de Producción" />

        <div class="flex flex-col gap-4">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div class="flex flex-col gap-1">
                    <h2 class="text-2xl font-semibold tracking-tight text-ink">
                        Cronograma de producción
                    </h2>
                    <p class="text-sm text-steel">
                        Proyección por etapa según el tiempo por unidad de la hoja de ruta.
                    </p>
                </div>
            </div>

            <div
                v-if="page.props.flash.success"
                class="rounded-control border border-pine/20 bg-pine-wash px-4 py-2.5 text-sm text-pine"
            >
                {{ page.props.flash.success }}
            </div>

            <div
                class="overflow-hidden rounded-panel border border-hairline bg-surface shadow-diffused"
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="border-b border-hairline bg-workshop">
                            <tr>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Orden
                                </th>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Proyección
                                </th>
                                <th
                                    class="px-4 py-2.5 text-right text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-hairline">
                            <tr v-for="fila in props.cronograma.filas" :key="fila.orden_id">
                                <td class="px-4 py-2.5 align-top text-sm">
                                    <p class="font-medium text-ink">{{ fila.producto }}</p>
                                    <p class="font-mono text-xs text-steel">
                                        #{{ fila.orden_id }} · prioridad {{ fila.prioridad }}
                                    </p>
                                    <p
                                        v-if="fila.fecha_entrega"
                                        class="text-xs"
                                        :class="
                                            fila.conflicto_entrega ? 'text-oxide' : 'text-steel'
                                        "
                                    >
                                        Entrega {{ fila.fecha_entrega }}
                                    </p>
                                </td>
                                <td class="px-4 py-2.5">
                                    <div
                                        class="grid gap-1"
                                        :style="{
                                            gridTemplateColumns: `repeat(${props.cronograma.dias}, minmax(28px, 1fr))`,
                                        }"
                                    >
                                        <div
                                            v-for="segmento in fila.etapas"
                                            :key="`${segmento.etapa}-${segmento.inicio}`"
                                            class="truncate rounded px-1 py-1 text-center text-[0.625rem]"
                                            :class="claseDeEstado(fila.estado)"
                                            :style="{
                                                gridColumn: `${offset(segmento.inicio) + 1} / span ${segmento.dias}`,
                                            }"
                                            :title="`${segmento.etapa}: ${segmento.inicio} → ${segmento.fin}`"
                                        >
                                            {{ segmento.etapa }}
                                        </div>
                                    </div>
                                    <p
                                        v-if="fila.conflicto_entrega"
                                        class="mt-1 text-xs text-oxide"
                                        data-testid="conflicto-entrega"
                                    >
                                        ⚠ Incumple la fecha de entrega
                                    </p>
                                </td>
                                <td class="px-4 py-2.5 text-right text-xs">
                                    <button
                                        type="button"
                                        class="font-medium text-teja hover:underline disabled:cursor-not-allowed disabled:text-clay/60"
                                        :disabled="!fila.reubicable"
                                        @click="abrir(fila)"
                                    >
                                        Reordenar
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="props.cronograma.filas.length === 0"
                    class="px-4 py-10 text-center text-sm text-steel"
                >
                    No hay órdenes de producción para programar.
                </div>
            </div>

            <div class="flex flex-wrap gap-2 text-xs text-steel">
                <span>Días proyectados: {{ props.cronograma.dias }}</span>
                <span>Inicio: {{ props.cronograma.inicio }}</span>
            </div>
        </div>

        <div
            v-if="drawerOpen"
            class="fixed inset-0 z-40 flex justify-end bg-ink/20"
            @click.self="drawerOpen = false"
        >
            <aside
                class="flex h-full w-full max-w-[420px] flex-col overflow-y-auto border-l border-hairline bg-surface p-5 shadow-diffused"
            >
                <div class="flex items-center justify-between border-b border-hairline pb-3">
                    <h3 class="text-lg font-semibold text-ink">Reordenar orden</h3>
                    <button
                        type="button"
                        class="text-sm text-steel hover:text-ink"
                        @click="drawerOpen = false"
                    >
                        Cerrar
                    </button>
                </div>

                <p class="mt-3 text-sm text-steel">{{ filaActiva?.producto }}</p>

                <form
                    class="mt-4 flex flex-col gap-4"
                    data-testid="reordenar-form"
                    @submit.prevent="guardar"
                >
                    <div class="flex flex-col gap-1.5">
                        <label for="prioridad" class="text-xs font-medium text-steel"
                            >Nueva prioridad</label
                        >
                        <input
                            id="prioridad"
                            v-model="prioridad"
                            type="number"
                            min="1"
                            required
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        />
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="motivo" class="text-xs font-medium text-steel">Motivo</label>
                        <select
                            id="motivo"
                            v-model="motivo"
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        >
                            <option v-for="opcion in props.motivos" :key="opcion" :value="opcion">
                                {{ opcion }}
                            </option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="detalle" class="text-xs font-medium text-steel"
                            >Detalle (opcional)</label
                        >
                        <input
                            id="detalle"
                            v-model="detalle"
                            type="text"
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        />
                    </div>

                    <div
                        v-if="page.props.errors.conflicto"
                        class="rounded-control border border-oxide/20 bg-oxide/5 px-4 py-2.5 text-sm text-oxide"
                    >
                        {{ page.props.errors.conflicto }}
                        <label class="mt-2 flex items-center gap-2 text-xs text-ink">
                            <input v-model="confirmar" type="checkbox" class="text-teja" />
                            Confirmar de todas formas
                        </label>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-hairline pt-3">
                        <button
                            type="button"
                            class="rounded-control border border-hairline px-4 py-2 text-sm text-ink transition-colors hover:bg-canvas"
                            @click="drawerOpen = false"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="rounded-control bg-teja px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-teja-deep active:scale-[0.98]"
                        >
                            Guardar
                        </button>
                    </div>
                </form>
            </aside>
        </div>
    </AppLayout>
</template>
