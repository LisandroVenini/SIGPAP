<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '../../../Layouts/AppLayout.vue';
import { confirmar } from '@/composables/useConfirm';

interface Unidad {
    id: number;
    nombre: string;
    factor_conversion: number;
    articulos: number;
}

type SharedProps = {
    flash: { success: string | null };
    errors: Record<string, string>;
};

const props = defineProps<{ unidades: Unidad[] }>();

const page = usePage<SharedProps>();

const drawerOpen = ref(false);
const modo = ref<'crear' | 'editar'>('crear');
const unidadEditando = ref<Unidad | null>(null);

const form = useForm({
    nombre: '',
    factor_conversion: '1',
});

function abrirCrear(): void {
    modo.value = 'crear';
    unidadEditando.value = null;
    form.reset();
    form.clearErrors();
    form.factor_conversion = '1';
    drawerOpen.value = true;
}

function abrirEditar(unidad: Unidad): void {
    modo.value = 'editar';
    unidadEditando.value = unidad;
    form.clearErrors();
    form.nombre = unidad.nombre;
    form.factor_conversion = String(unidad.factor_conversion);
    drawerOpen.value = true;
}

function cerrarDrawer(): void {
    drawerOpen.value = false;
}

function guardar(): void {
    if (modo.value === 'crear') {
        form.post('/inventario/unidades-de-medida', {
            preserveScroll: true,
            onSuccess: () => {
                drawerOpen.value = false;
            },
        });

        return;
    }

    if (!unidadEditando.value) {
        return;
    }

    form.put(`/inventario/unidades-de-medida/${unidadEditando.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            drawerOpen.value = false;
        },
    });
}

async function eliminar(unidad: Unidad): Promise<void> {
    const confirmado = await confirmar({
        titulo: 'Eliminar unidad de medida',
        mensaje: `¿Eliminar la unidad "${unidad.nombre}"?`,
        confirmar: 'Eliminar',
        peligro: true,
    });

    if (!confirmado) {
        return;
    }

    router.delete(`/inventario/unidades-de-medida/${unidad.id}`, { preserveScroll: true });
}
</script>

<template>
    <AppLayout title="Unidades de medida">
        <Head title="Unidades de medida" />

        <div class="flex flex-col gap-4">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div class="flex flex-col gap-1">
                    <h2 class="text-2xl font-semibold tracking-tight text-ink">
                        Unidades de medida
                    </h2>
                    <p class="text-sm text-steel">
                        Catálogo de unidades que usan los artículos del inventario.
                    </p>
                </div>
                <button
                    type="button"
                    class="rounded-control bg-teja px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-teja-deep active:scale-[0.98]"
                    @click="abrirCrear"
                >
                    Nueva unidad
                </button>
            </div>

            <div
                v-if="page.props.flash.success"
                class="rounded-control border border-pine/20 bg-pine-wash px-4 py-2.5 text-sm text-pine"
            >
                {{ page.props.flash.success }}
            </div>

            <div
                v-if="page.props.errors.unidad"
                class="rounded-control border border-oxide/20 bg-oxide/5 px-4 py-2.5 text-sm text-oxide"
            >
                {{ page.props.errors.unidad }}
            </div>

            <div
                class="overflow-hidden rounded-panel border border-hairline bg-surface shadow-diffused"
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="border-b border-hairline bg-workshop">
                            <tr>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Nombre
                                </th>
                                <th
                                    class="px-4 py-2.5 text-right text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Factor
                                </th>
                                <th
                                    class="px-4 py-2.5 text-right text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Artículos
                                </th>
                                <th
                                    class="px-4 py-2.5 text-right text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-hairline">
                            <tr
                                v-for="unidad in props.unidades"
                                :key="unidad.id"
                                class="hover:bg-teja-wash/30"
                            >
                                <td class="px-4 py-2.5 text-sm font-medium text-ink">
                                    {{ unidad.nombre }}
                                </td>
                                <td class="num px-4 py-2.5 text-right text-xs text-steel">
                                    {{ unidad.factor_conversion }}
                                </td>
                                <td class="num px-4 py-2.5 text-right text-xs text-steel">
                                    {{ unidad.articulos }}
                                </td>
                                <td class="px-4 py-2.5 text-right text-xs">
                                    <button
                                        type="button"
                                        class="font-medium text-teja hover:underline"
                                        :data-testid="`editar-unidad-${unidad.id}`"
                                        @click="abrirEditar(unidad)"
                                    >
                                        Editar
                                    </button>
                                    <span class="mx-2 text-clay/40">|</span>
                                    <button
                                        type="button"
                                        class="font-medium text-steel hover:text-oxide disabled:cursor-not-allowed disabled:text-clay"
                                        :disabled="unidad.articulos > 0"
                                        :data-testid="`eliminar-unidad-${unidad.id}`"
                                        @click="eliminar(unidad)"
                                    >
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="props.unidades.length === 0"
                    class="px-4 py-10 text-center text-sm text-steel"
                >
                    No hay unidades de medida registradas.
                </div>
            </div>
        </div>

        <div
            v-if="drawerOpen"
            class="fixed inset-0 z-40 flex justify-end bg-ink/20"
            @click.self="cerrarDrawer"
        >
            <aside
                class="flex h-full w-full max-w-[420px] flex-col overflow-y-auto border-l border-hairline bg-surface p-5 shadow-diffused"
            >
                <div class="flex items-center justify-between border-b border-hairline pb-3">
                    <h3 class="text-lg font-semibold text-ink">
                        {{ modo === 'crear' ? 'Nueva unidad' : 'Editar unidad' }}
                    </h3>
                    <button
                        type="button"
                        class="text-sm text-steel hover:text-ink"
                        @click="cerrarDrawer"
                    >
                        Cerrar
                    </button>
                </div>

                <form
                    class="mt-4 flex flex-col gap-4"
                    data-testid="unidad-form"
                    @submit.prevent="guardar"
                >
                    <div class="flex flex-col gap-1.5">
                        <label for="nombre" class="text-xs font-medium text-steel">Nombre</label>
                        <input
                            id="nombre"
                            v-model="form.nombre"
                            type="text"
                            required
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        />
                        <p v-if="form.errors.nombre" class="text-xs text-oxide">
                            {{ form.errors.nombre }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="factor_conversion" class="text-xs font-medium text-steel"
                            >Factor de conversión</label
                        >
                        <input
                            id="factor_conversion"
                            v-model="form.factor_conversion"
                            type="number"
                            min="0.000001"
                            step="0.000001"
                            required
                            class="rounded-control border border-hairline bg-surface px-3 py-2 font-mono text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        />
                        <p v-if="form.errors.factor_conversion" class="text-xs text-oxide">
                            {{ form.errors.factor_conversion }}
                        </p>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-hairline pt-3">
                        <button
                            type="button"
                            class="rounded-control border border-hairline px-4 py-2 text-sm text-ink transition-colors hover:bg-canvas"
                            @click="cerrarDrawer"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="rounded-control bg-teja px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-teja-deep active:scale-[0.98]"
                        >
                            Guardar
                        </button>
                    </div>
                </form>
            </aside>
        </div>
    </AppLayout>
</template>
