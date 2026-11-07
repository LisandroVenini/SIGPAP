<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

interface ArticuloResumen {
    id: number;
    codigo_sku: string;
    descripcion: string;
    unidad: string | null;
}

interface LineaOrden {
    articulo_id: number;
    articulo: ArticuloResumen;
    cantidad: number;
    precio_pactado: number;
}

interface OrdenRecepcionable {
    id: number;
    numero_oc: string;
    proveedor: string;
    fecha_emision: string;
    fecha_confirmacion: string | null;
    lineas: LineaOrden[];
}

interface LineaRecibida {
    articulo_id: number;
    cantidad: number;
}

type SharedProps = {
    flash: { success: string | null };
};

const props = defineProps<{
    ordenesRecepcionables: OrdenRecepcionable[];
    filtros: Record<string, string | null>;
}>();

const page = usePage<SharedProps>();

const form = useForm<{
    numero_oc: string;
    numero_remito: string;
    entrega_parcial: boolean;
    confirmar_excedente: boolean;
    lineas: LineaRecibida[];
}>({
    numero_oc: '',
    numero_remito: '',
    entrega_parcial: false,
    confirmar_excedente: false,
    lineas: [],
});

const ordenSeleccionada = ref<OrdenRecepcionable | null>(null);
const lineasRecibidas = ref<LineaRecibida[]>([]);

function seleccionarOrden(event: Event): void {
    const numero = (event.target as HTMLSelectElement).value;
    form.numero_oc = numero;

    const orden = props.ordenesRecepcionables.find((item) => item.numero_oc === numero) ?? null;
    ordenSeleccionada.value = orden;
    lineasRecibidas.value = orden
        ? orden.lineas.map((linea) => ({
              articulo_id: linea.articulo_id,
              cantidad: linea.cantidad,
          }))
        : [];
}

function enviar(): void {
    form.lineas = lineasRecibidas.value.map((linea) => ({
        articulo_id: linea.articulo_id,
        cantidad: Number(linea.cantidad),
    }));

    form.post('/inventario/ingresos', { preserveScroll: true });
}
</script>

<template>
    <AppLayout title="Ingreso de mercancía">
        <Head title="Ingreso de mercancía" />

        <div class="flex flex-col gap-4">
            <div class="flex flex-col gap-1">
                <h2 class="text-2xl font-semibold tracking-tight text-ink">Ingreso de mercancía</h2>
                <p class="text-sm text-steel">
                    Registrá la recepción física de una orden de compra pendiente y actualizá el
                    stock de los artículos.
                </p>
            </div>

            <div
                v-if="page.props.flash.success"
                class="rounded-control border border-pine/20 bg-pine-wash px-4 py-2.5 text-sm text-pine"
            >
                {{ page.props.flash.success }}
            </div>

            <div
                v-if="!ordenesRecepcionables.length"
                class="rounded-panel border border-hairline bg-surface px-4 py-10 text-center text-sm text-steel shadow-diffused"
                data-testid="ingresos-empty"
            >
                No hay órdenes de compra pendientes de recepción.
            </div>

            <form
                v-else
                class="flex flex-col gap-4 rounded-panel border border-hairline bg-surface p-4 shadow-diffused"
                data-testid="ingreso-form"
                @submit.prevent="enviar"
            >
                <div class="grid gap-3 md:grid-cols-2">
                    <div class="flex flex-col gap-1">
                        <label for="orden-select" class="text-xs font-medium text-steel"
                            >Orden de compra</label
                        >
                        <select
                            id="orden-select"
                            v-model="form.numero_oc"
                            data-testid="orden-select"
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                            @change="seleccionarOrden"
                        >
                            <option value="">Seleccioná una orden</option>
                            <option
                                v-for="orden in ordenesRecepcionables"
                                :key="orden.id"
                                :value="orden.numero_oc"
                            >
                                {{ orden.numero_oc }} — {{ orden.proveedor }}
                            </option>
                        </select>
                        <span v-if="form.errors.numero_oc" class="text-xs text-oxide">
                            {{ form.errors.numero_oc }}
                        </span>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label for="numero-remito" class="text-xs font-medium text-steel"
                            >Número de remito</label
                        >
                        <input
                            id="numero-remito"
                            v-model="form.numero_remito"
                            data-testid="numero-remito"
                            type="text"
                            maxlength="255"
                            placeholder="REM-0001"
                            class="rounded-control border border-hairline bg-surface px-3 py-2 text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                        />
                        <span v-if="form.errors.numero_remito" class="text-xs text-oxide">
                            {{ form.errors.numero_remito }}
                        </span>
                    </div>
                </div>

                <div
                    v-if="ordenSeleccionada"
                    class="overflow-hidden rounded-control border border-hairline"
                >
                    <div
                        class="flex flex-wrap gap-4 border-b border-hairline bg-workshop px-4 py-2.5 text-xs text-steel"
                    >
                        <span>
                            Proveedor:
                            <strong class="font-medium text-ink">{{
                                ordenSeleccionada.proveedor
                            }}</strong>
                        </span>
                        <span>
                            Emisión:
                            <strong class="font-medium text-ink">{{
                                ordenSeleccionada.fecha_emision
                            }}</strong>
                        </span>
                        <span v-if="ordenSeleccionada.fecha_confirmacion">
                            Confirmación:
                            <strong class="font-medium text-ink">{{
                                ordenSeleccionada.fecha_confirmacion
                            }}</strong>
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="border-b border-hairline">
                                <tr>
                                    <th
                                        class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                    >
                                        Artículo
                                    </th>
                                    <th
                                        class="px-4 py-2.5 text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                    >
                                        SKU
                                    </th>
                                    <th
                                        class="px-4 py-2.5 text-right text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                    >
                                        Cantidad pedida
                                    </th>
                                    <th
                                        class="px-4 py-2.5 text-right text-[0.6875rem] font-semibold tracking-wider text-steel uppercase"
                                    >
                                        Cantidad a recibir
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(linea, indice) in ordenSeleccionada.lineas"
                                    :key="linea.articulo_id"
                                    class="border-b border-hairline last:border-b-0"
                                >
                                    <td class="px-4 py-2.5 text-sm text-ink">
                                        {{ linea.articulo.descripcion }}
                                    </td>
                                    <td class="px-4 py-2.5 text-sm text-steel">
                                        {{ linea.articulo.codigo_sku }}
                                    </td>
                                    <td class="px-4 py-2.5 text-right text-sm text-ink">
                                        {{ linea.cantidad }}
                                    </td>
                                    <td class="px-4 py-2.5 text-right">
                                        <input
                                            v-model.number="lineasRecibidas[indice].cantidad"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="w-28 rounded-control border border-hairline bg-surface px-3 py-1.5 text-right text-sm text-ink focus:ring-2 focus:ring-teja/40 focus:outline-none"
                                        />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-6">
                    <label class="flex items-center gap-2 text-sm text-ink">
                        <input
                            v-model="form.entrega_parcial"
                            data-testid="entrega-parcial"
                            type="checkbox"
                            class="h-4 w-4 rounded border-hairline text-teja focus:ring-teja/40"
                        />
                        Entrega parcial
                    </label>

                    <label class="flex items-center gap-2 text-sm text-ink">
                        <input
                            v-model="form.confirmar_excedente"
                            data-testid="confirmar-excedente"
                            type="checkbox"
                            class="h-4 w-4 rounded border-hairline text-teja focus:ring-teja/40"
                        />
                        Confirmar excedente
                    </label>
                </div>

                <div v-if="form.errors.lineas" class="text-xs text-oxide">
                    {{ form.errors.lineas }}
                </div>

                <div class="flex justify-end">
                    <button
                        type="submit"
                        :disabled="form.processing || !ordenSeleccionada"
                        class="rounded-control bg-teja px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-teja-deep active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        Registrar ingreso
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
