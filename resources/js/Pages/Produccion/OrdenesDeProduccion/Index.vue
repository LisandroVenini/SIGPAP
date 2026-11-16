<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import type { ComponentPublicInstance } from 'vue';
import AppLayout from '../../../Layouts/AppLayout.vue';
import DatePicker from '@/Components/DatePicker.vue';
import { hoyISO } from '@/lib/fechas';

const hoy = hoyISO();

interface Producto {
    id: number;
    codigo_sku: string;
    descripcion: string;
}

interface Planificacion {
    id: number;
    nombre: string;
}

interface OperarioDisponible {
    id: number;
    nombre: string;
}

interface MaquinariaDisponible {
    id: number;
    nombre: string;
    estado: string;
    seleccionable: boolean;
}

interface EtapaOpcion {
    id: number;
    nombre: string;
}

interface EtapaDetalle {
    etapa_id: number;
    nombre: string;
    producida: number;
    rechazadas: number;
    faltante: number;
}

interface Parte {
    id: number;
    etapa_id: number;
    etapa: string;
    fecha: string;
    cantidad: number;
    maquinaria: string | null;
    observaciones: string | null;
}

interface Orden {
    id: number;
    producto: Producto | null;
    planificacion: string | null;
    estado: string;
    cantidad_requerida: number;
    cantidad_producida: number;
    fecha_entrega: string;
    fecha_inicio: string | null;
    fecha_prevista_inicio: string | null;
    prioridad_actual: number;
    operarios: string[];
    operario_ids: number[];
    maquinarias: string[];
    maquinaria_ids: number[];
    etapas: string[];
    etapas_con_ids: EtapaOpcion[];
    etapas_detalle: EtapaDetalle[];
    partes: Parte[];
    conformado_primera_etapa: number;
    conformado_ultima_etapa: number;
    aprobadas_ultima_etapa: number | null;
    tiene_control_calidad: boolean;
    avance: number;
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

type Drawer =
    | 'cerrado'
    | 'nueva'
    | 'modificar'
    | 'anular'
    | 'asignar'
    | 'calidad'
    | 'cerrar'
    | 'posponer'
    | 'reanudar';

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
    operariosDisponibles: OperarioDisponible[];
    maquinariasDisponibles: MaquinariaDisponible[];
}>();

const page = usePage<SharedProps>();

const busqueda = ref(props.filtros.buscar ?? '');
const estado = ref(props.filtros.estado ?? '');
const fechaDesde = ref(props.filtros.fecha_desde ?? '');
const fechaHasta = ref(props.filtros.fecha_hasta ?? '');

const drawerAbierto = ref<Drawer>('cerrado');
const ordenActiva = ref<Orden | null>(null);
const filaExpandida = ref<number | null>(null);
const menuAbierto = ref<number | null>(null);
const posicionMenu = ref<{ top: number; left: number } | null>(null);
const ANCHO_MENU_ACCIONES = 208;

const menusPorOrden = new Map<number, HTMLElement>();
const botonesMenuPorOrden = new Map<number, HTMLButtonElement>();

function toggleFila(ordenId: number): void {
    filaExpandida.value = filaExpandida.value === ordenId ? null : ordenId;
}

function toggleMenu(ordenId: number): void {
    if (menuAbierto.value === ordenId) {
        menuAbierto.value = null;

        return;
    }

    const boton = botonesMenuPorOrden.get(ordenId);
    const rect = boton?.getBoundingClientRect();

    posicionMenu.value = rect
        ? {
              top: rect.bottom + 4,
              left: Math.max(8, rect.right - ANCHO_MENU_ACCIONES),
          }
        : null;
    menuAbierto.value = ordenId;
}

function cerrarMenuPorScroll(): void {
    menuAbierto.value = null;
}

function ejecutarAccion(accion: Accion): void {
    menuAbierto.value = null;
    accion.onClick();
}

function registrarMenu(ordenId: number, elemento: Element | ComponentPublicInstance | null): void {
    if (elemento instanceof HTMLElement) {
        menusPorOrden.set(ordenId, elemento);
    } else {
        menusPorOrden.delete(ordenId);
    }
}

function registrarBotonMenu(
    ordenId: number,
    elemento: Element | ComponentPublicInstance | null,
): void {
    if (elemento instanceof HTMLButtonElement) {
        botonesMenuPorOrden.set(ordenId, elemento);
    } else {
        botonesMenuPorOrden.delete(ordenId);
    }
}

function cerrarMenuSiCorresponde(evento: Event): void {
    if (menuAbierto.value === null) {
        return;
    }

    const menu = menusPorOrden.get(menuAbierto.value);

    if (menu && !menu.contains(evento.target as Node)) {
        menuAbierto.value = null;
    }
}

function cerrarMenuConEscape(evento: KeyboardEvent): void {
    if (evento.key !== 'Escape' || menuAbierto.value === null) {
        return;
    }

    const ordenId = menuAbierto.value;
    menuAbierto.value = null;
    botonesMenuPorOrden.get(ordenId)?.focus();
}

onMounted(() => {
    document.addEventListener('pointerdown', cerrarMenuSiCorresponde);
    document.addEventListener('keydown', cerrarMenuConEscape);
    window.addEventListener('scroll', cerrarMenuPorScroll, true);
});

onBeforeUnmount(() => {
    document.removeEventListener('pointerdown', cerrarMenuSiCorresponde);
    document.removeEventListener('keydown', cerrarMenuConEscape);
    window.removeEventListener('scroll', cerrarMenuPorScroll, true);
    menusPorOrden.clear();
    botonesMenuPorOrden.clear();
});

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

const formAsignar = useForm({
    operarios: [] as number[],
    maquinarias: [] as number[],
});

const maximoRechazadas = computed<number | undefined>(() => {
    if (!ordenActiva.value) {
        return undefined;
    }

    if (formCalidad.parte_diario_id !== '') {
        const parte = ordenActiva.value.partes.find(
            (registro) => String(registro.id) === String(formCalidad.parte_diario_id),
        );

        return parte ? Number(parte.cantidad) : undefined;
    }

    return Number(ordenActiva.value.conformado_ultima_etapa);
});

const formCalidad = useForm({
    parte_diario_id: '' as string | number,
    cantidad_rechazada: '' as string | number,
    motivo: '',
});

const formCerrar = useForm({
    unidades_finales: '' as string | number,
});

const formPosponer = useForm({
    fecha_prevista_inicio: '',
    liberar_reservas: false,
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

function abrirAsignar(orden: Orden): void {
    drawerAbierto.value = 'asignar';
    ordenActiva.value = orden;
    formAsignar.clearErrors();
    formAsignar.operarios = [...orden.operario_ids];
    formAsignar.maquinarias = [...orden.maquinaria_ids];
}

function abrirCalidad(orden: Orden): void {
    drawerAbierto.value = 'calidad';
    ordenActiva.value = orden;
    formCalidad.clearErrors();
    formCalidad.parte_diario_id = '';
    formCalidad.cantidad_rechazada = '';
    formCalidad.motivo = '';
}

function abrirCerrar(orden: Orden): void {
    drawerAbierto.value = 'cerrar';
    ordenActiva.value = orden;
    formCerrar.clearErrors();
    formCerrar.unidades_finales = orden.cantidad_requerida;
}

function abrirPosponer(orden: Orden): void {
    drawerAbierto.value = 'posponer';
    ordenActiva.value = orden;
    formPosponer.clearErrors();
    formPosponer.fecha_prevista_inicio = orden.fecha_prevista_inicio ?? '';
    formPosponer.liberar_reservas = false;
    formPosponer.motivo = '';
}

function abrirReanudar(orden: Orden): void {
    drawerAbierto.value = 'reanudar';
    ordenActiva.value = orden;
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

function guardarAsignacion(): void {
    if (!ordenActiva.value) {
        return;
    }

    formAsignar.put(`/produccion/ordenes-de-produccion/${ordenActiva.value.id}/recursos`, {
        preserveScroll: true,
        onSuccess: () => {
            cerrarDrawer();
        },
    });
}

function iniciarOrden(orden: Orden): void {
    router.post(
        `/produccion/ordenes-de-produccion/${orden.id}/iniciar`,
        {},
        {
            preserveScroll: true,
        },
    );
}

function guardarCalidad(): void {
    if (!ordenActiva.value) {
        return;
    }

    formCalidad.post(`/produccion/ordenes-de-produccion/${ordenActiva.value.id}/calidad`, {
        preserveScroll: true,
        onSuccess: () => {
            cerrarDrawer();
        },
    });
}

function guardarCierre(): void {
    if (!ordenActiva.value) {
        return;
    }

    formCerrar.post(`/produccion/ordenes-de-produccion/${ordenActiva.value.id}/cerrar`, {
        preserveScroll: true,
        onSuccess: () => {
            cerrarDrawer();
        },
    });
}

function guardarPosposicion(): void {
    if (!ordenActiva.value) {
        return;
    }

    formPosponer.post(`/produccion/ordenes-de-produccion/${ordenActiva.value.id}/posponer`, {
        preserveScroll: true,
        onSuccess: () => {
            cerrarDrawer();
        },
    });
}

function guardarReanudacion(): void {
    if (!ordenActiva.value) {
        return;
    }

    router.post(
        `/produccion/ordenes-de-produccion/${ordenActiva.value.id}/reanudar`,
        {},
        {
            preserveScroll: true,
        },
    );
}

function puedeAsignar(estado: string): boolean {
    return ['Pendiente', 'Pendiente por Insumos', 'Asignada'].includes(estado);
}

function puedeRegistrarCalidad(estado: string): boolean {
    return estado === 'En Curso';
}

function puedeCerrar(estado: string): boolean {
    return estado === 'En Curso';
}

function puedePosponer(estado: string): boolean {
    return ['Pendiente', 'Pendiente por Insumos', 'Asignada', 'En Curso'].includes(estado);
}

function puedeReanudar(estado: string): boolean {
    return estado === 'Pospuesta';
}

function puedeAnular(orden: Orden): boolean {
    return orden.fecha_inicio === null && orden.partes.length === 0;
}

function tieneAcciones(orden: Orden): boolean {
    return !['Cerrada', 'Anulada'].includes(orden.estado);
}

interface Accion {
    key: string;
    label: string;
    class?: string;
    disabled?: boolean;
    separada?: boolean;
    onClick: () => void;
}

function accionesDeOrden(orden: Orden): Accion[] {
    const acciones: Accion[] = [];

    acciones.push({
        key: 'modificar',
        label: 'Modificar',
        onClick: (): void => abrirModificar(orden),
    });

    if (puedeAnular(orden)) {
        acciones.push({
            key: 'anular',
            label: 'Anular',
            class: 'text-oxide hover:text-oxide/80',
            separada: true,
            onClick: (): void => abrirAnular(orden),
        });
    }

    acciones.push({
        key: 'asignar',
        label: 'Asignar recursos',
        disabled: !puedeAsignar(orden.estado),
        onClick: (): void => abrirAsignar(orden),
    });

    if (orden.estado === 'Asignada') {
        acciones.push({
            key: 'iniciar',
            label: 'Iniciar',
            class: 'text-purple-700 hover:text-purple-900',
            onClick: (): void => iniciarOrden(orden),
        });
    }

    if (puedeRegistrarCalidad(orden.estado)) {
        acciones.push({
            key: 'calidad',
            label: 'Registrar calidad',
            class: 'text-purple-700 hover:text-purple-900',
            onClick: (): void => abrirCalidad(orden),
        });
    }

    if (puedeCerrar(orden.estado)) {
        acciones.push({
            key: 'cerrar',
            label: 'Cerrar orden',
            class: 'text-teja hover:text-teja-deep',
            onClick: (): void => abrirCerrar(orden),
        });
    }

    if (puedePosponer(orden.estado)) {
        acciones.push({
            key: 'posponer',
            label: 'Posponer',
            class: 'text-steel hover:text-ink',
            onClick: (): void => abrirPosponer(orden),
        });
    }

    if (puedeReanudar(orden.estado)) {
        acciones.push({
            key: 'reanudar',
            label: 'Reanudar',
            class: 'text-teja hover:text-teja-deep',
            onClick: (): void => abrirReanudar(orden),
        });
    }

    return acciones;
}

function textoAvance(orden: Orden): string {
    const conformado = orden.partes.reduce((total, parte) => total + parte.cantidad, 0);

    return `${conformado.toFixed(0)} / ${orden.cantidad_requerida}`;
}

function estadoEtapa(etapa: EtapaDetalle): string {
    if (etapa.producida === 0) {
        return 'Sin iniciar';
    }

    if (etapa.faltante === 0) {
        return 'Completada';
    }

    return 'En curso';
}

function claseBadgeEtapa(estado: string): string {
    const clases: Record<string, string> = {
        'Sin iniciar': 'bg-workshop text-steel',
        'En curso': 'bg-purple-100 text-purple-800',
        Completada: 'bg-pine-wash text-pine',
    };

    return clases[estado] ?? 'bg-workshop text-steel';
}

function claseBadge(estado: string): string {
    const clases: Record<string, string> = {
        Pendiente: 'bg-pine-wash text-pine',
        'Pendiente por Insumos': 'bg-amber-100 text-amber-800',
        Asignada: 'bg-blue-100 text-blue-800',
        'En Curso': 'bg-purple-100 text-purple-800',
        Pospuesta: 'bg-stone-100 text-stone-700',
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

            <div
                v-if="page.props.errors.insumos"
                class="rounded-control border border-oxide/20 bg-oxide/5 px-4 py-2.5 text-sm text-oxide"
            >
                {{ page.props.errors.insumos }}
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
                        <DatePicker id="fecha_desde" v-model="fechaDesde" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label for="fecha_hasta" class="text-xs font-medium text-steel"
                            >Hasta</label
                        >
                        <DatePicker
                            id="fecha_hasta"
                            v-model="fechaHasta"
                            :min="fechaDesde || null"
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
                                    class="px-4 py-2.5 text-right text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Avance
                                </th>
                                <th
                                    class="px-4 py-2.5 text-right text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Entrega
                                </th>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Recursos
                                </th>
                                <th
                                    class="px-4 py-2.5 text-right text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-hairline">
                            <template v-for="orden in props.ordenes.data" :key="orden.id">
                                <tr
                                    class="cursor-pointer hover:bg-teja-wash/30"
                                    @click="toggleFila(orden.id)"
                                >
                                    <td class="px-4 py-2.5 text-sm font-medium text-ink">
                                        <div class="flex items-start gap-2">
                                            <button
                                                type="button"
                                                :data-testid="`expandir-orden-${orden.id}`"
                                                :aria-expanded="filaExpandida === orden.id"
                                                :aria-controls="`etapas-orden-${orden.id}`"
                                                :aria-label="
                                                    filaExpandida === orden.id
                                                        ? 'Ocultar etapas'
                                                        : 'Mostrar etapas'
                                                "
                                                class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-control text-steel transition-colors hover:bg-workshop hover:text-ink"
                                                @click.stop="toggleFila(orden.id)"
                                            >
                                                <svg
                                                    class="h-4 w-4 transition-transform duration-150"
                                                    :class="
                                                        filaExpandida === orden.id
                                                            ? 'rotate-90'
                                                            : ''
                                                    "
                                                    viewBox="0 0 20 20"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="1.75"
                                                    aria-hidden="true"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M7.5 4.5 13 10l-5.5 5.5"
                                                    />
                                                </svg>
                                            </button>
                                            <div class="min-w-0">
                                                <span class="block">{{
                                                    orden.producto?.descripcion ?? '—'
                                                }}</span>
                                                <span class="block text-xs text-steel">
                                                    <span class="num">{{
                                                        orden.producto?.codigo_sku ?? ''
                                                    }}</span>
                                                    <template v-if="orden.planificacion">
                                                        <span class="text-clay/70"> · </span
                                                        >{{ orden.planificacion }}
                                                    </template>
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2.5 text-sm">
                                        <span
                                            class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                            :class="claseBadge(orden.estado)"
                                        >
                                            {{ orden.estado }}
                                        </span>
                                    </td>
                                    <td class="num px-4 py-2.5 text-right text-xs text-steel">
                                        {{ orden.cantidad_requerida }}
                                    </td>
                                    <td class="num px-4 py-2.5 text-right text-xs text-steel">
                                        {{ orden.cantidad_producida }}
                                    </td>
                                    <td class="px-4 py-2.5 text-right text-xs text-steel">
                                        <div class="flex items-center justify-end gap-2">
                                            <div
                                                class="h-2 w-16 overflow-hidden rounded-full bg-workshop"
                                            >
                                                <div
                                                    class="h-full bg-teja"
                                                    :style="{
                                                        width: `${Math.min(100, orden.avance)}%`,
                                                    }"
                                                ></div>
                                            </div>
                                            <span class="num text-xs">{{
                                                textoAvance(orden)
                                            }}</span>
                                        </div>
                                    </td>
                                    <td class="num px-4 py-2.5 text-right text-xs text-steel">
                                        {{ orden.fecha_entrega }}
                                    </td>
                                    <td class="px-4 py-2.5 text-xs text-steel">
                                        <template
                                            v-if="
                                                orden.operarios.length > 0 ||
                                                orden.maquinarias.length > 0
                                            "
                                        >
                                            <div v-if="orden.operarios.length > 0">
                                                {{ orden.operarios.join(', ') }}
                                            </div>
                                            <div v-if="orden.maquinarias.length > 0">
                                                {{ orden.maquinarias.join(', ') }}
                                            </div>
                                        </template>
                                        <span v-else>—</span>
                                    </td>
                                    <td class="px-4 py-2.5 text-right text-xs" @click.stop>
                                        <span v-if="!tieneAcciones(orden)" class="text-clay"
                                            >—</span
                                        >
                                        <div
                                            v-else
                                            :ref="(elemento) => registrarMenu(orden.id, elemento)"
                                            class="relative inline-block text-left"
                                        >
                                            <button
                                                :ref="
                                                    (elemento) =>
                                                        registrarBotonMenu(orden.id, elemento)
                                                "
                                                type="button"
                                                :data-testid="`menu-orden-${orden.id}`"
                                                :aria-label="`Acciones de la orden ${orden.id}`"
                                                aria-haspopup="true"
                                                :aria-expanded="menuAbierto === orden.id"
                                                class="flex h-7 w-7 items-center justify-center rounded-control text-steel transition-colors hover:bg-workshop hover:text-ink"
                                                @click="toggleMenu(orden.id)"
                                            >
                                                <svg
                                                    class="h-4 w-4"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                    aria-hidden="true"
                                                >
                                                    <circle cx="4" cy="10" r="1.5" />
                                                    <circle cx="10" cy="10" r="1.5" />
                                                    <circle cx="16" cy="10" r="1.5" />
                                                </svg>
                                            </button>

                                            <div
                                                v-if="menuAbierto === orden.id && posicionMenu"
                                                data-testid="menu-acciones"
                                                class="fixed z-50 w-52 rounded-panel border border-hairline bg-surface p-1.5 text-left shadow-[0_12px_28px_rgba(34,40,31,0.14)]"
                                                :style="{
                                                    top: `${posicionMenu.top}px`,
                                                    left: `${posicionMenu.left}px`,
                                                }"
                                            >
                                                <button
                                                    v-for="accion in accionesDeOrden(orden)"
                                                    :key="accion.key"
                                                    type="button"
                                                    class="block w-full rounded px-2.5 py-1.5 text-left text-sm font-medium whitespace-nowrap disabled:text-clay/40 disabled:hover:text-clay/40"
                                                    :class="[
                                                        accion.class ??
                                                            'text-teja hover:text-teja-deep',
                                                        accion.separada
                                                            ? 'mt-1 border-t border-hairline pt-2'
                                                            : '',
                                                    ]"
                                                    :disabled="accion.disabled"
                                                    @click="ejecutarAccion(accion)"
                                                >
                                                    {{ accion.label }}
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                <tr
                                    v-if="filaExpandida === orden.id"
                                    :id="`etapas-orden-${orden.id}`"
                                    :data-testid="`etapas-orden-${orden.id}`"
                                    class="bg-workshop/40"
                                >
                                    <td colspan="8" class="px-4 py-3">
                                        <table
                                            v-if="orden.etapas_detalle.length > 0"
                                            class="w-full text-left"
                                        >
                                            <thead>
                                                <tr>
                                                    <th
                                                        class="px-2 py-1.5 text-[0.625rem] font-semibold tracking-wider text-steel uppercase"
                                                    >
                                                        Etapa
                                                    </th>
                                                    <th
                                                        class="px-2 py-1.5 text-[0.625rem] font-semibold tracking-wider text-steel uppercase"
                                                    >
                                                        Estado
                                                    </th>
                                                    <th
                                                        class="px-2 py-1.5 text-right text-[0.625rem] font-semibold tracking-wider text-steel uppercase"
                                                    >
                                                        Producida
                                                    </th>
                                                    <th
                                                        class="px-2 py-1.5 text-right text-[0.625rem] font-semibold tracking-wider text-steel uppercase"
                                                    >
                                                        Faltante
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-hairline">
                                                <tr
                                                    v-for="etapa in orden.etapas_detalle"
                                                    :key="etapa.etapa_id"
                                                >
                                                    <td class="px-2 py-1.5 text-sm text-ink">
                                                        {{ etapa.nombre }}
                                                    </td>
                                                    <td class="px-2 py-1.5 text-sm">
                                                        <span
                                                            class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                                            :class="
                                                                claseBadgeEtapa(estadoEtapa(etapa))
                                                            "
                                                        >
                                                            {{ estadoEtapa(etapa) }}
                                                        </span>
                                                    </td>
                                                    <td
                                                        class="num px-2 py-1.5 text-right text-xs text-steel"
                                                    >
                                                        {{ etapa.producida }}
                                                    </td>
                                                    <td
                                                        class="num px-2 py-1.5 text-right text-xs text-steel"
                                                    >
                                                        {{ etapa.faltante }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <p v-else class="px-2 py-1.5 text-sm text-steel">
                                            Sin etapas registradas.
                                        </p>
                                    </td>
                                </tr>
                            </template>
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
                                  : drawerAbierto === 'anular'
                                    ? 'Anular orden'
                                    : drawerAbierto === 'asignar'
                                      ? 'Asignar recursos'
                                      : drawerAbierto === 'calidad'
                                        ? 'Registrar control de calidad'
                                        : drawerAbierto === 'cerrar'
                                          ? 'Cerrar orden'
                                          : drawerAbierto === 'posponer'
                                            ? 'Posponer orden'
                                            : 'Reanudar orden'
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
                        <DatePicker
                            id="fecha_entrega"
                            v-model="formNueva.fecha_entrega"
                            :min="hoy"
                            required
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
                        <DatePicker
                            id="fecha_entrega_modificar"
                            v-model="formModificar.fecha_entrega"
                            :min="hoy"
                            required
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

                <form
                    v-if="drawerAbierto === 'asignar' && ordenActiva"
                    class="mt-4 flex flex-col gap-4"
                    data-testid="asignar-recursos-form"
                    @submit.prevent="guardarAsignacion"
                >
                    <div
                        v-if="ordenActiva.etapas.length > 0"
                        class="flex flex-col gap-1.5 rounded-control border border-hairline bg-canvas p-3"
                    >
                        <span class="text-xs font-medium text-steel"
                            >Etapas de la hoja de ruta</span
                        >
                        <ul class="flex flex-wrap gap-1 text-sm text-ink">
                            <li
                                v-for="etapa in ordenActiva.etapas"
                                :key="etapa"
                                class="rounded-full bg-workshop px-2 py-0.5 text-xs"
                            >
                                {{ etapa }}
                            </li>
                        </ul>
                    </div>

                    <div class="flex flex-col gap-2">
                        <span class="text-xs font-medium text-steel">Operarios</span>
                        <label
                            v-for="operario in props.operariosDisponibles"
                            :key="operario.id"
                            class="flex items-center gap-2 text-sm text-ink"
                        >
                            <input
                                v-model="formAsignar.operarios"
                                type="checkbox"
                                :value="operario.id"
                                class="text-teja focus:ring-teja"
                            />
                            {{ operario.nombre }}
                        </label>
                    </div>

                    <div class="flex flex-col gap-2">
                        <span class="text-xs font-medium text-steel">Maquinarias</span>
                        <label
                            v-for="maquinaria in props.maquinariasDisponibles"
                            :key="maquinaria.id"
                            class="flex items-center gap-2 text-sm"
                            :class="maquinaria.seleccionable ? 'text-ink' : 'text-clay/70'"
                        >
                            <input
                                v-model="formAsignar.maquinarias"
                                type="checkbox"
                                :value="maquinaria.id"
                                :disabled="!maquinaria.seleccionable"
                                class="text-teja focus:ring-teja"
                            />
                            {{ maquinaria.nombre }}
                            <span class="text-xs text-steel">({{ maquinaria.estado }})</span>
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
                            :disabled="formAsignar.processing"
                            class="rounded-control bg-teja px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-teja-deep active:scale-[0.98] disabled:opacity-60"
                        >
                            {{ formAsignar.processing ? 'Guardando…' : 'Guardar' }}
                        </button>
                    </div>
                </form>

                <form
                    v-if="drawerAbierto === 'calidad' && ordenActiva"
                    class="mt-4 flex flex-col gap-4"
                    data-testid="calidad-form"
                    @submit.prevent="guardarCalidad"
                >
                    <div class="flex flex-col gap-1.5">
                        <label for="parte_diario_id" class="text-xs font-medium text-steel"
                            >Parte</label
                        >
                        <select
                            id="parte_diario_id"
                            v-model="formCalidad.parte_diario_id"
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        >
                            <option value="">Orden completa</option>
                            <option
                                v-for="parte in ordenActiva.partes"
                                :key="parte.id"
                                :value="parte.id"
                            >
                                {{ parte.etapa }} — {{ parte.cantidad }}
                            </option>
                        </select>
                        <div v-if="formCalidad.errors.parte_diario_id" class="text-xs text-oxide">
                            {{ formCalidad.errors.parte_diario_id }}
                        </div>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="cantidad_rechazada" class="text-xs font-medium text-steel"
                            >Cantidad rechazada</label
                        >
                        <input
                            id="cantidad_rechazada"
                            v-model="formCalidad.cantidad_rechazada"
                            type="number"
                            step="0.01"
                            min="0"
                            :max="maximoRechazadas"
                            required
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        />
                        <div
                            v-if="formCalidad.errors.cantidad_rechazada"
                            class="text-xs text-oxide"
                        >
                            {{ formCalidad.errors.cantidad_rechazada }}
                        </div>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="motivo_calidad" class="text-xs font-medium text-steel"
                            >Motivo del rechazo</label
                        >
                        <textarea
                            id="motivo_calidad"
                            v-model="formCalidad.motivo"
                            rows="3"
                            maxlength="255"
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        ></textarea>
                        <div v-if="formCalidad.errors.motivo" class="text-xs text-oxide">
                            {{ formCalidad.errors.motivo }}
                        </div>
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
                            :disabled="formCalidad.processing"
                            class="rounded-control bg-teja px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-teja-deep active:scale-[0.98] disabled:opacity-60"
                        >
                            {{ formCalidad.processing ? 'Guardando…' : 'Guardar' }}
                        </button>
                    </div>
                </form>

                <form
                    v-if="drawerAbierto === 'cerrar' && ordenActiva"
                    class="mt-4 flex flex-col gap-4"
                    data-testid="cerrar-orden-form"
                    @submit.prevent="guardarCierre"
                >
                    <div
                        class="flex flex-col gap-2 rounded-control border border-hairline bg-canvas p-3 text-sm text-ink"
                    >
                        <div class="flex justify-between">
                            <span class="text-steel">Requerido</span>
                            <span class="font-mono">{{ ordenActiva.cantidad_requerida }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-steel">Conformado última etapa</span>
                            <span class="font-mono">{{ ordenActiva.conformado_ultima_etapa }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-steel">Aprobadas</span>
                            <span class="font-mono">{{
                                ordenActiva.aprobadas_ultima_etapa ?? '—'
                            }}</span>
                        </div>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="unidades_finales" class="text-xs font-medium text-steel"
                            >Unidades finales</label
                        >
                        <input
                            id="unidades_finales"
                            v-model="formCerrar.unidades_finales"
                            type="number"
                            step="0.01"
                            min="0.01"
                            required
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        />
                        <div v-if="formCerrar.errors.unidades_finales" class="text-xs text-oxide">
                            {{ formCerrar.errors.unidades_finales }}
                        </div>
                        <div v-if="page.props.errors.calidad" class="text-xs text-oxide">
                            {{ page.props.errors.calidad }}
                        </div>
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
                            :disabled="formCerrar.processing"
                            class="rounded-control bg-teja px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-teja-deep active:scale-[0.98] disabled:opacity-60"
                        >
                            {{ formCerrar.processing ? 'Cerrando…' : 'Cerrar orden' }}
                        </button>
                    </div>
                </form>

                <form
                    v-if="drawerAbierto === 'posponer' && ordenActiva"
                    class="mt-4 flex flex-col gap-4"
                    data-testid="posponer-orden-form"
                    @submit.prevent="guardarPosposicion"
                >
                    <div class="flex flex-col gap-1.5">
                        <label for="fecha_prevista_inicio" class="text-xs font-medium text-steel"
                            >Nueva fecha prevista de inicio</label
                        >
                        <DatePicker
                            id="fecha_prevista_inicio"
                            v-model="formPosponer.fecha_prevista_inicio"
                            :min="hoy"
                        />
                        <div
                            v-if="formPosponer.errors.fecha_prevista_inicio"
                            class="text-xs text-oxide"
                        >
                            {{ formPosponer.errors.fecha_prevista_inicio }}
                        </div>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-ink">
                        <input
                            v-model="formPosponer.liberar_reservas"
                            type="checkbox"
                            class="rounded border-hairline text-teja focus:ring-teja"
                        />
                        Devolver reservas vigentes al stock
                    </label>

                    <div class="flex flex-col gap-1.5">
                        <label for="motivo_posponer" class="text-xs font-medium text-steel"
                            >Motivo</label
                        >
                        <textarea
                            id="motivo_posponer"
                            v-model="formPosponer.motivo"
                            rows="3"
                            maxlength="255"
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        ></textarea>
                        <div v-if="formPosponer.errors.motivo" class="text-xs text-oxide">
                            {{ formPosponer.errors.motivo }}
                        </div>
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
                            :disabled="formPosponer.processing"
                            class="rounded-control bg-teja px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-teja-deep active:scale-[0.98] disabled:opacity-60"
                        >
                            {{ formPosponer.processing ? 'Guardando…' : 'Posponer' }}
                        </button>
                    </div>
                </form>

                <div
                    v-if="drawerAbierto === 'reanudar' && ordenActiva"
                    class="mt-4 flex flex-col gap-4"
                    data-testid="reanudar-orden-form"
                >
                    <p class="text-sm text-ink">
                        ¿Reanudar la orden <span class="font-medium">#{{ ordenActiva.id }}</span
                        >? Se recalcularán las reservas necesarias para la cantidad restante.
                    </p>

                    <div class="flex justify-end gap-2 border-t border-hairline pt-3">
                        <button
                            type="button"
                            class="rounded-control border border-hairline px-4 py-2 text-sm text-ink transition-colors hover:bg-canvas"
                            @click="cerrarDrawer"
                        >
                            Cancelar
                        </button>
                        <button
                            type="button"
                            class="rounded-control bg-teja px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-teja-deep active:scale-[0.98]"
                            @click="guardarReanudacion"
                        >
                            Reanudar
                        </button>
                    </div>
                </div>
            </aside>
        </div>
    </AppLayout>
</template>
