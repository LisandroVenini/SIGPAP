<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

interface Articulo {
    id: number;
    codigo_sku: string;
    descripcion: string;
    stock_fisico: number;
    unidad: string | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedArticulos {
    data: Articulo[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
}

type SharedProps = {
    flash: { success: string | null };
};

const props = defineProps<{
    articulos: PaginatedArticulos;
    motivos: string[];
    filtros: { buscar?: string | null };
}>();

const page = usePage<SharedProps>();

const busqueda = ref(props.filtros.buscar ?? '');
const drawerOpen = ref(false);
const articuloSeleccionado = ref<Articulo | null>(null);

const form = useForm({
    nuevo_stock: '',
    motivo: '',
});

function buscar(): void {
    router.get(
        '/inventario/ajustes',
        { buscar: busqueda.value || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function abrirAjuste(articulo: Articulo): void {
    articuloSeleccionado.value = articulo;
    form.clearErrors();
    form.nuevo_stock = String(articulo.stock_fisico);
    form.motivo = props.motivos[0] ?? '';
    drawerOpen.value = true;
}

function cerrarAjuste(): void {
    drawerOpen.value = false;
}

function guardarAjuste(): void {
    if (!articuloSeleccionado.value) {
        return;
    }

    form.patch(`/inventario/articulos/${articuloSeleccionado.value.id}/ajuste`, {
        preserveScroll: true,
        onSuccess: () => {
            drawerOpen.value = false;
        },
    });
}

function labelDePagina(label: string): string {
    if (label.includes('Previous')) {
        return '‹';
    }

    if (label.includes('Next')) {
        return '›';
    }

    return label;
}
</script>

<template>
    <AppLayout title="Ajuste de Inventario">
        <Head title="Ajuste de Inventario" />

        <div class="flex flex-col gap-4">
            <div class="flex flex-col gap-1">
                <h2 class="text-2xl font-semibold tracking-tight text-ink">Ajuste de inventario</h2>
                <p class="text-sm text-steel">
                    Corrección manual del stock real por rotura, merma o auditoría. El sistema
                    calcula la diferencia, sobrescribe el stock físico y deja un registro inmutable.
                </p>
            </div>

            <div
                v-if="page.props.flash.success"
                class="rounded-control border border-pine/20 bg-pine-wash px-4 py-2.5 text-sm text-pine"
            >
                {{ page.props.flash.success }}
            </div>

            <div class="rounded-panel border border-hairline bg-surface p-4 shadow-diffused">
                <form class="flex flex-wrap items-end gap-2" @submit.prevent="buscar">
                    <div class="flex flex-1 flex-col gap-1">
                        <label for="buscar" class="text-xs font-medium text-steel"
                            >Buscar artículo</label
                        >
                        <input
                            id="buscar"
                            v-model="busqueda"
                            type="text"
                            placeholder="SKU o nombre..."
                            class="w-full rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink placeholder:text-clay/70 focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        />
                    </div>
                    <button
                        type="submit"
                        class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-steel transition-colors hover:text-ink"
                    >
                        Buscar
                    </button>
                </form>
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
                                    SKU
                                </th>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Descripción
                                </th>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Unidad
                                </th>
                                <th
                                    class="px-4 py-2.5 text-right text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Stock actual
                                </th>
                                <th
                                    class="px-4 py-2.5 text-right text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Acción
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-hairline">
                            <tr
                                v-for="articulo in props.articulos.data"
                                :key="articulo.id"
                                class="hover:bg-teja-wash/30"
                            >
                                <td class="px-4 py-2.5 font-mono text-xs font-medium text-ink">
                                    {{ articulo.codigo_sku }}
                                </td>
                                <td class="px-4 py-2.5 text-sm text-ink">
                                    {{ articulo.descripcion }}
                                </td>
                                <td class="px-4 py-2.5 text-xs text-steel">
                                    {{ articulo.unidad ?? '—' }}
                                </td>
                                <td
                                    class="px-4 py-2.5 text-right font-mono text-xs font-medium text-ink"
                                >
                                    {{ articulo.stock_fisico }}
                                </td>
                                <td class="px-4 py-2.5 text-right text-xs">
                                    <button
                                        type="button"
                                        class="font-medium text-teja hover:underline"
                                        @click="abrirAjuste(articulo)"
                                    >
                                        Ajustar
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="props.articulos.data.length === 0"
                    class="px-4 py-10 text-center text-sm text-steel"
                >
                    No se encontraron artículos para el criterio especificado.
                </div>

                <div
                    v-if="props.articulos.total > 0"
                    class="flex flex-wrap items-center justify-between gap-2 border-t border-hairline px-4 py-2.5 text-xs text-steel"
                >
                    <span
                        >Mostrando {{ props.articulos.from }}–{{ props.articulos.to }} de
                        {{ props.articulos.total }}</span
                    >
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="link in props.articulos.links"
                            :key="link.label"
                            :href="link.url ?? '#'"
                            class="rounded px-2 py-0.5"
                            :class="
                                link.active
                                    ? 'bg-workshop font-semibold text-ink'
                                    : 'hover:bg-workshop/50'
                            "
                        >
                            {{ labelDePagina(link.label) }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="drawerOpen"
            class="fixed inset-0 z-40 flex justify-end bg-ink/20"
            @click.self="cerrarAjuste"
        >
            <aside
                class="flex h-full w-full max-w-[420px] flex-col overflow-y-auto border-l border-hairline bg-surface p-5 shadow-diffused"
            >
                <div class="flex items-center justify-between border-b border-hairline pb-3">
                    <h3 class="text-lg font-semibold text-ink">Registrar ajuste</h3>
                    <button
                        type="button"
                        class="text-sm text-steel hover:text-ink"
                        @click="cerrarAjuste"
                    >
                        Cerrar
                    </button>
                </div>

                <div
                    v-if="articuloSeleccionado"
                    class="mt-3 rounded-control border border-hairline bg-workshop/40 px-3 py-2 text-xs text-steel"
                >
                    <div class="font-medium text-ink">{{ articuloSeleccionado.descripcion }}</div>
                    <div class="font-mono">
                        SKU {{ articuloSeleccionado.codigo_sku }} · Stock actual:
                        {{ articuloSeleccionado.stock_fisico }}
                    </div>
                </div>

                <form
                    class="mt-4 flex flex-col gap-4"
                    data-testid="ajuste-form"
                    @submit.prevent="guardarAjuste"
                >
                    <div class="flex flex-col gap-1.5">
                        <label for="nuevo_stock" class="text-xs font-medium text-steel"
                            >Nuevo stock observado</label
                        >
                        <input
                            id="nuevo_stock"
                            v-model="form.nuevo_stock"
                            type="number"
                            min="0"
                            step="0.01"
                            required
                            class="rounded-control border border-hairline bg-surface px-3 py-2 font-mono text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        />
                        <p v-if="form.errors.nuevo_stock" class="text-xs text-oxide">
                            {{ form.errors.nuevo_stock }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="motivo" class="text-xs font-medium text-steel">Motivo</label>
                        <select
                            id="motivo"
                            v-model="form.motivo"
                            required
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        >
                            <option value="" disabled>Seleccioná un motivo</option>
                            <option v-for="motivo in props.motivos" :key="motivo" :value="motivo">
                                {{ motivo }}
                            </option>
                        </select>
                        <p v-if="form.errors.motivo" class="text-xs text-oxide">
                            {{ form.errors.motivo }}
                        </p>
                    </div>

                    <p class="text-[11px] leading-snug text-clay">
                        El sistema calcula la diferencia, sobrescribe el stock físico y guarda un
                        registro inmutable del ajuste. No se permite stock negativo.
                    </p>

                    <div class="flex justify-end gap-2 border-t border-hairline pt-3">
                        <button
                            type="button"
                            class="rounded-control border border-hairline px-4 py-2 text-sm text-ink transition-colors hover:bg-canvas"
                            @click="cerrarAjuste"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-control bg-teja px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-teja-deep active:scale-[0.98] disabled:opacity-60"
                        >
                            {{ form.processing ? 'Guardando…' : 'Registrar ajuste' }}
                        </button>
                    </div>
                </form>
            </aside>
        </div>
    </AppLayout>
</template>
