<script setup lang="ts">
import { computed } from 'vue';
import type { FilaCronograma, MarcaDeAbastecimiento, Segmento } from './gantt';
import { barrasPorDia, claseDeBarra, columnasDe, posicionarMarca } from './gantt';

const props = defineProps<{
    filas: FilaCronograma[];
    desde: string;
    dias: number;
    hoy: string;
    marcas?: MarcaDeAbastecimiento[];
}>();

const emit = defineEmits<{ reordenar: [fila: FilaCronograma] }>();

const columnas = computed(() => columnasDe(props.desde, props.dias, props.hoy));

const indiceHoy = computed(() => columnas.value.findIndex((columna) => columna.esHoy));

const marcasVisibles = computed(() =>
    (props.marcas ?? [])
        .map((marca) => {
            const posicion = posicionarMarca(marca, props.desde, props.dias);

            return posicion === null ? null : { ...marca, ...posicion };
        })
        .filter((marca): marca is NonNullable<typeof marca> => marca !== null),
);

const estiloGrilla = computed(() => ({
    gridTemplateColumns: `repeat(${props.dias}, minmax(44px, 1fr))`,
    minWidth: `${props.dias * 44}px`,
}));

function barrasDe(
    fila: FilaCronograma,
): Array<{ segmento: Segmento; columna: number; span: number; fecha: string; horario: string }> {
    return fila.etapas.flatMap((segmento) =>
        barrasPorDia(segmento, props.desde, props.dias).map((barra) => ({
            segmento,
            columna: barra.columna,
            span: 1,
            fecha: barra.fecha,
            horario: barra.horario,
        })),
    );
}

function claseDeEstado(estado: string, conflicto: boolean): string {
    return claseDeBarra(estado, conflicto);
}

function claseBadge(estado: string): string {
    if (estado === 'En Curso') {
        return 'bg-teja-wash text-teja-deep';
    }

    if (estado === 'Pospuesta') {
        return 'bg-oxide/10 text-oxide';
    }

    if (estado === 'Asignada') {
        return 'bg-pine-wash text-pine';
    }

    return 'bg-workshop text-steel';
}

function etiquetaDeBarra(fila: FilaCronograma, segmento: Segmento, horario: string): string {
    const rango =
        horario !== ''
            ? `${segmento.inicio} → ${segmento.fin} · ${horario}`
            : `${segmento.inicio} → ${segmento.fin} · ${segmento.dias} d`;

    if (fila.estado === 'En Curso') {
        return `${segmento.etapa} · ${rango} · ${fila.avance}% de avance`;
    }

    return `${segmento.etapa} · ${rango}`;
}
</script>

<template>
    <div class="overflow-x-auto">
        <div class="min-w-full" :style="{ minWidth: `calc(16rem + ${dias * 44}px)` }">
            <div class="flex border-b border-hairline bg-workshop">
                <div
                    class="w-64 shrink-0 px-4 py-2 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                >
                    Orden de producción / Artículo
                </div>
                <div class="grid flex-1 border-l border-hairline" :style="estiloGrilla">
                    <div
                        v-for="columna in columnas"
                        :key="columna.fecha"
                        class="border-r border-hairline px-1 py-1.5 text-center"
                        :class="columna.esFinDeSemana ? 'text-clay' : 'text-steel'"
                    >
                        <span class="block text-[0.625rem] font-medium tracking-wider uppercase">
                            {{ columna.etiqueta }}
                        </span>
                        <span
                            class="num block text-xs"
                            :class="columna.esHoy ? 'font-bold text-teja' : 'text-ink'"
                        >
                            {{ columna.numero }}
                        </span>
                    </div>
                </div>
            </div>

            <div v-for="fila in filas" :key="fila.orden_id">
                <div
                    class="flex border-b border-hairline/60 hover:bg-teja-wash/10"
                    :class="fila.conflicto_entrega ? 'bg-oxide/[0.03]' : ''"
                    :data-testid="`fila-orden-${fila.orden_id}`"
                >
                    <div class="flex w-64 shrink-0 items-center gap-2 px-4 py-2.5">
                        <button
                            type="button"
                            :data-testid="`reordenar-orden-${fila.orden_id}`"
                            :disabled="!fila.reubicable"
                            :aria-label="
                                fila.reubicable
                                    ? 'Reordenar prioridad'
                                    : 'En curso: reubicación bloqueada'
                            "
                            :title="
                                fila.reubicable
                                    ? 'Reordenar prioridad'
                                    : 'En curso: reubicación bloqueada'
                            "
                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-control text-steel transition-colors hover:bg-workshop hover:text-ink disabled:cursor-not-allowed disabled:text-clay/50"
                            @click="emit('reordenar', fila)"
                        >
                            <svg
                                v-if="fila.reubicable"
                                class="h-4 w-4"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                                aria-hidden="true"
                            >
                                <circle cx="7" cy="5" r="1.3" />
                                <circle cx="13" cy="5" r="1.3" />
                                <circle cx="7" cy="10" r="1.3" />
                                <circle cx="13" cy="10" r="1.3" />
                                <circle cx="7" cy="15" r="1.3" />
                                <circle cx="13" cy="15" r="1.3" />
                            </svg>
                            <svg
                                v-else
                                class="h-4 w-4"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                                aria-hidden="true"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M10 1a4 4 0 0 0-4 4v3H5.5A1.5 1.5 0 0 0 4 9.5v7A1.5 1.5 0 0 0 5.5 18h9a1.5 1.5 0 0 0 1.5-1.5v-7A1.5 1.5 0 0 0 14.5 8H14V5a4 4 0 0 0-4-4Zm2.5 7V5a2.5 2.5 0 0 0-5 0v3h5Z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </button>

                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5">
                                <span class="num text-[0.6875rem] font-semibold text-teja-deep"
                                    >#{{ fila.orden_id }}</span
                                >
                                <span
                                    class="inline-flex rounded-full px-1.5 py-0.5 text-[0.625rem] font-medium"
                                    :class="claseBadge(fila.estado)"
                                >
                                    {{ fila.estado }}
                                </span>
                            </div>
                            <p class="truncate text-xs font-medium text-ink" :title="fila.producto">
                                {{ fila.producto }}
                            </p>
                            <p class="num text-[0.625rem] text-steel">
                                {{ fila.cantidad_requerida }} u
                                <template v-if="fila.etapa_actual">
                                    · {{ fila.etapa_actual }}
                                </template>
                            </p>
                        </div>
                    </div>

                    <div class="relative flex-1 border-l border-hairline">
                        <div
                            v-if="indiceHoy >= 0"
                            class="pointer-events-none absolute inset-y-0 z-20 border-l border-dashed border-teja/50"
                            :style="{ left: `${(indiceHoy / dias) * 100}%` }"
                        ></div>

                        <div
                            v-for="marca in marcasVisibles"
                            :key="`${marca.fecha}-${marca.etiqueta}`"
                            class="pointer-events-none absolute inset-y-0 z-10 border-l"
                            :class="
                                marca.tono === 'riesgo' ? 'border-oxide/60' : 'border-amber-400/70'
                            "
                            :style="{ left: `${marca.left}%` }"
                            :title="marca.etiqueta"
                            :data-testid="`marca-${marca.fecha}`"
                        ></div>

                        <div class="grid h-full" :style="estiloGrilla">
                            <div
                                v-for="columna in columnas"
                                :key="columna.fecha"
                                data-testid="celda-dia"
                                class="border-r border-hairline/40"
                                :class="columna.esFinDeSemana ? 'bg-canvas/60' : ''"
                                :style="{ gridRow: 1 }"
                            ></div>

                            <div
                                v-for="barra in barrasDe(fila)"
                                :key="`${barra.segmento.etapa}-${barra.fecha}`"
                                class="relative z-10 my-1.5 flex h-7 items-center justify-between gap-1 overflow-hidden rounded px-1.5 text-[0.625rem] font-medium"
                                :class="claseDeEstado(fila.estado, fila.conflicto_entrega)"
                                :style="{
                                    gridRow: 1,
                                    gridColumn: `${barra.columna} / span ${barra.span}`,
                                }"
                                :title="etiquetaDeBarra(fila, barra.segmento, barra.horario)"
                                :data-testid="`barra-orden-${fila.orden_id}`"
                            >
                                <span class="truncate">{{ barra.segmento.etapa }}</span>
                                <span
                                    v-if="fila.estado === 'En Curso'"
                                    class="num shrink-0 opacity-90"
                                    >{{ fila.avance }}%</span
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
