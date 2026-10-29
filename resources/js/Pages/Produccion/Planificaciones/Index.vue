<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

interface Planificacion {
    id: number;
    nombre: string;
    fecha_inicio: string | null;
    fecha_fin: string | null;
    observaciones: string | null;
    ordenes: number;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedPlanificaciones {
    data: Planificacion[];
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
    planificaciones: PaginatedPlanificaciones;
    filtros: { buscar?: string | null };
}>();

const page = usePage<SharedProps>();

const busqueda = ref(props.filtros.buscar ?? '');
const drawerOpen = ref(false);
const modo = ref<'crear' | 'editar'>('crear');
const planificacionEditando = ref<Planificacion | null>(null);

const form = useForm({
    nombre: '',
    fecha_inicio: '',
    fecha_fin: '',
    observaciones: '',
});

function aplicarFiltros(): void {
    router.get(
        '/produccion/planificaciones',
        { buscar: busqueda.value || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function abrirCrear(): void {
    modo.value = 'crear';
    planificacionEditando.value = null;
    form.reset();
    form.clearErrors();
    drawerOpen.value = true;
}

function abrirEditar(planificacion: Planificacion): void {
    modo.value = 'editar';
    planificacionEditando.value = planificacion;
    form.clearErrors();
    form.nombre = planificacion.nombre;
    form.fecha_inicio = planificacion.fecha_inicio ?? '';
    form.fecha_fin = planificacion.fecha_fin ?? '';
    form.observaciones = planificacion.observaciones ?? '';
    drawerOpen.value = true;
}

function cerrarDrawer(): void {
    drawerOpen.value = false;
}

function guardar(): void {
    if (modo.value === 'crear') {
        form.post('/produccion/planificaciones', {
            preserveScroll: true,
            onSuccess: () => {
                drawerOpen.value = false;
            },
        });

        return;
    }

    if (!planificacionEditando.value) {
        return;
    }

    form.put(`/produccion/planificaciones/${planificacionEditando.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            drawerOpen.value = false;
        },
    });
}

function eliminar(planificacion: Planificacion): void {
    if (!window.confirm(`¿Eliminar la planificación "${planificacion.nombre}"?`)) {
        return;
    }

    router.delete(`/produccion/planificaciones/${planificacion.id}`, { preserveScroll: true });
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
    <AppLayout title="Planificaciones de Producción">
        <Head title="Planificaciones de Producción" />

        <div class="flex flex-col gap-4">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div class="flex flex-col gap-1">
                    <h2 class="text-2xl font-semibold tracking-tight text-ink">
                        Planificaciones de producción
                    </h2>
                    <p class="text-sm text-steel">
                        Bloques de planificación que agrupan órdenes de producción por período.
                    </p>
                </div>
                <button
                    type="button"
                    class="rounded-control bg-teja px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-teja-deep active:scale-[0.98]"
                    @click="abrirCrear"
                >
                    Nueva planificación
                </button>
            </div>

            <div
                v-if="page.props.flash.success"
                class="rounded-control border border-pine/20 bg-pine-wash px-4 py-2.5 text-sm text-pine"
            >
                {{ page.props.flash.success }}
            </div>

            <div
                v-if="page.props.errors.planificacion"
                class="rounded-control border border-oxide/20 bg-oxide/5 px-4 py-2.5 text-sm text-oxide"
            >
                {{ page.props.errors.planificacion }}
            </div>

            <div class="rounded-panel border border-hairline bg-surface p-4 shadow-diffused">
                <form class="flex flex-wrap items-end gap-2" @submit.prevent="aplicarFiltros">
                    <div class="flex flex-1 flex-col gap-1">
                        <label for="buscar" class="text-xs font-medium text-steel"
                            >Buscar planificación</label
                        >
                        <input
                            id="buscar"
                            v-model="busqueda"
                            type="text"
                            placeholder="Nombre u observaciones..."
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
                                    Inicio
                                </th>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Fin
                                </th>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Observaciones
                                </th>
                                <th
                                    class="px-4 py-2.5 text-right text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Órdenes
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
                                v-for="planificacion in props.planificaciones.data"
                                :key="planificacion.id"
                                class="hover:bg-teja-wash/30"
                            >
                                <td class="px-4 py-2.5 text-sm font-medium text-ink">
                                    {{ planificacion.nombre }}
                                </td>
                                <td class="px-4 py-2.5 font-mono text-xs text-steel">
                                    {{ planificacion.fecha_inicio ?? '—' }}
                                </td>
                                <td class="px-4 py-2.5 font-mono text-xs text-steel">
                                    {{ planificacion.fecha_fin ?? '—' }}
                                </td>
                                <td class="px-4 py-2.5 text-sm text-steel">
                                    {{ planificacion.observaciones ?? '—' }}
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono text-xs text-steel">
                                    {{ planificacion.ordenes }}
                                </td>
                                <td class="px-4 py-2.5 text-right text-xs">
                                    <button
                                        type="button"
                                        class="font-medium text-teja hover:underline"
                                        @click="abrirEditar(planificacion)"
                                    >
                                        Editar
                                    </button>
                                    <span class="mx-2 text-clay/40">|</span>
                                    <button
                                        type="button"
                                        class="font-medium text-steel hover:text-oxide"
                                        @click="eliminar(planificacion)"
                                    >
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="props.planificaciones.data.length === 0"
                    class="px-4 py-10 text-center text-sm text-steel"
                >
                    No hay planificaciones de producción registradas.
                </div>

                <div
                    v-if="props.planificaciones.total > 0"
                    class="flex flex-wrap items-center justify-between gap-2 border-t border-hairline px-4 py-2.5 text-xs text-steel"
                >
                    <span
                        >Mostrando {{ props.planificaciones.from }}–{{
                            props.planificaciones.to
                        }}
                        de {{ props.planificaciones.total }}</span
                    >
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="link in props.planificaciones.links"
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
                        {{ modo === 'crear' ? 'Nueva planificación' : 'Editar planificación' }}
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

                    <div class="grid grid-cols-2 gap-3">
                        <div class="flex flex-col gap-1.5">
                            <label for="fecha_inicio" class="text-xs font-medium text-steel"
                                >Fecha de inicio</label
                            >
                            <input
                                id="fecha_inicio"
                                v-model="form.fecha_inicio"
                                type="date"
                                required
                                class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                            />
                            <p v-if="form.errors.fecha_inicio" class="text-xs text-oxide">
                                {{ form.errors.fecha_inicio }}
                            </p>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="fecha_fin" class="text-xs font-medium text-steel"
                                >Fecha de fin</label
                            >
                            <input
                                id="fecha_fin"
                                v-model="form.fecha_fin"
                                type="date"
                                required
                                class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                            />
                            <p v-if="form.errors.fecha_fin" class="text-xs text-oxide">
                                {{ form.errors.fecha_fin }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="observaciones" class="text-xs font-medium text-steel"
                            >Observaciones</label
                        >
                        <textarea
                            id="observaciones"
                            v-model="form.observaciones"
                            rows="3"
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        ></textarea>
                        <p v-if="form.errors.observaciones" class="text-xs text-oxide">
                            {{ form.errors.observaciones }}
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
