<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

interface MaquinariaOption {
    id: number;
    nombre: string;
}

interface Plan {
    id: number;
    maquinaria_id: number;
    maquinaria: string;
    umbral_uso: number;
    descripcion: string;
    maquina_bloqueada: boolean;
}

interface Intervencion {
    id: number;
    maquinaria_id: number;
    maquinaria: string;
    fecha: string;
    tipo: string;
    responsable: string;
    observaciones: string | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedPlanes {
    data: Plan[];
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
    planes: PaginatedPlanes;
    intervenciones: Intervencion[];
    maquinarias: MaquinariaOption[];
    filtros: { buscar?: string | null };
}>();

const page = usePage<SharedProps>();

const busqueda = ref(props.filtros.buscar ?? '');
const planDrawerOpen = ref(false);
const planModo = ref<'crear' | 'editar'>('crear');
const planEditando = ref<Plan | null>(null);
const intervencionDrawerOpen = ref(false);
const maquinariaIntervencion = ref<Plan | null>(null);

const planForm = useForm({
    maquinaria_id: '',
    umbral_uso: '',
    descripcion: '',
});

const intervencionForm = useForm({
    maquinaria_id: '',
    fecha: '',
    tipo: '',
    responsable: '',
    observaciones: '',
});

function aplicarFiltros(): void {
    router.get(
        '/produccion/planes-de-mantenimiento',
        { buscar: busqueda.value || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function abrirCrearPlan(): void {
    planModo.value = 'crear';
    planEditando.value = null;
    planForm.reset();
    planForm.clearErrors();
    planDrawerOpen.value = true;
}

function abrirEditarPlan(plan: Plan): void {
    planModo.value = 'editar';
    planEditando.value = plan;
    planForm.clearErrors();
    planForm.maquinaria_id = String(plan.maquinaria_id);
    planForm.umbral_uso = String(plan.umbral_uso);
    planForm.descripcion = plan.descripcion;
    planDrawerOpen.value = true;
}

function cerrarPlanDrawer(): void {
    planDrawerOpen.value = false;
}

function guardarPlan(): void {
    if (planModo.value === 'crear') {
        planForm.post('/produccion/planes-de-mantenimiento', {
            preserveScroll: true,
            onSuccess: () => {
                planDrawerOpen.value = false;
            },
        });

        return;
    }

    if (!planEditando.value) {
        return;
    }

    planForm.put(`/produccion/planes-de-mantenimiento/${planEditando.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            planDrawerOpen.value = false;
        },
    });
}

function abrirIntervencion(plan: Plan): void {
    maquinariaIntervencion.value = plan;
    intervencionForm.reset();
    intervencionForm.clearErrors();
    intervencionForm.maquinaria_id = String(plan.maquinaria_id);
    intervencionForm.fecha = new Date().toISOString().split('T')[0];
    intervencionDrawerOpen.value = true;
}

function cerrarIntervencionDrawer(): void {
    intervencionDrawerOpen.value = false;
}

function guardarIntervencion(): void {
    if (!maquinariaIntervencion.value) {
        return;
    }

    intervencionForm.post(
        `/produccion/maquinarias/${maquinariaIntervencion.value.maquinaria_id}/mantenimientos`,
        {
            preserveScroll: true,
            onSuccess: () => {
                intervencionDrawerOpen.value = false;
            },
        },
    );
}

function eliminarPlan(plan: Plan): void {
    if (!window.confirm(`¿Eliminar el plan de mantenimiento de "${plan.maquinaria}"?`)) {
        return;
    }

    router.delete(`/produccion/planes-de-mantenimiento/${plan.id}`, { preserveScroll: true });
}

function claseDeEstado(bloqueada: boolean): string {
    return bloqueada
        ? 'bg-oxide/10 text-oxide border-oxide/20'
        : 'bg-pine/10 text-pine border-pine/20';
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
    <AppLayout title="Mantenimiento">
        <Head title="Mantenimiento" />

        <div class="flex flex-col gap-6">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div class="flex flex-col gap-1">
                    <h2 class="text-2xl font-semibold tracking-tight text-ink">Mantenimiento</h2>
                    <p class="text-sm text-steel">
                        Planes preventivos por máquina e intervenciones registradas.
                    </p>
                </div>
                <button
                    type="button"
                    class="rounded-control bg-teja px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-teja-deep active:scale-[0.98]"
                    @click="abrirCrearPlan"
                >
                    Nuevo plan
                </button>
            </div>

            <div
                v-if="page.props.flash.success"
                class="rounded-control border border-pine/20 bg-pine-wash px-4 py-2.5 text-sm text-pine"
            >
                {{ page.props.flash.success }}
            </div>

            <div
                v-if="page.props.errors.plan"
                class="rounded-control border border-oxide/20 bg-oxide/5 px-4 py-2.5 text-sm text-oxide"
            >
                {{ page.props.errors.plan }}
            </div>

            <div class="rounded-panel border border-hairline bg-surface p-4 shadow-diffused">
                <form class="flex flex-wrap items-end gap-2" @submit.prevent="aplicarFiltros">
                    <div class="flex flex-1 flex-col gap-1">
                        <label for="buscar" class="text-xs font-medium text-steel"
                            >Buscar plan</label
                        >
                        <input
                            id="buscar"
                            v-model="busqueda"
                            type="text"
                            placeholder="Máquina o descripción..."
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
                                    Máquina
                                </th>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Umbral de uso
                                </th>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Descripción
                                </th>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Estado
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
                                v-for="plan in props.planes.data"
                                :key="plan.id"
                                class="hover:bg-teja-wash/30"
                            >
                                <td class="px-4 py-2.5 text-sm font-medium text-ink">
                                    {{ plan.maquinaria }}
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono text-xs text-steel">
                                    {{ plan.umbral_uso }}
                                </td>
                                <td class="px-4 py-2.5 text-sm text-steel">
                                    {{ plan.descripcion }}
                                </td>
                                <td class="px-4 py-2.5 text-sm">
                                    <span
                                        class="inline-flex items-center rounded border px-2 py-0.5 text-xs font-medium"
                                        :class="claseDeEstado(plan.maquina_bloqueada)"
                                    >
                                        {{ plan.maquina_bloqueada ? 'Bloqueada' : 'Disponible' }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-right text-xs">
                                    <button
                                        type="button"
                                        class="font-medium text-teja hover:underline"
                                        @click="abrirEditarPlan(plan)"
                                    >
                                        Editar plan
                                    </button>
                                    <span class="mx-2 text-clay/40">|</span>
                                    <button
                                        type="button"
                                        class="font-medium text-teja hover:underline"
                                        @click="abrirIntervencion(plan)"
                                    >
                                        Registrar mantenimiento
                                    </button>
                                    <span class="mx-2 text-clay/40">|</span>
                                    <button
                                        type="button"
                                        class="font-medium text-steel hover:text-oxide"
                                        @click="eliminarPlan(plan)"
                                    >
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="props.planes.data.length === 0"
                    class="px-4 py-10 text-center text-sm text-steel"
                >
                    No hay planes de mantenimiento registrados.
                </div>

                <div
                    v-if="props.planes.total > 0"
                    class="flex flex-wrap items-center justify-between gap-2 border-t border-hairline px-4 py-2.5 text-xs text-steel"
                >
                    <span
                        >Mostrando {{ props.planes.from }}–{{ props.planes.to }} de
                        {{ props.planes.total }}</span
                    >
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="link in props.planes.links"
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

            <div
                class="overflow-hidden rounded-panel border border-hairline bg-surface shadow-diffused"
            >
                <div class="border-b border-hairline bg-workshop px-4 py-2.5">
                    <h3 class="text-[0.6875rem] font-semibold tracking-wider text-steel uppercase">
                        Últimas intervenciones
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="border-b border-hairline bg-workshop">
                            <tr>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Fecha
                                </th>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Máquina
                                </th>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Tipo
                                </th>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Responsable
                                </th>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Observaciones
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-hairline">
                            <tr v-for="intervencion in props.intervenciones" :key="intervencion.id">
                                <td class="px-4 py-2.5 text-sm text-steel">
                                    {{ intervencion.fecha }}
                                </td>
                                <td class="px-4 py-2.5 text-sm font-medium text-ink">
                                    {{ intervencion.maquinaria }}
                                </td>
                                <td class="px-4 py-2.5 text-sm text-steel">
                                    {{ intervencion.tipo }}
                                </td>
                                <td class="px-4 py-2.5 text-sm text-steel">
                                    {{ intervencion.responsable }}
                                </td>
                                <td class="px-4 py-2.5 text-sm text-steel">
                                    {{ intervencion.observaciones ?? '-' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div
                    v-if="props.intervenciones.length === 0"
                    class="px-4 py-10 text-center text-sm text-steel"
                >
                    No hay intervenciones registradas.
                </div>
            </div>
        </div>

        <div
            v-if="planDrawerOpen"
            class="fixed inset-0 z-40 flex justify-end bg-ink/20"
            @click.self="cerrarPlanDrawer"
        >
            <aside
                class="flex h-full w-full max-w-[420px] flex-col overflow-y-auto border-l border-hairline bg-surface p-5 shadow-diffused"
            >
                <div class="flex items-center justify-between border-b border-hairline pb-3">
                    <h3 class="text-lg font-semibold text-ink">
                        {{ planModo === 'crear' ? 'Nuevo plan' : 'Editar plan' }}
                    </h3>
                    <button
                        type="button"
                        class="text-sm text-steel hover:text-ink"
                        @click="cerrarPlanDrawer"
                    >
                        Cerrar
                    </button>
                </div>

                <form
                    class="mt-4 flex flex-col gap-4"
                    data-testid="plan-form"
                    @submit.prevent="guardarPlan"
                >
                    <div class="flex flex-col gap-1.5">
                        <label for="maquinaria_id" class="text-xs font-medium text-steel"
                            >Máquina</label
                        >
                        <select
                            id="maquinaria_id"
                            v-model="planForm.maquinaria_id"
                            required
                            :disabled="planModo === 'editar'"
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none disabled:bg-workshop"
                        >
                            <option value="" disabled>Seleccionar máquina</option>
                            <option
                                v-for="maquinaria in props.maquinarias"
                                :key="maquinaria.id"
                                :value="maquinaria.id"
                            >
                                {{ maquinaria.nombre }}
                            </option>
                        </select>
                        <p v-if="planForm.errors.maquinaria_id" class="text-xs text-oxide">
                            {{ planForm.errors.maquinaria_id }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="umbral_uso" class="text-xs font-medium text-steel"
                            >Umbral de uso</label
                        >
                        <input
                            id="umbral_uso"
                            v-model="planForm.umbral_uso"
                            type="number"
                            step="0.01"
                            required
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        />
                        <p v-if="planForm.errors.umbral_uso" class="text-xs text-oxide">
                            {{ planForm.errors.umbral_uso }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="descripcion" class="text-xs font-medium text-steel"
                            >Descripción</label
                        >
                        <textarea
                            id="descripcion"
                            v-model="planForm.descripcion"
                            required
                            rows="3"
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        ></textarea>
                        <p v-if="planForm.errors.descripcion" class="text-xs text-oxide">
                            {{ planForm.errors.descripcion }}
                        </p>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-hairline pt-3">
                        <button
                            type="button"
                            class="rounded-control border border-hairline px-4 py-2 text-sm text-ink transition-colors hover:bg-canvas"
                            @click="cerrarPlanDrawer"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="planForm.processing"
                            class="rounded-control bg-teja px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-teja-deep active:scale-[0.98] disabled:opacity-60"
                        >
                            {{ planForm.processing ? 'Guardando…' : 'Guardar' }}
                        </button>
                    </div>
                </form>
            </aside>
        </div>

        <div
            v-if="intervencionDrawerOpen"
            class="fixed inset-0 z-40 flex justify-end bg-ink/20"
            @click.self="cerrarIntervencionDrawer"
        >
            <aside
                class="flex h-full w-full max-w-[420px] flex-col overflow-y-auto border-l border-hairline bg-surface p-5 shadow-diffused"
            >
                <div class="flex items-center justify-between border-b border-hairline pb-3">
                    <h3 class="text-lg font-semibold text-ink">Registrar mantenimiento</h3>
                    <button
                        type="button"
                        class="text-sm text-steel hover:text-ink"
                        @click="cerrarIntervencionDrawer"
                    >
                        Cerrar
                    </button>
                </div>

                <form
                    class="mt-4 flex flex-col gap-4"
                    data-testid="intervencion-form"
                    @submit.prevent="guardarIntervencion"
                >
                    <div class="flex flex-col gap-1.5">
                        <label for="fecha" class="text-xs font-medium text-steel">Fecha</label>
                        <input
                            id="fecha"
                            v-model="intervencionForm.fecha"
                            type="date"
                            required
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        />
                        <p v-if="intervencionForm.errors.fecha" class="text-xs text-oxide">
                            {{ intervencionForm.errors.fecha }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="tipo" class="text-xs font-medium text-steel">Tipo</label>
                        <input
                            id="tipo"
                            v-model="intervencionForm.tipo"
                            type="text"
                            required
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        />
                        <p v-if="intervencionForm.errors.tipo" class="text-xs text-oxide">
                            {{ intervencionForm.errors.tipo }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="responsable" class="text-xs font-medium text-steel"
                            >Responsable</label
                        >
                        <input
                            id="responsable"
                            v-model="intervencionForm.responsable"
                            type="text"
                            required
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        />
                        <p v-if="intervencionForm.errors.responsable" class="text-xs text-oxide">
                            {{ intervencionForm.errors.responsable }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="observaciones" class="text-xs font-medium text-steel"
                            >Observaciones</label
                        >
                        <textarea
                            id="observaciones"
                            v-model="intervencionForm.observaciones"
                            rows="3"
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        ></textarea>
                        <p v-if="intervencionForm.errors.observaciones" class="text-xs text-oxide">
                            {{ intervencionForm.errors.observaciones }}
                        </p>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-hairline pt-3">
                        <button
                            type="button"
                            class="rounded-control border border-hairline px-4 py-2 text-sm text-ink transition-colors hover:bg-canvas"
                            @click="cerrarIntervencionDrawer"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="intervencionForm.processing"
                            class="rounded-control bg-teja px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-teja-deep active:scale-[0.98] disabled:opacity-60"
                        >
                            {{ intervencionForm.processing ? 'Guardando…' : 'Guardar' }}
                        </button>
                    </div>
                </form>
            </aside>
        </div>
    </AppLayout>
</template>
