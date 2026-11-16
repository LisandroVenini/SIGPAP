<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AppLayout from '../../../Layouts/AppLayout.vue';
import DatePicker from '@/Components/DatePicker.vue';
import { hoyISO } from '@/lib/fechas';

const hoy = hoyISO();

interface Producto {
    id: number;
    codigo_sku: string;
    descripcion: string;
    unidad: string | null;
}

interface Etapa {
    etapa_id: number;
    nombre: string;
    producida: number;
    rechazadas: number;
    faltante: number;
}

interface Maquinaria {
    id: number;
    nombre: string;
}

interface ParteHoy {
    id: number;
    etapa_id: number;
    etapa: string;
    fecha: string;
    cantidad: number;
    maquinaria: string | null;
    observaciones: string | null;
}

interface Orden {
    id: number;
    producto: Producto | null;
    cantidad_requerida: number;
    etapas: Etapa[];
    maquinarias: Maquinaria[];
    partes_hoy: ParteHoy[];
}

type SharedProps = {
    flash: { success: string | null };
    errors: Record<string, string>;
};

const props = defineProps<{
    ordenes: Orden[];
    ordenSeleccionadaId: number | null;
    fechaHoy: string;
}>();

const page = usePage<SharedProps>();

function primeraEtapaConFaltante(orden: Orden | null): number | '' {
    if (!orden) {
        return '';
    }

    const etapa = orden.etapas.find((candidata) => candidata.faltante > 0);

    return etapa?.etapa_id ?? orden.etapas[0]?.etapa_id ?? '';
}

function resolverOrdenInicial(): number | null {
    if (
        props.ordenSeleccionadaId !== null &&
        props.ordenes.some((orden) => orden.id === props.ordenSeleccionadaId)
    ) {
        return props.ordenSeleccionadaId;
    }

    return props.ordenes[0]?.id ?? null;
}

const ordenActivaId = ref<number | null>(resolverOrdenInicial());

const ordenActiva = computed<Orden | null>(
    () => props.ordenes.find((orden) => orden.id === ordenActivaId.value) ?? null,
);

const form = useForm({
    etapa_id: primeraEtapaConFaltante(ordenActiva.value),
    maquinaria_id: '' as number | '',
    fecha: props.fechaHoy,
    cantidad: '' as number | '',
    observaciones: '',
});

const etapaSeleccionada = computed<Etapa | null>(() => {
    if (!ordenActiva.value) {
        return null;
    }

    return (
        ordenActiva.value.etapas.find((etapa) => etapa.etapa_id === Number(form.etapa_id)) ?? null
    );
});

const faltanteMaximo = computed<number | undefined>(() => etapaSeleccionada.value?.faltante);

watch(ordenActivaId, () => {
    form.clearErrors();
    form.etapa_id = primeraEtapaConFaltante(ordenActiva.value);
    form.maquinaria_id = '';
    form.cantidad = '';
});

function faltanteDeOrden(orden: Orden): number {
    if (orden.etapas.length === 0) {
        return orden.cantidad_requerida;
    }

    return Math.max(...orden.etapas.map((etapa) => etapa.faltante));
}

function estadoEtapa(etapa: Etapa): string {
    if (etapa.producida === 0) {
        return 'Sin iniciar';
    }

    if (etapa.faltante === 0) {
        return 'Completada';
    }

    return 'En curso';
}

function claseBadge(estado: string): string {
    const clases: Record<string, string> = {
        'Sin iniciar': 'bg-workshop text-steel',
        'En curso': 'bg-purple-100 text-purple-800',
        Completada: 'bg-pine-wash text-pine',
    };

    return clases[estado] ?? 'bg-workshop text-steel';
}

function avanceEtapa(etapa: Etapa): number {
    const total = etapa.producida + etapa.faltante;

    if (total <= 0) {
        return 0;
    }

    return Math.min(100, Math.round((etapa.producida / total) * 100));
}

function registrarParte(): void {
    if (!ordenActiva.value) {
        return;
    }

    form.post(`/produccion/ordenes-de-produccion/${ordenActiva.value.id}/partes`, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('cantidad', 'observaciones');
        },
    });
}
</script>

<template>
    <AppLayout title="Parte Diario">
        <Head title="Parte Diario" />

        <div class="flex flex-col gap-4">
            <div class="flex flex-col gap-1">
                <h2 class="text-2xl font-semibold tracking-tight text-ink">Parte diario</h2>
                <p class="text-sm text-steel">
                    Cargá partes de producción y seguí el avance por etapas de las órdenes En Curso.
                </p>
            </div>

            <div
                v-if="page.props.flash.success"
                class="rounded-control border border-pine/20 bg-pine-wash px-4 py-2.5 text-sm text-pine"
            >
                {{ page.props.flash.success }}
            </div>

            <div
                v-if="props.ordenes.length === 0"
                class="rounded-panel border border-hairline bg-surface px-4 py-10 text-center text-sm text-steel"
            >
                No hay órdenes En Curso
            </div>

            <template v-else>
                <div class="rounded-panel border border-hairline bg-surface p-4 shadow-diffused">
                    <div class="flex flex-wrap items-end justify-between gap-3">
                        <div class="flex flex-col gap-1.5">
                            <label for="orden" class="text-xs font-medium text-steel"
                                >Orden de producción</label
                            >
                            <select
                                id="orden"
                                v-model="ordenActivaId"
                                data-testid="orden-select"
                                class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                            >
                                <option
                                    v-for="orden in props.ordenes"
                                    :key="orden.id"
                                    :value="orden.id"
                                >
                                    #{{ orden.id }} · {{ orden.producto?.descripcion ?? '—' }} ({{
                                        orden.producto?.codigo_sku ?? '—'
                                    }})
                                </option>
                            </select>
                        </div>
                        <p v-if="ordenActiva" class="text-sm text-steel">
                            Pendiente:
                            <span class="num font-medium text-ink">{{
                                faltanteDeOrden(ordenActiva)
                            }}</span>
                            de
                            <span class="num">{{ ordenActiva.cantidad_requerida }}</span>
                            unidades
                        </p>
                    </div>
                </div>

                <div
                    v-if="ordenActiva"
                    class="overflow-hidden rounded-panel border border-hairline bg-surface shadow-diffused"
                >
                    <div
                        class="flex flex-wrap items-baseline justify-between gap-2 border-b border-hairline px-4 py-3"
                    >
                        <div class="flex flex-col gap-0.5">
                            <h3 class="text-lg font-semibold text-ink">
                                {{ ordenActiva.producto?.descripcion ?? 'Orden sin producto' }}
                            </h3>
                            <span class="font-mono text-xs text-steel">{{
                                ordenActiva.producto?.codigo_sku ?? '—'
                            }}</span>
                        </div>
                        <p class="text-sm text-steel">
                            Requeridas
                            <span class="num font-medium text-ink">{{
                                ordenActiva.cantidad_requerida
                            }}</span>
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left" data-testid="etapas-table">
                            <thead class="border-b border-hairline bg-workshop">
                                <tr>
                                    <th
                                        class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                    >
                                        Etapa
                                    </th>
                                    <th
                                        class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                    >
                                        Estado
                                    </th>
                                    <th
                                        class="px-4 py-2.5 text-right text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                    >
                                        Producida
                                    </th>
                                    <th
                                        class="px-4 py-2.5 text-right text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                    >
                                        Faltante
                                    </th>
                                    <th
                                        class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                    >
                                        Avance
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-hairline">
                                <tr
                                    v-for="etapa in ordenActiva.etapas"
                                    :key="etapa.etapa_id"
                                    class="hover:bg-teja-wash/30"
                                >
                                    <td class="px-4 py-2.5 text-sm font-medium text-ink">
                                        {{ etapa.nombre }}
                                    </td>
                                    <td class="px-4 py-2.5 text-sm">
                                        <span
                                            class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                            :class="claseBadge(estadoEtapa(etapa))"
                                        >
                                            {{ estadoEtapa(etapa) }}
                                        </span>
                                    </td>
                                    <td class="num px-4 py-2.5 text-right text-xs text-steel">
                                        {{ etapa.producida }}
                                    </td>
                                    <td class="num px-4 py-2.5 text-right text-xs text-steel">
                                        {{ etapa.faltante }}
                                    </td>
                                    <td class="px-4 py-2.5 text-xs text-steel">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="h-2 w-16 overflow-hidden rounded-full bg-workshop"
                                            >
                                                <div
                                                    class="h-full bg-teja"
                                                    :style="{ width: `${avanceEtapa(etapa)}%` }"
                                                ></div>
                                            </div>
                                            <span class="num text-xs"
                                                >{{ avanceEtapa(etapa) }}%</span
                                            >
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        v-if="ordenActiva.etapas.length === 0"
                        class="px-4 py-8 text-center text-sm text-steel"
                    >
                        La orden no tiene etapas en su hoja de ruta.
                    </div>
                </div>

                <div v-if="ordenActiva" class="grid gap-4 lg:grid-cols-2">
                    <div
                        class="rounded-panel border border-hairline bg-surface p-4 shadow-diffused"
                    >
                        <h3 class="text-base font-semibold text-ink">Registrar parte</h3>
                        <form
                            class="mt-3 flex flex-col gap-3"
                            data-testid="parte-form"
                            @submit.prevent="registrarParte"
                        >
                            <div class="flex flex-col gap-1.5">
                                <label for="etapa_id" class="text-xs font-medium text-steel"
                                    >Etapa</label
                                >
                                <select
                                    id="etapa_id"
                                    v-model="form.etapa_id"
                                    required
                                    class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                                >
                                    <option
                                        v-for="etapa in ordenActiva.etapas"
                                        :key="etapa.etapa_id"
                                        :value="etapa.etapa_id"
                                    >
                                        {{ etapa.nombre }} — faltan {{ etapa.faltante }}
                                    </option>
                                </select>
                                <p v-if="form.errors.etapa_id" class="text-xs text-oxide">
                                    {{ form.errors.etapa_id }}
                                </p>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label for="maquinaria_id" class="text-xs font-medium text-steel"
                                    >Maquinaria</label
                                >
                                <select
                                    id="maquinaria_id"
                                    v-model="form.maquinaria_id"
                                    class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                                >
                                    <option value="">Sin maquinaria</option>
                                    <option
                                        v-for="maquinaria in ordenActiva.maquinarias"
                                        :key="maquinaria.id"
                                        :value="maquinaria.id"
                                    >
                                        {{ maquinaria.nombre }}
                                    </option>
                                </select>
                                <p v-if="form.errors.maquinaria_id" class="text-xs text-oxide">
                                    {{ form.errors.maquinaria_id }}
                                </p>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label for="fecha" class="text-xs font-medium text-steel"
                                    >Fecha</label
                                >
                                <DatePicker id="fecha" v-model="form.fecha" :max="hoy" required />
                                <p v-if="form.errors.fecha" class="text-xs text-oxide">
                                    {{ form.errors.fecha }}
                                </p>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label for="cantidad" class="text-xs font-medium text-steel"
                                    >Cantidad</label
                                >
                                <input
                                    id="cantidad"
                                    v-model="form.cantidad"
                                    type="number"
                                    min="0.01"
                                    step="0.01"
                                    :max="faltanteMaximo"
                                    required
                                    class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                                />
                                <p v-if="etapaSeleccionada" class="text-xs text-steel">
                                    Faltante de la etapa:
                                    <span class="num">{{ etapaSeleccionada.faltante }}</span>
                                </p>
                                <p v-if="form.errors.cantidad" class="text-xs text-oxide">
                                    {{ form.errors.cantidad }}
                                </p>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label for="observaciones" class="text-xs font-medium text-steel"
                                    >Observaciones</label
                                >
                                <textarea
                                    id="observaciones"
                                    v-model="form.observaciones"
                                    rows="3"
                                    maxlength="500"
                                    class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                                ></textarea>
                                <p v-if="form.errors.observaciones" class="text-xs text-oxide">
                                    {{ form.errors.observaciones }}
                                </p>
                            </div>

                            <div class="flex justify-end border-t border-hairline pt-3">
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="rounded-control bg-teja px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-teja-deep active:scale-[0.98] disabled:opacity-60"
                                >
                                    {{ form.processing ? 'Registrando…' : 'Registrar parte' }}
                                </button>
                            </div>
                        </form>
                    </div>

                    <div
                        class="overflow-hidden rounded-panel border border-hairline bg-surface shadow-diffused"
                    >
                        <div class="border-b border-hairline px-4 py-3">
                            <h3 class="text-base font-semibold text-ink">Partes de hoy</h3>
                        </div>

                        <div
                            v-if="ordenActiva.partes_hoy.length === 0"
                            class="px-4 py-10 text-center text-sm text-steel"
                        >
                            No hay partes registrados hoy para esta orden.
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="w-full text-left" data-testid="partes-hoy-table">
                                <thead class="border-b border-hairline bg-workshop">
                                    <tr>
                                        <th
                                            class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                        >
                                            Etapa
                                        </th>
                                        <th
                                            class="px-4 py-2.5 text-right text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                        >
                                            Cantidad
                                        </th>
                                        <th
                                            class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                        >
                                            Maquinaria
                                        </th>
                                        <th
                                            class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                        >
                                            Observaciones
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-hairline">
                                    <tr v-for="parte in ordenActiva.partes_hoy" :key="parte.id">
                                        <td class="px-4 py-2.5 text-sm text-ink">
                                            {{ parte.etapa }}
                                        </td>
                                        <td class="num px-4 py-2.5 text-right text-xs text-steel">
                                            {{ parte.cantidad }}
                                        </td>
                                        <td class="px-4 py-2.5 text-xs text-steel">
                                            {{ parte.maquinaria ?? '—' }}
                                        </td>
                                        <td class="px-4 py-2.5 text-xs text-steel">
                                            {{ parte.observaciones || '—' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </AppLayout>
</template>
