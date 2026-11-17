<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

interface Parametro {
    id: number;
    nombre: string;
    valor: string;
    categoria: string;
}

type SharedProps = {
    flash: { success: string | null };
};

const props = defineProps<{
    parametros: Record<string, Parametro[]>;
    puedeEditar: boolean;
}>();

const page = usePage<SharedProps>();

const form = useForm({
    parametros: Object.fromEntries(
        Object.values(props.parametros)
            .flat()
            .map((parametro) => [parametro.nombre, parametro.valor]),
    ) as Record<string, string>,
});

interface ReglaNumerica {
    min: number;
    max?: number;
    step: number;
}

const PARAMETROS_NUMERICOS: Record<string, ReglaNumerica> = {
    prod_tolerancia_desvio_dias: { min: 0, step: 1 },
    prod_umbral_calidad: { min: 0, max: 100, step: 0.01 },
    compras_vigencia_cotizacion_dias: { min: 1, step: 1 },
    compras_minimo_cotizaciones: { min: 1, step: 1 },
    compras_umbral_aprobacion_gerente: { min: 0, step: 0.01 },
    compras_ranking_peso_perfecta: { min: 0, max: 1, step: 0.01 },
    compras_ranking_peso_retraso: { min: 0, max: 1, step: 0.01 },
    compras_ranking_peso_faltante: { min: 0, max: 1, step: 0.01 },
    compras_ranking_peso_rotura: { min: 0, max: 1, step: 0.01 },
    compras_ranking_ventana_meses: { min: 1, max: 60, step: 1 },
    compras_multicriterio_peso_precio: { min: 0, max: 1, step: 0.01 },
    compras_multicriterio_peso_entrega: { min: 0, max: 1, step: 0.01 },
    compras_multicriterio_peso_confiabilidad: { min: 0, max: 1, step: 0.01 },
    compras_multicriterio_confiabilidad_sin_historial: { min: 0, max: 100, step: 0.01 },
    compras_multicriterio_penalizar_cobertura: { min: 0, max: 1, step: 1 },
    predictivo_intervalo_recalculo_min: { min: 1, max: 1440, step: 1 },
    predictivo_ventana_entrenamiento_meses: { min: 1, max: 60, step: 1 },
    predictivo_confianza_minima: { min: 0, max: 100, step: 0.01 },
    seguridad_expiracion_sesion_min: { min: 1, max: 1440, step: 1 },
    seguridad_intentos_fallidos: { min: 1, max: 20, step: 1 },
};

function reglaNumerica(nombre: string): ReglaNumerica | null {
    return PARAMETROS_NUMERICOS[nombre] ?? null;
}

function humanize(nombre: string): string {
    const texto = nombre.replace(/_/g, ' ');

    return texto.charAt(0).toUpperCase() + texto.slice(1);
}

function submit(): void {
    form.put('/usuarios/configuracion', { preserveScroll: true });
}
</script>

<template>
    <AppLayout title="Configuración del Sistema">
        <Head title="Configuración del Sistema" />

        <div class="mx-auto flex w-full max-w-4xl flex-col gap-6">
            <div class="flex flex-col gap-1">
                <h2 class="text-2xl font-semibold tracking-tight text-ink">
                    Parámetros globales de la fábrica
                </h2>
                <p class="text-sm text-steel">
                    Los cambios afectan a todos los módulos y quedan registrados en auditoría.
                </p>
            </div>

            <div
                v-if="page.props.flash.success"
                class="rounded-control border border-pine/20 bg-pine-wash px-4 py-2.5 text-sm text-pine"
            >
                {{ page.props.flash.success }}
            </div>

            <div
                v-if="form.hasErrors"
                class="rounded-control border border-oxide/20 bg-oxide/5 px-4 py-2.5 text-sm text-oxide"
            >
                {{ form.errors.parametros ?? 'Revisá los valores ingresados.' }}
            </div>

            <div
                v-if="!props.puedeEditar"
                class="rounded-control border border-amber/20 bg-amber-wash px-4 py-2.5 text-sm text-amber"
            >
                Solo lectura: únicamente un Administrador puede modificar estos valores.
            </div>

            <form class="flex flex-col gap-5" @submit.prevent="submit">
                <section
                    v-for="(parametrosDeCategoria, categoria) in props.parametros"
                    :key="categoria"
                    class="rounded-panel border border-hairline bg-surface p-6 shadow-diffused"
                >
                    <h3 class="border-b border-hairline pb-3 text-lg font-semibold text-ink">
                        {{ categoria }}
                    </h3>

                    <div class="mt-4 grid grid-cols-1 gap-x-6 gap-y-4 md:grid-cols-2">
                        <div
                            v-for="parametro in parametrosDeCategoria"
                            :key="parametro.id"
                            class="flex flex-col gap-1.5"
                        >
                            <label :for="parametro.nombre" class="text-xs font-medium text-steel">
                                {{ humanize(parametro.nombre) }}
                            </label>
                            <input
                                :id="parametro.nombre"
                                v-model="form.parametros[parametro.nombre]"
                                :type="reglaNumerica(parametro.nombre) ? 'number' : 'text'"
                                :min="reglaNumerica(parametro.nombre)?.min"
                                :max="reglaNumerica(parametro.nombre)?.max"
                                :step="reglaNumerica(parametro.nombre)?.step"
                                :disabled="!props.puedeEditar"
                                class="w-full rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink transition-all focus:border-teja focus:ring-2 focus:ring-teja/20 focus:outline-none disabled:cursor-not-allowed disabled:bg-workshop/40"
                            />
                            <span class="text-[11px] text-clay">{{ parametro.nombre }}</span>
                        </div>
                    </div>
                </section>

                <div v-if="props.puedeEditar" class="flex justify-end">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-control bg-teja px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-teja-deep active:scale-[0.98] disabled:opacity-60"
                    >
                        {{ form.processing ? 'Guardando…' : 'Guardar cambios' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
