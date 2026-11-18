<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import ConfirmDialog from '../Components/ConfirmDialog.vue';

interface AuthUser {
    id: number;
    nombre: string;
    email: string;
    roles: string[];
}

type SharedProps = {
    auth: { user: AuthUser | null };
    alertas_no_leidas: number;
    alertas_recientes: {
        id: number;
        tipo: string;
        severidad: string;
        descripcion: string;
        fecha: string | null;
    }[];
};

defineProps<{ title: string }>();

const page = usePage<SharedProps>();
const user = computed(() => page.props.auth.user);
const alertasNoLeidas = computed(() => page.props.alertas_no_leidas ?? 0);
const alertasRecientes = computed(() => page.props.alertas_recientes ?? []);
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

interface NavEntry {
    label: string;
    href?: string;
    items?: NavItem[];
}

const navEntries = computed<NavEntry[]>(() => [
    { label: 'Tablero', href: '/dashboard' },
    {
        label: 'Producción',
        items: [
            { label: 'Planificaciones', href: '/produccion/planificaciones' },
            { label: 'Órdenes de Producción', href: '/produccion/ordenes-de-produccion' },
            { label: 'Parte Diario', href: '/produccion/partes-diarios' },
            { label: 'Cronograma', href: '/produccion/cronograma' },
            { label: 'Etapas', href: '/produccion/etapas' },
            { label: 'Hoja de Ruta', href: '/produccion/hoja-de-ruta' },
            { label: 'Operarios', href: '/produccion/operarios' },
            { label: 'Maquinarias', href: '/produccion/maquinarias' },
            { label: 'Mantenimiento', href: '/produccion/planes-de-mantenimiento' },
        ],
    },
    {
        label: 'Inventario',
        items: [
            { label: 'Artículos', href: '/inventario/articulos' },
            { label: 'Ingreso de Mercancía', href: '/inventario/ingresos' },
            { label: 'Ajuste de Inventario', href: '/inventario/ajustes' },
            { label: 'Unidades de Medida', href: '/inventario/unidades-de-medida' },
        ],
    },
    {
        label: 'Compras',
        items: [
            { label: 'Proveedores', href: '/compras/proveedores' },
            {
                label: 'Solicitudes de Cotización',
                href: '/compras/solicitudes-de-cotizacion',
            },
            { label: 'Órdenes de Compra', href: '/compras/ordenes-de-compra' },
            { label: 'Ranking de Proveedores', href: '/compras/ranking' },
        ],
    },
    {
        label: 'Predictivo',
        items: [{ label: 'Tablero Predictivo' }, { label: 'Simulador' }],
    },
    { label: 'Reportes', href: '/reportes' },
    { label: 'Auditoría', href: '/auditoria' },
    {
        label: 'Usuarios',
        items: [
            { label: 'Usuarios y Seguridad', href: '/usuarios' },
            { label: 'Configuración', href: '/usuarios/configuracion' },
        ],
    },
]);

function isActive(href?: string): boolean {
    return href !== undefined && currentUrl.value.split('?')[0] === href.split('?')[0];
}

function isEntryActive(entry: NavEntry): boolean {
    if (entry.href !== undefined) {
        return isActive(entry.href);
    }

    return entry.items?.some((item) => isActive(item.href)) ?? false;
}

const openMenu = ref<string | null>(null);
const mobileOpen = ref(false);
const alertasOpen = ref(false);
const navRoot = ref<HTMLElement | null>(null);

function toggleMenu(label: string): void {
    openMenu.value = openMenu.value === label ? null : label;
}

function toggleAlertas(): void {
    closeMenus();
    alertasOpen.value = !alertasOpen.value;
}

function closeMenus(): void {
    openMenu.value = null;
    mobileOpen.value = false;
    alertasOpen.value = false;
}

function claseSeveridad(valor: string): string {
    if (valor === 'Critica') {
        return 'bg-oxide';
    }

    if (valor === 'Atencion') {
        return 'bg-amber';
    }

    return 'bg-steel';
}

function handlePointerDown(event: Event): void {
    if (navRoot.value && !navRoot.value.contains(event.target as Node)) {
        openMenu.value = null;
    }
}

function handleKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape') {
        closeMenus();
    }
}

onMounted(() => {
    document.addEventListener('pointerdown', handlePointerDown);
    document.addEventListener('keydown', handleKeydown);
});

onBeforeUnmount(() => {
    document.removeEventListener('pointerdown', handlePointerDown);
    document.removeEventListener('keydown', handleKeydown);
});

watch(currentUrl, closeMenus);

const topLevelLinkClass = (active: boolean): string =>
    [
        'inline-flex items-center gap-1 rounded-control px-2.5 py-2 text-[0.8125rem] font-medium whitespace-nowrap transition-colors duration-150',
        active ? 'bg-teja-wash text-teja-deep' : 'text-steel hover:bg-workshop/60 hover:text-ink',
    ].join(' ');

const dropdownItemClass = (active: boolean): string =>
    [
        'block rounded-r-control border-l-[3px] px-3 py-2 text-sm whitespace-nowrap transition-colors duration-150',
        active
            ? 'border-teja bg-teja-wash font-medium text-ink'
            : 'border-transparent text-steel hover:bg-workshop/40 hover:text-ink',
    ].join(' ');

const mobileItemClass = (active: boolean): string =>
    [
        'block rounded-control px-3 py-2.5 text-sm transition-colors duration-150',
        active
            ? 'bg-teja-wash font-medium text-teja-deep'
            : 'text-steel hover:bg-workshop/40 hover:text-ink',
    ].join(' ');
</script>

<template>
    <div class="flex min-h-[100dvh] flex-col bg-canvas text-ink">
        <header ref="navRoot" class="sticky top-0 z-30 w-full border-b border-hairline bg-surface">
            <div
                class="mx-auto flex h-14 w-full max-w-[1440px] items-center gap-3 px-4 lg:gap-4 lg:px-6"
            >
                <Link href="/dashboard" class="flex shrink-0 items-center gap-2">
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-control bg-teja text-sm font-bold text-surface"
                        aria-hidden="true"
                        >S</span
                    >
                    <span class="flex flex-col leading-none">
                        <span class="text-sm font-semibold tracking-tight text-ink">SIGPAP</span>
                        <span class="mt-0.5 text-[0.625rem] tracking-wider text-clay uppercase"
                            >Confort y Muebles</span
                        >
                    </span>
                </Link>

                <nav
                    class="hidden min-w-0 flex-1 items-center gap-0.5 xl:flex"
                    aria-label="Navegación principal"
                >
                    <template v-for="entry in navEntries" :key="entry.label">
                        <Link
                            v-if="entry.href"
                            :href="entry.href"
                            :aria-current="isActive(entry.href) ? 'page' : undefined"
                            :class="topLevelLinkClass(isActive(entry.href))"
                        >
                            {{ entry.label }}
                        </Link>

                        <div v-else class="relative">
                            <button
                                type="button"
                                :aria-expanded="openMenu === entry.label"
                                aria-haspopup="true"
                                :class="
                                    topLevelLinkClass(
                                        openMenu === entry.label || isEntryActive(entry),
                                    )
                                "
                                @click="toggleMenu(entry.label)"
                            >
                                {{ entry.label }}
                                <svg
                                    class="h-3.5 w-3.5 transition-transform duration-150"
                                    :class="openMenu === entry.label ? 'rotate-180' : ''"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                    aria-hidden="true"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </button>

                            <div
                                v-if="openMenu === entry.label"
                                class="absolute top-full left-0 z-40 mt-1.5 w-64 rounded-panel border border-hairline bg-surface p-1.5 shadow-[0_12px_28px_rgba(34,40,31,0.14)]"
                            >
                                <ul class="space-y-0.5">
                                    <li v-for="item in entry.items" :key="item.label">
                                        <Link
                                            v-if="item.href"
                                            :href="item.href"
                                            :aria-current="isActive(item.href) ? 'page' : undefined"
                                            :class="dropdownItemClass(isActive(item.href))"
                                        >
                                            {{ item.label }}
                                        </Link>
                                        <span
                                            v-else
                                            class="block cursor-default border-l-[3px] border-transparent px-3 py-2 text-sm whitespace-nowrap text-steel/60"
                                        >
                                            {{ item.label }}
                                        </span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </template>
                </nav>

                <div class="ml-auto flex items-center gap-2 xl:ml-0 xl:gap-3">
                    <div v-if="user" class="relative">
                        <button
                            type="button"
                            data-testid="campana-alertas"
                            class="relative inline-flex h-9 w-9 items-center justify-center rounded-control border border-hairline text-steel transition-colors hover:bg-workshop/60 hover:text-ink"
                            :aria-expanded="alertasOpen"
                            aria-haspopup="true"
                            :aria-label="
                                alertasNoLeidas > 0
                                    ? `Alertas, ${alertasNoLeidas} sin leer`
                                    : 'Alertas'
                            "
                            @click="toggleAlertas"
                        >
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="h-5 w-5"
                                aria-hidden="true"
                            >
                                <path d="M18 8a6 6 0 1 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9" />
                                <path d="M10.3 21a2 2 0 0 0 3.4 0" />
                            </svg>
                            <span
                                v-if="alertasNoLeidas > 0"
                                class="absolute -top-1 -right-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-oxide px-1 text-[0.625rem] font-semibold text-white"
                            >
                                {{ alertasNoLeidas > 9 ? '9+' : alertasNoLeidas }}
                            </span>
                        </button>

                        <div
                            v-if="alertasOpen"
                            data-testid="alertas-dropdown"
                            class="absolute top-full right-0 z-40 mt-2 w-80 overflow-hidden rounded-panel border border-hairline bg-surface shadow-diffused"
                        >
                            <div class="max-h-80 overflow-y-auto">
                                <ul
                                    v-if="alertasRecientes.length > 0"
                                    class="divide-y divide-hairline"
                                >
                                    <li
                                        v-for="alerta in alertasRecientes"
                                        :key="alerta.id"
                                        class="flex items-start gap-2.5 px-3 py-2.5"
                                    >
                                        <span
                                            :class="[
                                                'mt-1.5 h-2 w-2 shrink-0 rounded-full',
                                                claseSeveridad(alerta.severidad),
                                            ]"
                                            aria-hidden="true"
                                        ></span>
                                        <div class="flex flex-col gap-0.5">
                                            <p class="text-xs leading-snug font-medium text-ink">
                                                {{ alerta.descripcion }}
                                            </p>
                                            <p class="text-[0.625rem] text-clay">
                                                {{ alerta.fecha }}
                                            </p>
                                        </div>
                                    </li>
                                </ul>
                                <p v-else class="px-3 py-6 text-center text-xs text-steel">
                                    No hay alertas recientes.
                                </p>
                            </div>
                            <Link
                                href="/alertas"
                                class="block border-t border-hairline px-3 py-2.5 text-center text-xs font-medium text-teja transition-colors hover:bg-teja-wash/40 hover:underline"
                                @click="alertasOpen = false"
                            >
                                Ver todas las alertas
                            </Link>
                        </div>
                    </div>

                    <template v-if="user">
                        <div class="hidden items-center gap-2.5 xl:flex">
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
                            class="hidden text-xs font-medium whitespace-nowrap text-teja transition-opacity hover:underline active:opacity-80 sm:inline-flex"
                        >
                            Cerrar sesión
                        </Link>
                    </template>

                    <button
                        type="button"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-control border border-hairline text-steel transition-colors hover:bg-workshop/60 hover:text-ink xl:hidden"
                        :aria-expanded="mobileOpen"
                        aria-controls="mobile-nav"
                        :aria-label="
                            mobileOpen ? 'Cerrar menú de navegación' : 'Abrir menú de navegación'
                        "
                        @click="mobileOpen = !mobileOpen"
                    >
                        <svg
                            v-if="mobileOpen"
                            class="h-5 w-5"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            aria-hidden="true"
                        >
                            <path
                                d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"
                            />
                        </svg>
                        <svg
                            v-else
                            class="h-5 w-5"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            aria-hidden="true"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M3 5.75A.75.75 0 0 1 3.75 5h12.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 5.75Zm0 4.25A.75.75 0 0 1 3.75 9.25h12.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 10Zm0 4.25a.75.75 0 0 1 .75-.75h12.5a.75.75 0 0 1 0 1.5H3.75a.75.75 0 0 1-.75-.75Z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </button>
                </div>
            </div>

            <div
                v-if="mobileOpen"
                id="mobile-nav"
                class="border-t border-hairline bg-surface xl:hidden"
            >
                <div class="max-h-[calc(100dvh-3.5rem)] overflow-y-auto px-4 py-3">
                    <nav class="space-y-2" aria-label="Navegación móvil">
                        <div v-for="entry in navEntries" :key="entry.label">
                            <Link
                                v-if="entry.href"
                                :href="entry.href"
                                :aria-current="isActive(entry.href) ? 'page' : undefined"
                                :class="mobileItemClass(isActive(entry.href))"
                            >
                                {{ entry.label }}
                            </Link>

                            <template v-else>
                                <button
                                    type="button"
                                    class="flex w-full items-center justify-between rounded-control px-3 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-clay uppercase transition-colors hover:bg-workshop/40 hover:text-steel"
                                    :aria-expanded="openMenu === entry.label"
                                    @click="toggleMenu(entry.label)"
                                >
                                    {{ entry.label }}
                                    <svg
                                        class="h-3.5 w-3.5 transition-transform duration-150"
                                        :class="openMenu === entry.label ? 'rotate-180' : ''"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                        aria-hidden="true"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </button>
                                <ul
                                    v-if="openMenu === entry.label"
                                    class="mt-1 space-y-0.5 border-l border-hairline pl-2"
                                >
                                    <li v-for="item in entry.items" :key="item.label">
                                        <Link
                                            v-if="item.href"
                                            :href="item.href"
                                            :aria-current="isActive(item.href) ? 'page' : undefined"
                                            :class="mobileItemClass(isActive(item.href))"
                                        >
                                            {{ item.label }}
                                        </Link>
                                        <span
                                            v-else
                                            class="block cursor-default px-3 py-2.5 text-sm text-steel/60"
                                        >
                                            {{ item.label }}
                                        </span>
                                    </li>
                                </ul>
                            </template>
                        </div>
                    </nav>

                    <div
                        v-if="user"
                        class="mt-3 flex items-center justify-between gap-3 border-t border-hairline pt-3"
                    >
                        <div class="flex items-center gap-2.5">
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
                            class="text-xs font-medium whitespace-nowrap text-teja transition-opacity hover:underline active:opacity-80"
                        >
                            Cerrar sesión
                        </Link>
                    </div>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-[1440px] flex-1 space-y-5 p-4 lg:p-6">
            <h1 class="sr-only">{{ title }}</h1>
            <slot />
        </main>

        <ConfirmDialog />
    </div>
</template>
