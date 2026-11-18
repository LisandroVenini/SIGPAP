<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '../../../Layouts/AppLayout.vue';
import { confirmar } from '@/composables/useConfirm';

interface Condicion {
    clave: string;
    etiqueta: string;
    tipo: string;
    minimo: number;
    maximo: number;
    por_defecto: number;
}

interface TipoDeAlerta {
    clave: string;
    etiqueta: string;
    descripcion: string;
    modulo: string;
    origen: string;
    severidad_por_defecto: string;
    roles_por_defecto: string[];
    condiciones: Condicion[];
    variables: string[];
}

interface Regla {
    id: number;
    nombre: string;
    descripcion: string | null;
    tipo: string;
    modulo: string;
    severidad: string;
    condiciones: Record<string, number>;
    mensaje: string;
    activa: boolean;
    roles: { id: number; name: string }[];
}

interface Paginado {
    data: Regla[];
    links: { url: string | null; label: string; active: boolean }[];
    from: number | null;
    to: number | null;
    total: number;
}

const props = defineProps<{
    reglas: Paginado;
    filtros: { buscar?: string | null; modulo?: string | null; activa?: boolean | null };
    tipos: TipoDeAlerta[];
    roles: { id: number; name: string }[];
    modulos: string[];
    severidades: { valor: string; etiqueta: string }[];
    puedeGestionar: boolean;
}>();

const busca = ref(props.filtros.buscar ?? '');
const modulo = ref(props.filtros.modulo ?? '');

const drawerOpen = ref(false);
const editando = ref<Regla | null>(null);
const tipoSeleccionado = ref<TipoDeAlerta | null>(null);

const form = useForm({
    nombre: '',
    descripcion: '',
    tipo: '',
    severidad: 'Atencion',
    mensaje: '',
    activa: true,
    roles: [] as number[],
    condiciones: {} as Record<string, number>,
});

const esEdicion = computed(() => editando.value !== null);

function filtrar(): void {
    router.get(
        '/alertas/reglas',
        {
            buscar: busca.value || undefined,
            modulo: modulo.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function abrirCrear(): void {
    editando.value = null;
    tipoSeleccionado.value = null;
    form.reset();
    form.clearErrors();
    form.roles = [];
    form.condiciones = {};
    drawerOpen.value = true;
}

function abrirEditar(regla: Regla): void {
    editando.value = regla;
    tipoSeleccionado.value = props.tipos.find((tipo) => tipo.clave === regla.tipo) ?? null;
    form.clearErrors();
    form.nombre = regla.nombre;
    form.descripcion = regla.descripcion ?? '';
    form.tipo = regla.tipo;
    form.severidad = regla.severidad;
    form.mensaje = regla.mensaje;
    form.activa = regla.activa;
    form.roles = regla.roles.map((rol) => rol.id);
    form.condiciones = { ...regla.condiciones };
    drawerOpen.value = true;
}

function cerrarDrawer(): void {
    drawerOpen.value = false;
}

function elegirTipo(clave: string): void {
    form.tipo = clave;
    const tipo = props.tipos.find((item) => item.clave === clave) ?? null;
    tipoSeleccionado.value = tipo;

    if (tipo === null) {
        return;
    }

    form.severidad = tipo.severidad_por_defecto;
    form.condiciones = Object.fromEntries(
        tipo.condiciones.map((condicion) => [condicion.clave, condicion.por_defecto]),
    );
    form.mensaje = form.mensaje || `Alerta: ${tipo.descripcion}`;

    const ids = props.roles
        .filter((rol) => tipo.roles_por_defecto.includes(rol.name))
        .map((rol) => rol.id);
    form.roles = ids;
}

function guardar(): void {
    if (esEdicion.value && editando.value !== null) {
        form.put(`/alertas/reglas/${editando.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                drawerOpen.value = false;
            },
        });

        return;
    }

    form.post('/alertas/reglas', {
        preserveScroll: true,
        onSuccess: () => {
            drawerOpen.value = false;
        },
    });
}

function alternar(regla: Regla): void {
    router.patch(`/alertas/reglas/${regla.id}/estado`, {}, { preserveScroll: true });
}

async function eliminar(regla: Regla): Promise<void> {
    const confirmado = await confirmar({
        titulo: 'Eliminar regla',
        mensaje: `¿Eliminar la regla "${regla.nombre}"? Las alertas ya emitidas se conservan.`,
        confirmar: 'Eliminar',
        peligro: true,
    });

    if (confirmado) {
        router.delete(`/alertas/reglas/${regla.id}`, { preserveScroll: true });
    }
}

function etiquetaTipo(clave: string): string {
    return props.tipos.find((tipo) => tipo.clave === clave)?.etiqueta ?? clave;
}

function etiquetaSeveridad(valor: string): string {
    return props.severidades.find((item) => item.valor === valor)?.etiqueta ?? valor;
}

function variablesDe(tipo: TipoDeAlerta): string {
    return tipo.variables.map((variable) => '{' + '{' + variable + '}' + '}').join(', ');
}

function labelDePagina(label: string): string {
    return label.replace('&laquo;', '‹').replace('&raquo;', '›');
}
</script>

<template>
    <AppLayout title="Reglas de alerta">
        <Head title="Reglas de alerta" />

        <div class="flex flex-col gap-4">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div class="flex flex-col gap-1">
                    <h2 class="text-2xl font-semibold tracking-tight text-ink">Reglas de alerta</h2>
                    <p class="text-sm text-steel">
                        Cada regla define qué se avisa, con qué umbral y a qué roles.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <a
                        href="/alertas"
                        class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm font-medium text-ink transition-colors hover:bg-workshop/40"
                    >
                        Ver alertas
                    </a>
                    <button
                        v-if="puedeGestionar"
                        type="button"
                        class="rounded-control bg-teja px-3 py-2 text-sm font-medium text-white transition-colors hover:bg-teja-deep"
                        @click="abrirCrear"
                    >
                        Nueva regla
                    </button>
                </div>
            </div>

            <form
                class="flex flex-wrap items-end gap-3 rounded-panel border border-hairline bg-surface p-4 shadow-diffused"
                @submit.prevent="filtrar"
            >
                <div class="flex flex-col gap-1">
                    <label for="buscar" class="text-xs font-medium text-steel">Buscar</label>
                    <input
                        id="buscar"
                        v-model="busca"
                        type="search"
                        placeholder="Nombre de la regla"
                        class="w-56 rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                    />
                </div>

                <div class="flex flex-col gap-1">
                    <label for="modulo" class="text-xs font-medium text-steel">Módulo</label>
                    <select
                        id="modulo"
                        v-model="modulo"
                        class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink"
                    >
                        <option value="">Todos</option>
                        <option v-for="opcion in modulos" :key="opcion" :value="opcion">
                            {{ opcion }}
                        </option>
                    </select>
                </div>

                <button
                    type="submit"
                    class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm font-medium text-ink transition-colors hover:bg-workshop/40"
                >
                    Filtrar
                </button>
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
                                    Regla
                                </th>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Módulo
                                </th>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Severidad
                                </th>
                                <th
                                    class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                >
                                    Roles
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
                                v-for="regla in reglas.data"
                                :key="regla.id"
                                class="hover:bg-teja-wash/30"
                            >
                                <td class="px-4 py-2.5">
                                    <p class="text-sm font-medium text-ink">{{ regla.nombre }}</p>
                                    <p class="text-xs text-steel">{{ etiquetaTipo(regla.tipo) }}</p>
                                </td>
                                <td class="px-4 py-2.5 text-sm text-steel">{{ regla.modulo }}</td>
                                <td class="px-4 py-2.5 text-sm text-steel">
                                    {{ etiquetaSeveridad(regla.severidad) }}
                                </td>
                                <td class="px-4 py-2.5 text-xs text-steel">
                                    {{ regla.roles.map((rol) => rol.name).join(', ') }}
                                </td>
                                <td class="px-4 py-2.5 text-xs">
                                    <span :class="regla.activa ? 'text-pine' : 'text-clay'">
                                        {{ regla.activa ? 'Activa' : 'Inactiva' }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-right text-xs">
                                    <div
                                        v-if="puedeGestionar"
                                        class="flex items-center justify-end gap-2"
                                    >
                                        <button
                                            type="button"
                                            class="font-medium text-teja hover:underline"
                                            @click="abrirEditar(regla)"
                                        >
                                            Editar
                                        </button>
                                        <button
                                            type="button"
                                            class="font-medium text-steel hover:underline"
                                            @click="alternar(regla)"
                                        >
                                            {{ regla.activa ? 'Desactivar' : 'Activar' }}
                                        </button>
                                        <button
                                            type="button"
                                            class="font-medium text-oxide hover:underline"
                                            @click="eliminar(regla)"
                                        >
                                            Eliminar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="reglas.data.length === 0"
                    class="px-4 py-10 text-center text-sm text-steel"
                >
                    No hay reglas configuradas.
                </div>
            </div>

            <div
                v-if="reglas.links.length > 3"
                class="flex items-center justify-between text-xs text-steel"
            >
                <span>Mostrando {{ reglas.from }}–{{ reglas.to }} de {{ reglas.total }}</span>
                <div class="flex items-center gap-1">
                    <template v-for="link in reglas.links" :key="link.label">
                        <span
                            v-if="link.url === null"
                            aria-disabled="true"
                            class="rounded px-2 py-0.5 text-clay"
                        >
                            {{ labelDePagina(link.label) }}
                        </span>
                        <a
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
                        </a>
                    </template>
                </div>
            </div>
        </div>

        <div
            v-if="drawerOpen"
            class="fixed inset-0 z-40 flex justify-end bg-ink/20"
            @click.self="cerrarDrawer"
        >
            <aside
                v-dialog="cerrarDrawer"
                class="flex h-full w-full max-w-[560px] flex-col overflow-y-auto border-l border-hairline bg-surface p-5 shadow-diffused"
            >
                <div class="flex items-center justify-between border-b border-hairline pb-3">
                    <h3 class="text-lg font-semibold text-ink">
                        {{ esEdicion ? 'Editar regla' : 'Nueva regla' }}
                    </h3>
                    <button
                        type="button"
                        class="text-sm text-steel hover:text-ink"
                        @click="cerrarDrawer"
                    >
                        Cerrar
                    </button>
                </div>

                <form class="flex flex-col gap-4 pt-4" @submit.prevent="guardar">
                    <div class="flex flex-col gap-1">
                        <label for="nombre" class="text-xs font-medium text-steel">Nombre</label>
                        <input
                            id="nombre"
                            v-model="form.nombre"
                            type="text"
                            required
                            maxlength="120"
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        />
                        <p v-if="form.errors.nombre" class="text-xs text-oxide">
                            {{ form.errors.nombre }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label for="tipo" class="text-xs font-medium text-steel"
                            >Tipo de alerta</label
                        >
                        <select
                            id="tipo"
                            :value="form.tipo"
                            :disabled="esEdicion"
                            required
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink disabled:bg-workshop/40"
                            @change="elegirTipo(($event.target as HTMLSelectElement).value)"
                        >
                            <option value="">Elegí un tipo</option>
                            <option v-for="tipo in tipos" :key="tipo.clave" :value="tipo.clave">
                                {{ tipo.modulo }} · {{ tipo.etiqueta }}
                            </option>
                        </select>
                        <p v-if="tipoSeleccionado" class="text-xs text-steel">
                            {{ tipoSeleccionado.descripcion }}
                        </p>
                        <p v-if="form.errors.tipo" class="text-xs text-oxide">
                            {{ form.errors.tipo }}
                        </p>
                    </div>

                    <div
                        v-if="tipoSeleccionado && tipoSeleccionado.condiciones.length > 0"
                        class="grid grid-cols-1 gap-3 md:grid-cols-2"
                    >
                        <div
                            v-for="condicion in tipoSeleccionado.condiciones"
                            :key="condicion.clave"
                            class="flex flex-col gap-1"
                        >
                            <label
                                :for="`condicion-${condicion.clave}`"
                                class="text-xs font-medium text-steel"
                            >
                                {{ condicion.etiqueta }}
                            </label>
                            <input
                                :id="`condicion-${condicion.clave}`"
                                v-model.number="form.condiciones[condicion.clave]"
                                type="number"
                                :min="condicion.minimo"
                                :max="condicion.maximo"
                                :step="condicion.tipo === 'integer' ? 1 : 0.01"
                                required
                                class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                            />
                            <p
                                v-if="form.errors[`condiciones.${condicion.clave}`]"
                                class="text-xs text-oxide"
                            >
                                {{ form.errors[`condiciones.${condicion.clave}`] }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label for="severidad" class="text-xs font-medium text-steel"
                            >Severidad</label
                        >
                        <select
                            id="severidad"
                            v-model="form.severidad"
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink"
                        >
                            <option
                                v-for="opcion in severidades"
                                :key="opcion.valor"
                                :value="opcion.valor"
                            >
                                {{ opcion.etiqueta }}
                            </option>
                        </select>
                        <p v-if="form.errors.severidad" class="text-xs text-oxide">
                            {{ form.errors.severidad }}
                        </p>
                    </div>

                    <fieldset class="flex flex-col gap-2">
                        <legend class="text-xs font-medium text-steel">
                            Roles que reciben la alerta
                        </legend>
                        <label
                            v-for="rol in roles"
                            :key="rol.id"
                            class="flex items-center gap-2 text-sm text-ink"
                        >
                            <input
                                v-model="form.roles"
                                type="checkbox"
                                :value="rol.id"
                                class="h-4 w-4 rounded border-hairline text-teja"
                            />
                            {{ rol.name }}
                        </label>
                        <p v-if="form.errors.roles" class="text-xs text-oxide">
                            {{ form.errors.roles }}
                        </p>
                    </fieldset>

                    <div class="flex flex-col gap-1">
                        <label for="mensaje" class="text-xs font-medium text-steel">Mensaje</label>
                        <textarea
                            id="mensaje"
                            v-model="form.mensaje"
                            rows="3"
                            maxlength="500"
                            required
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        ></textarea>
                        <p v-if="tipoSeleccionado" class="text-xs text-clay">
                            Variables: {{ variablesDe(tipoSeleccionado) }}
                        </p>
                        <p v-if="form.errors.mensaje" class="text-xs text-oxide">
                            {{ form.errors.mensaje }}
                        </p>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-ink">
                        <input
                            v-model="form.activa"
                            type="checkbox"
                            class="h-4 w-4 rounded border-hairline text-teja"
                        />
                        Activa
                    </label>

                    <div class="flex justify-end gap-2 border-t border-hairline pt-3">
                        <button
                            type="button"
                            class="rounded-control border border-hairline px-4 py-2 text-sm text-ink"
                            @click="cerrarDrawer"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-control bg-teja px-4 py-2 text-sm font-medium text-white disabled:opacity-60"
                        >
                            {{ form.processing ? 'Guardando…' : 'Guardar' }}
                        </button>
                    </div>
                </form>
            </aside>
        </div>
    </AppLayout>
</template>
