<script setup lang="ts">
import { nextTick, ref, watch } from 'vue';
import { estadoDeConfirmacion, resolverConfirmacion } from '../composables/useConfirm';

const estado = estadoDeConfirmacion();
const botonConfirmar = ref<HTMLButtonElement | null>(null);

watch(
    () => estado.abierto,
    async (abierto) => {
        if (!abierto) {
            return;
        }

        await nextTick();
        botonConfirmar.value?.focus();
    },
);

function aceptar(): void {
    resolverConfirmacion(true);
}

function cancelar(): void {
    resolverConfirmacion(false);
}

function alPresionarTecla(evento: KeyboardEvent): void {
    if (evento.key === 'Escape') {
        evento.preventDefault();
        cancelar();
    }
}
</script>

<template>
    <Teleport to="body">
        <div
            v-if="estado.abierto"
            class="fixed inset-0 z-50 flex items-center justify-center bg-ink/30 p-4"
            data-testid="confirm-dialog"
            @click.self="cancelar"
            @keydown="alPresionarTecla"
        >
            <div
                role="alertdialog"
                aria-modal="true"
                aria-labelledby="confirm-dialog-title"
                aria-describedby="confirm-dialog-message"
                class="w-full max-w-md rounded-panel border border-hairline bg-surface p-5 shadow-[0_16px_40px_rgba(34,40,31,0.18)]"
            >
                <h3
                    id="confirm-dialog-title"
                    class="text-base font-semibold tracking-tight text-ink"
                >
                    {{ estado.titulo }}
                </h3>
                <p
                    id="confirm-dialog-message"
                    class="mt-2 text-sm leading-relaxed text-steel"
                    data-testid="confirm-message"
                >
                    {{ estado.mensaje }}
                </p>

                <div class="mt-5 flex justify-end gap-2">
                    <button
                        type="button"
                        data-testid="confirm-cancel"
                        class="rounded-control border border-hairline px-4 py-2 text-sm text-ink transition-colors hover:bg-canvas"
                        @click="cancelar"
                    >
                        {{ estado.cancelar }}
                    </button>
                    <button
                        ref="botonConfirmar"
                        type="button"
                        data-testid="confirm-accept"
                        class="rounded-control px-4 py-2 text-sm font-medium text-white transition-colors active:scale-[0.98]"
                        :class="
                            estado.peligro
                                ? 'bg-oxide hover:bg-oxide/85'
                                : 'bg-teja hover:bg-teja-deep'
                        "
                        @click="aceptar"
                    >
                        {{ estado.confirmar }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
