<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

type FiltrosReporte = {
    tipo?: string | null;
    desde?: string | null;
    hasta?: string | null;
    estado?: string | null;
};

type FilaReporte = Record<string, string | number | null>;

interface PreviewReporte {
    tipo: string;
    titulo: string;
    columnas: string[];
    filas: FilaReporte[];
    total: number;
    desde: string;
    hasta: string;
}

const props = defineProps<{
    tipos: Record<string, string>;
    filtros: FiltrosReporte;
    preview: PreviewReporte | null;
}>();

const tipo = ref<string>(props.filtros.tipo ?? Object.keys(props.tipos)[0] ?? '');
const desde = ref<string>(props.filtros.desde ?? '');
const hasta = ref<string>(props.filtros.hasta ?? '');
const estado = ref<string>(props.filtros.estado ?? '');

const esProduccion = computed(() => tipo.value === 'produccion');
const filasVisibles = computed(() => props.preview?.filas.slice(0, 50) ?? []);

function generar(): void {
    router.get(
        '/reportes',
        {
            tipo: tipo.value || undefined,
            desde: desde.value || undefined,
            hasta: hasta.value || undefined,
            estado: esProduccion.value && estado.value ? estado.value : undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function urlExportar(formato: 'pdf' | 'xlsx'): string {
    const parametros = new URLSearchParams({
        tipo: tipo.value,
        desde: desde.value,
        hasta: hasta.value,
        formato,
    });

    if (esProduccion.value && estado.value) {
        parametros.set('estado', estado.value);
    }

    return `/reportes/exportar?${parametros.toString()}`;
}

function valoresDe(fila: FilaReporte): (string | number | null)[] {
    return Object.values(fila);
}
</script>

<template>
    <AppLayout title="Generador de Reportes">
        <Head title="Generador de Reportes" />

        <div class="flex flex-col gap-4">
            <div class="flex flex-col gap-1">
                <h2 class="text-2xl font-semibold tracking-tight text-ink">
                    Generador de Reportes
                </h2>
                <p class="text-sm text-steel">
                    Consultá y exportá reportes de Producción, Inventario y Compras por rango de
                    fechas.
                </p>
            </div>

            <div class="rounded-panel border border-hairline bg-surface p-4 shadow-diffused">
                <form
                    data-testid="filtros-reporte"
                    class="flex flex-wrap items-end gap-2"
                    @submit.prevent="generar"
                >
                    <div class="flex flex-col gap-1">
                        <label for="tipo" class="text-xs font-medium text-steel">Tipo</label>
                        <select
                            id="tipo"
                            v-model="tipo"
                            data-testid="tipo"
                            class="min-w-48 rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        >
                            <option
                                v-for="(titulo, clave) in props.tipos"
                                :key="clave"
                                :value="clave"
                            >
                                {{ titulo }}
                            </option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label for="desde" class="text-xs font-medium text-steel">Desde</label>
                        <input
                            id="desde"
                            v-model="desde"
                            data-testid="desde"
                            type="date"
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        />
                    </div>

                    <div class="flex flex-col gap-1">
                        <label for="hasta" class="text-xs font-medium text-steel">Hasta</label>
                        <input
                            id="hasta"
                            v-model="hasta"
                            data-testid="hasta"
                            type="date"
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        />
                    </div>

                    <div v-if="esProduccion" class="flex flex-col gap-1">
                        <label for="estado" class="text-xs font-medium text-steel">Estado</label>
                        <input
                            id="estado"
                            v-model="estado"
                            data-testid="estado"
                            type="text"
                            placeholder="Todos"
                            class="w-40 rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink placeholder:text-clay/70 focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        />
                    </div>

                    <button
                        type="submit"
                        class="rounded-control bg-teja px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-teja-deep active:scale-[0.98]"
                    >
                        Generar
                    </button>
                </form>
            </div>

            <template v-if="props.preview">
                <div
                    class="flex flex-wrap items-center justify-between gap-3 rounded-panel border border-hairline bg-surface p-4 shadow-diffused"
                >
                    <p data-testid="total" class="text-sm text-steel">
                        <span class="font-medium text-ink">{{ props.preview.titulo }}</span>
                        · {{ props.preview.desde }} al {{ props.preview.hasta }} ·
                        {{ props.preview.total }} fila(s)
                    </p>

                    <div v-if="props.preview.total > 0" class="flex items-center gap-2">
                        <a
                            :href="urlExportar('pdf')"
                            data-testid="exportar-pdf"
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm font-medium text-ink transition-colors hover:bg-workshop/40"
                        >
                            Descargar PDF
                        </a>
                        <a
                            :href="urlExportar('xlsx')"
                            data-testid="exportar-xlsx"
                            class="rounded-control bg-teja px-3 py-2 text-sm font-medium text-white transition-colors hover:bg-teja-deep"
                        >
                            Descargar Excel
                        </a>
                    </div>
                </div>

                <div
                    v-if="props.preview.total > 0"
                    data-testid="preview"
                    class="overflow-hidden rounded-panel border border-hairline bg-surface shadow-diffused"
                >
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="border-b border-hairline bg-workshop">
                                <tr>
                                    <th
                                        v-for="columna in props.preview.columnas"
                                        :key="columna"
                                        class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                    >
                                        {{ columna }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-hairline">
                                <tr
                                    v-for="(fila, indice) in filasVisibles"
                                    :key="indice"
                                    class="hover:bg-teja-wash/30"
                                >
                                    <td
                                        v-for="(valor, columna) in valoresDe(fila)"
                                        :key="columna"
                                        class="px-4 py-2.5 text-sm text-steel"
                                    >
                                        {{ valor ?? '—' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div
                    v-if="props.preview.total === 0"
                    data-testid="sin-datos"
                    class="rounded-panel border border-hairline bg-surface px-4 py-10 text-center text-sm text-steel shadow-diffused"
                >
                    Sin datos para generar
                </div>
            </template>
        </div>
    </AppLayout>
</template>
