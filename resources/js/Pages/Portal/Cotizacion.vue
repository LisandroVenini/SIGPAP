<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

interface LineaSolicitud {
    articulo_id: number;
    articulo: string;
    cantidad_solicitada: number;
}

interface SolicitudPortal {
    solicitud_id: number;
    proveedor: string;
    fecha_vencimiento: string;
    lineas: LineaSolicitud[];
}

const props = defineProps<{
    solicitud?: SolicitudPortal;
    token: string;
    enviada?: boolean;
}>();

const form = useForm({
    lineas: (props.solicitud?.lineas ?? []).map((linea) => ({
        articulo_id: linea.articulo_id,
        precio_unitario: '',
        cantidad_ofertada: String(linea.cantidad_solicitada),
        fecha_prometida: '',
    })),
});

function submit(): void {
    form.post(`/portal/cotizaciones/${props.token}`);
}

function errorPara(indice: number, campo: string): string | undefined {
    const errores = form.errors as Record<string, string | undefined>;

    return errores[`lineas.${indice}.${campo}`];
}
</script>

<template>
    <div class="flex min-h-[100dvh] items-center justify-center bg-canvas px-4 py-10">
        <Head :title="props.enviada ? 'Cotización enviada' : 'Solicitud de cotización'" />

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

                <section v-if="props.enviada" class="flex flex-col gap-5">
                    <div
                        class="rounded-control border border-pine/20 bg-pine-wash px-4 py-3 text-sm text-pine"
                    >
                        Cotización enviada correctamente.
                    </div>
                    <p class="text-sm leading-relaxed text-steel">
                        Gracias por responder. El enlace quedó deshabilitado para evitar envíos
                        duplicados.
                    </p>
                </section>

                <section v-else-if="props.solicitud" class="flex flex-col gap-6">
                    <div class="flex flex-col gap-1">
                        <h2 class="text-xl font-semibold tracking-tight text-ink">
                            Solicitud de cotización
                        </h2>
                        <p class="text-sm text-steel">
                            {{ props.solicitud.proveedor }} · Vence el
                            {{ props.solicitud.fecha_vencimiento }}
                        </p>
                    </div>

                    <form
                        class="flex flex-col gap-5"
                        data-testid="cotizacion-form"
                        @submit.prevent="submit"
                    >
                        <div
                            v-for="(linea, indice) in form.lineas"
                            :key="linea.articulo_id"
                            class="rounded-control border border-hairline p-4"
                        >
                            <div class="mb-3 flex flex-wrap items-baseline justify-between gap-2">
                                <span class="text-sm font-medium text-ink">{{
                                    props.solicitud?.lineas[indice]?.articulo
                                }}</span>
                                <span class="text-xs text-steel">
                                    Cantidad solicitada:
                                    <span class="font-mono text-ink tabular-nums">{{
                                        props.solicitud?.lineas[indice]?.cantidad_solicitada
                                    }}</span>
                                </span>
                            </div>

                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                <div class="flex flex-col gap-1">
                                    <label
                                        :for="`precio-${indice}`"
                                        class="text-xs font-medium text-steel"
                                        >Precio unitario</label
                                    >
                                    <input
                                        :id="`precio-${indice}`"
                                        v-model="linea.precio_unitario"
                                        :data-testid="`precio-${indice}`"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        required
                                        class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                                    />
                                    <p
                                        v-if="errorPara(indice, 'precio_unitario')"
                                        class="text-xs text-oxide"
                                    >
                                        {{ errorPara(indice, 'precio_unitario') }}
                                    </p>
                                </div>

                                <div class="flex flex-col gap-1">
                                    <label
                                        :for="`cantidad-${indice}`"
                                        class="text-xs font-medium text-steel"
                                        >Cantidad ofertada</label
                                    >
                                    <input
                                        :id="`cantidad-${indice}`"
                                        v-model="linea.cantidad_ofertada"
                                        :data-testid="`cantidad-${indice}`"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        required
                                        class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                                    />
                                    <p
                                        v-if="errorPara(indice, 'cantidad_ofertada')"
                                        class="text-xs text-oxide"
                                    >
                                        {{ errorPara(indice, 'cantidad_ofertada') }}
                                    </p>
                                </div>

                                <div class="flex flex-col gap-1">
                                    <label
                                        :for="`fecha-${indice}`"
                                        class="text-xs font-medium text-steel"
                                        >Fecha prometida</label
                                    >
                                    <input
                                        :id="`fecha-${indice}`"
                                        v-model="linea.fecha_prometida"
                                        :data-testid="`fecha-${indice}`"
                                        type="date"
                                        required
                                        class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                                    />
                                    <p
                                        v-if="errorPara(indice, 'fecha_prometida')"
                                        class="text-xs text-oxide"
                                    >
                                        {{ errorPara(indice, 'fecha_prometida') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end border-t border-hairline pt-4">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="rounded-control bg-teja px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-teja-deep active:scale-[0.98] disabled:opacity-60"
                            >
                                {{ form.processing ? 'Enviando…' : 'Enviar cotización' }}
                            </button>
                        </div>
                    </form>
                </section>
            </div>
        </main>
    </div>
</template>
