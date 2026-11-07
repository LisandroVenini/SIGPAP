<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

interface Proveedor {
    id: number;
    razon_social: string;
    cuit: string;
    email: string | null;
    telefono: string | null;
    estado: string;
    solicitudes: number;
    ordenes: number;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedProveedores {
    data: Proveedor[];
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
    proveedores: PaginatedProveedores;
    filtros: { buscar?: string | null };
}>();

const page = usePage<SharedProps>();

const busqueda = ref(props.filtros.buscar ?? '');
const drawerOpen = ref(false);
const modo = ref<'crear' | 'editar'>('crear');
const proveedorEditando = ref<Proveedor | null>(null);

const form = useForm({
    razon_social: '',
    cuit: '',
    email: '',
    telefono: '',
});

function aplicarFiltros(): void {
    router.get(
        '/compras/proveedores',
        { buscar: busqueda.value || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function abrirCrear(): void {
    modo.value = 'crear';
    proveedorEditando.value = null;
    form.reset();
    form.clearErrors();
    form.transform((datos) => datos);
    drawerOpen.value = true;
}

function abrirEditar(proveedor: Proveedor): void {
    modo.value = 'editar';
    proveedorEditando.value = proveedor;
    form.clearErrors();
    form.razon_social = proveedor.razon_social;
    form.cuit = proveedor.cuit;
    form.email = proveedor.email ?? '';
    form.telefono = proveedor.telefono ?? '';
    drawerOpen.value = true;
}

function cerrarDrawer(): void {
    drawerOpen.value = false;
}

function guardar(): void {
    if (modo.value === 'crear') {
        form.post('/compras/proveedores', {
            preserveScroll: true,
            onSuccess: () => {
                drawerOpen.value = false;
            },
        });

        return;
    }

    if (!proveedorEditando.value) {
        return;
    }

    form.transform((datos) => ({
        razon_social: datos.razon_social,
        email: datos.email,
        telefono: datos.telefono,
    })).put(`/compras/proveedores/${proveedorEditando.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            drawerOpen.value = false;
        },
    });
}

function darDeBaja(proveedor: Proveedor): void {
    if (!window.confirm(`¿Dar de baja al proveedor "${proveedor.razon_social}"?`)) {
        return;
    }

    router.delete(`/compras/proveedores/${proveedor.id}`, { preserveScroll: true });
}

function claseDeEstado(estado: string): string {
    switch (estado) {
        case 'Activo':
            return 'bg-pine/10 text-pine border-pine/20';
        case 'Inactivo':
            return 'bg-oxide/10 text-oxide border-oxide/20';
        default:
            return 'bg-workshop text-steel border-hairline';
    }
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
    <AppLayout title="Proveedores">
        <Head title="Proveedores" />

        <div class="flex flex-col gap-4">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div class="flex flex-col gap-1">
                    <h2 class="text-2xl font-semibold tracking-tight text-ink">Proveedores</h2>
                    <p class="text-sm text-steel">
                        Padrón de proveedores de compras. La baja es lógica: el registro se conserva
                        como inactivo.
                    </p>
                </div>
                <button
                    type="button"
                    class="rounded-control bg-teja px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-teja-deep active:scale-[0.98]"
                    @click="abrirCrear"
                >
                    Nuevo proveedor
                </button>
            </div>

            <div
                v-if="page.props.flash.success"
                class="rounded-control border border-pine/20 bg-pine-wash px-4 py-2.5 text-sm text-pine"
            >
                {{ page.props.flash.success }}
            </div>

            <div
                v-if="page.props.errors.proveedor"
                class="rounded-control border border-oxide/20 bg-oxide/5 px-4 py-2.5 text-sm text-oxide"
            >
                {{ page.props.errors.proveedor }}
            </div>

            <div class="rounded-panel border border-hairline bg-surface p-4 shadow-diffused">
                <form class="flex flex-wrap items-end gap-2" @submit.prevent="aplicarFiltros">
                    <div class="flex flex-1 flex-col gap-1">
                        <label for="buscar" class="text-xs font-medium text-steel"
                            >Buscar proveedor</label
                        >
                        <input
                            id="buscar"
                            v-model="busqueda"
                            type="text"
                            placeholder="Razón social, CUIT o email..."
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
                                    Razón social
                                </th>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    CUIT
                                </th>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Email
                                </th>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Teléfono
                                </th>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Estado
                                </th>
                                <th
                                    class="px-4 py-2.5 text-right text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Solicitudes
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
                                v-for="proveedor in props.proveedores.data"
                                :key="proveedor.id"
                                class="hover:bg-teja-wash/30"
                            >
                                <td class="px-4 py-2.5 text-sm font-medium text-ink">
                                    {{ proveedor.razon_social }}
                                </td>
                                <td class="px-4 py-2.5 font-mono text-xs text-steel">
                                    {{ proveedor.cuit }}
                                </td>
                                <td class="px-4 py-2.5 text-sm text-steel">
                                    {{ proveedor.email ?? '—' }}
                                </td>
                                <td class="px-4 py-2.5 text-sm text-steel">
                                    {{ proveedor.telefono ?? '—' }}
                                </td>
                                <td class="px-4 py-2.5 text-sm">
                                    <span
                                        class="inline-flex items-center rounded border px-2 py-0.5 text-xs font-medium"
                                        :class="claseDeEstado(proveedor.estado)"
                                    >
                                        {{ proveedor.estado }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono text-xs text-steel">
                                    {{ proveedor.solicitudes }}
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono text-xs text-steel">
                                    {{ proveedor.ordenes }}
                                </td>
                                <td class="px-4 py-2.5 text-right text-xs">
                                    <button
                                        type="button"
                                        class="font-medium text-teja hover:underline"
                                        @click="abrirEditar(proveedor)"
                                    >
                                        Editar
                                    </button>
                                    <span class="mx-2 text-clay/40">|</span>
                                    <button
                                        type="button"
                                        class="font-medium text-steel hover:text-oxide"
                                        @click="darDeBaja(proveedor)"
                                    >
                                        Dar de baja
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="props.proveedores.data.length === 0"
                    class="px-4 py-10 text-center text-sm text-steel"
                >
                    No hay proveedores registrados.
                </div>

                <div
                    v-if="props.proveedores.total > 0"
                    class="flex flex-wrap items-center justify-between gap-2 border-t border-hairline px-4 py-2.5 text-xs text-steel"
                >
                    <span
                        >Mostrando {{ props.proveedores.from }}–{{ props.proveedores.to }} de
                        {{ props.proveedores.total }}</span
                    >
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="link in props.proveedores.links"
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
                        {{ modo === 'crear' ? 'Nuevo proveedor' : 'Editar proveedor' }}
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
                        <label for="razon_social" class="text-xs font-medium text-steel"
                            >Razón social</label
                        >
                        <input
                            id="razon_social"
                            v-model="form.razon_social"
                            type="text"
                            required
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        />
                        <p v-if="form.errors.razon_social" class="text-xs text-oxide">
                            {{ form.errors.razon_social }}
                        </p>
                    </div>

                    <div v-if="modo === 'crear'" class="flex flex-col gap-1.5">
                        <label for="cuit" class="text-xs font-medium text-steel">CUIT</label>
                        <input
                            id="cuit"
                            v-model="form.cuit"
                            type="text"
                            required
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        />
                        <p v-if="form.errors.cuit" class="text-xs text-oxide">
                            {{ form.errors.cuit }}
                        </p>
                    </div>

                    <div v-else class="flex flex-col gap-1.5">
                        <span class="text-xs font-medium text-steel">CUIT</span>
                        <p
                            class="rounded-control bg-workshop px-3 py-2 font-mono text-sm text-steel"
                        >
                            {{ proveedorEditando?.cuit }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="email" class="text-xs font-medium text-steel">Email</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        />
                        <p v-if="form.errors.email" class="text-xs text-oxide">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="telefono" class="text-xs font-medium text-steel"
                            >Teléfono</label
                        >
                        <input
                            id="telefono"
                            v-model="form.telefono"
                            type="text"
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        />
                        <p v-if="form.errors.telefono" class="text-xs text-oxide">
                            {{ form.errors.telefono }}
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
