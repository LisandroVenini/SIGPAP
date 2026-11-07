<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

interface Pesos {
    perfecta: number;
    retraso: number;
    faltante: number;
    rotura: number;
}

interface ParametrosRanking {
    pesos: Pesos;
    ventana_meses: number;
}

interface FilaRanking {
    proveedor_id: number;
    proveedor: string;
    entregas: number;
    perfectas: number;
    retrasos: number;
    faltantes: number;
    roturas: number;
    puntaje: number;
}

interface ProveedorSinHistorial {
    proveedor_id: number;
    proveedor: string;
}

const props = defineProps<{
    parametros: ParametrosRanking;
    filas: FilaRanking[];
    sin_historial: ProveedorSinHistorial[];
}>();

const notaParametros = computed(
    () =>
        `Pesos activos: perfecta ${props.parametros.pesos.perfecta} · retraso ${props.parametros.pesos.retraso} · faltante ${props.parametros.pesos.faltante} · rotura ${props.parametros.pesos.rotura} · Ventana: ${props.parametros.ventana_meses} meses.`,
);

function claseDePuntaje(puntaje: number): string {
    if (puntaje >= 80) {
        return 'bg-pine/10 text-pine border-pine/20';
    }

    if (puntaje >= 60) {
        return 'bg-amber/10 text-amber border-amber/20';
    }

    return 'bg-oxide/10 text-oxide border-oxide/20';
}

function formatoPuntaje(puntaje: number): string {
    return puntaje.toFixed(2);
}
</script>

<template>
    <AppLayout title="Ranking de Proveedores">
        <Head title="Ranking de Proveedores" />

        <div class="flex flex-col gap-4">
            <div class="flex flex-col gap-1">
                <h2 class="text-2xl font-semibold tracking-tight text-ink">
                    Ranking de Proveedores
                </h2>
                <p class="text-sm text-steel">
                    Confiabilidad de los proveedores activos según sus entregas registradas.
                </p>
                <p data-testid="nota-parametros" class="text-xs text-clay">
                    {{ notaParametros }}
                </p>
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
                                    Proveedor
                                </th>
                                <th
                                    class="px-4 py-2.5 text-right text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Entregas
                                </th>
                                <th
                                    class="px-4 py-2.5 text-right text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Perfectas
                                </th>
                                <th
                                    class="px-4 py-2.5 text-right text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Retrasos
                                </th>
                                <th
                                    class="px-4 py-2.5 text-right text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Faltantes
                                </th>
                                <th
                                    class="px-4 py-2.5 text-right text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Roturas
                                </th>
                                <th
                                    class="px-4 py-2.5 text-right text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Puntaje
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-hairline">
                            <tr
                                v-for="fila in props.filas"
                                :key="fila.proveedor_id"
                                class="hover:bg-teja-wash/30"
                            >
                                <td class="px-4 py-2.5 text-sm font-medium text-ink">
                                    {{ fila.proveedor }}
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono text-xs text-steel">
                                    {{ fila.entregas }}
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono text-xs text-steel">
                                    {{ fila.perfectas }}
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono text-xs text-steel">
                                    {{ fila.retrasos }}
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono text-xs text-steel">
                                    {{ fila.faltantes }}
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono text-xs text-steel">
                                    {{ fila.roturas }}
                                </td>
                                <td class="px-4 py-2.5 text-right">
                                    <span
                                        data-testid="puntaje"
                                        class="inline-flex items-center rounded border px-2 py-0.5 font-mono text-xs font-medium"
                                        :class="claseDePuntaje(fila.puntaje)"
                                    >
                                        {{ formatoPuntaje(fila.puntaje) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="props.filas.length === 0"
                    class="px-4 py-10 text-center text-sm text-steel"
                >
                    No hay entregas registradas en el período.
                </div>
            </div>

            <section
                v-if="props.sin_historial.length > 0"
                class="rounded-panel border border-hairline bg-surface p-4 shadow-diffused"
            >
                <h3 class="text-sm font-semibold text-ink">Sin historial</h3>
                <p class="mt-1 text-xs text-steel">
                    Proveedores activos sin entregas registradas en la ventana configurada.
                </p>
                <ul class="mt-3 flex flex-wrap gap-2">
                    <li
                        v-for="proveedor in props.sin_historial"
                        :key="proveedor.proveedor_id"
                        class="rounded-control border border-hairline bg-workshop px-3 py-1 text-xs text-steel"
                    >
                        {{ proveedor.proveedor }}
                    </li>
                </ul>
            </section>
        </div>
    </AppLayout>
</template>
