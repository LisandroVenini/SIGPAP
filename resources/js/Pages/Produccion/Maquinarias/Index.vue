<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

interface Maquinaria {
    id: number;
    nombre: string;
    tipo: string;
    estado_operativo: string;
    desgaste_acumulado: number;
    planes: number;
    intervenciones: number;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedMaquinarias {
    data: Maquinaria[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
}

type SharedProps = {
    flash: { success: string | null };
    errors: Record<string, string>;
};

const props = defineProps<{
    maquinarias: PaginatedMaquinarias;
    filtros: { buscar?: string | null };
}>();

const page = usePage<SharedProps>();

const busqueda = ref(props.filtros.buscar ?? '');
const drawerOpen = ref(false);
const modo = ref<'crear' | 'editar'>('crear');
const maquinariaEditando = ref<Maquinaria | null>(null);

const form = useForm({
    nombre: '',
    tipo: '',
});

function aplicarFiltros(): void {
    router.get(
        '/produccion/maquinarias',
        { buscar: busqueda.value || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function abrirCrear(): void {
    modo.value = 'crear';
    maquinariaEditando.value = null;
    form.reset();
    form.clearErrors();
    drawerOpen.value = true;
}

function abrirEditar(maquinaria: Maquinaria): void {
    modo.value = 'editar';
    maquinariaEditando.value = maquinaria;
    form.clearErrors();
    form.nombre = maquinaria.nombre;
    form.tipo = maquinaria.tipo;
    drawerOpen.value = true;
}

function cerrarDrawer(): void {
    drawerOpen.value = false;
}

function guardar(): void {
    if (modo.value === 'crear') {
        form.post('/produccion/maquinarias', {
            preserveScroll: true,
            onSuccess: () => {
                drawerOpen.value = false;
            },
        });

        return;
    }

    if (!maquinariaEditando.value) {
        return;
    }

    form.put(`/produccion/maquinarias/${maquinariaEditando.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            drawerOpen.value = false;
        },
    });
}

function eliminar(maquinaria: Maquinaria): void {
    if (!window.confirm(`¿Eliminar la maquinaria "${maquinaria.nombre}"?`)) {
        return;
    }

    router.delete(`/produccion/maquinarias/${maquinaria.id}`, { preserveScroll: true });
}

function claseDeEstado(estado: string): string {
    switch (estado) {
        case 'Disponible':
            return 'bg-pine/10 text-pine border-pine/20';
        case 'En Mantenimiento':
            return 'bg-amber/10 text-amber border-amber/20';
        case 'Bloqueada':
            return 'bg-oxide/10 text-oxide border-oxide/20';
        default:
            return 'bg-workshop text-steel border-hairline';
    }
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
    <AppLayout title="Maquinarias">
        <Head title="Maquinarias" />

        <div class="flex flex-col gap-4">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div class="flex flex-col gap-1">
                    <h2 class="text-2xl font-semibold tracking-tight text-ink">Maquinarias</h2>
                    <p class="text-sm text-steel">
                        Equipamiento de planta y su estado operativo. El mantenimiento gestiona el
                        desgaste acumulado.
                    </p>
                </div>
                <button
                    type="button"
                    class="rounded-control bg-teja px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-teja-deep active:scale-[0.98]"
                    @click="abrirCrear"
                >
                    Nueva maquinaria
                </button>
            </div>

            <div
                v-if="page.props.flash.success"
                class="rounded-control border border-pine/20 bg-pine-wash px-4 py-2.5 text-sm text-pine"
            >
                {{ page.props.flash.success }}
            </div>

            <div
                v-if="page.props.errors.maquinaria"
                class="rounded-control border border-oxide/20 bg-oxide/5 px-4 py-2.5 text-sm text-oxide"
            >
                {{ page.props.errors.maquinaria }}
            </div>

            <div class="rounded-panel border border-hairline bg-surface p-4 shadow-diffused">
                <form class="flex flex-wrap items-end gap-2" @submit.prevent="aplicarFiltros">
                    <div class="flex flex-1 flex-col gap-1">
                        <label for="buscar" class="text-xs font-medium text-steel"
                            >Buscar maquinaria</label
                        >
                        <input
                            id="buscar"
                            v-model="busqueda"
                            type="text"
                            placeholder="Nombre o tipo..."
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
                                    Nombre
                                </th>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Tipo
                                </th>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Estado
                                </th>
                                <th
                                    class="px-4 py-2.5 text-right text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Desgaste
                                </th>
                                <th
                                    class="px-4 py-2.5 text-right text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Planes
                                </th>
                                <th
                                    class="px-4 py-2.5 text-right text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Int.
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
                                v-for="maquinaria in props.maquinarias.data"
                                :key="maquinaria.id"
                                class="hover:bg-teja-wash/30"
                            >
                                <td class="px-4 py-2.5 text-sm font-medium text-ink">
                                    {{ maquinaria.nombre }}
                                </td>
                                <td class="px-4 py-2.5 text-sm text-steel">
                                    {{ maquinaria.tipo }}
                                </td>
                                <td class="px-4 py-2.5 text-sm">
                                    <span
                                        class="inline-flex items-center rounded border px-2 py-0.5 text-xs font-medium"
                                        :class="claseDeEstado(maquinaria.estado_operativo)"
                                    >
                                        {{ maquinaria.estado_operativo }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono text-xs text-steel">
                                    {{ maquinaria.desgaste_acumulado }}
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono text-xs text-steel">
                                    {{ maquinaria.planes }}
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono text-xs text-steel">
                                    {{ maquinaria.intervenciones }}
                                </td>
                                <td class="px-4 py-2.5 text-right text-xs">
                                    <button
                                        type="button"
                                        class="font-medium text-teja hover:underline"
                                        @click="abrirEditar(maquinaria)"
                                    >
                                        Editar
                                    </button>
                                    <span class="mx-2 text-clay/40">|</span>
                                    <button
                                        type="button"
                                        class="font-medium text-steel hover:text-oxide"
                                        @click="eliminar(maquinaria)"
                                    >
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="props.maquinarias.data.length === 0"
                    class="px-4 py-10 text-center text-sm text-steel"
                >
                    No hay maquinarias registradas.
                </div>

                <div
                    v-if="props.maquinarias.total > 0"
                    class="flex flex-wrap items-center justify-between gap-2 border-t border-hairline px-4 py-2.5 text-xs text-steel"
                >
                    <span
                        >Mostrando {{ props.maquinarias.from }}–{{ props.maquinarias.to }} de
                        {{ props.maquinarias.total }}</span
                    >
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="link in props.maquinarias.links"
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
            @click.self="cerrarDrawer"
        >
            <aside
                class="flex h-full w-full max-w-[420px] flex-col overflow-y-auto border-l border-hairline bg-surface p-5 shadow-diffused"
            >
                <div class="flex items-center justify-between border-b border-hairline pb-3">
                    <h3 class="text-lg font-semibold text-ink">
                        {{ modo === 'crear' ? 'Nueva maquinaria' : 'Editar maquinaria' }}
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
                    data-testid="drawer-form"
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
                        <label for="tipo" class="text-xs font-medium text-steel">Tipo</label>
                        <input
                            id="tipo"
                            v-model="form.tipo"
                            type="text"
                            required
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        />
                        <p v-if="form.errors.tipo" class="text-xs text-oxide">
                            {{ form.errors.tipo }}
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
                            :disabled="form.processing"
                            class="rounded-control bg-teja px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-teja-deep active:scale-[0.98] disabled:opacity-60"
                        >
                            {{ form.processing ? 'Guardando…' : 'Guardar' }}
                        </button>
                    </div>
                </form>
            </aside>
        </div>
    </AppLayout>
</template>
