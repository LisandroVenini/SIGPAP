import { reactive } from 'vue';

export interface OpcionesDeConfirmacion {
    titulo: string;
    mensaje: string;
    confirmar?: string;
    cancelar?: string;
    peligro?: boolean;
}

export interface EstadoDeConfirmacion extends OpcionesDeConfirmacion {
    abierto: boolean;
    confirmar: string;
    cancelar: string;
    peligro: boolean;
}

const estado = reactive<EstadoDeConfirmacion>({
    abierto: false,
    titulo: '',
    mensaje: '',
    confirmar: 'Confirmar',
    cancelar: 'Cancelar',
    peligro: false,
});

let resolverActual: ((valor: boolean) => void) | null = null;

/**
 * Opens the shared confirmation dialog and resolves with the user's answer.
 *
 * The dialog is a single global instance mounted by the app layout, so any
 * page can ask for confirmation without owning markup or state.
 */
export function confirmar(opciones: OpcionesDeConfirmacion): Promise<boolean> {
    if (resolverActual !== null) {
        resolverActual(false);
        resolverActual = null;
    }

    estado.titulo = opciones.titulo;
    estado.mensaje = opciones.mensaje;
    estado.confirmar = opciones.confirmar ?? 'Confirmar';
    estado.cancelar = opciones.cancelar ?? 'Cancelar';
    estado.peligro = opciones.peligro ?? false;
    estado.abierto = true;

    return new Promise<boolean>((resolver) => {
        resolverActual = resolver;
    });
}

export function resolverConfirmacion(valor: boolean): void {
    const resolver = resolverActual;
    resolverActual = null;
    estado.abierto = false;
    resolver?.(valor);
}

export function estadoDeConfirmacion(): EstadoDeConfirmacion {
    return estado;
}

/** Test-only helper: closes an open dialog without touching component state. */
export function reiniciarConfirmacion(): void {
    resolverActual?.(false);
    resolverActual = null;
    estado.abierto = false;
}
