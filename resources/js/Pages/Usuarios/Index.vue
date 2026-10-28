<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

interface Role {
    id: number;
    name: string;
}

interface Usuario {
    id: number;
    nombre: string;
    email: string;
    estado: string;
    roles: Role[];
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedUsuarios {
    data: Usuario[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
}

type SharedProps = {
    auth: { user: { id: number } };
    flash: { success: string | null };
};

const props = defineProps<{
    usuarios: PaginatedUsuarios;
    resumen: { total: number; activos: number; inhabilitados: number };
    roles: string[];
    filtros: { buscar?: string | null; rol?: string | null; estado?: string | null };
}>();

const page = usePage<SharedProps>();

const busqueda = ref(props.filtros.buscar ?? '');
const rolFiltro = ref(props.filtros.rol ?? '');
const estadoFiltro = ref(props.filtros.estado ?? '');

const drawerOpen = ref(false);
const modo = ref<'crear' | 'editar'>('crear');
const usuarioEditando = ref<Usuario | null>(null);

const form = useForm({
    nombre: '',
    email: '',
    rol: '',
    password: '',
    estado: 'Activo',
});

function iniciales(nombre: string): string {
    return nombre
        .split(' ')
        .map((parte) => parte.charAt(0))
        .join('')
        .slice(0, 2)
        .toUpperCase();
}

function rolDe(usuario: Usuario): string {
    return usuario.roles[0]?.name ?? 'Sin rol';
}

function esPropio(usuario: Usuario): boolean {
    return usuario.id === page.props.auth.user.id;
}

const esEdicionPropia = computed(() => {
    const usuario = usuarioEditando.value;

    return usuario !== null && esPropio(usuario);
});

function aplicarFiltros(): void {
    router.get(
        '/usuarios',
        {
            buscar: busqueda.value || undefined,
            rol: rolFiltro.value || undefined,
            estado: estadoFiltro.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function abrirCrear(): void {
    modo.value = 'crear';
    usuarioEditando.value = null;
    form.reset();
    form.clearErrors();
    form.estado = 'Activo';
    drawerOpen.value = true;
}

function abrirEditar(usuario: Usuario): void {
    modo.value = 'editar';
    usuarioEditando.value = usuario;
    form.clearErrors();
    form.nombre = usuario.nombre;
    form.email = usuario.email;
    form.rol = rolDe(usuario);
    form.password = '';
    form.estado = usuario.estado;
    drawerOpen.value = true;
}

function cerrarDrawer(): void {
    drawerOpen.value = false;
}

function guardar(): void {
    if (modo.value === 'crear') {
        form.post('/usuarios', {
            preserveScroll: true,
            onSuccess: () => {
                drawerOpen.value = false;
                form.reset();
            },
        });

        return;
    }

    if (usuarioEditando.value) {
        form.put(`/usuarios/${usuarioEditando.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                drawerOpen.value = false;
            },
        });
    }
}

function alternarEstado(usuario: Usuario): void {
    router.patch(`/usuarios/${usuario.id}/estado`, {}, { preserveScroll: true });
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
    <AppLayout title="Usuarios y Seguridad">
        <Head title="Usuarios y Seguridad" />

        <div class="flex flex-col gap-4">
            <div class="flex flex-col gap-1">
                <h2 class="text-2xl font-semibold tracking-tight text-ink">Usuarios y Seguridad</h2>
                <p class="text-sm text-steel">
                    Alta, modificación, baja lógica y búsqueda de usuarios. Solo el Administrador
                    gestiona este módulo.
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
                                placeholder="Nombre, email o rol..."
                                class="w-56 rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink placeholder:text-clay/70 focus:ring-2 focus:ring-teja/40 focus:outline-none"
                            />
                        </div>

                        <div class="flex flex-col gap-1">
                            <label for="rol" class="text-xs font-medium text-steel">Rol</label>
                            <select
                                id="rol"
                                v-model="rolFiltro"
                                class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                                @change="aplicarFiltros"
                            >
                                <option value="">Todos los roles</option>
                                <option v-for="rol in props.roles" :key="rol" :value="rol">
                                    {{ rol }}
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
                                <option value="Inhabilitado">Inhabilitado</option>
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
                        + Nuevo usuario
                    </button>
                </div>

                <div
                    class="mt-3 flex flex-wrap items-center gap-2 border-t border-hairline pt-2.5 text-xs"
                >
                    <span class="font-semibold text-ink"
                        >{{ props.resumen.total }} usuarios registrados</span
                    >
                    <span class="text-clay">·</span>
                    <span class="font-medium text-pine">{{ props.resumen.activos }} activos</span>
                    <span class="text-clay">·</span>
                    <span class="text-clay">{{ props.resumen.inhabilitados }} inhabilitados</span>
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
                                    Nombre
                                </th>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Email
                                </th>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Rol
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
                                v-for="usuario in props.usuarios.data"
                                :key="usuario.id"
                                class="hover:bg-teja-wash/30"
                            >
                                <td class="px-4 py-2.5">
                                    <div class="flex items-center gap-2.5">
                                        <span
                                            class="flex h-7 w-7 items-center justify-center rounded-full bg-workshop text-xs font-semibold text-ink"
                                        >
                                            {{ iniciales(usuario.nombre) }}
                                        </span>
                                        <span class="text-sm font-medium text-ink">{{
                                            usuario.nombre
                                        }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-2.5 font-mono text-xs text-steel">
                                    {{ usuario.email }}
                                </td>
                                <td class="px-4 py-2.5">
                                    <span
                                        class="rounded border border-hairline bg-canvas px-2 py-0.5 text-[0.6875rem] font-medium text-slate"
                                    >
                                        {{ rolDe(usuario) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5">
                                    <span
                                        class="inline-flex items-center gap-1 rounded px-2 py-0.5 text-[0.6875rem] font-medium"
                                        :class="
                                            usuario.estado === 'Activo'
                                                ? 'bg-pine-wash text-pine'
                                                : 'bg-workshop text-steel'
                                        "
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full"
                                            :class="
                                                usuario.estado === 'Activo' ? 'bg-pine' : 'bg-steel'
                                            "
                                        ></span>
                                        {{ usuario.estado }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-right text-xs">
                                    <button
                                        type="button"
                                        class="font-medium text-teja hover:underline"
                                        @click="abrirEditar(usuario)"
                                    >
                                        Editar
                                    </button>
                                    <span class="mx-2 text-clay/40">|</span>
                                    <button
                                        v-if="
                                            !esPropio(usuario) || usuario.estado === 'Inhabilitado'
                                        "
                                        type="button"
                                        class="font-medium transition-colors"
                                        :class="
                                            usuario.estado === 'Inhabilitado'
                                                ? 'text-pine hover:underline'
                                                : 'text-steel hover:text-oxide'
                                        "
                                        @click="alternarEstado(usuario)"
                                    >
                                        {{
                                            usuario.estado === 'Inhabilitado'
                                                ? 'Habilitar'
                                                : 'Inhabilitar'
                                        }}
                                    </button>
                                    <span
                                        v-else
                                        class="text-clay/60"
                                        title="No podés inhabilitar tu propia cuenta"
                                    >
                                        Inhabilitar
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="props.usuarios.data.length === 0"
                    class="px-4 py-10 text-center text-sm text-steel"
                >
                    {{
                        props.filtros.buscar || props.filtros.rol || props.filtros.estado
                            ? 'No se encontraron usuarios para el criterio especificado.'
                            : 'No hay usuarios registrados.'
                    }}
                </div>

                <div
                    v-if="props.usuarios.total > 0"
                    class="flex flex-wrap items-center justify-between gap-2 border-t border-hairline px-4 py-2.5 text-xs text-steel"
                >
                    <span
                        >Mostrando {{ props.usuarios.from }}–{{ props.usuarios.to }} de
                        {{ props.usuarios.total }}</span
                    >
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="link in props.usuarios.links"
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
                        {{ modo === 'crear' ? 'Nuevo usuario' : 'Editar usuario' }}
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
                        <label for="nombre" class="text-xs font-medium text-steel"
                            >Nombre completo</label
                        >
                        <input
                            id="nombre"
                            v-model="form.nombre"
                            type="text"
                            required
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        />
                        <p v-if="form.errors.nombre" class="text-xs text-oxide">
                            {{ form.errors.nombre }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="email" class="text-xs font-medium text-steel"
                            >Correo electrónico</label
                        >
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            required
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        />
                        <p v-if="form.errors.email" class="text-xs text-oxide">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="rol-form" class="text-xs font-medium text-steel"
                            >Rol asignado</label
                        >
                        <select
                            id="rol-form"
                            v-model="form.rol"
                            required
                            :disabled="esEdicionPropia"
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        >
                            <option value="" disabled>Seleccioná un rol</option>
                            <option v-for="rol in props.roles" :key="rol" :value="rol">
                                {{ rol }}
                            </option>
                        </select>
                        <p v-if="form.errors.rol" class="text-xs text-oxide">
                            {{ form.errors.rol }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="password" class="text-xs font-medium text-steel">
                            {{
                                modo === 'crear'
                                    ? 'Contraseña temporal'
                                    : 'Nueva contraseña (opcional)'
                            }}
                        </label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            :required="modo === 'crear'"
                            :placeholder="modo === 'editar' ? 'Dejar vacío para no cambiar' : ''"
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink placeholder:text-clay/70 focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        />
                        <p v-if="form.errors.password" class="text-xs text-oxide">
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <div v-if="modo === 'editar'" class="flex flex-col gap-1.5">
                        <label for="estado-form" class="text-xs font-medium text-steel"
                            >Estado de la cuenta</label
                        >
                        <select
                            id="estado-form"
                            v-model="form.estado"
                            :disabled="esEdicionPropia"
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        >
                            <option value="Activo">Activo</option>
                            <option value="Inhabilitado">Inhabilitado</option>
                        </select>
                        <p v-if="esEdicionPropia" class="text-[11px] text-amber">
                            No podés inhabilitarte ni quitarte el rol de Administrador.
                        </p>
                        <p v-if="form.errors.estado" class="text-xs text-oxide">
                            {{ form.errors.estado }}
                        </p>
                    </div>

                    <p class="text-[11px] leading-snug text-clay">
                        Los cambios de rol y contraseña quedan registrados con timestamp e IP en la
                        auditoría.
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
