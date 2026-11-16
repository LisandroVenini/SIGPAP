export const MS_DIA = 86_400_000;

export const MESES = [
    'enero',
    'febrero',
    'marzo',
    'abril',
    'mayo',
    'junio',
    'julio',
    'agosto',
    'septiembre',
    'octubre',
    'noviembre',
    'diciembre',
];

/** Monday-first weekday labels. */
export const DIAS_SEMANA = ['LU', 'MA', 'MI', 'JU', 'VI', 'SA', 'DO'];

function pad(valor: number): string {
    return String(valor).padStart(2, '0');
}

/** Parses a `YYYY-MM-DD` string as UTC midnight to keep day math stable. */
export function aUTC(fecha: string): number {
    const [anio, mes, dia] = fecha.split('-').map(Number);

    return Date.UTC(anio, mes - 1, dia);
}

export function aISO(ms: number): string {
    return new Date(ms).toISOString().slice(0, 10);
}

export function sumarDias(fecha: string, dias: number): string {
    return aISO(aUTC(fecha) + dias * MS_DIA);
}

/** Today in the user's local time zone, as `YYYY-MM-DD`. */
export function hoyISO(): string {
    const ahora = new Date();

    return `${ahora.getFullYear()}-${pad(ahora.getMonth() + 1)}-${pad(ahora.getDate())}`;
}

export function esFechaISO(texto: string): boolean {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(texto)) {
        return false;
    }

    return aISO(aUTC(texto)) === texto;
}

/** Formats an ISO date as `dd/mm/aaaa`; returns an empty string for invalid input. */
export function formatearFecha(fecha: string | null | undefined): string {
    if (!fecha || !esFechaISO(fecha)) {
        return '';
    }

    return `${fecha.slice(8, 10)}/${fecha.slice(5, 7)}/${fecha.slice(0, 4)}`;
}

/** Accepts `YYYY-MM-DD` or `dd/mm/aaaa` and returns an ISO date, or null. */
export function parsearFecha(texto: string): string | null {
    const limpio = texto.trim();

    if (esFechaISO(limpio)) {
        return limpio;
    }

    const partes = limpio.match(/^(\d{1,2})[/-](\d{1,2})[/-](\d{4})$/);

    if (partes === null) {
        return null;
    }

    const [, dia, mes, anio] = partes;
    const iso = `${anio}-${pad(Number(mes))}-${pad(Number(dia))}`;

    return esFechaISO(iso) ? iso : null;
}

export function entreRango(fecha: string, min?: string | null, max?: string | null): boolean {
    if (min && fecha < min) {
        return false;
    }

    if (max && fecha > max) {
        return false;
    }

    return true;
}

/** ISO dates of every day of the given month. */
export function diasDelMes(anio: number, mes: number): string[] {
    const total = new Date(Date.UTC(anio, mes, 0)).getUTCDate();

    return Array.from({ length: total }, (_, indice) => `${anio}-${pad(mes)}-${pad(indice + 1)}`);
}

/** Monday-first index (0..6) of the first day of the month. */
export function primerDiaSemana(anio: number, mes: number): number {
    return (new Date(Date.UTC(anio, mes - 1, 1)).getUTCDay() + 6) % 7;
}

export function nombreMes(mes: number): string {
    return MESES[mes - 1] ?? '';
}

export function mesAnterior(anio: number, mes: number): { anio: number; mes: number } {
    return mes === 1 ? { anio: anio - 1, mes: 12 } : { anio, mes: mes - 1 };
}

export function mesSiguiente(anio: number, mes: number): { anio: number; mes: number } {
    return mes === 12 ? { anio: anio + 1, mes: 1 } : { anio, mes: mes + 1 };
}

export function partesDeFecha(fecha: string): { anio: number; mes: number } {
    return { anio: Number(fecha.slice(0, 4)), mes: Number(fecha.slice(5, 7)) };
}
