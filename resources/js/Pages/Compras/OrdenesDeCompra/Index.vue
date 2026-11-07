<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

interface Articulo {
    id: number;
    codigo_sku: string;
    descripcion: string;
    unidad: string | null;
}

interface Orden {
    id: number;
    numero_oc: string;
    proveedor: string;
    estado: string;
    fecha_emision: string;
    lineas: number;
    total: number;
}

interface CotizacionOpcion {
    id: number;
    proveedor: string;
    total: number;
    ultima_fecha_prometida: string | null;
}

interface SolicitudConCotizaciones {
    id: number;
    fecha: string;
    fecha_vencimiento: string;
    cotizaciones: CotizacionOpcion[];
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

interface LineaForm {
    articulo_id: number | '';
    cantidad: number | '';
    precio_pactado: number | '';
}

type SharedProps = {
    flash: { success: string | null };
    errors: Record<string, string>;
};

interface FiltrosOrdenes {
    numero_oc: string;
    estado: string;
    proveedor: string;
    fecha_desde: string;
    fecha_hasta: string;
}

const props = defineProps<{
    ordenes: PaginatedOrdenes;
    solicitudesConCotizaciones: SolicitudConCotizaciones[];
    articulos: Articulo[];
    estados: string[];
}>();

const page = usePage<SharedProps>();

const filtros = ref<FiltrosOrdenes>({
    numero_oc: '',
    estado: '',
    proveedor: '',
    fecha_desde: '',
    fecha_hasta: '',
});

const hayFiltros = computed(() => Object.values(filtros.value).some((valor) => valor !== ''));

const drawerEmitirOpen = ref(false);
const cotizacionSeleccionada = ref<number | ''>('');

const emitirForm = useForm<{ cotizacion_id: number | '' }>({
    cotizacion_id: '',
});

const drawerModificarOpen = ref(false);
const ordenEditando = ref<Orden | null>(null);

const modificarForm = useForm<{ lineas: LineaForm[] }>({
    lineas: [{ articulo_id: '', cantidad: '', precio_pactado: '' }],
});

function buscar(): void {
    router.get(
        '/compras/ordenes-de-compra',
        {
            numero_oc: filtros.value.numero_oc || undefined,
            estado: filtros.value.estado || undefined,
            proveedor: filtros.value.proveedor || undefined,
            fecha_desde: filtros.value.fecha_desde || undefined,
            fecha_hasta: filtros.value.fecha_hasta || undefined,
        },
        { preserveState: true, preserveScroll: true },
    );
}

function limpiarFiltros(): void {
    filtros.value = {
        numero_oc: '',
        estado: '',
        proveedor: '',
        fecha_desde: '',
        fecha_hasta: '',
    };
    buscar();
}

function abrirEmitir(): void {
    cotizacionSeleccionada.value = '';
    emitirForm.clearErrors();
    emitirForm.cotizacion_id = '';
    drawerEmitirOpen.value = true;
}

function cerrarEmitir(): void {
    drawerEmitirOpen.value = false;
}

function emitir(): void {
    emitirForm.cotizacion_id = cotizacionSeleccionada.value;
    emitirForm.post('/compras/ordenes-de-compra', {
        preserveScroll: true,
        onSuccess: () => {
            drawerEmitirOpen.value = false;
            cotizacionSeleccionada.value = '';
        },
    });
}

function abrirModificar(orden: Orden): void {
    ordenEditando.value = orden;
    modificarForm.clearErrors();
    modificarForm.lineas = [{ articulo_id: '', cantidad: '', precio_pactado: '' }];
    drawerModificarOpen.value = true;
}

function cerrarModificar(): void {
    drawerModificarOpen.value = false;
}

function agregarLinea(): void {
    modificarForm.lineas.push({ articulo_id: '', cantidad: '', precio_pactado: '' });
}

function quitarLinea(indice: number): void {
    modificarForm.lineas.splice(indice, 1);

    if (modificarForm.lineas.length === 0) {
        agregarLinea();
    }
}

function guardarModificar(): void {
    if (!ordenEditando.value) {
        return;
    }

    modificarForm.put(`/compras/ordenes-de-compra/${ordenEditando.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            drawerModificarOpen.value = false;
        },
    });
}

function anular(orden: Orden): void {
    if (!window.confirm(`¿Anular la orden ${orden.numero_oc}?`)) {
        return;
    }

    router.post(
        `/compras/ordenes-de-compra/${orden.id}/anular`,
        {},
        {
            preserveScroll: true,
        },
    );
}

function puedeGestionar(orden: Orden): boolean {
    return orden.estado === 'Emitida';
}

function claseDeEstado(estado: string): string {
    switch (estado) {
        case 'Emitida':
            return 'bg-slate-wash text-slate border-slate/20';
        case 'Confirmada':
            return 'bg-pine/10 text-pine border-pine/20';
        case 'Rechazada':
            return 'bg-oxide/10 text-oxide border-oxide/20';
        case 'Anulada':
            return 'bg-workshop text-steel border-hairline';
        default:
            return 'bg-workshop text-steel border-hairline';
    }
}

function formatearMoneda(valor: number): string {
    return new Intl.NumberFormat('es-AR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(valor);
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
    <AppLayout title="Órdenes de compra">
        <Head title="Órdenes de compra" />

        <div class="flex flex-col gap-4">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div class="flex flex-col gap-1">
                    <h2 class="text-2xl font-semibold tracking-tight text-ink">
                        Órdenes de compra
                    </h2>
                    <p class="text-sm text-steel">
                        Emití una orden adjudicando una cotización recibida y seguí su confirmación
                        con el proveedor.
                    </p>
                </div>
                <button
                    type="button"
                    class="rounded-control bg-teja px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-teja-deep active:scale-[0.98]"
                    @click="abrirEmitir"
                >
                    Emitir OC
                </button>
            </div>

            <div
                v-if="page.props.flash.success"
                class="rounded-control border border-pine/20 bg-pine-wash px-4 py-2.5 text-sm text-pine"
            >
                {{ page.props.flash.success }}
            </div>

            <div
                v-if="page.props.errors.solicitud"
                class="rounded-control border border-oxide/20 bg-oxide/5 px-4 py-2.5 text-sm text-oxide"
            >
                {{ page.props.errors.solicitud }}
            </div>

            <form
                class="flex flex-wrap items-end gap-3 rounded-panel border border-hairline bg-surface p-4 shadow-diffused"
                data-testid="ordenes-filtros"
                @submit.prevent="buscar"
            >
                <div class="flex min-w-[160px] flex-1 flex-col gap-1">
                    <label for="filtro-numero" class="text-xs font-medium text-steel">Número</label>
                    <input
                        id="filtro-numero"
                        v-model="filtros.numero_oc"
                        data-testid="filtro-numero"
                        type="text"
                        placeholder="OC-2026-0001"
                        class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                    />
                </div>
                <div class="flex min-w-[150px] flex-col gap-1">
                    <label for="filtro-estado" class="text-xs font-medium text-steel">Estado</label>
                    <select
                        id="filtro-estado"
                        v-model="filtros.estado"
                        data-testid="filtro-estado"
                        class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                    >
                        <option value="">Todos</option>
                        <option v-for="estado in props.estados" :key="estado" :value="estado">
                            {{ estado }}
                        </option>
                    </select>
                </div>
                <div class="flex min-w-[160px] flex-1 flex-col gap-1">
                    <label for="filtro-proveedor" class="text-xs font-medium text-steel"
                        >Proveedor</label
                    >
                    <input
                        id="filtro-proveedor"
                        v-model="filtros.proveedor"
                        data-testid="filtro-proveedor"
                        type="text"
                        placeholder="Razón social"
                        class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                    />
                </div>
                <div class="flex flex-col gap-1">
                    <label for="filtro-fecha-desde" class="text-xs font-medium text-steel"
                        >Desde</label
                    >
                    <input
                        id="filtro-fecha-desde"
                        v-model="filtros.fecha_desde"
                        data-testid="filtro-fecha-desde"
                        type="date"
                        class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                    />
                </div>
                <div class="flex flex-col gap-1">
                    <label for="filtro-fecha-hasta" class="text-xs font-medium text-steel"
                        >Hasta</label
                    >
                    <input
                        id="filtro-fecha-hasta"
                        v-model="filtros.fecha_hasta"
                        data-testid="filtro-fecha-hasta"
                        type="date"
                        class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                    />
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="submit"
                        class="rounded-control bg-teja px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-teja-deep active:scale-[0.98]"
                    >
                        Buscar
                    </button>
                    <button
                        v-if="hayFiltros"
                        type="button"
                        class="rounded-control border border-hairline px-4 py-2 text-sm text-ink transition-colors hover:bg-canvas"
                        @click="limpiarFiltros"
                    >
                        Limpiar
                    </button>
                </div>
            </form>

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
                                    Número
                                </th>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Proveedor
                                </th>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Estado
                                </th>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Fecha
                                </th>
                                <th
                                    class="px-4 py-2.5 text-right text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Líneas
                                </th>
                                <th
                                    class="px-4 py-2.5 text-right text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Total
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
                                <td class="px-4 py-2.5 font-mono text-xs text-ink">
                                    {{ orden.numero_oc }}
                                </td>
                                <td class="px-4 py-2.5 text-sm text-ink">
                                    {{ orden.proveedor }}
                                </td>
                                <td class="px-4 py-2.5 text-sm">
                                    <span
                                        class="inline-flex items-center rounded border px-2 py-0.5 text-xs font-medium"
                                        :class="claseDeEstado(orden.estado)"
                                    >
                                        {{ orden.estado }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 font-mono text-xs text-steel">
                                    {{ orden.fecha_emision }}
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono text-xs text-steel">
                                    {{ orden.lineas }}
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono text-xs text-ink">
                                    $ {{ formatearMoneda(orden.total) }}
                                </td>
                                <td class="px-4 py-2.5 text-right text-xs">
                                    <button
                                        type="button"
                                        class="font-medium text-teja hover:underline disabled:cursor-not-allowed disabled:text-clay disabled:no-underline"
                                        :disabled="!puedeGestionar(orden)"
                                        @click="abrirModificar(orden)"
                                    >
                                        Modificar
                                    </button>
                                    <span class="mx-2 text-clay/40">|</span>
                                    <button
                                        type="button"
                                        class="font-medium text-steel hover:text-oxide disabled:cursor-not-allowed disabled:text-clay"
                                        :disabled="!puedeGestionar(orden)"
                                        @click="anular(orden)"
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
                    {{
                        hayFiltros
                            ? 'No se encontraron órdenes para el criterio especificado'
                            : 'No hay órdenes de compra registradas.'
                    }}
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
            v-if="drawerEmitirOpen"
            class="fixed inset-0 z-40 flex justify-end bg-ink/20"
            @click.self="cerrarEmitir"
        >
            <aside
                class="flex h-full w-full max-w-[520px] flex-col overflow-y-auto border-l border-hairline bg-surface p-5 shadow-diffused"
            >
                <div class="flex items-center justify-between border-b border-hairline pb-3">
                    <h3 class="text-lg font-semibold text-ink">Emitir OC</h3>
                    <button
                        type="button"
                        class="text-sm text-steel hover:text-ink"
                        @click="cerrarEmitir"
                    >
                        Cerrar
                    </button>
                </div>

                <form
                    class="mt-4 flex flex-col gap-4"
                    data-testid="emitir-form"
                    @submit.prevent="emitir"
                >
                    <div
                        v-if="props.solicitudesConCotizaciones.length === 0"
                        class="rounded-control bg-workshop px-3 py-2 text-xs text-steel"
                    >
                        No hay solicitudes enviadas con cotizaciones recibidas.
                    </div>

                    <div
                        v-for="solicitud in props.solicitudesConCotizaciones"
                        :key="solicitud.id"
                        class="flex flex-col gap-2 rounded-control border border-hairline p-3"
                    >
                        <div class="flex items-baseline justify-between gap-2">
                            <span class="text-sm font-medium text-ink">
                                Solicitud del {{ solicitud.fecha }}
                            </span>
                            <span class="text-xs text-steel">
                                Vence el {{ solicitud.fecha_vencimiento }}
                            </span>
                        </div>

                        <label
                            v-for="cotizacion in solicitud.cotizaciones"
                            :key="cotizacion.id"
                            class="flex flex-wrap items-center justify-between gap-2 rounded-control bg-workshop/60 px-3 py-2 text-sm text-ink"
                        >
                            <span class="flex items-center gap-2">
                                <input
                                    v-model="cotizacionSeleccionada"
                                    :data-testid="`cotizacion-${cotizacion.id}`"
                                    type="radio"
                                    name="cotizacion_id"
                                    :value="cotizacion.id"
                                    class="text-teja focus:ring-teja"
                                />
                                {{ cotizacion.proveedor }}
                            </span>
                            <span class="text-xs text-steel">
                                $ {{ formatearMoneda(cotizacion.total) }} ·
                                {{ cotizacion.ultima_fecha_prometida ?? 'sin fecha' }}
                            </span>
                        </label>
                    </div>

                    <p v-if="emitirForm.errors.cotizacion_id" class="text-xs text-oxide">
                        {{ emitirForm.errors.cotizacion_id }}
                    </p>

                    <div class="flex justify-end gap-2 border-t border-hairline pt-3">
                        <button
                            type="button"
                            class="rounded-control border border-hairline px-4 py-2 text-sm text-ink transition-colors hover:bg-canvas"
                            @click="cerrarEmitir"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="emitirForm.processing || cotizacionSeleccionada === ''"
                            class="rounded-control bg-teja px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-teja-deep active:scale-[0.98] disabled:opacity-60"
                        >
                            {{ emitirForm.processing ? 'Emitiendo…' : 'Emitir orden' }}
                        </button>
                    </div>
                </form>
            </aside>
        </div>

        <div
            v-if="drawerModificarOpen"
            class="fixed inset-0 z-40 flex justify-end bg-ink/20"
            @click.self="cerrarModificar"
        >
            <aside
                class="flex h-full w-full max-w-[480px] flex-col overflow-y-auto border-l border-hairline bg-surface p-5 shadow-diffused"
            >
                <div class="flex items-center justify-between border-b border-hairline pb-3">
                    <h3 class="text-lg font-semibold text-ink">
                        Modificar {{ ordenEditando?.numero_oc }}
                    </h3>
                    <button
                        type="button"
                        class="text-sm text-steel hover:text-ink"
                        @click="cerrarModificar"
                    >
                        Cerrar
                    </button>
                </div>

                <form
                    class="mt-4 flex flex-col gap-4"
                    data-testid="modificar-form"
                    @submit.prevent="guardarModificar"
                >
                    <p class="rounded-control bg-workshop px-3 py-2 text-xs text-steel">
                        Al guardar se reemplazan las líneas de la orden.
                    </p>

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
                            v-for="(linea, indice) in modificarForm.lineas"
                            :key="indice"
                            class="flex items-start gap-2"
                        >
                            <select
                                v-model="linea.articulo_id"
                                required
                                class="flex-1 rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                            >
                                <option value="">Seleccionar artículo</option>
                                <option
                                    v-for="articulo in props.articulos"
                                    :key="articulo.id"
                                    :value="articulo.id"
                                >
                                    {{ articulo.descripcion }}
                                </option>
                            </select>
                            <input
                                v-model="linea.cantidad"
                                type="number"
                                step="0.01"
                                min="0.01"
                                required
                                placeholder="Cantidad"
                                class="w-24 rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                            />
                            <input
                                v-model="linea.precio_pactado"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                placeholder="Precio"
                                class="w-24 rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                            />
                            <button
                                v-if="modificarForm.lineas.length > 1"
                                type="button"
                                class="text-sm text-oxide hover:text-oxide/80"
                                @click="quitarLinea(indice)"
                            >
                                Quitar
                            </button>
                        </div>
                        <p v-if="modificarForm.errors.lineas" class="text-xs text-oxide">
                            {{ modificarForm.errors.lineas }}
                        </p>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-hairline pt-3">
                        <button
                            type="button"
                            class="rounded-control border border-hairline px-4 py-2 text-sm text-ink transition-colors hover:bg-canvas"
                            @click="cerrarModificar"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="modificarForm.processing"
                            class="rounded-control bg-teja px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-teja-deep active:scale-[0.98] disabled:opacity-60"
                        >
                            {{ modificarForm.processing ? 'Guardando…' : 'Guardar' }}
                        </button>
                    </div>
                </form>
            </aside>
        </div>
    </AppLayout>
</template>
