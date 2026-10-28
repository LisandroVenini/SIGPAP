<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface AuthUser {
    id: number;
    nombre: string;
    email: string;
    roles: string[];
}

type SharedProps = {
    auth: { user: AuthUser | null };
};

defineProps<{ title: string }>();

const page = usePage<SharedProps>();
const user = computed(() => page.props.auth.user);
const currentUrl = computed(() => page.url);
const initials = computed(() =>
    user.value
        ? user.value.nombre
              .split(' ')
              .map((part) => part.charAt(0))
              .join('')
              .slice(0, 2)
              .toUpperCase()
        : '',
);

interface NavItem {
    label: string;
    href?: string;
}

interface NavGroup {
    label: string;
    items: NavItem[];
}

const navGroups: NavGroup[] = [
    { label: 'General', items: [{ label: 'Tablero', href: '/dashboard' }] },
    {
        label: 'Producción',
        items: [
            { label: 'Planificaciones' },
            { label: 'Órdenes de Producción' },
            { label: 'Parte Diario' },
            { label: 'Cronograma' },
            { label: 'Etapas' },
            { label: 'Hoja de Ruta' },
            { label: 'Operarios' },
            { label: 'Maquinarias' },
            { label: 'Mantenimiento' },
        ],
    },
    {
        label: 'Inventario',
        items: [
            { label: 'Artículos', href: '/inventario/articulos' },
            { label: 'Ingreso de Mercancía' },
            { label: 'Ajuste de Inventario', href: '/inventario/ajustes' },
        ],
    },
    {
        label: 'Compras',
        items: [
            { label: 'Proveedores' },
            { label: 'Solicitudes de Cotización' },
            { label: 'Órdenes de Compra' },
        ],
    },
    {
        label: 'Predictivo',
        items: [{ label: 'Tablero Predictivo' }, { label: 'Simulador' }],
    },
    { label: 'Reportes', items: [{ label: 'Reportes de Producción' }] },
    { label: 'Auditoría', items: [{ label: 'Registro de Auditoría' }] },
    {
        label: 'Usuarios',
        items: [
            { label: 'Usuarios y Seguridad', href: '/usuarios' },
            { label: 'Configuración', href: '/usuarios/configuracion' },
        ],
    },
];

function isActive(href?: string): boolean {
    return href !== undefined && currentUrl.value.split('?')[0] === href;
}
</script>

<template>
    <div class="flex min-h-[100dvh] flex-col bg-canvas text-ink">
        <header class="w-full shrink-0 border-b border-hairline bg-surface">
            <div class="mx-auto flex w-full max-w-[1440px] items-center justify-between px-6 py-2">
                <div class="flex items-center gap-3">
                    <span class="text-sm font-semibold tracking-tight text-ink">
                        SIGPAP ERP | Confort y Muebles
                    </span>
                    <span
                        class="inline-flex items-center rounded bg-workshop px-2 py-0.5 text-[0.6875rem] font-medium text-steel"
                    >
                        Planta Posadas · Misiones
                    </span>
                </div>
            </div>
        </header>

        <div class="mx-auto flex w-full max-w-[1440px] flex-1 overflow-hidden">
            <aside
                class="hidden w-64 shrink-0 flex-col justify-between border-r border-hairline bg-surface md:flex"
            >
                <div class="flex flex-1 flex-col overflow-y-auto">
                    <div class="border-b border-hairline px-5 py-4">
                        <div class="flex items-baseline justify-between">
                            <span class="text-lg font-bold tracking-tight text-ink">SIGPAP</span>
                            <span class="text-[0.625rem] tracking-wider text-clay uppercase"
                                >Maderera ERP</span
                            >
                        </div>
                        <p class="mt-0.5 text-[0.6875rem] leading-snug text-clay">
                            Confort y Muebles · Sistema de Gestión
                        </p>
                    </div>

                    <nav class="flex-1 space-y-4 px-3 py-3">
                        <div v-for="group in navGroups" :key="group.label">
                            <div
                                class="px-2 pt-2 pb-1 text-[0.75rem] font-semibold tracking-wider text-clay uppercase"
                            >
                                {{ group.label }}
                            </div>
                            <ul class="mt-1 space-y-0.5">
                                <li v-for="item in group.items" :key="item.label">
                                    <Link
                                        v-if="item.href"
                                        :href="item.href"
                                        class="flex items-center gap-2.5 rounded px-3 py-1.5 text-sm transition-colors duration-150"
                                        :class="
                                            isActive(item.href)
                                                ? 'border-l-[3px] border-teja bg-teja-wash font-medium text-ink'
                                                : 'text-steel hover:bg-workshop/30 hover:text-ink'
                                        "
                                    >
                                        {{ item.label }}
                                    </Link>
                                    <span
                                        v-else
                                        class="flex cursor-default items-center gap-2.5 rounded px-3 py-1.5 text-sm text-steel/60"
                                    >
                                        {{ item.label }}
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </nav>
                </div>

                <div class="flex items-center justify-between border-t border-hairline px-4 py-3">
                    <div class="flex items-center gap-2">
                        <span class="inline-block h-2 w-2 rounded-full bg-pine"></span>
                        <span class="text-[0.6875rem] text-steel">En línea</span>
                    </div>
                    <span class="text-[0.625rem] text-clay">SIGPAP</span>
                </div>
            </aside>

            <div class="flex min-w-0 flex-1 flex-col overflow-y-auto">
                <header
                    class="sticky top-0 z-10 flex h-14 shrink-0 items-center justify-between border-b border-hairline bg-surface px-6"
                >
                    <h1 class="text-lg font-semibold tracking-tight text-ink">{{ title }}</h1>

                    <div class="flex items-center gap-4">
                        <template v-if="user">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-full border border-hairline bg-sage-wash text-xs font-semibold text-sage-deep"
                                >
                                    {{ initials }}
                                </div>
                                <div class="flex flex-col text-left">
                                    <span class="text-xs leading-tight font-medium text-ink">{{
                                        user.nombre
                                    }}</span>
                                    <span class="text-[0.6875rem] leading-tight text-clay">
                                        {{ user.roles.join(', ') || 'Sin rol' }}
                                    </span>
                                </div>
                            </div>
                            <Link
                                href="/logout"
                                method="post"
                                as="button"
                                class="text-xs font-medium text-teja transition-opacity hover:underline active:opacity-80"
                            >
                                Cerrar sesión
                            </Link>
                        </template>
                    </div>
                </header>

                <main class="flex-1 space-y-5 bg-canvas p-6">
                    <slot />
                </main>

                <footer class="shrink-0 border-t border-hairline">
                    <div
                        class="mx-auto flex w-full max-w-[1440px] flex-col items-center justify-between gap-2 px-6 py-3 text-center text-xs text-clay md:flex-row md:text-left"
                    >
                        <span class="text-steel">
                            © 2024 Confort y Muebles S.A. — Sistema Integrado de Gestión de
                            Producción (SIGPAP)
                        </span>
                    </div>
                </footer>
            </div>
        </div>
    </div>
</template>
