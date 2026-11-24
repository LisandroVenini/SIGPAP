<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import GanttTimeline from '@/Components/Cronograma/GanttTimeline.vue';
import type { FilaCronograma, Horizonte } from '@/Components/Cronograma/gantt';
import { HORIZONTES, lunesDe } from '@/Components/Cronograma/gantt';
import AppLayout from '@/Layouts/AppLayout.vue';
import { sumarDias } from '@/lib/fechas';

interface Cronograma {
    inicio: string;
    hoy: string;
    dias: number;
    filas: FilaCronograma[];
}

interface Kpis {
    ordenes_activas: number;
    en_riesgo: number;
    avance_promedio: number;
    tasa_promedio: number | null;
}

interface AlertaResumen {
    id: number;
    descripcion: string;
    severidad: string;
    fecha: string | null;
    orden_de_produccion_id: number | null;
}

interface AbastecimientoItem {
    articulo_id: number | null;
    articulo: string;
    disponible: number;
    minimo: number;
    dias_hasta_quiebre: number | null;
    fecha_limite_pedido: string | null;
    fecha_quiebre: string | null;
    cantidad_sugerida: number;
    riesgo: string;
}

interface Filtros {
    estado: string | null;
    solo_riesgo: boolean;
}

const props = defineProps<{
    cronograma: Cronograma;
    kpis: Kpis;
    alertas: AlertaResumen[];
    abastecimiento: AbastecimientoItem[];
    tasas: Record<string, { valor: number; desviacion: number; fecha_calculo: string | null }>;
    estados: string[];
    filtros: Filtros;
    sinDatos: boolean;
    calendarioConfigurado: boolean;
}>();

const marcas = computed(() =>
    props.abastecimiento
        .filter((item) => item.fecha_limite_pedido !== null)
        .map((item) => ({
            fecha: item.fecha_limite_pedido as string,
            etiqueta: `Pedir ${item.articulo} antes del ${item.fecha_limite_pedido}`,
            tono: item.riesgo === 'alto' ? ('riesgo' as const) : ('aviso' as const),
        })),
);

const desde = ref(lunesDe(props.cronograma.hoy));
const horizonte = ref<Horizonte>(14);
const filtroEstado = ref(props.filtros.estado ?? '');
const soloRiesgo = ref(props.filtros.solo_riesgo);

function aplicarFiltros(): void {
    router.get(
        '/predictivo/tablero',
        {
            estado: filtroEstado.value !== '' ? filtroEstado.value : undefined,
            solo_riesgo: soloRiesgo.value ? 1 : undefined,
        },
        { preserveState: true, preserveScroll: true },
    );
}

function moverSemana(semanas: number): void {
    desde.value = sumarDias(desde.value, semanas * 7);
}

function irAHoy(): void {
    desde.value = lunesDe(props.cronograma.hoy);
}

const claseSeveridad: Record<string, string> = {
    Critica: 'bg-oxide/10 text-oxide',
    Atencion: 'bg-amber-100 text-amber-800',
    Info: 'bg-pine-wash text-pine',
};

const tasaPromedio = (valor: number | null): string =>
    valor === null ? '—' : `${(valor * 100).toFixed(1)}%`;
</script>

<template>
    <Head title="Tablero Predictivo" />

    <AppLayout title="Tablero Predictivo">
        <div class="space-y-6 p-6">
            <header class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-pine">Tablero Predictivo</h1>
                    <p class="mt-1 text-sm text-steel">
                        Cronograma real, riesgo de atraso y alertas de la planta.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="rounded-md border border-hairline bg-white px-3 py-1.5 text-sm text-steel hover:bg-workshop"
                        @click="moverSemana(-1)"
                    >
                        ← Semana
                    </button>
                    <button
                        type="button"
                        class="rounded-md border border-hairline bg-white px-3 py-1.5 text-sm text-steel hover:bg-workshop"
                        @click="irAHoy"
                    >
                        Hoy
                    </button>
                    <button
                        type="button"
                        class="rounded-md border border-hairline bg-white px-3 py-1.5 text-sm text-steel hover:bg-workshop"
                        @click="moverSemana(1)"
                    >
                        Semana →
                    </button>
                    <select
                        v-model.number="horizonte"
                        class="rounded-md border border-hairline bg-white px-2 py-1.5 text-sm text-steel"
                    >
                        <option v-for="valor in HORIZONTES" :key="valor" :value="valor">
                            {{ valor }} días
                        </option>
                    </select>
                </div>
            </header>

            <div
                v-if="!calendarioConfigurado"
                class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"
            >
                El calendario laboral no está configurado: se usa el horario por defecto (lunes a
                viernes 08-12 y 16-20, sábado 08-12).
            </div>

            <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg border border-hairline bg-white p-4">
                    <p class="text-xs tracking-wider text-steel uppercase">Órdenes activas</p>
                    <p data-testid="kpi-ordenes" class="mt-2 text-2xl font-semibold text-pine">
                        {{ kpis.ordenes_activas }}
                    </p>
                </div>
                <div class="rounded-lg border border-hairline bg-white p-4">
                    <p class="text-xs tracking-wider text-steel uppercase">En riesgo de atraso</p>
                    <p data-testid="kpi-riesgo" class="mt-2 text-2xl font-semibold text-oxide">
                        {{ kpis.en_riesgo }}
                    </p>
                </div>
                <div class="rounded-lg border border-hairline bg-white p-4">
                    <p class="text-xs tracking-wider text-steel uppercase">Avance promedio</p>
                    <p data-testid="kpi-avance" class="mt-2 text-2xl font-semibold text-pine">
                        {{ kpis.avance_promedio }}%
                    </p>
                </div>
                <div class="rounded-lg border border-hairline bg-white p-4">
                    <p class="text-xs tracking-wider text-steel uppercase">Tasa de rendimiento</p>
                    <p data-testid="kpi-tasa" class="mt-2 text-2xl font-semibold text-pine">
                        {{ tasaPromedio(kpis.tasa_promedio) }}
                    </p>
                </div>
            </section>

            <section class="rounded-lg border border-hairline bg-white p-4">
                <div class="flex flex-wrap items-end gap-4">
                    <label class="flex flex-col gap-1 text-sm text-steel">
                        Estado
                        <select
                            v-model="filtroEstado"
                            class="rounded-md border border-hairline px-2 py-1.5 text-sm"
                        >
                            <option value="">Todos</option>
                            <option v-for="estado in estados" :key="estado" :value="estado">
                                {{ estado }}
                            </option>
                        </select>
                    </label>

                    <label class="flex items-center gap-2 text-sm text-steel">
                        <input
                            v-model="soloRiesgo"
                            type="checkbox"
                            class="rounded border-hairline"
                        />
                        Solo en riesgo
                    </label>

                    <button
                        type="button"
                        data-testid="aplicar-filtros"
                        class="rounded-md bg-teja px-4 py-1.5 text-sm font-medium text-white hover:bg-teja-deep"
                        @click="aplicarFiltros"
                    >
                        Aplicar
                    </button>
                </div>
            </section>

            <section
                v-if="sinDatos"
                data-testid="estado-vacio"
                class="rounded-lg border border-dashed border-hairline bg-workshop px-6 py-12 text-center"
            >
                <p class="text-lg font-medium text-steel">
                    Recolectando datos de planta para primeras predicciones
                </p>
            </section>

            <template v-else>
                <section
                    v-if="cronograma.filas.length === 0"
                    class="rounded-lg border border-dashed border-hairline bg-workshop px-6 py-8 text-center text-sm text-steel"
                >
                    No hay órdenes que coincidan con el filtro.
                </section>

                <section v-else class="rounded-lg border border-hairline bg-white p-4">
                    <GanttTimeline
                        :filas="cronograma.filas"
                        :desde="desde"
                        :dias="horizonte"
                        :hoy="cronograma.hoy"
                        :marcas="marcas"
                    />
                </section>

                <section class="rounded-lg border border-hairline bg-white p-4">
                    <h2 class="text-sm font-semibold tracking-wider text-steel uppercase">
                        Alertas de cuello de botella
                    </h2>
                    <ul v-if="alertas.length > 0" class="mt-3 space-y-2">
                        <li
                            v-for="alerta in alertas"
                            :key="alerta.id"
                            class="flex items-start gap-3 rounded-md border border-hairline px-3 py-2"
                        >
                            <span
                                class="mt-0.5 rounded px-2 py-0.5 text-xs font-medium"
                                :class="
                                    claseSeveridad[alerta.severidad] ?? 'bg-workshop text-steel'
                                "
                            >
                                {{ alerta.severidad }}
                            </span>
                            <span class="text-sm text-steel">{{ alerta.descripcion }}</span>
                        </li>
                    </ul>
                    <p v-else class="mt-3 text-sm text-steel">Sin alertas de cuello de botella.</p>
                </section>
            </template>

            <section class="rounded-lg border border-hairline bg-white p-4">
                <h2 class="text-sm font-semibold tracking-wider text-steel uppercase">
                    Abastecimiento
                </h2>

                <p v-if="abastecimiento.length === 0" class="mt-3 text-sm text-steel">
                    Sin insumos en riesgo de quiebre.
                </p>

                <div v-else class="mt-3 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr
                                class="border-b border-hairline text-xs tracking-wider text-steel uppercase"
                            >
                                <th class="px-2 py-2 font-medium">Insumo</th>
                                <th class="px-2 py-2 font-medium">Disponible</th>
                                <th class="px-2 py-2 font-medium">Quiebre</th>
                                <th class="px-2 py-2 font-medium">Pedir antes</th>
                                <th class="px-2 py-2 font-medium">Sugerido</th>
                                <th class="px-2 py-2 font-medium">Riesgo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="item in abastecimiento"
                                :key="item.articulo_id ?? item.articulo"
                                data-testid="fila-abastecimiento"
                                class="border-b border-hairline/60"
                            >
                                <td class="px-2 py-2 text-ink">{{ item.articulo }}</td>
                                <td class="num px-2 py-2 text-steel">{{ item.disponible }}</td>
                                <td class="num px-2 py-2 text-steel">
                                    {{ item.fecha_quiebre ?? '—' }}
                                </td>
                                <td
                                    class="num px-2 py-2 font-medium"
                                    :class="
                                        item.riesgo === 'alto' ? 'text-oxide' : 'text-amber-700'
                                    "
                                >
                                    {{ item.fecha_limite_pedido ?? '—' }}
                                </td>
                                <td class="num px-2 py-2 text-steel">
                                    {{ item.cantidad_sugerida }}
                                </td>
                                <td class="px-2 py-2">
                                    <span
                                        class="rounded px-2 py-0.5 text-xs font-medium"
                                        :class="
                                            item.riesgo === 'alto'
                                                ? 'bg-oxide/10 text-oxide'
                                                : 'bg-amber-100 text-amber-800'
                                        "
                                    >
                                        {{ item.riesgo }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
