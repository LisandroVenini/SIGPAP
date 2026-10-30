<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

interface Producto {
    id: number;
    codigo_sku: string;
    descripcion: string;
}

interface Planificacion {
    id: number;
    nombre: string;
}

interface Orden {
    id: number;
    producto: Producto | null;
    planificacion: string | null;
    estado: string;
    cantidad_requerida: number;
    cantidad_producida: number;
    fecha_entrega: string;
    prioridad_actual: number;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedOrdenes {
    data: Orden[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
}

type Modo = 'solo_disponibles' | 'todas';

type Drawer = 'cerrado' | 'nueva' | 'modificar' | 'anular';

type SharedProps = {
    flash: { success: string | null };
    errors: Record<string, string>;
};

const props = defineProps<{
    ordenes: PaginatedOrdenes;
    planificaciones: Planificacion[];
    productos: Producto[];
    estados: string[];
    filtros: {
        buscar?: string | null;
        estado?: string | null;
        fecha_desde?: string | null;
        fecha_hasta?: string | null;
    };
}>();

const page = usePage<SharedProps>();

const busqueda = ref(props.filtros.buscar ?? '');
const estado = ref(props.filtros.estado ?? '');
const fechaDesde = ref(props.filtros.fecha_desde ?? '');
const fechaHasta = ref(props.filtros.fecha_hasta ?? '');

const drawerAbierto = ref<Drawer>('cerrado');
const ordenActiva = ref<Orden | null>(null);

const formNueva = useForm({
    planificacion_de_produccion_id: props.planificaciones[0]?.id ?? '',
    fecha_entrega: '',
    modo: 'solo_disponibles' as Modo,
    lineas: [{ articulo_id: '', cantidad: '' }],
});

const formModificar = useForm({
    cantidad: '',
    fecha_entrega: '',
});

const formAnular = useForm({
    motivo: '',
});

function aplicarFiltros(): void {
    router.get(
        '/produccion/ordenes-de-produccion',
        {
            buscar: busqueda.value || undefined,
            estado: estado.value || undefined,
            fecha_desde: fechaDesde.value || undefined,
            fecha_hasta: fechaHasta.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function abrirNueva(): void {
    drawerAbierto.value = 'nueva';
    formNueva.clearErrors();
    formNueva.reset();
    formNueva.lineas = [{ articulo_id: '', cantidad: '' }];
}

function abrirModificar(orden: Orden): void {
    drawerAbierto.value = 'modificar';
    ordenActiva.value = orden;
    formModificar.clearErrors();
    formModificar.cantidad = String(orden.cantidad_requerida);
    formModificar.fecha_entrega = orden.fecha_entrega;
}

function abrirAnular(orden: Orden): void {
    drawerAbierto.value = 'anular';
    ordenActiva.value = orden;
    formAnular.clearErrors();
    formAnular.reset();
}

function cerrarDrawer(): void {
    drawerAbierto.value = 'cerrado';
    ordenActiva.value = null;
}

function agregarLinea(): void {
    formNueva.lineas.push({ articulo_id: '', cantidad: '' });
}

function quitarLinea(indice: number): void {
    formNueva.lineas.splice(indice, 1);

    if (formNueva.lineas.length === 0) {
        agregarLinea();
    }
}

function guardarNueva(): void {
    formNueva.post('/produccion/ordenes-de-produccion', {
        preserveScroll: true,
        onSuccess: () => {
            cerrarDrawer();
        },
    });
}

function guardarModificacion(): void {
    if (!ordenActiva.value) {
        return;
    }

    formModificar.put(`/produccion/ordenes-de-produccion/${ordenActiva.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            cerrarDrawer();
        },
    });
}

function confirmarAnulacion(): void {
    if (!ordenActiva.value) {
        return;
    }

    formAnular.post(`/produccion/ordenes-de-produccion/${ordenActiva.value.id}/anular`, {
        preserveScroll: true,
        onSuccess: () => {
            cerrarDrawer();
        },
    });
}

function claseBadge(estado: string): string {
    const clases: Record<string, string> = {
        Pendiente: 'bg-pine-wash text-pine',
        'Pendiente por Insumos': 'bg-amber-100 text-amber-800',
        Asignada: 'bg-blue-100 text-blue-800',
        'En Curso': 'bg-purple-100 text-purple-800',
        Cerrada: 'bg-workshop text-steel',
        Anulada: 'bg-oxide/10 text-oxide',
    };

    return clases[estado] ?? 'bg-workshop text-steel';
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
    <AppLayout title="Órdenes de Producción">
        <Head title="Órdenes de Producción" />

        <div class="flex flex-col gap-4">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div class="flex flex-col gap-1">
                    <h2 class="text-2xl font-semibold tracking-tight text-ink">
                        Órdenes de producción
                    </h2>
                    <p class="text-sm text-steel">
                        Órdenes generadas por planificación con reserva de insumos.
                    </p>
                </div>
                <button
                    type="button"
                    class="rounded-control bg-teja px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-teja-deep active:scale-[0.98]"
                    @click="abrirNueva"
                >
                    Nueva orden
                </button>
            </div>

            <div
                v-if="page.props.flash.success"
                class="rounded-control border border-pine/20 bg-pine-wash px-4 py-2.5 text-sm text-pine"
            >
                {{ page.props.flash.success }}
            </div>

            <div
                v-if="page.props.errors.estado"
                class="rounded-control border border-oxide/20 bg-oxide/5 px-4 py-2.5 text-sm text-oxide"
            >
                {{ page.props.errors.estado }}
            </div>

            <div class="rounded-panel border border-hairline bg-surface p-4 shadow-diffused">
                <form
                    class="flex flex-wrap items-end gap-2"
                    data-testid="filtros-form"
                    @submit.prevent="aplicarFiltros"
                >
                    <div class="flex flex-1 flex-col gap-1">
                        <label for="buscar" class="text-xs font-medium text-steel">Buscar</label>
                        <input
                            id="buscar"
                            v-model="busqueda"
                            type="text"
                            placeholder="SKU o descripción..."
                            class="w-full rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink placeholder:text-clay/70 focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label for="estado" class="text-xs font-medium text-steel">Estado</label>
                        <select
                            id="estado"
                            v-model="estado"
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        >
                            <option value="">Todos</option>
                            <option v-for="e in props.estados" :key="e" :value="e">{{ e }}</option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label for="fecha_desde" class="text-xs font-medium text-steel"
                            >Desde</label
                        >
                        <input
                            id="fecha_desde"
                            v-model="fechaDesde"
                            type="date"
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label for="fecha_hasta" class="text-xs font-medium text-steel"
                            >Hasta</label
                        >
                        <input
                            id="fecha_hasta"
                            v-model="fechaHasta"
                            type="date"
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
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
                                    Producto
                                </th>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Planificación
                                </th>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Estado
                                </th>
                                <th
                                    class="px-4 py-2.5 text-right text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Requerida
                                </th>
                                <th
                                    class="px-4 py-2.5 text-right text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Producida
                                </th>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Entrega
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
                                v-for="orden in props.ordenes.data"
                                :key="orden.id"
                                class="hover:bg-teja-wash/30"
                            >
                                <td class="px-4 py-2.5 text-sm font-medium text-ink">
                                    {{ orden.producto?.descripcion ?? '—' }}
                                    <span class="block font-mono text-xs text-steel">{{
                                        orden.producto?.codigo_sku ?? ''
                                    }}</span>
                                </td>
                                <td class="px-4 py-2.5 text-sm text-steel">
                                    {{ orden.planificacion ?? '—' }}
                                </td>
                                <td class="px-4 py-2.5 text-sm">
                                    <span
                                        class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="claseBadge(orden.estado)"
                                    >
                                        {{ orden.estado }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono text-xs text-steel">
                                    {{ orden.cantidad_requerida }}
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono text-xs text-steel">
                                    {{ orden.cantidad_producida }}
                                </td>
                                <td class="px-4 py-2.5 font-mono text-xs text-steel">
                                    {{ orden.fecha_entrega }}
                                </td>
                                <td class="px-4 py-2.5 text-right text-xs">
                                    <button
                                        type="button"
                                        class="font-medium text-teja hover:underline"
                                        @click="abrirModificar(orden)"
                                    >
                                        Modificar
                                    </button>
                                    <span class="mx-2 text-clay/40">|</span>
                                    <button
                                        type="button"
                                        class="font-medium text-steel hover:text-oxide"
                                        @click="abrirAnular(orden)"
                                    >
                                        Anular
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="props.ordenes.data.length === 0"
                    class="px-4 py-10 text-center text-sm text-steel"
                >
                    No hay órdenes de producción registradas.
                </div>

                <div
                    v-if="props.ordenes.total > 0"
                    class="flex flex-wrap items-center justify-between gap-2 border-t border-hairline px-4 py-2.5 text-xs text-steel"
                >
                    <span
                        >Mostrando {{ props.ordenes.from }}–{{ props.ordenes.to }} de
                        {{ props.ordenes.total }}</span
                    >
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="link in props.ordenes.links"
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
            v-if="drawerAbierto !== 'cerrado'"
            class="fixed inset-0 z-40 flex justify-end bg-ink/20"
            @click.self="cerrarDrawer"
        >
            <aside
                class="flex h-full w-full max-w-[460px] flex-col overflow-y-auto border-l border-hairline bg-surface p-5 shadow-diffused"
            >
                <div class="flex items-center justify-between border-b border-hairline pb-3">
                    <h3 class="text-lg font-semibold text-ink">
                        {{
                            drawerAbierto === 'nueva'
                                ? 'Nueva orden'
                                : drawerAbierto === 'modificar'
                                  ? 'Modificar orden'
                                  : 'Anular orden'
                        }}
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
                    v-if="drawerAbierto === 'nueva'"
                    class="mt-4 flex flex-col gap-4"
                    data-testid="nueva-orden-form"
                    @submit.prevent="guardarNueva"
                >
                    <div class="flex flex-col gap-1.5">
                        <label for="planificacion" class="text-xs font-medium text-steel"
                            >Planificación</label
                        >
                        <select
                            id="planificacion"
                            v-model="formNueva.planificacion_de_produccion_id"
                            required
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        >
                            <option v-for="p in props.planificaciones" :key="p.id" :value="p.id">
                                {{ p.nombre }}
                            </option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="fecha_entrega" class="text-xs font-medium text-steel"
                            >Fecha de entrega</label
                        >
                        <input
                            id="fecha_entrega"
                            v-model="formNueva.fecha_entrega"
                            type="date"
                            required
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        />
                    </div>

                    <div class="flex flex-col gap-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium text-steel">Líneas</span>
                            <button
                                type="button"
                                class="text-xs font-medium text-teja hover:underline"
                                @click="agregarLinea"
                            >
                                Agregar línea
                            </button>
                        </div>
                        <div
                            v-for="(linea, indice) in formNueva.lineas"
                            :key="indice"
                            class="flex items-start gap-2"
                        >
                            <select
                                v-model="linea.articulo_id"
                                required
                                class="flex-1 rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                            >
                                <option value="">Seleccionar producto</option>
                                <option v-for="p in props.productos" :key="p.id" :value="p.id">
                                    {{ p.descripcion }}
                                </option>
                            </select>
                            <input
                                v-model="linea.cantidad"
                                type="number"
                                step="0.01"
                                min="0.01"
                                required
                                placeholder="Cantidad"
                                class="w-28 rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                            />
                            <button
                                v-if="formNueva.lineas.length > 1"
                                type="button"
                                class="text-sm text-oxide hover:text-oxide/80"
                                @click="quitarLinea(indice)"
                            >
                                Quitar
                            </button>
                        </div>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <span class="text-xs font-medium text-steel">Modo de creación</span>
                        <label class="flex items-center gap-2 text-sm text-ink">
                            <input
                                v-model="formNueva.modo"
                                type="radio"
                                value="solo_disponibles"
                                class="text-teja focus:ring-teja"
                            />
                            Crear solo las que tengan stock
                        </label>
                        <label class="flex items-center gap-2 text-sm text-ink">
                            <input
                                v-model="formNueva.modo"
                                type="radio"
                                value="todas"
                                class="text-teja focus:ring-teja"
                            />
                            Crear todas
                        </label>
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
                            :disabled="formNueva.processing"
                            class="rounded-control bg-teja px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-teja-deep active:scale-[0.98] disabled:opacity-60"
                        >
                            {{ formNueva.processing ? 'Guardando…' : 'Guardar' }}
                        </button>
                    </div>
                </form>

                <form
                    v-if="drawerAbierto === 'modificar' && ordenActiva"
                    class="mt-4 flex flex-col gap-4"
                    data-testid="modificar-orden-form"
                    @submit.prevent="guardarModificacion"
                >
                    <div class="flex flex-col gap-1.5">
                        <label for="cantidad" class="text-xs font-medium text-steel"
                            >Cantidad</label
                        >
                        <input
                            id="cantidad"
                            v-model="formModificar.cantidad"
                            type="number"
                            step="0.01"
                            min="0.01"
                            required
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        />
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="fecha_entrega_modificar" class="text-xs font-medium text-steel"
                            >Fecha de entrega</label
                        >
                        <input
                            id="fecha_entrega_modificar"
                            v-model="formModificar.fecha_entrega"
                            type="date"
                            required
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        />
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
                            :disabled="formModificar.processing"
                            class="rounded-control bg-teja px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-teja-deep active:scale-[0.98] disabled:opacity-60"
                        >
                            {{ formModificar.processing ? 'Guardando…' : 'Guardar' }}
                        </button>
                    </div>
                </form>

                <form
                    v-if="drawerAbierto === 'anular' && ordenActiva"
                    class="mt-4 flex flex-col gap-4"
                    data-testid="anular-orden-form"
                    @submit.prevent="confirmarAnulacion"
                >
                    <div class="flex flex-col gap-1.5">
                        <label for="motivo" class="text-xs font-medium text-steel">Motivo</label>
                        <textarea
                            id="motivo"
                            v-model="formAnular.motivo"
                            rows="3"
                            maxlength="255"
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        ></textarea>
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
                            :disabled="formAnular.processing"
                            class="rounded-control bg-oxide px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-oxide/90 active:scale-[0.98] disabled:opacity-60"
                        >
                            {{ formAnular.processing ? 'Anulando…' : 'Anular' }}
                        </button>
                    </div>
                </form>
            </aside>
        </div>
    </AppLayout>
</template>
