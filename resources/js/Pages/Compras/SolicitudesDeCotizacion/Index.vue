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

interface ProveedorOpcion {
    id: number;
    razon_social: string;
}

interface Solicitud {
    id: number;
    codigo: string;
    fecha: string;
    fecha_vencimiento: string;
    estado: string;
    estado_visible: string;
    lineas: number;
    proveedores: number;
    cotizaciones: number;
    respuestas: { recibidas: number; esperadas: number };
}

interface LineaDetalle {
    articulo: string;
    precio_unitario: number;
    cantidad_solicitada: number;
    cantidad_ofertada: number;
    fecha_prometida: string | null;
    subtotal: number;
}

interface CotizacionDetalle {
    id: number;
    proveedor: string;
    fecha: string | null;
    total_ofertado: number;
    costo_pedido: number;
    fecha_prometida: string | null;
    lineas: LineaDetalle[];
}

interface DetalleSolicitud {
    id: number;
    codigo: string;
    fecha: string;
    fecha_vencimiento: string;
    estado: string;
    estado_visible: string;
    insumos: string;
    lineas: Array<{ articulo_id: number; articulo: string; cantidad_solicitada: number }>;
    proveedores: Array<{
        id: number;
        razon_social: string;
        enlace_vigente: boolean;
        respondio: boolean;
    }>;
    cotizaciones: CotizacionDetalle[];
}

interface InformeFila {
    cotizacion_id: number;
    proveedor: string;
    precio: number;
    dias: number;
    cobertura: number;
    vencida: boolean;
    confiabilidad: number;
    puntaje: number;
}

interface Informe {
    parametros: {
        pesos: Record<string, number>;
        sin_historial: number;
        penalizar_cobertura: boolean;
    };
    cotizaciones: InformeFila[];
    recomendada_id: number | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedSolicitudes {
    data: Solicitud[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
}

type SharedProps = {
    flash: { success: string | null };
    errors: Record<string, string>;
};

interface FiltrosSolicitudes {
    proveedor: string;
    estado: string;
    fecha_desde: string;
    fecha_hasta: string;
}

const props = defineProps<{
    solicitudes: PaginatedSolicitudes;
    detalles: Record<string, DetalleSolicitud>;
    informes: Record<string, Informe>;
    articulos: Articulo[];
    proveedores: ProveedorOpcion[];
}>();

const page = usePage<SharedProps>();

const drawerOpen = ref(false);
const modo = ref<'crear' | 'editar'>('crear');
const solicitudEditando = ref<Solicitud | null>(null);

const filtros = ref<FiltrosSolicitudes>({
    proveedor: '',
    estado: '',
    fecha_desde: '',
    fecha_hasta: '',
});

const hayFiltros = computed(() => Object.values(filtros.value).some((valor) => valor !== ''));

function buscar(): void {
    router.get(
        '/compras/solicitudes-de-cotizacion',
        {
            proveedor: filtros.value.proveedor || undefined,
            estado: filtros.value.estado || undefined,
            fecha_desde: filtros.value.fecha_desde || undefined,
            fecha_hasta: filtros.value.fecha_hasta || undefined,
        },
        { preserveState: true, preserveScroll: true },
    );
}

function limpiarFiltros(): void {
    filtros.value = { proveedor: '', estado: '', fecha_desde: '', fecha_hasta: '' };
    buscar();
}

const form = useForm({
    fecha_vencimiento: '',
    lineas: [{ articulo_id: '', cantidad_solicitada: '' }],
    proveedores: [] as number[],
});

function abrirCrear(): void {
    modo.value = 'crear';
    solicitudEditando.value = null;
    form.clearErrors();
    form.fecha_vencimiento = '';
    form.lineas = [{ articulo_id: '', cantidad_solicitada: '' }];
    form.proveedores = [];
    drawerOpen.value = true;
}

function abrirModificar(solicitud: Solicitud): void {
    modo.value = 'editar';
    solicitudEditando.value = solicitud;
    form.clearErrors();
    form.fecha_vencimiento = solicitud.fecha_vencimiento;
    form.lineas = [{ articulo_id: '', cantidad_solicitada: '' }];
    form.proveedores = [];
    drawerOpen.value = true;
}

function cerrarDrawer(): void {
    drawerOpen.value = false;
}

function agregarLinea(): void {
    form.lineas.push({ articulo_id: '', cantidad_solicitada: '' });
}

function quitarLinea(indice: number): void {
    form.lineas.splice(indice, 1);

    if (form.lineas.length === 0) {
        agregarLinea();
    }
}

function guardar(): void {
    if (modo.value === 'crear') {
        form.post('/compras/solicitudes-de-cotizacion', {
            preserveScroll: true,
            onSuccess: () => {
                drawerOpen.value = false;
            },
        });

        return;
    }

    if (!solicitudEditando.value) {
        return;
    }

    form.put(`/compras/solicitudes-de-cotizacion/${solicitudEditando.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            drawerOpen.value = false;
        },
    });
}

function anular(solicitud: Solicitud): void {
    if (!window.confirm(`¿Anular la solicitud del ${solicitud.fecha}?`)) {
        return;
    }

    router.post(
        `/compras/solicitudes-de-cotizacion/${solicitud.id}/anular`,
        {},
        {
            preserveScroll: true,
        },
    );
}

function puedeModificar(solicitud: Solicitud): boolean {
    return solicitud.cotizaciones === 0 && solicitud.estado !== 'Anulada';
}

function puedeAnular(solicitud: Solicitud): boolean {
    return solicitud.estado !== 'Anulada' && solicitud.estado !== 'Adjudicada';
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

const detalleOpen = ref(false);
const detalleActivo = ref<DetalleSolicitud | null>(null);

const informeActivo = computed<Informe | null>(() =>
    detalleActivo.value === null ? null : (props.informes[String(detalleActivo.value.id)] ?? null),
);

function abrirDetalle(solicitud: Solicitud): void {
    detalleActivo.value = props.detalles[String(solicitud.id)] ?? null;
    detalleOpen.value = true;
}

function cerrarDetalle(): void {
    detalleOpen.value = false;
}

function puedeAdjudicar(detalle: DetalleSolicitud): boolean {
    return detalle.estado_visible !== 'Adjudicada' && detalle.estado_visible !== 'Anulada';
}

function adjudicar(cotizacionId: number): void {
    if (!window.confirm('¿Adjudicar esta cotización y emitir la orden de compra?')) {
        return;
    }

    router.post(
        '/compras/ordenes-de-compra',
        { cotizacion_id: cotizacionId },
        { preserveScroll: true },
    );
}

function claseDeEstadoVisible(estado: string): string {
    switch (estado) {
        case 'Respondida':
            return 'bg-teja-wash text-teja-deep border-teja/30';
        case 'Vencida':
            return 'bg-amber-wash text-amber border-amber/30';
        case 'Adjudicada':
            return 'bg-pine/10 text-pine border-pine/20';
        case 'Anulada':
            return 'bg-oxide/10 text-oxide border-oxide/20';
        default:
            return 'bg-slate-wash text-slate border-slate/20';
    }
}

function detalleInsumos(solicitud: Solicitud): string {
    return props.detalles[String(solicitud.id)]?.insumos ?? `${solicitud.lineas} insumo(s)`;
}

function moneda(valor: number): string {
    return valor.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function porcentaje(valor: number): string {
    return `${Math.round(valor * 100)}%`;
}
</script>

<template>
    <AppLayout title="Solicitudes de cotización">
        <Head title="Solicitudes de cotización" />

        <div class="flex flex-col gap-4">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div class="flex flex-col gap-1">
                    <h2 class="text-2xl font-semibold tracking-tight text-ink">
                        Solicitudes de cotización
                    </h2>
                    <p class="text-sm text-steel">
                        Cada solicitud se envía a los proveedores seleccionados con un enlace único
                        para que carguen su oferta.
                    </p>
                </div>
                <button
                    type="button"
                    class="rounded-control bg-teja px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-teja-deep active:scale-[0.98]"
                    @click="abrirCrear"
                >
                    Nueva solicitud
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
                data-testid="solicitud-filtros"
                @submit.prevent="buscar"
            >
                <div class="flex min-w-[180px] flex-1 flex-col gap-1">
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
                <div class="flex min-w-[150px] flex-col gap-1">
                    <label for="filtro-estado" class="text-xs font-medium text-steel">Estado</label>
                    <select
                        id="filtro-estado"
                        v-model="filtros.estado"
                        data-testid="filtro-estado"
                        class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                    >
                        <option value="">Todos</option>
                        <option value="Borrador">Borrador</option>
                        <option value="Enviada">Enviada</option>
                        <option value="Adjudicada">Adjudicada</option>
                        <option value="Anulada">Anulada</option>
                    </select>
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
                                    Código
                                </th>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Insumos
                                </th>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Emitida
                                </th>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Vence
                                </th>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Estado
                                </th>
                                <th
                                    class="px-4 py-2.5 text-right text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Proveedores
                                </th>
                                <th
                                    class="px-4 py-2.5 text-right text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Respuestas
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
                                v-for="solicitud in props.solicitudes.data"
                                :key="solicitud.id"
                                class="hover:bg-teja-wash/30"
                            >
                                <td class="px-4 py-2.5 font-mono text-xs font-semibold text-ink">
                                    {{ solicitud.codigo }}
                                </td>
                                <td class="max-w-[220px] px-4 py-2.5 text-xs text-ink">
                                    <span class="block truncate" :title="detalleInsumos(solicitud)">
                                        {{ detalleInsumos(solicitud) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 font-mono text-xs text-steel">
                                    {{ solicitud.fecha }}
                                </td>
                                <td class="px-4 py-2.5 font-mono text-xs text-steel">
                                    {{ solicitud.fecha_vencimiento }}
                                </td>
                                <td class="px-4 py-2.5 text-sm">
                                    <span
                                        class="inline-flex items-center rounded border px-2 py-0.5 text-xs font-medium"
                                        :class="claseDeEstadoVisible(solicitud.estado_visible)"
                                    >
                                        {{ solicitud.estado_visible }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono text-xs text-steel">
                                    {{ solicitud.proveedores }}
                                </td>
                                <td
                                    class="px-4 py-2.5 text-right font-mono text-xs"
                                    :class="
                                        solicitud.respuestas.recibidas > 0
                                            ? 'font-semibold text-teja-deep'
                                            : 'text-steel'
                                    "
                                    :data-testid="`respuestas-${solicitud.id}`"
                                >
                                    {{ solicitud.respuestas.recibidas }} /
                                    {{ solicitud.respuestas.esperadas }}
                                </td>
                                <td class="px-4 py-2.5 text-right text-xs whitespace-nowrap">
                                    <button
                                        type="button"
                                        class="font-medium text-teja hover:underline"
                                        :data-testid="`detalle-${solicitud.id}`"
                                        @click="abrirDetalle(solicitud)"
                                    >
                                        Ver detalle
                                    </button>
                                    <span class="mx-2 text-clay/40">|</span>
                                    <button
                                        type="button"
                                        class="font-medium text-steel hover:underline disabled:cursor-not-allowed disabled:text-clay"
                                        :disabled="!puedeModificar(solicitud)"
                                        @click="abrirModificar(solicitud)"
                                    >
                                        Modificar
                                    </button>
                                    <span class="mx-2 text-clay/40">|</span>
                                    <button
                                        type="button"
                                        class="font-medium text-steel hover:text-oxide disabled:cursor-not-allowed disabled:text-clay"
                                        :disabled="!puedeAnular(solicitud)"
                                        @click="anular(solicitud)"
                                    >
                                        Anular
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="props.solicitudes.data.length === 0"
                    class="px-4 py-10 text-center text-sm text-steel"
                >
                    {{
                        hayFiltros
                            ? 'No se encontraron solicitudes para el criterio especificado'
                            : 'No hay solicitudes de cotización registradas.'
                    }}
                </div>

                <div
                    v-if="props.solicitudes.total > 0"
                    class="flex flex-wrap items-center justify-between gap-2 border-t border-hairline px-4 py-2.5 text-xs text-steel"
                >
                    <span
                        >Mostrando {{ props.solicitudes.from }}–{{ props.solicitudes.to }} de
                        {{ props.solicitudes.total }}</span
                    >
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="link in props.solicitudes.links"
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
            v-if="detalleOpen && detalleActivo"
            class="fixed inset-0 z-40 flex justify-end bg-ink/20"
            data-testid="detalle-drawer"
            @click.self="cerrarDetalle"
        >
            <aside
                class="flex h-full w-full max-w-[680px] flex-col overflow-y-auto border-l border-hairline bg-surface p-5 shadow-diffused"
            >
                <div class="flex items-start justify-between gap-3 border-b border-hairline pb-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="num text-sm font-semibold text-ink">{{
                                detalleActivo.codigo
                            }}</span>
                            <span
                                class="inline-flex rounded border px-2 py-0.5 text-xs font-medium"
                                :class="claseDeEstadoVisible(detalleActivo.estado_visible)"
                            >
                                {{ detalleActivo.estado_visible }}
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-steel">
                            Emitida {{ detalleActivo.fecha }} · vence
                            {{ detalleActivo.fecha_vencimiento }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="text-sm text-steel hover:text-ink"
                        @click="cerrarDetalle"
                    >
                        Cerrar
                    </button>
                </div>

                <div
                    v-if="page.props.errors.solicitud || page.props.errors.cotizacion"
                    class="mt-3 rounded-control border border-oxide/20 bg-oxide/5 px-3 py-2 text-sm text-oxide"
                >
                    {{ page.props.errors.solicitud ?? page.props.errors.cotizacion }}
                </div>

                <section class="mt-4">
                    <h4 class="text-xs font-semibold tracking-wider text-steel uppercase">
                        Insumos solicitados
                    </h4>
                    <table class="mt-2 w-full text-left text-xs">
                        <thead
                            class="border-b border-hairline text-[0.6875rem] tracking-wider text-steel uppercase"
                        >
                            <tr>
                                <th class="py-1.5">Artículo</th>
                                <th class="py-1.5 text-right">Cantidad</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-hairline">
                            <tr v-for="linea in detalleActivo.lineas" :key="linea.articulo_id">
                                <td class="py-1.5 text-ink">{{ linea.articulo }}</td>
                                <td class="num py-1.5 text-right text-steel">
                                    {{ linea.cantidad_solicitada }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </section>

                <section class="mt-4">
                    <h4 class="text-xs font-semibold tracking-wider text-steel uppercase">
                        Proveedores
                    </h4>
                    <ul
                        class="mt-2 divide-y divide-hairline rounded-control border border-hairline"
                    >
                        <li
                            v-for="proveedor in detalleActivo.proveedores"
                            :key="proveedor.id"
                            class="flex items-center justify-between gap-2 px-3 py-2 text-xs"
                        >
                            <span class="text-ink">{{ proveedor.razon_social }}</span>
                            <span
                                v-if="proveedor.respondio"
                                class="rounded bg-pine/10 px-2 py-0.5 font-medium text-pine"
                                >Respondió</span
                            >
                            <span
                                v-else-if="proveedor.enlace_vigente"
                                class="rounded bg-slate-wash px-2 py-0.5 text-slate"
                                >Esperando respuesta</span
                            >
                            <span v-else class="rounded bg-workshop px-2 py-0.5 text-steel"
                                >Sin enlace</span
                            >
                        </li>
                    </ul>
                </section>

                <section class="mt-4">
                    <h4 class="text-xs font-semibold tracking-wider text-steel uppercase">
                        Cotizaciones recibidas
                    </h4>

                    <p
                        v-if="detalleActivo.cotizaciones.length === 0"
                        class="mt-2 rounded-control bg-workshop px-3 py-2 text-xs text-steel"
                    >
                        Todavía no hay cotizaciones recibidas para esta solicitud.
                    </p>

                    <div
                        v-for="cotizacion in detalleActivo.cotizaciones"
                        :key="cotizacion.id"
                        class="mt-2 rounded-control border border-hairline"
                        :data-testid="`cotizacion-${cotizacion.id}`"
                    >
                        <div
                            class="flex flex-wrap items-center justify-between gap-2 border-b border-hairline bg-workshop/50 px-3 py-2"
                        >
                            <div>
                                <span class="text-sm font-medium text-ink">{{
                                    cotizacion.proveedor
                                }}</span>
                                <span class="ml-2 text-[0.6875rem] text-steel"
                                    >recibida {{ cotizacion.fecha }}</span
                                >
                            </div>
                            <button
                                v-if="puedeAdjudicar(detalleActivo)"
                                type="button"
                                class="rounded-control bg-teja px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-teja-deep active:scale-[0.98]"
                                :data-testid="`adjudicar-${cotizacion.id}`"
                                @click="adjudicar(cotizacion.id)"
                            >
                                Adjudicar y emitir OC
                            </button>
                            <span v-else class="text-[0.6875rem] text-steel">No adjudicable</span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead
                                    class="border-b border-hairline text-[0.625rem] tracking-wider text-steel uppercase"
                                >
                                    <tr>
                                        <th class="px-3 py-1.5">Artículo</th>
                                        <th class="px-2 py-1.5 text-right">Precio u.</th>
                                        <th class="px-2 py-1.5 text-right">Pedidas</th>
                                        <th class="px-2 py-1.5 text-right">Ofertadas</th>
                                        <th class="px-2 py-1.5">Entrega</th>
                                        <th class="px-3 py-1.5 text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-hairline">
                                    <tr v-for="linea in cotizacion.lineas" :key="linea.articulo">
                                        <td class="px-3 py-1.5 text-ink">{{ linea.articulo }}</td>
                                        <td class="num px-2 py-1.5 text-right text-steel">
                                            $ {{ moneda(linea.precio_unitario) }}
                                        </td>
                                        <td class="num px-2 py-1.5 text-right text-steel">
                                            {{ linea.cantidad_solicitada }}
                                        </td>
                                        <td
                                            class="num px-2 py-1.5 text-right"
                                            :class="
                                                linea.cantidad_ofertada < linea.cantidad_solicitada
                                                    ? 'font-medium text-amber'
                                                    : 'text-steel'
                                            "
                                        >
                                            {{ linea.cantidad_ofertada }}
                                        </td>
                                        <td class="px-2 py-1.5 text-steel">
                                            {{ linea.fecha_prometida ?? '—' }}
                                        </td>
                                        <td class="num px-3 py-1.5 text-right text-ink">
                                            $ {{ moneda(linea.subtotal) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div
                            class="flex flex-wrap items-center justify-end gap-4 border-t border-hairline px-3 py-2 text-xs"
                        >
                            <span class="text-steel"
                                >Costo de lo pedido:
                                <span class="num text-ink"
                                    >$ {{ moneda(cotizacion.costo_pedido) }}</span
                                ></span
                            >
                            <span class="text-steel"
                                >Subtotal ofertado:
                                <span class="num font-semibold text-ink"
                                    >$ {{ moneda(cotizacion.total_ofertado) }}</span
                                ></span
                            >
                        </div>
                    </div>
                </section>

                <section v-if="informeActivo && informeActivo.cotizaciones.length > 0" class="mt-4">
                    <h4 class="text-xs font-semibold tracking-wider text-steel uppercase">
                        Informe multicriterio
                    </h4>
                    <div class="mt-2 overflow-x-auto rounded-control border border-hairline">
                        <table class="w-full text-left text-xs" data-testid="detalle-informe">
                            <thead
                                class="border-b border-hairline bg-workshop text-[0.625rem] tracking-wider text-steel uppercase"
                            >
                                <tr>
                                    <th class="px-2 py-2">Proveedor</th>
                                    <th class="px-2 py-2 text-right">Precio</th>
                                    <th class="px-2 py-2 text-right">Días</th>
                                    <th class="px-2 py-2 text-right">Cobertura</th>
                                    <th class="px-2 py-2 text-right">Puntaje</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-hairline">
                                <tr
                                    v-for="fila in informeActivo.cotizaciones"
                                    :key="fila.cotizacion_id"
                                    :class="
                                        informeActivo.recomendada_id === fila.cotizacion_id
                                            ? 'bg-pine-wash/60'
                                            : ''
                                    "
                                >
                                    <td class="px-2 py-1.5 text-ink">
                                        {{ fila.proveedor }}
                                        <span
                                            v-if="
                                                informeActivo.recomendada_id === fila.cotizacion_id
                                            "
                                            class="ml-1 text-[0.625rem] font-semibold text-pine"
                                            >recomendada</span
                                        >
                                    </td>
                                    <td class="num px-2 py-1.5 text-right text-steel">
                                        $ {{ moneda(fila.precio) }}
                                    </td>
                                    <td class="num px-2 py-1.5 text-right text-steel">
                                        {{ fila.dias }}
                                    </td>
                                    <td class="num px-2 py-1.5 text-right text-steel">
                                        {{ porcentaje(fila.cobertura) }}
                                    </td>
                                    <td class="num px-2 py-1.5 text-right font-semibold text-ink">
                                        {{ fila.puntaje }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </aside>
        </div>

        <div
            v-if="drawerOpen"
            class="fixed inset-0 z-40 flex justify-end bg-ink/20"
            @click.self="cerrarDrawer"
        >
            <aside
                class="flex h-full w-full max-w-[480px] flex-col overflow-y-auto border-l border-hairline bg-surface p-5 shadow-diffused"
            >
                <div class="flex items-center justify-between border-b border-hairline pb-3">
                    <h3 class="text-lg font-semibold text-ink">
                        {{ modo === 'crear' ? 'Nueva solicitud' : 'Modificar solicitud' }}
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
                    data-testid="solicitud-form"
                    @submit.prevent="guardar"
                >
                    <p
                        v-if="modo === 'editar'"
                        class="rounded-control bg-workshop px-3 py-2 text-xs text-steel"
                    >
                        Al guardar se reemplazan las líneas y los proveedores de la solicitud.
                    </p>

                    <div class="flex flex-col gap-1.5">
                        <label for="fecha_vencimiento" class="text-xs font-medium text-steel"
                            >Fecha de vencimiento</label
                        >
                        <input
                            id="fecha_vencimiento"
                            v-model="form.fecha_vencimiento"
                            type="date"
                            required
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        />
                        <p v-if="form.errors.fecha_vencimiento" class="text-xs text-oxide">
                            {{ form.errors.fecha_vencimiento }}
                        </p>
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
                            v-for="(linea, indice) in form.lineas"
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
                                v-model="linea.cantidad_solicitada"
                                type="number"
                                step="0.01"
                                min="0.01"
                                required
                                placeholder="Cantidad"
                                class="w-28 rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                            />
                            <button
                                v-if="form.lineas.length > 1"
                                type="button"
                                class="text-sm text-oxide hover:text-oxide/80"
                                @click="quitarLinea(indice)"
                            >
                                Quitar
                            </button>
                        </div>
                        <p v-if="form.errors.lineas" class="text-xs text-oxide">
                            {{ form.errors.lineas }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-2">
                        <span class="text-xs font-medium text-steel">Proveedores</span>
                        <div
                            v-if="props.proveedores.length === 0"
                            class="rounded-control bg-workshop px-3 py-2 text-xs text-steel"
                        >
                            No hay proveedores activos disponibles.
                        </div>
                        <label
                            v-for="proveedor in props.proveedores"
                            :key="proveedor.id"
                            class="flex items-center gap-2 text-sm text-ink"
                        >
                            <input
                                v-model="form.proveedores"
                                type="checkbox"
                                :value="proveedor.id"
                                class="rounded border-hairline text-teja focus:ring-teja"
                            />
                            {{ proveedor.razon_social }}
                        </label>
                        <p v-if="form.errors.proveedores" class="text-xs text-oxide">
                            {{ form.errors.proveedores }}
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
