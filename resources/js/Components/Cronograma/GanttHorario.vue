<script setup lang="ts">
import type { FilaCronograma, Segmento } from './gantt';
import { bloquesDelDia, claseDeBarra } from './gantt';

const props = defineProps<{
    filas: FilaCronograma[];
    fecha: string;
}>();

const horas = Array.from({ length: 24 }, (_, hora) => hora);

function etiquetaHora(hora: number): string {
    return `${String(hora).padStart(2, '0')}:00`;
}

function bloquesDe(
    fila: FilaCronograma,
): Array<{ segmento: Segmento; izquierda: number; ancho: number }> {
    return fila.etapas.flatMap((segmento) =>
        bloquesDelDia(segmento, props.fecha).map((bloque) => ({
            segmento,
            izquierda: bloque.izquierda,
            ancho: bloque.ancho,
        })),
    );
}

function etiqueta(fila: FilaCronograma, segmento: Segmento): string {
    return `${segmento.etapa} · ${segmento.inicio_en ?? segmento.inicio} → ${segmento.fin_en ?? segmento.fin}`;
}
</script>

<template>
    <div class="overflow-x-auto">
        <div class="min-w-full" :style="{ minWidth: `calc(16rem + ${24 * 52}px)` }">
            <div class="flex border-b border-hairline bg-workshop">
                <div
                    class="w-64 shrink-0 px-4 py-2 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                >
                    Orden · {{ fecha }}
                </div>
                <div
                    class="grid flex-1 border-l border-hairline"
                    style="grid-template-columns: repeat(24, minmax(52px, 1fr))"
                >
                    <div
                        v-for="hora in horas"
                        :key="hora"
                        class="border-r border-hairline px-1 py-1.5 text-center text-[0.625rem] text-steel"
                    >
                        {{ etiquetaHora(hora) }}
                    </div>
                </div>
            </div>

            <div v-for="fila in filas" :key="fila.orden_id">
                <div
                    class="flex border-b border-hairline/60"
                    :class="fila.conflicto_entrega ? 'bg-oxide/[0.03]' : ''"
                >
                    <div class="flex w-64 shrink-0 items-center gap-2 px-4 py-2.5">
                        <span class="num text-[0.6875rem] font-semibold text-teja-deep">
                            #{{ fila.orden_id }}
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-xs font-medium text-ink" :title="fila.producto">
                                {{ fila.producto }}
                            </p>
                            <p class="text-[0.625rem] text-steel">{{ fila.estado }}</p>
                        </div>
                    </div>

                    <div class="relative flex-1 border-l border-hairline py-1.5">
                        <div
                            class="absolute inset-0 grid"
                            style="grid-template-columns: repeat(24, minmax(52px, 1fr))"
                        >
                            <div
                                v-for="hora in horas"
                                :key="hora"
                                class="border-r border-hairline/40"
                            ></div>
                        </div>

                        <div
                            v-for="bloque in bloquesDe(fila)"
                            :key="`${bloque.segmento.etapa}-${bloque.izquierda}`"
                            class="relative z-10 mb-1 flex h-7 items-center overflow-hidden rounded px-1 text-[0.625rem] font-medium"
                            :class="claseDeBarra(fila.estado, fila.conflicto_entrega)"
                            :style="{
                                marginLeft: `${bloque.izquierda}%`,
                                width: `${bloque.ancho}%`,
                            }"
                            :title="etiqueta(fila, bloque.segmento)"
                            :data-testid="`bloque-hora-${fila.orden_id}`"
                        >
                            <span class="truncate">{{ bloque.segmento.etapa }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
