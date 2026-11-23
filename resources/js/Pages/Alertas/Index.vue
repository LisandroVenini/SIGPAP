<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

interface RolDeAlerta {
    name: string;
}

interface Alerta {
    id: number;
    tipo: string;
    severidad: string;
    descripcion: string;
    fecha: string;
    resuelta_en: string | null;
    contexto: Record<string, string | number | null> | null;
    roles: RolDeAlerta[];
}

interface Paginado {
    data: Alerta[];
    links: { url: string | null; label: string; active: boolean }[];
    from: number | null;
    to: number | null;
    total: number;
}

const props = defineProps<{
    alertas: Paginado;
    filtros: {
        estado?: string | null;
        severidad?: string | null;
        tipo?: string | null;
        no_leidas?: boolean | null;
    };
    noLeidas: number;
    tipos: { clave: string; etiqueta: string; detalle?: string | null }[];
    severidades: { valor: string; etiqueta: string }[];
}>();

const estado = ref(props.filtros.estado ?? 'abiertas');
const severidad = ref(props.filtros.severidad ?? '');
const tipo = ref(props.filtros.tipo ?? '');
const soloNoLeidas = ref(Boolean(props.filtros.no_leidas));
const procesando = ref(false);

function filtrar(): void {
    procesando.value = true;

    router.get(
        '/alertas',
        {
            estado: estado.value || undefined,
            severidad: severidad.value || undefined,
            tipo: tipo.value || undefined,
            no_leidas: soloNoLeidas.value ? 1 : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onFinish: () => {
                procesando.value = false;
            },
        },
    );
}

function marcarLeida(alerta: Alerta): void {
    router.patch(`/alertas/${alerta.id}/leida`, {}, { preserveScroll: true });
}

function marcarTodas(): void {
    if (procesando.value) {
        return;
    }

    procesando.value = true;
    router.patch(
        '/alertas/leidas',
        {},
        { preserveScroll: true, onFinish: () => (procesando.value = false) },
    );
}

function etiquetaTipo(clave: string): string {
    return props.tipos.find((item) => item.clave === clave)?.etiqueta ?? clave;
}

function etiquetaSeveridad(valor: string): string {
    return props.severidades.find((item) => item.valor === valor)?.etiqueta ?? valor;
}

function claseSeveridad(valor: string): string {
    if (valor === 'Critica') {
        return 'bg-oxide/10 text-oxide';
    }

    if (valor === 'Atencion') {
        return 'bg-amber/10 text-amber';
    }

    return 'bg-workshop text-steel';
}

function contextoLegible(alerta: Alerta): string {
    if (alerta.contexto === null) {
        return '';
    }

    const contexto = alerta.contexto;
    const tipo = props.tipos.find((item) => item.clave === alerta.tipo);

    if (tipo?.detalle && placeholdersCompletos(tipo.detalle, contexto)) {
        return tipo.detalle.replace(
            /\{\{\s*([a-z_]+)\s*\}\}/g,
            (_coincidencia, clave: string) => formatearValor(clave, contexto[clave]),
        );
    }

    return Object.entries(contexto)
        .filter(([clave, valor]) => !clave.endsWith('_id') && valor !== null && valor !== '')
        .map(
            ([clave, valor]) =>
                `${etiquetaDeContexto(clave)}: ${formatearValor(clave, valor)}`,
        )
        .join(' · ');
}

function placeholdersCompletos(
    plantilla: string,
    contexto: Record<string, string | number | null>,
): boolean {
    return [...plantilla.matchAll(/\{\{\s*([a-z_]+)\s*\}\}/g)].every((coincidencia) => {
        const valor = contexto[coincidencia[1]];

        return valor !== null && valor !== undefined && valor !== '';
    });
}

const ETIQUETAS_DE_CONTEXTO: Record<string, string> = {
    articulo: 'Artículo',
    avance: 'Avance',
    cotizaciones: 'Cotizaciones',
    desgaste: 'Desgaste',
    dias: 'Días',
    dias_para_vencer: 'Días para vencer',
    disponible: 'Disponible',
    error: 'Error',
    etapa: 'Etapa',
    fecha_entrega: 'Entrega',
    fecha_vencimiento: 'Vencimiento',
    insumos: 'Insumos',
    maquinaria: 'Maquinaria',
    minimo: 'Mínimo',
    motivo: 'Motivo',
    numero_oc: 'OC',
    numero_solicitud: 'Solicitud',
    orden: 'Orden',
    producto: 'Producto',
    proveedor: 'Proveedor',
    puntaje: 'Puntaje',
    rechazadas: 'Rechazadas',
    rechazo_pct: 'Rechazo',
    umbral: 'Umbral',
    unidades: 'Unidades',
    ventana: 'Ventana',
};

const CLAVES_PORCENTAJE = new Set(['rechazo_pct', 'avance', 'puntaje']);

function etiquetaDeContexto(clave: string): string {
    return ETIQUETAS_DE_CONTEXTO[clave] ?? clave.replace(/_/g, ' ');
}

function formatearValor(clave: string, valor: unknown): string {
    if (valor === null || valor === undefined || valor === '') {
        return '';
    }

    return CLAVES_PORCENTAJE.has(clave) ? `${valor}%` : String(valor);
}

function labelDePagina(label: string): string {
    return label.replace('&laquo;', '‹').replace('&raquo;', '›');
}
</script>

<template>
    <AppLayout title="Alertas">
        <Head title="Alertas" />

        <div class="flex flex-col gap-4">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div class="flex flex-col gap-1">
                    <h2 class="text-2xl font-semibold tracking-tight text-ink">Alertas</h2>
                    <p class="text-sm text-steel">
                        {{ noLeidas }} sin leer · {{ alertas.total }} en total
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        href="/alertas/reglas"
                        class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm font-medium text-ink transition-colors hover:bg-workshop/40"
                    >
                        Reglas
                    </Link>
                    <button
                        type="button"
                        :disabled="procesando || noLeidas === 0"
                        class="rounded-control bg-teja px-3 py-2 text-sm font-medium text-white transition-colors hover:bg-teja-deep disabled:opacity-60"
                        @click="marcarTodas"
                    >
                        Marcar todas como leídas
                    </button>
                </div>
            </div>

            <form
                class="flex flex-wrap items-end gap-3 rounded-panel border border-hairline bg-surface p-4 shadow-diffused"
                @submit.prevent="filtrar"
            >
                <div class="flex flex-col gap-1">
                    <label for="estado" class="text-xs font-medium text-steel">Estado</label>
                    <select
                        id="estado"
                        v-model="estado"
                        class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink"
                    >
                        <option value="abiertas">Abiertas</option>
                        <option value="todas">Todas</option>
                    </select>
                </div>

                <div class="flex flex-col gap-1">
                    <label for="severidad" class="text-xs font-medium text-steel">Severidad</label>
                    <select
                        id="severidad"
                        v-model="severidad"
                        class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink"
                    >
                        <option value="">Todas</option>
                        <option
                            v-for="opcion in severidades"
                            :key="opcion.valor"
                            :value="opcion.valor"
                        >
                            {{ opcion.etiqueta }}
                        </option>
                    </select>
                </div>

                <div class="flex flex-col gap-1">
                    <label for="tipo" class="text-xs font-medium text-steel">Tipo</label>
                    <select
                        id="tipo"
                        v-model="tipo"
                        class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink"
                    >
                        <option value="">Todos</option>
                        <option v-for="opcion in tipos" :key="opcion.clave" :value="opcion.clave">
                            {{ opcion.etiqueta }}
                        </option>
                    </select>
                </div>

                <label class="flex items-center gap-2 pb-2 text-sm text-ink">
                    <input
                        v-model="soloNoLeidas"
                        type="checkbox"
                        class="h-4 w-4 rounded border-hairline text-teja"
                    />
                    Solo no leídas
                </label>

                <button
                    type="submit"
                    :disabled="procesando"
                    class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm font-medium text-ink transition-colors hover:bg-workshop/40 disabled:opacity-60"
                >
                    Filtrar
                </button>
            </form>

            <div
                v-if="alertas.data.length === 0"
                class="rounded-panel border border-hairline bg-surface px-4 py-10 text-center text-sm text-steel"
            >
                No hay alertas para los filtros aplicados.
            </div>

            <ul v-else class="flex flex-col gap-2">
                <li
                    v-for="alerta in alertas.data"
                    :key="alerta.id"
                    class="flex flex-col gap-2 rounded-panel border border-hairline bg-surface p-4 shadow-diffused md:flex-row md:items-start md:justify-between"
                >
                    <div class="flex flex-col gap-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                :class="[
                                    'rounded px-2 py-0.5 text-[0.6875rem] font-semibold',
                                    claseSeveridad(alerta.severidad),
                                ]"
                            >
                                {{ etiquetaSeveridad(alerta.severidad) }}
                            </span>
                            <span class="text-xs font-medium text-steel">{{
                                etiquetaTipo(alerta.tipo)
                            }}</span>
                            <span
                                v-if="alerta.resuelta_en === null"
                                class="text-[0.6875rem] text-amber"
                                >Abierta</span
                            >
                            <span v-else class="text-[0.6875rem] text-pine">Resuelta</span>
                        </div>
                        <p class="text-sm font-medium text-ink">{{ alerta.descripcion }}</p>
                        <p class="text-xs text-steel">{{ contextoLegible(alerta) }}</p>
                        <p class="text-[0.6875rem] text-clay">
                            {{ alerta.fecha }} ·
                            {{ alerta.roles.map((rol) => rol.name).join(', ') }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="shrink-0 rounded-control border border-hairline px-3 py-1.5 text-xs font-medium text-ink transition-colors hover:bg-workshop/40"
                        @click="marcarLeida(alerta)"
                    >
                        Marcar leída
                    </button>
                </li>
            </ul>

            <div
                v-if="alertas.links.length > 3"
                class="flex items-center justify-between text-xs text-steel"
            >
                <span>Mostrando {{ alertas.from }}–{{ alertas.to }} de {{ alertas.total }}</span>
                <div class="flex items-center gap-1">
                    <template v-for="link in alertas.links" :key="link.label">
                        <span
                            v-if="link.url === null"
                            aria-disabled="true"
                            class="rounded px-2 py-0.5 text-clay"
                        >
                            {{ labelDePagina(link.label) }}
                        </span>
                        <Link
                            v-else
                            :href="link.url"
                            :aria-current="link.active ? 'page' : undefined"
                            class="rounded px-2 py-0.5"
                            :class="
                                link.active
                                    ? 'bg-workshop font-semibold text-ink'
                                    : 'hover:bg-workshop/50'
                            "
                        >
                            {{ labelDePagina(link.label) }}
                        </Link>
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
