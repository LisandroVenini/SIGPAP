<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

interface Producto {
    id: number;
    codigo_sku: string;
    descripcion: string;
    unidad: string | null;
    etapas: number;
}

const props = defineProps<{
    productos: Producto[];
    filtros: { buscar?: string | null };
}>();

const busqueda = ref(props.filtros.buscar ?? '');

function aplicarFiltros(): void {
    router.get(
        '/produccion/hoja-de-ruta',
        { buscar: busqueda.value || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}
</script>

<template>
    <AppLayout title="Hoja de Ruta">
        <Head title="Hoja de Ruta" />

        <div class="flex flex-col gap-4">
            <div class="flex flex-col gap-1">
                <h2 class="text-2xl font-semibold tracking-tight text-ink">
                    Hoja de ruta de productos
                </h2>
                <p class="text-sm text-steel">
                    Etapas que recorre cada producto terminado fabricado, con su tiempo estimado por
                    unidad. Es la base del cronograma y de las órdenes de producción.
                </p>
            </div>

            <div class="rounded-panel border border-hairline bg-surface p-4 shadow-diffused">
                <form class="flex flex-wrap items-end gap-2" @submit.prevent="aplicarFiltros">
                    <div class="flex flex-1 flex-col gap-1">
                        <label for="buscar" class="text-xs font-medium text-steel"
                            >Buscar producto</label
                        >
                        <input
                            id="buscar"
                            v-model="busqueda"
                            type="text"
                            placeholder="SKU, descripción o tipo..."
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
                                    Producto
                                </th>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Unidad
                                </th>
                                <th
                                    class="px-4 py-2.5 text-right text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Etapas
                                </th>
                                <th
                                    class="px-4 py-2.5 text-right text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Hoja de ruta
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-hairline">
                            <tr
                                v-for="producto in productos"
                                :key="producto.id"
                                class="hover:bg-teja-wash/30"
                            >
                                <td class="px-4 py-2.5 font-mono text-xs font-medium text-ink">
                                    {{ producto.codigo_sku }}
                                </td>
                                <td class="px-4 py-2.5 text-sm text-ink">
                                    {{ producto.descripcion }}
                                </td>
                                <td class="px-4 py-2.5 text-xs text-steel">
                                    {{ producto.unidad ?? '—' }}
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono text-xs text-steel">
                                    {{ producto.etapas }}
                                </td>
                                <td class="px-4 py-2.5 text-right text-xs">
                                    <Link
                                        :href="`/produccion/hoja-de-ruta/${producto.id}`"
                                        class="font-medium text-teja hover:underline"
                                    >
                                        Gestionar
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="productos.length === 0"
                    class="px-4 py-10 text-center text-sm text-steel"
                >
                    No hay productos terminados de origen Fabricado en el catálogo.
                </div>
            </div>
        </div>
    </AppLayout>
</template>
