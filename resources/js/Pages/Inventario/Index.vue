<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

interface Articulo {
    id: number;
    codigo_sku: string;
    descripcion: string;
    tipo: string;
    origen: string | null;
    estado: string;
    unidad_de_medida_id: number;
    unidad: string | null;
    stock_fisico: number;
    stock_comprometido: number;
    stock_disponible: number;
    stock_minimo: number | null;
    stock_maximo: number | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedArticulos {
    data: Articulo[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
}

interface Unidad {
    id: number;
    nombre: string;
}

type SharedProps = {
    flash: { success: string | null };
};

const props = defineProps<{
    articulos: PaginatedArticulos;
    resumen: { total: number; activos: number; inactivos: number };
    tipos: string[];
    origenes: string[];
    unidades: Unidad[];
    filtros: { buscar?: string | null; tipo?: string | null; estado?: string | null };
}>();

const page = usePage<SharedProps>();

const busqueda = ref(props.filtros.buscar ?? '');
const tipoFiltro = ref(props.filtros.tipo ?? '');
const estadoFiltro = ref(props.filtros.estado ?? '');

const drawerOpen = ref(false);
const modo = ref<'crear' | 'editar'>('crear');
const articuloEditando = ref<Articulo | null>(null);

const form = useForm({
    codigo_sku: '',
    descripcion: '',
    tipo: '',
    origen: '',
    unidad_de_medida_id: '',
    stock_minimo: '',
    stock_maximo: '',
});

const esProductoTerminado = computed(() => form.tipo === 'Producto Terminado');

function aplicarFiltros(): void {
    router.get(
        '/inventario/articulos',
        {
            buscar: busqueda.value || undefined,
            tipo: tipoFiltro.value || undefined,
            estado: estadoFiltro.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function abrirCrear(): void {
    modo.value = 'crear';
    articuloEditando.value = null;
    form.reset();
    form.clearErrors();
    form.tipo = props.tipos[0] ?? '';
    drawerOpen.value = true;
}

function abrirEditar(articulo: Articulo): void {
    modo.value = 'editar';
    articuloEditando.value = articulo;
    form.clearErrors();
    form.codigo_sku = articulo.codigo_sku;
    form.descripcion = articulo.descripcion;
    form.tipo = articulo.tipo;
    form.origen = articulo.origen ?? '';
    form.unidad_de_medida_id = String(articulo.unidad_de_medida_id);
    form.stock_minimo = articulo.stock_minimo !== null ? String(articulo.stock_minimo) : '';
    form.stock_maximo = articulo.stock_maximo !== null ? String(articulo.stock_maximo) : '';
    drawerOpen.value = true;
}

function cerrarDrawer(): void {
    drawerOpen.value = false;
}

function guardar(): void {
    if (modo.value === 'crear') {
        form.post('/inventario/articulos', {
            preserveScroll: true,
            onSuccess: () => {
                drawerOpen.value = false;
                form.reset();
            },
        });

        return;
    }

    if (articuloEditando.value) {
        form.put(`/inventario/articulos/${articuloEditando.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                drawerOpen.value = false;
            },
        });
    }
}

function cambiarEstado(articulo: Articulo): void {
    if (articulo.estado === 'Activo' && articulo.stock_fisico > 0) {
        const confirmado = window.confirm(
            `El artículo "${articulo.descripcion}" tiene stock físico. ¿Confirmás la baja?`,
        );

        if (!confirmado) {
            return;
        }
    }

    router.patch(
        `/inventario/articulos/${articulo.id}/estado`,
        { confirmar: true },
        { preserveScroll: true },
    );
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
    <AppLayout title="Artículos">
        <Head title="Artículos" />

        <div class="flex flex-col gap-4">
            <div class="flex flex-col gap-1">
                <h2 class="text-2xl font-semibold tracking-tight text-ink">
                    Catálogo de artículos
                </h2>
                <p class="text-sm text-steel">
                    Materias primas, insumos y productos terminados. El stock disponible es el
                    físico menos el comprometido en reservas.
                </p>
            </div>

            <div
                v-if="page.props.flash.success"
                class="rounded-control border border-pine/20 bg-pine-wash px-4 py-2.5 text-sm text-pine"
            >
                {{ page.props.flash.success }}
            </div>

            <div class="rounded-panel border border-hairline bg-surface p-4 shadow-diffused">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <form class="flex flex-wrap items-end gap-2" @submit.prevent="aplicarFiltros">
                        <div class="flex flex-col gap-1">
                            <label for="buscar" class="text-xs font-medium text-steel"
                                >Buscar</label
                            >
                            <input
                                id="buscar"
                                v-model="busqueda"
                                type="text"
                                placeholder="SKU, nombre o tipo..."
                                class="w-56 rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink placeholder:text-clay/70 focus:ring-2 focus:ring-teja/40 focus:outline-none"
                            />
                        </div>

                        <div class="flex flex-col gap-1">
                            <label for="tipo" class="text-xs font-medium text-steel">Tipo</label>
                            <select
                                id="tipo"
                                v-model="tipoFiltro"
                                class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                                @change="aplicarFiltros"
                            >
                                <option value="">Todos los tipos</option>
                                <option v-for="tipo in props.tipos" :key="tipo" :value="tipo">
                                    {{ tipo }}
                                </option>
                            </select>
                        </div>

                        <div class="flex flex-col gap-1">
                            <label for="estado" class="text-xs font-medium text-steel"
                                >Estado</label
                            >
                            <select
                                id="estado"
                                v-model="estadoFiltro"
                                class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                                @change="aplicarFiltros"
                            >
                                <option value="">Todos los estados</option>
                                <option value="Activo">Activo</option>
                                <option value="Inactivo">Inactivo</option>
                            </select>
                        </div>

                        <button
                            type="submit"
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-steel transition-colors hover:text-ink"
                        >
                            Filtrar
                        </button>
                    </form>

                    <button
                        type="button"
                        class="rounded-control bg-teja px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-teja-deep active:scale-[0.98]"
                        @click="abrirCrear"
                    >
                        + Nuevo artículo
                    </button>
                </div>

                <div
                    class="mt-3 flex flex-wrap items-center gap-2 border-t border-hairline pt-2.5 text-xs"
                >
                    <span class="font-semibold text-ink">{{ props.resumen.total }} artículos</span>
                    <span class="text-clay">·</span>
                    <span class="font-medium text-pine">{{ props.resumen.activos }} activos</span>
                    <span class="text-clay">·</span>
                    <span class="text-clay">{{ props.resumen.inactivos }} inactivos</span>
                </div>
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
                                    SKU
                                </th>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Descripción
                                </th>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Tipo
                                </th>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Unidad
                                </th>
                                <th
                                    class="px-4 py-2.5 text-right text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Físico
                                </th>
                                <th
                                    class="px-4 py-2.5 text-right text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Comprom.
                                </th>
                                <th
                                    class="px-4 py-2.5 text-right text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Disponible
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
                                v-for="articulo in props.articulos.data"
                                :key="articulo.id"
                                class="hover:bg-teja-wash/30"
                            >
                                <td class="px-4 py-2.5 font-mono text-xs font-medium text-ink">
                                    {{ articulo.codigo_sku }}
                                </td>
                                <td class="px-4 py-2.5 text-sm text-ink">
                                    {{ articulo.descripcion }}
                                </td>
                                <td class="px-4 py-2.5 text-xs text-steel">{{ articulo.tipo }}</td>
                                <td class="px-4 py-2.5 text-xs text-steel">
                                    {{ articulo.unidad ?? '—' }}
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono text-xs text-ink">
                                    {{ articulo.stock_fisico }}
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono text-xs text-steel">
                                    {{ articulo.stock_comprometido }}
                                </td>
                                <td
                                    class="px-4 py-2.5 text-right font-mono text-xs font-medium text-ink"
                                >
                                    {{ articulo.stock_disponible }}
                                </td>
                                <td class="px-4 py-2.5">
                                    <span
                                        class="inline-flex items-center gap-1 rounded px-2 py-0.5 text-[0.6875rem] font-medium"
                                        :class="
                                            articulo.estado === 'Activo'
                                                ? 'bg-pine-wash text-pine'
                                                : 'bg-workshop text-steel'
                                        "
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full"
                                            :class="
                                                articulo.estado === 'Activo'
                                                    ? 'bg-pine'
                                                    : 'bg-steel'
                                            "
                                        ></span>
                                        {{ articulo.estado }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-right text-xs">
                                    <button
                                        type="button"
                                        class="font-medium text-teja hover:underline"
                                        @click="abrirEditar(articulo)"
                                    >
                                        Editar
                                    </button>
                                    <span class="mx-2 text-clay/40">|</span>
                                    <button
                                        type="button"
                                        class="font-medium transition-colors"
                                        :class="
                                            articulo.estado === 'Inactivo'
                                                ? 'text-pine hover:underline'
                                                : 'text-steel hover:text-oxide'
                                        "
                                        @click="cambiarEstado(articulo)"
                                    >
                                        {{
                                            articulo.estado === 'Inactivo'
                                                ? 'Habilitar'
                                                : 'Dar de baja'
                                        }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="props.articulos.data.length === 0"
                    class="px-4 py-10 text-center text-sm text-steel"
                >
                    {{
                        props.filtros.buscar || props.filtros.tipo || props.filtros.estado
                            ? 'No se encontraron artículos para el criterio especificado.'
                            : 'No hay artículos registrados.'
                    }}
                </div>

                <div
                    v-if="props.articulos.total > 0"
                    class="flex flex-wrap items-center justify-between gap-2 border-t border-hairline px-4 py-2.5 text-xs text-steel"
                >
                    <span
                        >Mostrando {{ props.articulos.from }}–{{ props.articulos.to }} de
                        {{ props.articulos.total }}</span
                    >
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="link in props.articulos.links"
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
                class="flex h-full w-full max-w-[460px] flex-col overflow-y-auto border-l border-hairline bg-surface p-5 shadow-diffused"
            >
                <div class="flex items-center justify-between border-b border-hairline pb-3">
                    <h3 class="text-lg font-semibold text-ink">
                        {{ modo === 'crear' ? 'Nuevo artículo' : 'Editar artículo' }}
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
                        <label for="codigo_sku" class="text-xs font-medium text-steel"
                            >Código SKU</label
                        >
                        <input
                            id="codigo_sku"
                            v-model="form.codigo_sku"
                            type="text"
                            required
                            :disabled="modo === 'editar'"
                            class="rounded-control border border-hairline bg-surface px-3 py-2 font-mono text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none disabled:bg-workshop/40"
                        />
                        <p v-if="form.errors.codigo_sku" class="text-xs text-oxide">
                            {{ form.errors.codigo_sku }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="descripcion" class="text-xs font-medium text-steel"
                            >Descripción</label
                        >
                        <input
                            id="descripcion"
                            v-model="form.descripcion"
                            type="text"
                            required
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        />
                        <p v-if="form.errors.descripcion" class="text-xs text-oxide">
                            {{ form.errors.descripcion }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="tipo_form" class="text-xs font-medium text-steel">Tipo</label>
                        <select
                            id="tipo_form"
                            v-model="form.tipo"
                            required
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        >
                            <option value="" disabled>Seleccioná un tipo</option>
                            <option v-for="tipo in props.tipos" :key="tipo" :value="tipo">
                                {{ tipo }}
                            </option>
                        </select>
                        <p v-if="form.errors.tipo" class="text-xs text-oxide">
                            {{ form.errors.tipo }}
                        </p>
                    </div>

                    <div v-if="esProductoTerminado" class="flex flex-col gap-1.5">
                        <label for="origen_form" class="text-xs font-medium text-steel"
                            >Origen</label
                        >
                        <select
                            id="origen_form"
                            v-model="form.origen"
                            required
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        >
                            <option value="" disabled>Seleccioná el origen</option>
                            <option v-for="origen in props.origenes" :key="origen" :value="origen">
                                {{ origen }}
                            </option>
                        </select>
                        <p v-if="form.errors.origen" class="text-xs text-oxide">
                            {{ form.errors.origen }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="unidad" class="text-xs font-medium text-steel"
                            >Unidad de medida</label
                        >
                        <select
                            id="unidad"
                            v-model="form.unidad_de_medida_id"
                            required
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        >
                            <option value="" disabled>Seleccioná una unidad</option>
                            <option
                                v-for="unidad in props.unidades"
                                :key="unidad.id"
                                :value="String(unidad.id)"
                            >
                                {{ unidad.nombre }}
                            </option>
                        </select>
                        <p v-if="form.errors.unidad_de_medida_id" class="text-xs text-oxide">
                            {{ form.errors.unidad_de_medida_id }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="flex flex-col gap-1.5">
                            <label for="stock_minimo" class="text-xs font-medium text-steel"
                                >Stock mínimo</label
                            >
                            <input
                                id="stock_minimo"
                                v-model="form.stock_minimo"
                                type="number"
                                min="0"
                                step="0.01"
                                class="rounded-control border border-hairline bg-surface px-3 py-2 font-mono text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                            />
                            <p v-if="form.errors.stock_minimo" class="text-xs text-oxide">
                                {{ form.errors.stock_minimo }}
                            </p>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="stock_maximo" class="text-xs font-medium text-steel"
                                >Stock máximo</label
                            >
                            <input
                                id="stock_maximo"
                                v-model="form.stock_maximo"
                                type="number"
                                min="0"
                                step="0.01"
                                class="rounded-control border border-hairline bg-surface px-3 py-2 font-mono text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                            />
                            <p v-if="form.errors.stock_maximo" class="text-xs text-oxide">
                                {{ form.errors.stock_maximo }}
                            </p>
                        </div>
                    </div>

                    <p class="text-[11px] leading-snug text-clay">
                        El stock físico inicial es cero. Los umbrales alimentan las alertas de
                        reposición. Si el artículo es Producto Terminado, su hoja de ruta se define
                        en Producción.
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
