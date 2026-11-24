import { MS_DIA, aUTC, sumarDias } from '@/lib/fechas';

export interface BloqueDeTrabajo {
    inicio: string;
    fin: string;
}

export interface Segmento {
    etapa: string;
    inicio: string;
    fin: string;
    inicio_en?: string;
    fin_en?: string;
    dias: number;
    minutos?: number;
    segmentos?: BloqueDeTrabajo[];
    plan?: { inicio: string; fin: string } | null;
    producida: number;
}

export interface BarraDeDia {
    columna: number;
    fecha: string;
    horario: string;
}

export interface RegistroDePrioridad {
    prioridad_anterior: number | null;
    prioridad_nueva: number;
    motivo: string;
    detalle: string | null;
    fecha: string | null;
    usuario: string | null;
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
    historial: RegistroDePrioridad[];
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

/** A dated marker drawn over the timeline (e.g. a supply order-by date). */
export interface MarcaDeAbastecimiento {
    fecha: string;
    etiqueta: string;
    tono?: 'riesgo' | 'aviso';
}

/** Places a dated marker inside the visible window, or null when outside. */
export function posicionarMarca(
    marca: Pick<MarcaDeAbastecimiento, 'fecha'>,
    desde: string,
    dias: number,
): { columna: number; left: number } | null {
    const indice = diasEntre(desde, marca.fecha);

    if (indice < 0 || indice >= dias) {
        return null;
    }

    return { columna: indice + 1, left: (indice / dias) * 100 };
}

/** Number of whole hours between two datetimes. */
export function horasEntre(desde: string, hasta: string): number {
    return (
        (new Date(hasta.replace(' ', 'T')).getTime() -
            new Date(desde.replace(' ', 'T')).getTime()) /
        3_600_000
    );
}

/**
 * Split a stage into one bar per working day, using its real working blocks.
 * A stage that stops at night or over the weekend no longer paints a
 * continuous bar across the calendar. Falls back to the plain date range.
 */
export function barrasPorDia(
    segmento: Pick<Segmento, 'inicio' | 'fin' | 'segmentos'>,
    desde: string,
    dias: number,
): BarraDeDia[] {
    const bloques = segmento.segmentos ?? [];

    if (bloques.length === 0) {
        const barra = posicionarSegmento(segmento, desde, dias);

        return barra === null
            ? []
            : [{ columna: barra.columna, fecha: segmento.inicio, horario: '' }];
    }

    const porFecha = new Map<string, string[]>();

    for (const bloque of bloques) {
        const fecha = bloque.inicio.slice(0, 10);
        const horario = `${bloque.inicio.slice(11, 16)}-${bloque.fin.slice(11, 16)}`;
        const horarios = porFecha.get(fecha) ?? [];

        horarios.push(horario);
        porFecha.set(fecha, horarios);
    }

    return [...porFecha.entries()]
        .map(([fecha, horarios]) => {
            const indice = diasEntre(desde, fecha);

            if (indice < 0 || indice >= dias) {
                return null;
            }

            return { columna: indice + 1, fecha, horario: horarios.join(' · ') };
        })
        .filter((barra): barra is BarraDeDia => barra !== null)
        .sort((a, b) => a.columna - b.columna);
}

/** Tailwind classes for a stage bar, shared by the day and hour views. */
export function claseDeBarra(estado: string, conflicto: boolean): string {
    if (conflicto) {
        return 'border border-dashed border-oxide/70 bg-oxide/10 text-oxide';
    }

    if (estado === 'En Curso') {
        return 'bg-teja text-white';
    }

    if (estado === 'Pospuesta') {
        return 'border border-dashed border-oxide/50 bg-oxide/10 text-oxide';
    }

    if (estado === 'Asignada') {
        return 'border border-dashed border-steel/60 bg-pine-wash text-pine';
    }

    return 'border border-dashed border-steel/50 bg-workshop text-steel';
}

export interface BloqueDelDia {
    inicio: string;
    fin: string;
    izquierda: number;
    ancho: number;
}

const MS_DIA_COMPLETO = 86_400_000;

function aHoraLocal(valor: string): number {
    return new Date(valor.replace(' ', 'T')).getTime();
}

/**
 * Working blocks of a stage clipped to one calendar day, positioned as
 * percentages of a 24-hour track.
 */
export function bloquesDelDia(
    segmento: Pick<Segmento, 'segmentos' | 'inicio_en' | 'fin_en'>,
    fecha: string,
): BloqueDelDia[] {
    const inicioDia = aHoraLocal(`${fecha} 00:00:00`);
    const finDia = inicioDia + MS_DIA_COMPLETO;

    const bloques =
        segmento.segmentos ??
        (segmento.inicio_en && segmento.fin_en
            ? [{ inicio: segmento.inicio_en, fin: segmento.fin_en }]
            : []);

    return bloques
        .map((bloque) => {
            const inicio = Math.max(aHoraLocal(bloque.inicio), inicioDia);
            const fin = Math.min(aHoraLocal(bloque.fin), finDia);

            if (fin <= inicio) {
                return null;
            }

            return {
                inicio: bloque.inicio,
                fin: bloque.fin,
                izquierda: ((inicio - inicioDia) / MS_DIA_COMPLETO) * 100,
                ancho: ((fin - inicio) / MS_DIA_COMPLETO) * 100,
            };
        })
        .filter((bloque): bloque is BloqueDelDia => bloque !== null);
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
