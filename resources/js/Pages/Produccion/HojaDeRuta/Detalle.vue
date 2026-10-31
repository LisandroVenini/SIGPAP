<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

interface Producto {
    id: number;
    codigo_sku: string;
    descripcion: string;
    unidad: string | null;
}

interface Asignada {
    etapa_id: number;
    etapa: string | null;
    tiempo_por_unidad: number;
    orden: number;
}

interface Disponible {
    id: number;
    nombre: string;
}

interface ComponenteDeReceta {
    id: number;
    articulo_id: number;
    codigo_sku: string;
    descripcion: string;
    unidad: string | null;
    cantidad: number;
}

interface InsumoDisponible {
    id: number;
    codigo_sku: string;
    descripcion: string;
    unidad: string | null;
}

interface Receta {
    componentes: ComponenteDeReceta[];
    disponibles: InsumoDisponible[];
}

type SharedProps = {
    flash: { success: string | null };
    errors: Record<string, string>;
};

const props = defineProps<{
    producto: Producto;
    asignadas: Asignada[];
    disponibles: Disponible[];
    receta: Receta;
}>();

const page = usePage<SharedProps>();

const form = useForm({
    etapa_id: '',
    tiempo_por_unidad: '',
});

const recetaForm = useForm({
    articulo_insumo_id: '',
    cantidad: '',
});

const insumoSeleccionado = computed(() => {
    return props.receta.disponibles.find(
        (insumo) => String(insumo.id) === recetaForm.articulo_insumo_id,
    );
});

const unidadSeleccionada = computed(() => insumoSeleccionado.value?.unidad ?? '—');

function asignar(): void {
    form.post(`/produccion/hoja-de-ruta/${props.producto.id}/etapas`, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
}

function desasignar(asignada: Asignada): void {
    if (!window.confirm(`¿Desasignar la etapa "${asignada.etapa}"?`)) {
        return;
    }

    router.delete(`/produccion/hoja-de-ruta/${props.producto.id}/etapas/${asignada.etapa_id}`, {
        preserveScroll: true,
    });
}

function mover(asignada: Asignada, direccion: 'subir' | 'bajar'): void {
    router.put(
        `/produccion/hoja-de-ruta/${props.producto.id}/etapas/${asignada.etapa_id}/mover`,
        {
            etapa_id: asignada.etapa_id,
            direccion,
        },
        {
            preserveScroll: true,
        },
    );
}

function agregarComponente(): void {
    recetaForm.post(`/produccion/hoja-de-ruta/${props.producto.id}/receta/componentes`, {
        preserveScroll: true,
        onSuccess: () => {
            recetaForm.reset();
        },
    });
}

function quitarComponente(componente: ComponenteDeReceta): void {
    if (!window.confirm(`¿Quitar el insumo "${componente.descripcion}" de la receta?`)) {
        return;
    }

    router.delete(
        `/produccion/hoja-de-ruta/${props.producto.id}/receta/componentes/${componente.id}`,
        {
            preserveScroll: true,
        },
    );
}
</script>

<template>
    <AppLayout title="Hoja de Ruta">
        <Head title="Hoja de Ruta" />

        <div class="flex flex-col gap-4">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div class="flex flex-col gap-1">
                    <Link
                        href="/produccion/hoja-de-ruta"
                        class="text-xs font-medium text-teja hover:underline"
                    >
                        ‹ Volver al listado
                    </Link>
                    <h2 class="text-2xl font-semibold tracking-tight text-ink">
                        {{ producto.descripcion }}
                    </h2>
                    <p class="font-mono text-xs text-steel">
                        SKU {{ producto.codigo_sku }} · Unidad: {{ producto.unidad ?? '—' }}
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
                v-if="page.props.errors.articulo"
                class="rounded-control border border-oxide/20 bg-oxide/5 px-4 py-2.5 text-sm text-oxide"
            >
                {{ page.props.errors.articulo }}
            </div>

            <div
                class="overflow-hidden rounded-panel border border-hairline bg-surface shadow-diffused"
            >
                <div
                    class="border-b border-hairline bg-workshop px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                >
                    Etapas asignadas
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="border-b border-hairline">
                            <tr>
                                <th
                                    class="px-4 py-2.5 text-center text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Orden
                                </th>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Etapa
                                </th>
                                <th
                                    class="px-4 py-2.5 text-right text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Tiempo por unidad (min)
                                </th>
                                <th
                                    class="px-4 py-2.5 text-right text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Acción
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-hairline">
                            <tr
                                v-for="(asignada, indice) in props.asignadas"
                                :key="asignada.etapa_id"
                                class="hover:bg-teja-wash/30"
                            >
                                <td
                                    class="px-4 py-2.5 text-center font-mono text-xs text-ink"
                                    data-testid="orden-etapa"
                                >
                                    {{ asignada.orden }}
                                </td>
                                <td class="px-4 py-2.5 text-sm font-medium text-ink">
                                    {{ asignada.etapa }}
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono text-xs text-ink">
                                    {{ asignada.tiempo_por_unidad }}
                                </td>
                                <td class="px-4 py-2.5 text-right text-xs">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            type="button"
                                            class="font-medium text-steel hover:text-ink disabled:cursor-not-allowed disabled:opacity-40"
                                            :disabled="indice === 0"
                                            @click="mover(asignada, 'subir')"
                                        >
                                            Subir
                                        </button>
                                        <button
                                            type="button"
                                            class="font-medium text-steel hover:text-ink disabled:cursor-not-allowed disabled:opacity-40"
                                            :disabled="indice === props.asignadas.length - 1"
                                            @click="mover(asignada, 'bajar')"
                                        >
                                            Bajar
                                        </button>
                                        <button
                                            type="button"
                                            class="font-medium text-steel hover:text-oxide"
                                            @click="desasignar(asignada)"
                                        >
                                            Desasignar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="props.asignadas.length === 0"
                    class="px-4 py-10 text-center text-sm text-steel"
                >
                    El producto todavía no tiene etapas asignadas.
                </div>
            </div>

            <div class="rounded-panel border border-hairline bg-surface p-4 shadow-diffused">
                <h3 class="text-sm font-semibold text-ink">Asignar etapa</h3>

                <form
                    v-if="props.disponibles.length > 0"
                    class="mt-3 flex flex-wrap items-end gap-3"
                    data-testid="asignar-form"
                    @submit.prevent="asignar"
                >
                    <div class="flex min-w-[220px] flex-1 flex-col gap-1.5">
                        <label for="etapa_id" class="text-xs font-medium text-steel"
                            >Etapa del catálogo</label
                        >
                        <select
                            id="etapa_id"
                            v-model="form.etapa_id"
                            required
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        >
                            <option value="" disabled>Seleccioná una etapa</option>
                            <option
                                v-for="etapa in props.disponibles"
                                :key="etapa.id"
                                :value="String(etapa.id)"
                            >
                                {{ etapa.nombre }}
                            </option>
                        </select>
                        <p v-if="form.errors.etapa_id" class="text-xs text-oxide">
                            {{ form.errors.etapa_id }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="tiempo_por_unidad" class="text-xs font-medium text-steel"
                            >Tiempo por unidad (min)</label
                        >
                        <input
                            id="tiempo_por_unidad"
                            v-model="form.tiempo_por_unidad"
                            type="number"
                            min="0.01"
                            step="0.01"
                            required
                            class="w-40 rounded-control border border-hairline bg-surface px-3 py-2 font-mono text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        />
                        <p v-if="form.errors.tiempo_por_unidad" class="text-xs text-oxide">
                            {{ form.errors.tiempo_por_unidad }}
                        </p>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-control bg-teja px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-teja-deep active:scale-[0.98] disabled:opacity-60"
                    >
                        {{ form.processing ? 'Asignando…' : 'Asignar etapa' }}
                    </button>
                </form>

                <p v-else class="mt-3 text-sm text-steel">
                    Todas las etapas del catálogo ya están asignadas a este producto.
                </p>
            </div>

            <div
                class="overflow-hidden rounded-panel border border-hairline bg-surface shadow-diffused"
            >
                <div
                    class="border-b border-hairline bg-workshop px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                >
                    Receta
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="border-b border-hairline">
                            <tr>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Insumo
                                </th>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Unidad
                                </th>
                                <th
                                    class="px-4 py-2.5 text-right text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Cantidad
                                </th>
                                <th
                                    class="px-4 py-2.5 text-right text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Acción
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-hairline">
                            <tr
                                v-for="componente in props.receta.componentes"
                                :key="componente.id"
                                class="hover:bg-teja-wash/30"
                            >
                                <td class="px-4 py-2.5 text-sm font-medium text-ink">
                                    {{ componente.descripcion }}
                                    <span class="block font-mono text-xs text-steel">
                                        {{ componente.codigo_sku }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-sm text-ink">
                                    {{ componente.unidad ?? '—' }}
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono text-xs text-ink">
                                    {{ componente.cantidad.toFixed(2) }}
                                </td>
                                <td class="px-4 py-2.5 text-right text-xs">
                                    <button
                                        type="button"
                                        class="font-medium text-steel hover:text-oxide"
                                        @click="quitarComponente(componente)"
                                    >
                                        Quitar
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="props.receta.componentes.length === 0"
                    class="px-4 py-10 text-center text-sm text-steel"
                >
                    Este producto no tiene receta definida.
                </div>
            </div>

            <div class="rounded-panel border border-hairline bg-surface p-4 shadow-diffused">
                <h3 class="text-sm font-semibold text-ink">Agregar insumo a la receta</h3>

                <form
                    v-if="props.receta.disponibles.length > 0"
                    class="mt-3 flex flex-wrap items-end gap-3"
                    data-testid="receta-form"
                    @submit.prevent="agregarComponente"
                >
                    <div class="flex min-w-[220px] flex-1 flex-col gap-1.5">
                        <label for="articulo_insumo_id" class="text-xs font-medium text-steel"
                            >Insumo disponible</label
                        >
                        <select
                            id="articulo_insumo_id"
                            v-model="recetaForm.articulo_insumo_id"
                            data-testid="receta-insumo-select"
                            required
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        >
                            <option value="" disabled>Seleccioná un insumo</option>
                            <option
                                v-for="insumo in props.receta.disponibles"
                                :key="insumo.id"
                                :value="String(insumo.id)"
                            >
                                {{ insumo.descripcion }}
                                <template v-if="insumo.unidad"> ({{ insumo.unidad }})</template>
                            </option>
                        </select>
                        <p v-if="recetaForm.errors.articulo_insumo_id" class="text-xs text-oxide">
                            {{ recetaForm.errors.articulo_insumo_id }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label
                            for="cantidad"
                            class="text-xs font-medium text-steel"
                            data-testid="receta-cantidad-label"
                        >
                            Cantidad ({{ unidadSeleccionada }})
                        </label>
                        <input
                            id="cantidad"
                            v-model="recetaForm.cantidad"
                            type="number"
                            min="0.01"
                            step="0.01"
                            required
                            class="w-40 rounded-control border border-hairline bg-surface px-3 py-2 font-mono text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        />
                        <p v-if="recetaForm.errors.cantidad" class="text-xs text-oxide">
                            {{ recetaForm.errors.cantidad }}
                        </p>
                    </div>

                    <button
                        type="submit"
                        :disabled="recetaForm.processing"
                        class="rounded-control bg-teja px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-teja-deep active:scale-[0.98] disabled:opacity-60"
                    >
                        {{ recetaForm.processing ? 'Agregando…' : 'Agregar insumo' }}
                    </button>
                </form>

                <p v-else class="mt-3 text-sm text-steel">
                    No hay insumos disponibles para agregar a la receta.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
