const formateador = new Intl.NumberFormat('es-AR', {
    maximumFractionDigits: 2,
});

/**
 * Formats a stock or quantity with Argentine separators, keeping two decimals max.
 */
export function formatearCantidad(valor: number | string | null | undefined): string {
    const numero = Number(valor ?? 0);

    return Number.isFinite(numero) ? formateador.format(numero) : '0';
}
