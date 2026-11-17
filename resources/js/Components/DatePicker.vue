<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import {
    DIAS_SEMANA,
    diasDelMes,
    entreRango,
    formatearFecha,
    hoyISO,
    mesAnterior,
    mesSiguiente,
    nombreMes,
    parsearFecha,
    partesDeFecha,
    primerDiaSemana,
} from '../lib/fechas';

defineOptions({ inheritAttrs: false });

const props = withDefaults(
    defineProps<{
        modelValue?: string | null;
        min?: string | null;
        max?: string | null;
        disabled?: boolean;
        invalid?: boolean;
        placeholder?: string;
    }>(),
    {
        modelValue: '',
        min: null,
        max: null,
        disabled: false,
        invalid: false,
        placeholder: 'dd/mm/aaaa',
    },
);

const emit = defineEmits<{ 'update:modelValue': [value: string] }>();

const abierto = ref(false);
const texto = ref(formatearFecha(props.modelValue));
const fechaHoy = hoyISO();
const inputEl = ref<HTMLInputElement | null>(null);
const contenedorEl = ref<HTMLElement | null>(null);
const posicion = ref<{ top: number; left: number } | null>(null);
const mesVisible = ref(partesDeFecha(props.modelValue || props.min || fechaHoy));

// Focusing the input opens the calendar; this guard prevents closing it from
// immediately reopening it when the focus is restored programmatically.
let ignorarProximoFoco = false;

watch(
    () => props.modelValue,
    (valor) => {
        texto.value = formatearFecha(valor);
    },
);

const dias = computed(() => {
    const { anio, mes } = mesVisible.value;
    const delMes = diasDelMes(anio, mes);
    const relleno = primerDiaSemana(anio, mes);
    const celdas: Array<string | null> = [
        ...Array.from({ length: relleno }, () => null),
        ...delMes,
    ];

    while (celdas.length % 7 !== 0) {
        celdas.push(null);
    }

    return celdas;
});

const tituloMes = computed(() => {
    const { anio, mes } = mesVisible.value;

    return `${nombreMes(mes)} ${anio}`;
});

function seleccionable(fecha: string): boolean {
    return entreRango(fecha, props.min, props.max);
}

function formatearEntrada(): void {
    const valor = inputEl.value?.value ?? '';
    const iso = parsearFecha(valor);

    if (iso === null || !seleccionable(iso)) {
        return;
    }

    emit('update:modelValue', iso);
    texto.value = formatearFecha(iso);
}

function normalizar(): void {
    texto.value = formatearFecha(props.modelValue);
}

function abrir(): void {
    if (props.disabled) {
        return;
    }

    const base = props.modelValue || props.min || fechaHoy;
    mesVisible.value = partesDeFecha(base);

    const rect = inputEl.value?.getBoundingClientRect();

    if (rect) {
        const ancho = Math.max(rect.width, 288);
        const alto = 320;
        const top =
            rect.bottom + alto > window.innerHeight
                ? Math.max(8, rect.top - alto)
                : rect.bottom + 4;

        posicion.value = {
            top,
            left: Math.max(8, Math.min(rect.left, window.innerWidth - ancho - 8)),
        };
    }

    abierto.value = true;
}

function alternar(): void {
    if (abierto.value) {
        cerrar();

        return;
    }

    abrir();
}

async function cerrar(): Promise<void> {
    abierto.value = false;
    ignorarProximoFoco = true;
    await nextTick();
    inputEl.value?.focus();
}

function alEnfocar(): void {
    if (ignorarProximoFoco) {
        ignorarProximoFoco = false;

        return;
    }

    abrir();
}

function elegir(fecha: string): void {
    if (!seleccionable(fecha)) {
        return;
    }

    emit('update:modelValue', fecha);
    texto.value = formatearFecha(fecha);
    abierto.value = false;
    ignorarProximoFoco = true;
    inputEl.value?.focus();
}

function mover(delta: number): void {
    mesVisible.value =
        delta < 0
            ? mesAnterior(mesVisible.value.anio, mesVisible.value.mes)
            : mesSiguiente(mesVisible.value.anio, mesVisible.value.mes);
}

function irAHoy(): void {
    mesVisible.value = partesDeFecha(fechaHoy);

    if (seleccionable(fechaHoy)) {
        elegir(fechaHoy);
    }
}

function alPresionarTecla(evento: KeyboardEvent): void {
    if (evento.key === 'Escape' && abierto.value) {
        evento.preventDefault();
        void cerrar();
    }
}

function cerrarSiCorresponde(evento: Event): void {
    if (!abierto.value) {
        return;
    }

    if (contenedorEl.value && !contenedorEl.value.contains(evento.target as Node)) {
        abierto.value = false;
    }
}

function cerrarPorDesplazamiento(): void {
    abierto.value = false;
}

onMounted(() => {
    document.addEventListener('pointerdown', cerrarSiCorresponde);
    document.addEventListener('keydown', alPresionarTecla);
    window.addEventListener('scroll', cerrarPorDesplazamiento, true);
    window.addEventListener('resize', cerrarPorDesplazamiento);
});

onBeforeUnmount(() => {
    document.removeEventListener('pointerdown', cerrarSiCorresponde);
    document.removeEventListener('keydown', alPresionarTecla);
    window.removeEventListener('scroll', cerrarPorDesplazamiento, true);
    window.removeEventListener('resize', cerrarPorDesplazamiento);
});
</script>

<template>
    <div ref="contenedorEl" class="relative">
        <div
            class="flex items-stretch overflow-hidden rounded-control border bg-surface transition-shadow focus-within:ring-2 focus-within:ring-teja/40"
            :class="invalid ? 'border-oxide/50' : 'border-hairline'"
        >
            <input
                ref="inputEl"
                v-bind="$attrs"
                v-model="texto"
                type="text"
                inputmode="numeric"
                autocomplete="off"
                :placeholder="placeholder"
                :disabled="disabled"
                class="min-w-0 flex-1 bg-transparent px-3 py-2 text-sm text-ink placeholder:text-clay focus:outline-none disabled:cursor-not-allowed disabled:text-clay"
                @input="formatearEntrada"
                @blur="normalizar"
                @focus="alEnfocar"
            />
            <button
                type="button"
                class="flex w-9 items-center justify-center border-l border-hairline text-steel transition-colors hover:bg-workshop/60 hover:text-ink disabled:cursor-not-allowed disabled:text-clay/60"
                :disabled="disabled"
                :aria-expanded="abierto"
                aria-haspopup="dialog"
                aria-label="Elegir fecha en el calendario"
                @click="alternar"
            >
                <svg
                    class="h-4 w-4"
                    viewBox="0 0 20 20"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.6"
                    aria-hidden="true"
                >
                    <rect x="3" y="4.5" width="14" height="12.5" rx="2" />
                    <path stroke-linecap="round" d="M3 8.5h14M7 3v3M13 3v3" />
                </svg>
            </button>
        </div>

        <div
            v-if="abierto && posicion"
            class="fixed z-50 w-72 rounded-panel border border-hairline bg-surface p-3 shadow-[0_12px_28px_rgba(34,40,31,0.14)]"
            :style="{ top: `${posicion.top}px`, left: `${posicion.left}px` }"
            role="dialog"
            aria-label="Calendario"
            :data-testid="'calendario'"
        >
            <div class="flex items-center justify-between gap-2">
                <button
                    type="button"
                    class="flex h-7 w-7 items-center justify-center rounded-control text-steel transition-colors hover:bg-workshop hover:text-ink"
                    aria-label="Mes anterior"
                    @click="mover(-1)"
                >
                    ‹
                </button>
                <span class="text-xs font-semibold tracking-wide text-ink capitalize">
                    {{ tituloMes }}
                </span>
                <button
                    type="button"
                    class="flex h-7 w-7 items-center justify-center rounded-control text-steel transition-colors hover:bg-workshop hover:text-ink"
                    aria-label="Mes siguiente"
                    @click="mover(1)"
                >
                    ›
                </button>
            </div>

            <div class="mt-2 grid grid-cols-7 gap-0.5 text-center">
                <span
                    v-for="dia in DIAS_SEMANA"
                    :key="dia"
                    class="py-1 text-[0.625rem] font-semibold tracking-wider text-clay"
                >
                    {{ dia }}
                </span>

                <template v-for="(fecha, indice) in dias" :key="fecha ?? `vacio-${indice}`">
                    <span v-if="fecha === null"></span>
                    <button
                        v-else
                        type="button"
                        class="num flex h-8 items-center justify-center rounded-control text-xs transition-colors"
                        :class="[
                            fecha === props.modelValue
                                ? 'bg-teja font-semibold text-white'
                                : seleccionable(fecha)
                                  ? 'text-ink hover:bg-teja-wash'
                                  : 'cursor-not-allowed text-clay/50',
                            fecha === fechaHoy && fecha !== props.modelValue
                                ? 'ring-1 ring-teja/40'
                                : '',
                        ]"
                        :disabled="!seleccionable(fecha)"
                        :data-testid="`dia-${fecha}`"
                        @click="elegir(fecha)"
                    >
                        {{ Number(fecha.slice(8, 10)) }}
                    </button>
                </template>
            </div>

            <div class="mt-2 flex items-center justify-between border-t border-hairline pt-2">
                <span class="num text-[0.625rem] text-steel">{{ fechaHoy }}</span>
                <button
                    type="button"
                    class="rounded-control px-2 py-1 text-[0.6875rem] font-medium text-teja transition-colors hover:bg-teja-wash disabled:cursor-not-allowed disabled:text-clay"
                    :disabled="!seleccionable(fechaHoy)"
                    @click="irAHoy"
                >
                    Hoy
                </button>
            </div>
        </div>
    </div>
</template>
