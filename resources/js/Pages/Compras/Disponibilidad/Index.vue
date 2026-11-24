<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

interface ArticuloBajo {
    articulo_id: number;
    articulo: string;
    disponible: number;
    minimo: number;
}

interface ProveedorOpcion {
    id: number;
    razon_social: string;
}

interface ProveedorSugerido {
    id: number;
    razon_social: string;
    puntaje: number;
}

interface Recomendacion {
    escenario: string;
    motivos: string[];
    desviacion_precio: number | null;
    dias_de_atraso: number | null;
    recomendacion: string;
    proveedor_sugerido: ProveedorSugerido | null;
    precio_referencia: number | null;
    precio_actual: number;
    fecha_prometida: string;
}

interface Solicitud {
    id: number;
    articulo: string;
    proveedor: string;
    cantidad_solicitada: number;
    fecha: string;
    fecha_requerida: string | null;
    estado: string;
    respondida: boolean;
    recomendacion: Recomendacion | null;
}

const props = defineProps<{
    bajos: ArticuloBajo[];
    solicitudes: Solicitud[];
    proveedores: ProveedorOpcion[];
    proveedorSugerido: ProveedorSugerido | null;
}>();

const form = useForm({
    articulo_id: '',
    cantidad: '',
    proveedor_id: props.proveedorSugerido?.id ?? '',
    fecha_requerida: '',
});

function consultar(): void {
    form.post('/compras/disponibilidad', {
        preserveScroll: true,
        onSuccess: () => form.reset('cantidad', 'fecha_requerida'),
    });
}

function claseEscenario(escenario: string): string {
    if (escenario === 'Variación Fuerte') {
        return 'bg-amber-wash text-amber';
    }

    if (escenario === 'Sin Stock') {
        return 'bg-oxide/10 text-oxide';
    }

    return 'bg-pine-wash text-pine';
}
</script>

<template>
    <Head title="Disponibilidad de proveedores" />

    <AppLayout title="Disponibilidad de proveedores">
        <div class="space-y-6 p-6">
            <header>
                <h1 class="text-2xl font-semibold text-pine">Disponibilidad de proveedores</h1>
                <p class="mt-1 text-sm text-steel">
                    Consultá la disponibilidad de un insumo a un único proveedor (Flujo A). El
                    predictivo evalúa la respuesta y te recomienda una acción.
                </p>
            </header>

            <section class="rounded-lg border border-hairline bg-white p-4">
                <h2 class="text-sm font-semibold tracking-wider text-steel uppercase">
                    Insumos bajo el mínimo
                </h2>

                <p v-if="bajos.length === 0" class="mt-3 text-sm text-steel">
                    No hay insumos bajo el mínimo.
                </p>

                <ul v-else class="mt-3 space-y-1 text-sm text-steel">
                    <li v-for="bajo in bajos" :key="bajo.articulo_id">
                        <strong class="text-ink">{{ bajo.articulo }}</strong> · disponible
                        {{ bajo.disponible }} · mínimo {{ bajo.minimo }}
                    </li>
                </ul>
            </section>

            <section class="rounded-lg border border-hairline bg-white p-4">
                <form class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5" @submit.prevent="consultar">
                    <label class="flex flex-col gap-1 text-sm text-steel">
                        Artículo
                        <select
                            v-model="form.articulo_id"
                            data-testid="articulo"
                            class="rounded-md border border-hairline px-2 py-1.5 text-sm"
                        >
                            <option value="">Elegí un insumo</option>
                            <option
                                v-for="bajo in bajos"
                                :key="bajo.articulo_id"
                                :value="bajo.articulo_id"
                            >
                                {{ bajo.articulo }}
                            </option>
                        </select>
                        <span v-if="form.errors.articulo_id" class="text-xs text-oxide">
                            {{ form.errors.articulo_id }}
                        </span>
                    </label>

                    <label class="flex flex-col gap-1 text-sm text-steel">
                        Cantidad
                        <input
                            v-model="form.cantidad"
                            type="number"
                            min="1"
                            step="1"
                            class="rounded-md border border-hairline px-2 py-1.5 text-sm"
                        />
                        <span v-if="form.errors.cantidad" class="text-xs text-oxide">
                            {{ form.errors.cantidad }}
                        </span>
                    </label>

                    <label class="flex flex-col gap-1 text-sm text-steel">
                        Proveedor
                        <select
                            v-model="form.proveedor_id"
                            data-testid="proveedor"
                            class="rounded-md border border-hairline px-2 py-1.5 text-sm"
                        >
                            <option value="">Elegí un proveedor</option>
                            <option
                                v-for="proveedor in proveedores"
                                :key="proveedor.id"
                                :value="proveedor.id"
                            >
                                {{ proveedor.razon_social }}
                            </option>
                        </select>
                        <span v-if="form.errors.proveedor_id" class="text-xs text-oxide">
                            {{ form.errors.proveedor_id }}
                        </span>
                    </label>

                    <label class="flex flex-col gap-1 text-sm text-steel">
                        Se necesita antes de
                        <input
                            v-model="form.fecha_requerida"
                            type="date"
                            class="rounded-md border border-hairline px-2 py-1.5 text-sm"
                        />
                        <span v-if="form.errors.fecha_requerida" class="text-xs text-oxide">
                            {{ form.errors.fecha_requerida }}
                        </span>
                    </label>

                    <div class="flex items-end">
                        <button
                            type="submit"
                            data-testid="consultar"
                            class="rounded-md bg-teja px-4 py-1.5 text-sm font-medium text-white hover:bg-teja-deep disabled:opacity-60"
                            :disabled="form.processing"
                        >
                            Consultar disponibilidad
                        </button>
                    </div>
                </form>
            </section>

            <section class="rounded-lg border border-hairline bg-white p-4">
                <h2 class="text-sm font-semibold tracking-wider text-steel uppercase">Consultas</h2>

                <p v-if="solicitudes.length === 0" class="mt-3 text-sm text-steel">
                    Todavía no hay consultas de disponibilidad.
                </p>

                <div v-else class="mt-3 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr
                                class="border-b border-hairline text-xs tracking-wider text-steel uppercase"
                            >
                                <th class="px-2 py-2 font-medium">Insumo</th>
                                <th class="px-2 py-2 font-medium">Proveedor</th>
                                <th class="px-2 py-2 font-medium">Cantidad</th>
                                <th class="px-2 py-2 font-medium">Requerida</th>
                                <th class="px-2 py-2 font-medium">Estado</th>
                                <th class="px-2 py-2 font-medium">Recomendación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="solicitud in solicitudes"
                                :key="solicitud.id"
                                data-testid="fila-solicitud"
                                class="border-b border-hairline/60 align-top"
                            >
                                <td class="px-2 py-2 text-ink">{{ solicitud.articulo }}</td>
                                <td class="px-2 py-2 text-steel">{{ solicitud.proveedor }}</td>
                                <td class="num px-2 py-2 text-steel">
                                    {{ solicitud.cantidad_solicitada }}
                                </td>
                                <td class="num px-2 py-2 text-steel">
                                    {{ solicitud.fecha_requerida ?? '—' }}
                                </td>
                                <td class="px-2 py-2 text-steel">{{ solicitud.estado }}</td>
                                <td class="px-2 py-2">
                                    <template v-if="solicitud.recomendacion">
                                        <span
                                            class="rounded px-2 py-0.5 text-xs font-medium"
                                            :class="
                                                claseEscenario(solicitud.recomendacion.escenario)
                                            "
                                        >
                                            {{ solicitud.recomendacion.escenario }}
                                        </span>
                                        <p class="mt-1 text-xs text-steel">
                                            {{ solicitud.recomendacion.recomendacion }}
                                        </p>
                                        <p
                                            v-if="solicitud.recomendacion.proveedor_sugerido"
                                            class="mt-1 text-xs font-medium text-pine"
                                        >
                                            Siguiente:
                                            {{
                                                solicitud.recomendacion.proveedor_sugerido
                                                    .razon_social
                                            }}
                                        </p>
                                    </template>
                                    <span v-else class="text-xs text-steel">Sin respuesta</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
