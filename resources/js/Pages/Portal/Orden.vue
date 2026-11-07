<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface LineaOrden {
    articulo: string;
    cantidad: number;
    precio_pactado: number;
}

interface OrdenPortal {
    numero_oc: string;
    proveedor: string;
    fecha_emision: string;
    lineas: LineaOrden[];
    total: number;
}

const props = defineProps<{
    orden?: OrdenPortal;
    token: string;
    resultado?: string;
}>();

const mostrarRechazo = ref(false);

const form = useForm({
    aceptada: true,
    motivo: '',
});

function confirmar(): void {
    form.aceptada = true;
    form.motivo = '';
    form.post(`/portal/ordenes/${props.token}`);
}

function rechazar(): void {
    form.aceptada = false;
    form.post(`/portal/ordenes/${props.token}`);
}

function formatearMoneda(valor: number): string {
    return new Intl.NumberFormat('es-AR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(valor);
}
</script>

<template>
    <div class="flex min-h-[100dvh] items-center justify-center bg-canvas px-4 py-10">
        <Head :title="props.resultado ? 'Orden de compra respondida' : 'Orden de compra'" />

        <main class="w-full max-w-[760px]">
            <div
                class="rounded-panel border border-hairline bg-surface p-8 shadow-diffused sm:p-10"
            >
                <div class="mb-8 border-b border-hairline pb-5">
                    <h1 class="text-2xl font-bold tracking-tight text-ink">SIGPAP</h1>
                    <p class="mt-1 text-[0.8125rem] text-clay">
                        Confort y Muebles · Portal de proveedores
                    </p>
                </div>

                <section v-if="props.resultado" class="flex flex-col gap-5">
                    <div
                        class="rounded-control border px-4 py-3 text-sm"
                        :class="
                            props.resultado === 'Confirmada'
                                ? 'border-pine/20 bg-pine-wash text-pine'
                                : 'border-oxide/20 bg-oxide/5 text-oxide'
                        "
                    >
                        {{
                            props.resultado === 'Confirmada'
                                ? 'La orden de compra fue confirmada.'
                                : 'La orden de compra fue rechazada.'
                        }}
                    </div>
                    <p class="text-sm leading-relaxed text-steel">
                        Gracias por responder. El enlace quedó deshabilitado para evitar respuestas
                        duplicadas.
                    </p>
                </section>

                <section v-else-if="props.orden" class="flex flex-col gap-6">
                    <div class="flex flex-col gap-1">
                        <h2 class="text-xl font-semibold tracking-tight text-ink">
                            Orden de compra {{ props.orden.numero_oc }}
                        </h2>
                        <p class="text-sm text-steel">
                            {{ props.orden.proveedor }} · Emitida el
                            {{ props.orden.fecha_emision }}
                        </p>
                    </div>

                    <div class="overflow-x-auto rounded-control border border-hairline">
                        <table class="w-full text-left">
                            <thead class="border-b border-hairline bg-workshop">
                                <tr>
                                    <th
                                        class="px-4 py-2 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                    >
                                        Artículo
                                    </th>
                                    <th
                                        class="px-4 py-2 text-right text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                    >
                                        Cantidad
                                    </th>
                                    <th
                                        class="px-4 py-2 text-right text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                    >
                                        Precio pactado
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-hairline">
                                <tr
                                    v-for="(linea, indice) in props.orden.lineas"
                                    :key="indice"
                                    :data-testid="`linea-${indice}`"
                                >
                                    <td class="px-4 py-2.5 text-sm text-ink">
                                        {{ linea.articulo }}
                                    </td>
                                    <td class="px-4 py-2.5 text-right font-mono text-xs text-steel">
                                        {{ linea.cantidad }}
                                    </td>
                                    <td class="px-4 py-2.5 text-right font-mono text-xs text-ink">
                                        $ {{ formatearMoneda(linea.precio_pactado) }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="border-t border-hairline">
                                <tr>
                                    <td
                                        colspan="2"
                                        class="px-4 py-2.5 text-right text-xs font-semibold text-steel"
                                    >
                                        Total
                                    </td>
                                    <td
                                        class="px-4 py-2.5 text-right font-mono text-sm font-semibold text-ink"
                                    >
                                        $ {{ formatearMoneda(props.orden.total) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="flex flex-col gap-3 border-t border-hairline pt-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                :disabled="form.processing"
                                class="hover:bg-pine-deep rounded-control bg-pine px-5 py-2.5 text-sm font-medium text-white transition-colors active:scale-[0.98] disabled:opacity-60"
                                @click="confirmar"
                            >
                                Confirmar
                            </button>
                            <button
                                type="button"
                                :disabled="form.processing"
                                class="rounded-control border border-oxide/30 px-5 py-2.5 text-sm font-medium text-oxide transition-colors hover:bg-oxide/5 active:scale-[0.98] disabled:opacity-60"
                                @click="mostrarRechazo = true"
                            >
                                Rechazar
                            </button>
                        </div>

                        <form
                            v-if="mostrarRechazo"
                            class="flex flex-col gap-2 rounded-control border border-hairline p-4"
                            data-testid="rechazo-form"
                            @submit.prevent="rechazar"
                        >
                            <label for="motivo" class="text-xs font-medium text-steel"
                                >Motivo del rechazo</label
                            >
                            <textarea
                                id="motivo"
                                v-model="form.motivo"
                                data-testid="motivo"
                                rows="3"
                                required
                                class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                            ></textarea>
                            <p v-if="form.errors.motivo" class="text-xs text-oxide">
                                {{ form.errors.motivo }}
                            </p>
                            <div class="flex justify-end">
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="rounded-control bg-oxide px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-oxide/90 active:scale-[0.98] disabled:opacity-60"
                                >
                                    Enviar rechazo
                                </button>
                            </div>
                        </form>
                    </div>
                </section>
            </div>
        </main>
    </div>
</template>
