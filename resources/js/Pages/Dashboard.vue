<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '../Layouts/AppLayout.vue';

type ProximaEntrega = {
    orden_id: number;
    producto: string;
    fecha_entrega: string | null;
    estado: string;
};

type MaquinaBloqueada = {
    id: number;
    nombre: string;
    desgaste: number;
};

type ResumenProduccion = {
    activas: number;
    en_curso: number;
    pendientes_por_insumos: number;
    atrasadas: number;
    proximas_entregas: ProximaEntrega[];
};

type ResumenInventario = {
    activos: number;
    bajo_minimo: number;
    comprometido: number;
};

type ResumenCompras = {
    pendientes_recepcion: number;
    solicitudes_abiertas: number;
    proveedores_activos: number;
};

type ResumenMantenimiento = {
    maquinas_bloqueadas: number;
    planes: number;
    bloqueadas: MaquinaBloqueada[];
};

type Resumen = {
    produccion: ResumenProduccion | null;
    inventario: ResumenInventario | null;
    compras: ResumenCompras | null;
    mantenimiento: ResumenMantenimiento | null;
};

type SharedProps = {
    auth: {
        user: {
            nombre: string;
        };
    };
};

const props = defineProps<{ resumen: Resumen }>();

const page = usePage<SharedProps>();
const nombre = computed(() => page.props.auth.user?.nombre ?? '');
const hayAreas = computed(() => Object.values(props.resumen).some((area) => area !== null));

const formatoNumero = new Intl.NumberFormat('es-AR', { maximumFractionDigits: 2 });

function numero(valor: number): string {
    return formatoNumero.format(valor);
}

function fecha(valor: string | null): string {
    if (!valor) {
        return 'Sin fecha';
    }

    const [anio, mes, dia] = valor.split('-');

    return `${dia}/${mes}/${anio}`;
}
</script>

<template>
    <AppLayout title="Tablero">
        <Head title="Tablero" />

        <div class="flex flex-col gap-5">
            <div class="flex flex-col gap-1">
                <p class="text-xs font-medium tracking-[0.18em] text-clay uppercase">
                    Tablero ejecutivo
                </p>
                <h2 data-testid="saludo" class="text-2xl font-semibold tracking-tight text-ink">
                    Bienvenido, {{ nombre }}
                </h2>
                <p class="max-w-2xl text-sm leading-relaxed text-steel">
                    Indicadores de las áreas a las que tenés acceso. Los datos se actualizan con
                    cada visita.
                </p>
            </div>

            <div
                v-if="!hayAreas"
                data-testid="sin-areas"
                class="rounded-panel border border-hairline bg-surface p-6 shadow-diffused"
            >
                <p class="text-sm font-medium text-ink">No tenés áreas disponibles.</p>
                <p class="mt-1 text-sm text-steel">
                    Contactá a un administrador para que te asigne los permisos de un área.
                </p>
            </div>

            <div v-else class="grid gap-5 xl:grid-cols-2">
                <section
                    v-if="props.resumen.produccion"
                    data-testid="area-produccion"
                    class="rounded-panel border border-hairline bg-surface p-5 shadow-diffused"
                >
                    <div class="flex items-baseline justify-between gap-3">
                        <h3 class="text-lg font-semibold text-ink">Producción</h3>
                        <Link
                            href="/produccion/ordenes-de-produccion"
                            class="text-xs font-medium text-teja hover:underline"
                        >
                            Ver órdenes
                        </Link>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <div
                            class="rounded-control border border-hairline bg-workshop/20 px-3 py-2"
                        >
                            <p class="text-xs text-clay">Activas</p>
                            <p
                                data-testid="produccion-activas"
                                class="mt-1 text-2xl font-semibold text-ink"
                            >
                                {{ numero(props.resumen.produccion.activas) }}
                            </p>
                        </div>
                        <div
                            class="rounded-control border border-hairline bg-workshop/20 px-3 py-2"
                        >
                            <p class="text-xs text-clay">En curso</p>
                            <p
                                data-testid="produccion-en-curso"
                                class="mt-1 text-2xl font-semibold text-ink"
                            >
                                {{ numero(props.resumen.produccion.en_curso) }}
                            </p>
                        </div>
                        <div
                            class="rounded-control border border-hairline bg-workshop/20 px-3 py-2"
                        >
                            <p class="text-xs text-clay">Por insumos</p>
                            <p
                                data-testid="produccion-pendientes-insumos"
                                class="mt-1 text-2xl font-semibold text-ink"
                            >
                                {{ numero(props.resumen.produccion.pendientes_por_insumos) }}
                            </p>
                        </div>
                        <div
                            class="rounded-control border border-hairline bg-workshop/20 px-3 py-2"
                        >
                            <p class="text-xs text-clay">Atrasadas</p>
                            <p
                                data-testid="produccion-atrasadas"
                                class="mt-1 text-2xl font-semibold text-ink"
                            >
                                {{ numero(props.resumen.produccion.atrasadas) }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-5">
                        <h4 class="text-sm font-semibold text-ink">Próximas entregas</h4>
                        <ul
                            v-if="props.resumen.produccion.proximas_entregas.length > 0"
                            data-testid="proximas-entregas"
                            class="mt-2 divide-y divide-hairline"
                        >
                            <li
                                v-for="entrega in props.resumen.produccion.proximas_entregas"
                                :key="entrega.orden_id"
                                class="flex items-center justify-between gap-3 py-2"
                            >
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-ink">
                                        {{ entrega.producto }}
                                    </p>
                                    <p class="text-xs text-steel">
                                        {{ fecha(entrega.fecha_entrega) }}
                                    </p>
                                </div>
                                <span
                                    class="shrink-0 rounded bg-workshop px-2 py-0.5 text-[0.6875rem] font-medium text-steel"
                                >
                                    {{ entrega.estado }}
                                </span>
                            </li>
                        </ul>
                        <p v-else class="mt-2 text-sm text-steel">Sin entregas próximas.</p>
                    </div>
                </section>

                <section
                    v-if="props.resumen.inventario"
                    data-testid="area-inventario"
                    class="rounded-panel border border-hairline bg-surface p-5 shadow-diffused"
                >
                    <div class="flex items-baseline justify-between gap-3">
                        <h3 class="text-lg font-semibold text-ink">Inventario</h3>
                        <Link
                            href="/inventario/articulos"
                            class="text-xs font-medium text-teja hover:underline"
                        >
                            Ver artículos
                        </Link>
                    </div>

                    <div class="mt-4 grid grid-cols-3 gap-3">
                        <div
                            class="rounded-control border border-hairline bg-workshop/20 px-3 py-2"
                        >
                            <p class="text-xs text-clay">Activos</p>
                            <p
                                data-testid="inventario-activos"
                                class="mt-1 text-2xl font-semibold text-ink"
                            >
                                {{ numero(props.resumen.inventario.activos) }}
                            </p>
                        </div>
                        <div
                            class="rounded-control border border-hairline bg-workshop/20 px-3 py-2"
                        >
                            <p class="text-xs text-clay">Bajo mínimo</p>
                            <p
                                data-testid="inventario-bajo-minimo"
                                class="mt-1 text-2xl font-semibold text-ink"
                            >
                                {{ numero(props.resumen.inventario.bajo_minimo) }}
                            </p>
                        </div>
                        <div
                            class="rounded-control border border-hairline bg-workshop/20 px-3 py-2"
                        >
                            <p class="text-xs text-clay">Comprometido</p>
                            <p
                                data-testid="inventario-comprometido"
                                class="mt-1 text-2xl font-semibold text-ink"
                            >
                                {{ numero(props.resumen.inventario.comprometido) }}
                            </p>
                        </div>
                    </div>
                </section>

                <section
                    v-if="props.resumen.compras"
                    data-testid="area-compras"
                    class="rounded-panel border border-hairline bg-surface p-5 shadow-diffused"
                >
                    <div class="flex items-baseline justify-between gap-3">
                        <h3 class="text-lg font-semibold text-ink">Compras</h3>
                        <Link
                            href="/compras/ordenes-de-compra"
                            class="text-xs font-medium text-teja hover:underline"
                        >
                            Ver órdenes
                        </Link>
                    </div>

                    <div class="mt-4 grid grid-cols-3 gap-3">
                        <div
                            class="rounded-control border border-hairline bg-workshop/20 px-3 py-2"
                        >
                            <p class="text-xs text-clay">Por recibir</p>
                            <p
                                data-testid="compras-pendientes-recepcion"
                                class="mt-1 text-2xl font-semibold text-ink"
                            >
                                {{ numero(props.resumen.compras.pendientes_recepcion) }}
                            </p>
                        </div>
                        <div
                            class="rounded-control border border-hairline bg-workshop/20 px-3 py-2"
                        >
                            <p class="text-xs text-clay">Solicitudes abiertas</p>
                            <p
                                data-testid="compras-solicitudes-abiertas"
                                class="mt-1 text-2xl font-semibold text-ink"
                            >
                                {{ numero(props.resumen.compras.solicitudes_abiertas) }}
                            </p>
                        </div>
                        <div
                            class="rounded-control border border-hairline bg-workshop/20 px-3 py-2"
                        >
                            <p class="text-xs text-clay">Proveedores activos</p>
                            <p
                                data-testid="compras-proveedores-activos"
                                class="mt-1 text-2xl font-semibold text-ink"
                            >
                                {{ numero(props.resumen.compras.proveedores_activos) }}
                            </p>
                        </div>
                    </div>
                </section>

                <section
                    v-if="props.resumen.mantenimiento"
                    data-testid="area-mantenimiento"
                    class="rounded-panel border border-hairline bg-surface p-5 shadow-diffused"
                >
                    <div class="flex items-baseline justify-between gap-3">
                        <h3 class="text-lg font-semibold text-ink">Mantenimiento</h3>
                        <Link
                            data-testid="link-maquinarias"
                            href="/produccion/maquinarias"
                            class="text-xs font-medium text-teja hover:underline"
                        >
                            Ver maquinarias
                        </Link>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <div
                            class="rounded-control border border-hairline bg-workshop/20 px-3 py-2"
                        >
                            <p class="text-xs text-clay">Máquinas bloqueadas</p>
                            <p
                                data-testid="mantenimiento-maquinas-bloqueadas"
                                class="mt-1 text-2xl font-semibold text-ink"
                            >
                                {{ numero(props.resumen.mantenimiento.maquinas_bloqueadas) }}
                            </p>
                        </div>
                        <div
                            class="rounded-control border border-hairline bg-workshop/20 px-3 py-2"
                        >
                            <p class="text-xs text-clay">Planes de mantenimiento</p>
                            <p
                                data-testid="mantenimiento-planes"
                                class="mt-1 text-2xl font-semibold text-ink"
                            >
                                {{ numero(props.resumen.mantenimiento.planes) }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-5">
                        <h4 class="text-sm font-semibold text-ink">Máquinas bloqueadas</h4>
                        <ul
                            v-if="props.resumen.mantenimiento.bloqueadas.length > 0"
                            data-testid="maquinas-bloqueadas"
                            class="mt-2 divide-y divide-hairline"
                        >
                            <li
                                v-for="maquina in props.resumen.mantenimiento.bloqueadas"
                                :key="maquina.id"
                                class="flex items-center justify-between gap-3 py-2"
                            >
                                <span class="truncate text-sm font-medium text-ink">
                                    {{ maquina.nombre }}
                                </span>
                                <span class="shrink-0 text-xs text-steel">
                                    Desgaste {{ numero(maquina.desgaste) }}
                                </span>
                            </li>
                        </ul>
                        <p v-else class="mt-2 text-sm text-steel">Sin máquinas bloqueadas.</p>
                    </div>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
