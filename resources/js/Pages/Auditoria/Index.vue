<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

interface EventoDeAuditoria {
    id: number;
    fecha_hora: string;
    usuario_id: number;
    accion: string;
    direccion_ip: string | null;
    auditable_type: string | null;
    auditable_id: number | null;
    estado_anterior: Record<string, unknown> | null;
    estado_nuevo: Record<string, unknown> | null;
}

interface UsuarioOpcion {
    id: number;
    nombre: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedEventos {
    data: EventoDeAuditoria[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
}

interface FiltrosAuditoria {
    usuario_id?: number | string | null;
    accion?: string | null;
    direccion_ip?: string | null;
    fecha_desde?: string | null;
    fecha_hasta?: string | null;
}

const props = defineProps<{
    eventos: PaginatedEventos;
    usuarios: UsuarioOpcion[];
    acciones: string[];
    filtros: FiltrosAuditoria;
}>();

const usuarioFiltro = ref<string>(props.filtros.usuario_id ? String(props.filtros.usuario_id) : '');
const accionFiltro = ref<string>(props.filtros.accion ?? '');
const ipFiltro = ref<string>(props.filtros.direccion_ip ?? '');
const fechaDesde = ref<string>(props.filtros.fecha_desde ?? '');
const fechaHasta = ref<string>(props.filtros.fecha_hasta ?? '');

const detalle = ref<EventoDeAuditoria | null>(null);

function aplicarFiltros(): void {
    router.get(
        '/auditoria',
        {
            usuario_id: usuarioFiltro.value || undefined,
            accion: accionFiltro.value || undefined,
            direccion_ip: ipFiltro.value || undefined,
            fecha_desde: fechaDesde.value || undefined,
            fecha_hasta: fechaHasta.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function usuarioNombre(usuarioId: number): string {
    return (
        props.usuarios.find((usuario) => usuario.id === usuarioId)?.nombre ??
        `Usuario #${usuarioId}`
    );
}

function fechaDe(fechaHora: string): string {
    return fechaHora.slice(0, 10);
}

function horaDe(fechaHora: string): string {
    return fechaHora.slice(11, 16);
}

function jsonLegible(estado: Record<string, unknown> | null): string {
    return estado === null ? '—' : JSON.stringify(estado, null, 2);
}

function abrirDetalle(evento: EventoDeAuditoria): void {
    detalle.value = evento;
}

function cerrarDetalle(): void {
    detalle.value = null;
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
    <AppLayout title="Registro de Auditoría">
        <Head title="Registro de Auditoría" />

        <div class="flex flex-col gap-4">
            <div class="flex flex-col gap-1">
                <h2 class="text-2xl font-semibold tracking-tight text-ink">
                    Registro de Auditoría
                </h2>
                <p class="text-sm text-steel">
                    Consulta inmutable de las acciones registradas en el sistema, con fecha, hora,
                    IP, usuario y detalle del estado anterior y nuevo.
                </p>
            </div>

            <div class="rounded-panel border border-hairline bg-surface p-4 shadow-diffused">
                <form
                    data-testid="filtros-auditoria"
                    class="flex flex-wrap items-end gap-2"
                    @submit.prevent="aplicarFiltros"
                >
                    <div class="flex flex-col gap-1">
                        <label for="usuario_id" class="text-xs font-medium text-steel"
                            >Usuario</label
                        >
                        <select
                            id="usuario_id"
                            v-model="usuarioFiltro"
                            data-testid="usuario"
                            class="min-w-48 rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        >
                            <option value="">Todos los usuarios</option>
                            <option
                                v-for="usuario in props.usuarios"
                                :key="usuario.id"
                                :value="usuario.id"
                            >
                                {{ usuario.nombre }}
                            </option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label for="accion" class="text-xs font-medium text-steel">Acción</label>
                        <select
                            id="accion"
                            v-model="accionFiltro"
                            data-testid="accion"
                            class="min-w-44 rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        >
                            <option value="">Todas las acciones</option>
                            <option v-for="accion in props.acciones" :key="accion" :value="accion">
                                {{ accion }}
                            </option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label for="direccion_ip" class="text-xs font-medium text-steel">IP</label>
                        <input
                            id="direccion_ip"
                            v-model="ipFiltro"
                            data-testid="direccion-ip"
                            type="text"
                            placeholder="192.168..."
                            class="w-40 rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink placeholder:text-clay/70 focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        />
                    </div>

                    <div class="flex flex-col gap-1">
                        <label for="fecha_desde" class="text-xs font-medium text-steel"
                            >Desde</label
                        >
                        <input
                            id="fecha_desde"
                            v-model="fechaDesde"
                            data-testid="fecha-desde"
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
                            data-testid="fecha-hasta"
                            type="date"
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        />
                    </div>

                    <button
                        type="submit"
                        class="rounded-control bg-teja px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-teja-deep active:scale-[0.98]"
                    >
                        Filtrar
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
                                    Fecha
                                </th>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Hora
                                </th>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Usuario
                                </th>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Acción
                                </th>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    IP
                                </th>
                                <th
                                    class="px-4 py-2.5 text-right text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Detalle
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-hairline">
                            <tr
                                v-for="evento in props.eventos.data"
                                :key="evento.id"
                                class="hover:bg-teja-wash/30"
                            >
                                <td class="px-4 py-2.5 font-mono text-xs text-ink">
                                    {{ fechaDe(evento.fecha_hora) }}
                                </td>
                                <td class="px-4 py-2.5 font-mono text-xs text-steel">
                                    {{ horaDe(evento.fecha_hora) }}
                                </td>
                                <td class="px-4 py-2.5 text-sm text-ink">
                                    {{ usuarioNombre(evento.usuario_id) }}
                                </td>
                                <td class="px-4 py-2.5">
                                    <span
                                        class="rounded border border-hairline bg-canvas px-2 py-0.5 text-[0.6875rem] font-medium text-slate"
                                    >
                                        {{ evento.accion }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 font-mono text-xs text-steel">
                                    {{ evento.direccion_ip ?? '—' }}
                                </td>
                                <td class="px-4 py-2.5 text-right">
                                    <button
                                        type="button"
                                        data-testid="ver-detalle"
                                        class="text-xs font-medium text-teja hover:underline"
                                        @click="abrirDetalle(evento)"
                                    >
                                        Ver detalle
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="props.eventos.data.length === 0"
                    class="px-4 py-10 text-center text-sm text-steel"
                >
                    No se encontraron registros para los criterios especificados
                </div>

                <div
                    v-if="props.eventos.total > 0"
                    class="flex flex-wrap items-center justify-between gap-2 border-t border-hairline px-4 py-2.5 text-xs text-steel"
                >
                    <span
                        >Mostrando {{ props.eventos.from }}–{{ props.eventos.to }} de
                        {{ props.eventos.total }}</span
                    >
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="link in props.eventos.links"
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
            v-if="detalle"
            class="fixed inset-0 z-40 flex justify-end bg-ink/20"
            @click.self="cerrarDetalle"
        >
            <aside
                data-testid="detalle-auditoria"
                class="flex h-full w-full max-w-[520px] flex-col overflow-y-auto border-l border-hairline bg-surface p-5 shadow-diffused"
            >
                <div class="flex items-center justify-between border-b border-hairline pb-3">
                    <div>
                        <h3 class="text-lg font-semibold text-ink">Detalle del evento</h3>
                        <p class="text-xs text-steel">
                            {{ fechaDe(detalle.fecha_hora) }} {{ horaDe(detalle.fecha_hora) }} ·
                            {{ usuarioNombre(detalle.usuario_id) }}
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

                <div class="mt-4 flex flex-col gap-4">
                    <div class="flex flex-col gap-1">
                        <span class="text-xs font-medium text-steel">Acción</span>
                        <span class="text-sm text-ink">{{ detalle.accion }}</span>
                    </div>

                    <div class="flex flex-col gap-1">
                        <span class="text-xs font-medium text-steel">Dirección IP</span>
                        <span class="font-mono text-sm text-ink">{{
                            detalle.direccion_ip ?? '—'
                        }}</span>
                    </div>

                    <div class="flex flex-col gap-1">
                        <span class="text-xs font-medium text-steel">Estado anterior</span>
                        <pre
                            class="overflow-x-auto rounded-control bg-workshop p-3 font-mono text-xs text-ink"
                            >{{ jsonLegible(detalle.estado_anterior) }}</pre>
                    </div>

                    <div class="flex flex-col gap-1">
                        <span class="text-xs font-medium text-steel">Estado nuevo</span>
                        <pre
                            class="overflow-x-auto rounded-control bg-workshop p-3 font-mono text-xs text-ink"
                            >{{ jsonLegible(detalle.estado_nuevo) }}</pre>
                    </div>
                </div>
            </aside>
        </div>
    </AppLayout>
</template>
