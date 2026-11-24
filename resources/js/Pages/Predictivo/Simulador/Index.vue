<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

interface Producto {
    id: number;
    codigo_sku: string;
    descripcion: string;
    unidad: string | null;
}

interface Retraso {
    orden_id: number;
    producto: string;
    fecha_entrega: string | null;
    dias_retraso: number;
}

interface Resultado {
    escenario_id: number | null;
    viable: boolean;
    motivo: string | null;
    pedido: {
        articulo_id: number;
        articulo: string | null;
        cantidad: number;
        fecha_limite: string;
        fecha_fin_proyectada: string | null;
    };
    retrasos: Retraso[];
    impacto_proyectado: number;
}

interface Filtros {
    articulo_id: number | string | null;
    cantidad: number | string | null;
    fecha_limite: string | null;
}

const props = defineProps<{
    productos: Producto[];
    resultado: Resultado | null;
    filtros: Filtros;
}>();

const form = useForm({
    articulo_id: props.filtros.articulo_id ?? '',
    cantidad: props.filtros.cantidad ?? '',
    fecha_limite: props.filtros.fecha_limite ?? '',
});

function simular(): void {
    form.post('/predictivo/simulador', { preserveScroll: true });
}

function motivoInviable(resultado: Resultado): string {
    return resultado.motivo === 'articulo_sin_ruta'
        ? 'El artículo no tiene hoja de ruta configurada.'
        : 'El pedido no llega a la fecha límite pedida.';
}
</script>

<template>
    <Head title="Simulador Predictivo" />

    <AppLayout title="Simulador Predictivo">
        <div class="space-y-6 p-6">
            <header>
                <h1 class="text-2xl font-semibold text-pine">Simulador Predictivo</h1>
                <p class="mt-1 text-sm text-steel">
                    Simulá un pedido urgente con prioridad 1 y mirá el efecto dominó sobre el
                    cronograma. No modifica ninguna orden real.
                </p>
            </header>

            <section class="rounded-lg border border-hairline bg-white p-4">
                <form class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4" @submit.prevent="simular">
                    <label class="flex flex-col gap-1 text-sm text-steel">
                        Artículo
                        <select
                            v-model="form.articulo_id"
                            class="rounded-md border border-hairline px-2 py-1.5 text-sm"
                        >
                            <option value="">Elegí un producto</option>
                            <option
                                v-for="producto in productos"
                                :key="producto.id"
                                :value="producto.id"
                            >
                                {{ producto.descripcion }}
                            </option>
                        </select>
                        <span v-if="form.errors.articulo_id" class="text-xs text-oxide">
                            {{ form.errors.articulo_id }}
                        </span>
                    </label>

                    <label class="flex flex-col gap-1 text-sm text-steel">
                        Cantidad
                        <input
                            v-model="form.cantidad"
                            type="number"
                            min="1"
                            step="1"
                            class="rounded-md border border-hairline px-2 py-1.5 text-sm"
                        />
                        <span v-if="form.errors.cantidad" class="text-xs text-oxide">
                            {{ form.errors.cantidad }}
                        </span>
                    </label>

                    <label class="flex flex-col gap-1 text-sm text-steel">
                        Fecha límite
                        <input
                            v-model="form.fecha_limite"
                            type="date"
                            class="rounded-md border border-hairline px-2 py-1.5 text-sm"
                        />
                        <span v-if="form.errors.fecha_limite" class="text-xs text-oxide">
                            {{ form.errors.fecha_limite }}
                        </span>
                    </label>

                    <div class="flex items-end">
                        <button
                            type="submit"
                            data-testid="simular"
                            class="rounded-md bg-teja px-4 py-1.5 text-sm font-medium text-white hover:bg-teja-deep disabled:opacity-60"
                            :disabled="form.processing"
                        >
                            Ejecutar simulación
                        </button>
                    </div>
                </form>
            </section>

            <section
                v-if="resultado === null"
                class="rounded-lg border border-dashed border-hairline bg-workshop px-6 py-10 text-center"
            >
                <p class="text-sm text-steel">
                    Elegí un artículo, una cantidad y una fecha límite para ver el impacto.
                </p>
            </section>

            <template v-else>
                <section
                    data-testid="resultado"
                    class="rounded-lg border p-4"
                    :class="
                        resultado.viable
                            ? 'border-pine/30 bg-pine-wash'
                            : 'border-oxide/40 bg-oxide/5'
                    "
                >
                    <p
                        class="text-sm font-semibold"
                        :class="resultado.viable ? 'text-pine' : 'text-oxide'"
                    >
                        {{ resultado.viable ? 'Escenario viable' : 'Escenario inviable' }}
                    </p>
                    <p class="mt-1 text-sm text-steel">
                        <template v-if="resultado.viable">
                            El pedido se proyecta para el
                            {{ resultado.pedido.fecha_fin_proyectada }}.
                        </template>
                        <template v-else>
                            {{ motivoInviable(resultado) }}
                        </template>
                    </p>
                </section>

                <section class="rounded-lg border border-hairline bg-white p-4">
                    <h2 class="text-sm font-semibold tracking-wider text-steel uppercase">
                        Órdenes que se retrasarían
                    </h2>

                    <p v-if="resultado.retrasos.length === 0" class="mt-3 text-sm text-steel">
                        No se retrasa ninguna orden existente.
                    </p>

                    <div v-else class="mt-3 overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr
                                    class="border-b border-hairline text-xs tracking-wider text-steel uppercase"
                                >
                                    <th class="px-2 py-2 font-medium">Orden</th>
                                    <th class="px-2 py-2 font-medium">Producto</th>
                                    <th class="px-2 py-2 font-medium">Entrega</th>
                                    <th class="px-2 py-2 font-medium">Días de retraso</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="retraso in resultado.retrasos"
                                    :key="retraso.orden_id"
                                    data-testid="fila-retraso"
                                    class="border-b border-hairline/60"
                                >
                                    <td class="num px-2 py-2 text-ink">#{{ retraso.orden_id }}</td>
                                    <td class="px-2 py-2 text-steel">{{ retraso.producto }}</td>
                                    <td class="num px-2 py-2 text-steel">
                                        {{ retraso.fecha_entrega ?? '—' }}
                                    </td>
                                    <td class="num px-2 py-2 font-medium text-oxide">
                                        {{ retraso.dias_retraso }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p class="mt-3 text-sm text-steel">
                            Impacto acumulado: {{ resultado.impacto_proyectado }} días.
                        </p>
                    </div>
                </section>
            </template>
        </div>
    </AppLayout>
</template>
