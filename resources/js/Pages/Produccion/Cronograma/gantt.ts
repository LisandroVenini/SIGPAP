import { MS_DIA, aUTC, sumarDias } from '@/lib/fechas';

export interface Segmento {
    etapa: string;
    inicio: string;
    fin: string;
    dias: number;
    producida: number;
}

export interface FilaCronograma {
    orden_id: number;
    producto: string;
    estado: string;
    prioridad: number;
    fecha_entrega: string | null;
    reubicable: boolean;
    conflicto_entrega: boolean;
    cantidad_requerida: number;
    etapa_actual: string | null;
    avance: number;
    dias_totales: number;
    etapas: Segmento[];
}

export const HORIZONTES = [7, 14, 30] as const;

export type Horizonte = (typeof HORIZONTES)[number];

export interface ColumnaDia {
    fecha: string;
    etiqueta: string;
    numero: number;
    esHoy: boolean;
    esFinDeSemana: boolean;
}

export interface Barra {
    columna: number;
    span: number;
    recortadaInicio: boolean;
    recortadaFin: boolean;
}

const DIAS_CORTOS_DOMINGO = ['DOM', 'LUN', 'MAR', 'MIÉ', 'JUE', 'VIE', 'SÁB'];

export function diasEntre(desde: string, hasta: string): number {
    return Math.round((aUTC(hasta) - aUTC(desde)) / MS_DIA);
}

/** Monday of the ISO week that contains the given date. */
export function lunesDe(fecha: string): string {
    const dia = new Date(aUTC(fecha)).getUTCDay();

    return sumarDias(fecha, -((dia + 6) % 7));
}

export function columnasDe(desde: string, dias: number, hoy: string): ColumnaDia[] {
    return Array.from({ length: dias }, (_, indice) => {
        const fecha = sumarDias(desde, indice);
        const dia = new Date(aUTC(fecha)).getUTCDay();

        return {
            fecha,
            etiqueta: DIAS_CORTOS_DOMINGO[dia],
            numero: Number(fecha.slice(8, 10)),
            esHoy: fecha === hoy,
            esFinDeSemana: dia === 0 || dia === 6,
        };
    });
}

/**
 * Places a stage segment inside the visible window, clipping it when it
 * starts before or ends after the window. Returns null when it falls outside.
 */
export function posicionarSegmento(
    segmento: Pick<Segmento, 'inicio' | 'fin'>,
    desde: string,
    dias: number,
): Barra | null {
    const inicioVentana = aUTC(desde);
    const finVentana = inicioVentana + (dias - 1) * MS_DIA;
    const inicio = aUTC(segmento.inicio);
    const fin = aUTC(segmento.fin);

    if (fin < inicioVentana || inicio > finVentana) {
        return null;
    }

    const inicioVisible = Math.max(inicio, inicioVentana);
    const finVisible = Math.min(fin, finVentana);

    return {
        columna: Math.round((inicioVisible - inicioVentana) / MS_DIA) + 1,
        span: Math.round((finVisible - inicioVisible) / MS_DIA) + 1,
        recortadaInicio: inicio < inicioVentana,
        recortadaFin: fin > finVentana,
    };
}

function formatearCorto(fecha: string): string {
    return `${fecha.slice(8, 10)}/${fecha.slice(5, 7)}`;
}

export function rangoVisible(desde: string, dias: number): string {
    return `${formatearCorto(desde)} → ${formatearCorto(sumarDias(desde, dias - 1))}`;
}

/** ISO-8601 week number. */
export function numeroSemana(fecha: string): number {
    const objetivo = new Date(aUTC(fecha));
    const dia = (objetivo.getUTCDay() + 6) % 7;

    objetivo.setUTCDate(objetivo.getUTCDate() - dia + 3);

    const primerJueves = new Date(Date.UTC(objetivo.getUTCFullYear(), 0, 4));
    const diaPrimerJueves = (primerJueves.getUTCDay() + 6) % 7;

    primerJueves.setUTCDate(primerJueves.getUTCDate() - diaPrimerJueves + 3);

    return 1 + Math.round((objetivo.getTime() - primerJueves.getTime()) / (7 * MS_DIA));
}
