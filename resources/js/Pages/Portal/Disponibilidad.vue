<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import DatePicker from '@/Components/DatePicker.vue';
import { hoyISO } from '@/lib/fechas';

interface SolicitudDisponibilidad {
    solicitud_id: number;
    proveedor: string;
    articulo: string;
    cantidad_solicitada: number;
    fecha_requerida: string | null;
}

const props = defineProps<{
    solicitud?: SolicitudDisponibilidad;
    token?: string;
    enviada?: boolean;
}>();

const form = useForm({
    cantidad_disponible: String(props.solicitud?.cantidad_solicitada ?? ''),
    precio_unitario: '',
    fecha_prometida: '',
});

function submit(): void {
    form.post(`/portal/disponibilidad/${props.token}`);
}

const hoy = hoyISO();
</script>

<template>
    <div class="flex min-h-[100dvh] items-center justify-center bg-canvas px-4 py-10">
        <Head :title="props.enviada ? 'Disponibilidad enviada' : 'Consulta de disponibilidad'" />

        <div class="w-full max-w-xl rounded-xl border border-hairline bg-white p-6">
            <template v-if="props.enviada">
                <h1 class="text-xl font-semibold text-pine">Respuesta enviada</h1>
                <p class="mt-2 text-sm text-steel">
                    Gracias. Tu respuesta de disponibilidad quedó registrada. El enlace ya no puede
                    reutilizarse.
                </p>
            </template>

            <template v-else-if="props.solicitud">
                <h1 class="text-xl font-semibold text-pine">Consulta de disponibilidad</h1>
                <p class="mt-2 text-sm text-steel">
                    Confort y Muebles consulta por
                    <strong class="text-ink">{{ props.solicitud.articulo }}</strong
                    >.
                </p>

                <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <dt class="text-xs tracking-wider text-steel uppercase">
                            Cantidad solicitada
                        </dt>
                        <dd class="num mt-1 text-ink">{{ props.solicitud.cantidad_solicitada }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs tracking-wider text-steel uppercase">
                            Se necesita antes
                        </dt>
                        <dd class="num mt-1 text-ink">
                            {{ props.solicitud.fecha_requerida ?? '—' }}
                        </dd>
                    </div>
                </dl>

                <form class="mt-6 space-y-4" @submit.prevent="submit">
                    <label class="flex flex-col gap-1 text-sm text-steel">
                        Cantidad disponible (0 si no tenés stock)
                        <input
                            v-model="form.cantidad_disponible"
                            type="number"
                            min="0"
                            step="0.01"
                            data-testid="cantidad-disponible"
                            class="rounded-md border border-hairline px-2 py-1.5 text-sm"
                        />
                        <span v-if="form.errors.cantidad_disponible" class="text-xs text-oxide">
                            {{ form.errors.cantidad_disponible }}
                        </span>
                    </label>

                    <label class="flex flex-col gap-1 text-sm text-steel">
                        Precio unitario
                        <input
                            v-model="form.precio_unitario"
                            type="number"
                            min="0"
                            step="0.01"
                            data-testid="precio-unitario"
                            class="rounded-md border border-hairline px-2 py-1.5 text-sm"
                        />
                        <span v-if="form.errors.precio_unitario" class="text-xs text-oxide">
                            {{ form.errors.precio_unitario }}
                        </span>
                    </label>

                    <label class="flex flex-col gap-1 text-sm text-steel">
                        Fecha prometida de entrega
                        <DatePicker v-model="form.fecha_prometida" :min="hoy" />
                        <span v-if="form.errors.fecha_prometida" class="text-xs text-oxide">
                            {{ form.errors.fecha_prometida }}
                        </span>
                    </label>

                    <button
                        type="submit"
                        data-testid="responder"
                        class="rounded-md bg-teja px-4 py-2 text-sm font-medium text-white hover:bg-teja-deep disabled:opacity-60"
                        :disabled="form.processing"
                    >
                        Enviar respuesta
                    </button>
                </form>
            </template>
        </div>
    </div>
</template>
