<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '../../../Layouts/AppLayout.vue';
import GanttTimeline from '@/Components/Cronograma/GanttTimeline.vue';
import type { FilaCronograma, Horizonte } from '@/Components/Cronograma/gantt';
import { sumarDias } from '@/lib/fechas';
import { HORIZONTES, lunesDe, numeroSemana, rangoVisible } from '@/Components/Cronograma/gantt';

interface Cronograma {
    inicio: string;
    hoy: string;
    dias: number;
    filas: FilaCronograma[];
}

const props = defineProps<{
    cronograma: Cronograma;
    motivos: string[];
}>();

const page = usePage<{
    flash: { success: string | null };
    errors: Record<string, string>;
}>();

const desde = ref(lunesDe(props.cronograma.hoy));
const horizonte = ref<Horizonte>(14);
const estadoFiltro = ref('');

const drawerOpen = ref(false);
const filaActiva = ref<FilaCronograma | null>(null);
const prioridad = ref<number | string>('');
const motivo = ref('');
const detalle = ref('');
const confirmar = ref(false);

const filasVisibles = computed(() =>
    estadoFiltro.value === ''
        ? props.cronograma.filas
        : props.cronograma.filas.filter((fila) => fila.estado === estadoFiltro.value),
);

const conflictos = computed(() => filasVisibles.value.filter((fila) => fila.conflicto_entrega));

const estados = computed(() =>
    [...new Set(props.cronograma.filas.map((fila) => fila.estado))].sort(),
);

function moverSemana(semanas: number): void {
    desde.value = sumarDias(desde.value, semanas * 7);
}

function irAHoy(): void {
    desde.value = lunesDe(props.cronograma.hoy);
}

function abrir(fila: FilaCronograma): void {
    filaActiva.value = fila;
    prioridad.value = fila.prioridad;
    motivo.value = props.motivos[0] ?? '';
    detalle.value = '';
    confirmar.value = false;
    drawerOpen.value = true;
}

const enviando = ref(false);

function cerrarDrawer(): void {
    drawerOpen.value = false;
}

function guardar(): void {
    if (!filaActiva.value || enviando.value) {
        return;
    }

    enviando.value = true;

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
            onFinish: () => {
                enviando.value = false;
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

            <section
                class="flex flex-col gap-3 rounded-control border border-hairline bg-workshop/50 p-3 lg:flex-row lg:items-center lg:justify-between"
            >
                <div class="flex flex-wrap items-center gap-2">
                    <div
                        class="inline-flex items-center overflow-hidden rounded-control border border-hairline bg-surface"
                    >
                        <button
                            type="button"
                            class="px-2 py-1.5 text-steel transition-colors hover:bg-canvas hover:text-ink"
                            aria-label="Semana anterior"
                            @click="moverSemana(-1)"
                        >
                            ‹
                        </button>
                        <div
                            class="flex items-center gap-2 border-x border-hairline px-3 py-1 text-xs font-semibold text-ink"
                        >
                            <span class="num" data-testid="rango-ventana">
                                {{ rangoVisible(desde, horizonte) }}
                            </span>
                            <span
                                class="num rounded bg-teja-wash px-1.5 py-0.5 text-[0.6875rem] font-medium text-teja-deep"
                            >
                                Semana {{ numeroSemana(desde) }}
                            </span>
                        </div>
                        <button
                            type="button"
                            class="px-2 py-1.5 text-steel transition-colors hover:bg-canvas hover:text-ink"
                            aria-label="Semana siguiente"
                            @click="moverSemana(1)"
                        >
                            ›
                        </button>
                    </div>

                    <button
                        type="button"
                        class="rounded-control border border-hairline bg-surface px-3 py-1.5 text-xs font-medium text-ink transition-colors hover:bg-canvas"
                        @click="irAHoy"
                    >
                        Hoy
                    </button>

                    <div
                        class="inline-flex overflow-hidden rounded-control border border-hairline bg-surface text-xs"
                    >
                        <button
                            v-for="opcion in HORIZONTES"
                            :key="opcion"
                            type="button"
                            class="px-2.5 py-1.5 font-medium transition-colors"
                            :class="
                                horizonte === opcion
                                    ? 'bg-teja-wash text-teja-deep'
                                    : 'text-steel hover:bg-canvas hover:text-ink'
                            "
                            :data-testid="`horizonte-${opcion}`"
                            @click="horizonte = opcion"
                        >
                            {{ opcion }} d
                        </button>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <select
                        v-model="estadoFiltro"
                        data-testid="filtro-estado"
                        class="rounded-control border border-hairline bg-surface px-2.5 py-1.5 text-xs text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                    >
                        <option value="">Estado: todos</option>
                        <option v-for="estado in estados" :key="estado" :value="estado">
                            {{ estado }}
                        </option>
                    </select>
                </div>
            </section>

            <div
                class="flex flex-wrap items-center gap-x-4 gap-y-1.5 rounded-control border border-hairline bg-surface px-3 py-2 text-[0.6875rem] text-steel"
            >
                <span class="font-semibold tracking-wider text-ink uppercase">Convención:</span>
                <span class="flex items-center gap-1.5">
                    <span class="h-2 w-3 rounded-sm bg-teja"></span> En curso
                </span>
                <span class="flex items-center gap-1.5">
                    <span
                        class="h-2 w-3 rounded-sm border border-dashed border-steel/60 bg-workshop"
                    ></span>
                    Planificado
                </span>
                <span class="flex items-center gap-1.5">
                    <span
                        class="h-2 w-3 rounded-sm border border-dashed border-oxide/70 bg-oxide/10"
                    ></span>
                    Conflicto de entrega
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="text-clay">🔒</span> Reubicación bloqueada
                </span>
            </div>

            <div
                v-if="conflictos.length > 0"
                class="flex flex-col gap-1 rounded-control border border-amber/30 bg-amber-wash px-3 py-2 text-xs text-amber md:flex-row md:items-center md:justify-between"
                data-testid="banner-conflictos"
            >
                <span>
                    <span class="font-semibold tracking-wider uppercase">Regla de negocio:</span>
                    {{ conflictos.length }}
                    {{ conflictos.length === 1 ? 'orden incumple' : 'órdenes incumplen' }} su fecha
                    de entrega con la proyección actual.
                </span>
                <span class="num font-semibold">
                    {{ conflictos.map((fila) => `#${fila.orden_id}`).join(' · ') }}
                </span>
            </div>

            <div
                class="overflow-hidden rounded-panel border border-hairline bg-surface shadow-diffused"
            >
                <GanttTimeline
                    v-if="filasVisibles.length > 0"
                    :filas="filasVisibles"
                    :desde="desde"
                    :dias="horizonte"
                    :hoy="props.cronograma.hoy"
                    @reordenar="abrir"
                />

                <div v-else class="px-4 py-10 text-center text-sm text-steel">
                    No hay órdenes de producción para programar.
                </div>

                <div
                    class="flex flex-wrap items-center justify-between gap-2 border-t border-hairline px-4 py-2.5 text-xs text-steel"
                >
                    <span>
                        {{ filasVisibles.length }}
                        {{ filasVisibles.length === 1 ? 'orden activa' : 'órdenes activas' }} ·
                        {{ conflictos.length }} en conflicto · ventana
                        <span class="num">{{ rangoVisible(desde, horizonte) }}</span>
                    </span>
                </div>
            </div>
        </div>

        <div
            v-if="drawerOpen"
            class="fixed inset-0 z-40 flex justify-end bg-ink/20"
            @click.self="cerrarDrawer"
        >
            <aside
                v-dialog="cerrarDrawer"
                class="flex h-full w-full max-w-[420px] flex-col overflow-y-auto border-l border-hairline bg-surface p-5 shadow-diffused"
            >
                <div class="flex items-center justify-between border-b border-hairline pb-3">
                    <h3 class="text-lg font-semibold text-ink">Reordenar orden</h3>
                    <button
                        type="button"
                        class="text-sm text-steel hover:text-ink"
                        @click="cerrarDrawer"
                    >
                        Cerrar
                    </button>
                </div>

                <p class="mt-3 text-sm text-steel">{{ filaActiva?.producto }}</p>

                <section class="mt-4" data-testid="historial-prioridad">
                    <h4 class="text-xs font-semibold tracking-wider text-steel uppercase">
                        Historial de prioridad
                    </h4>
                    <p
                        v-if="!filaActiva?.historial.length"
                        class="mt-2 rounded-control bg-workshop px-3 py-2 text-xs text-steel"
                    >
                        Sin cambios de prioridad registrados.
                    </p>
                    <div v-else class="mt-2 overflow-x-auto rounded-control border border-hairline">
                        <table class="w-full text-left text-xs">
                            <thead
                                class="border-b border-hairline bg-workshop text-[0.625rem] tracking-wider text-steel uppercase"
                            >
                                <tr>
                                    <th class="px-2 py-1.5">Fecha</th>
                                    <th class="px-2 py-1.5 text-right">Cambio</th>
                                    <th class="px-2 py-1.5">Motivo</th>
                                    <th class="px-2 py-1.5">Usuario</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-hairline">
                                <tr
                                    v-for="(registro, indice) in filaActiva.historial"
                                    :key="indice"
                                >
                                    <td class="num px-2 py-1.5 text-steel">
                                        {{ registro.fecha ?? '—' }}
                                    </td>
                                    <td class="num px-2 py-1.5 text-right text-ink">
                                        {{ registro.prioridad_anterior ?? '—' }} →
                                        {{ registro.prioridad_nueva }}
                                    </td>
                                    <td class="px-2 py-1.5 text-steel">{{ registro.motivo }}</td>
                                    <td class="px-2 py-1.5 text-steel">
                                        {{ registro.usuario ?? '—' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

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
                        v-if="
                            page.props.errors.prioridad ||
                            page.props.errors.motivo ||
                            page.props.errors.estado
                        "
                        class="rounded-control border border-oxide/20 bg-oxide/5 px-4 py-2.5 text-sm text-oxide"
                    >
                        {{
                            page.props.errors.prioridad ??
                            page.props.errors.motivo ??
                            page.props.errors.estado
                        }}
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
                            @click="cerrarDrawer"
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
