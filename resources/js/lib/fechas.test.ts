import { describe, expect, it } from 'vitest';
import {
    diasDelMes,
    entreRango,
    esFechaISO,
    formatearFecha,
    hoyISO,
    mesAnterior,
    mesSiguiente,
    nombreMes,
    parsearFecha,
    partesDeFecha,
    primerDiaSemana,
    sumarDias,
} from './fechas';

describe('fechas helpers', () => {
    it('returns today in ISO format', () => {
        // Act
        const hoy = hoyISO();

        // Assert
        expect(hoy).toMatch(/^\d{4}-\d{2}-\d{2}$/);
    });

    it('validates real ISO dates only', () => {
        // Act
        const valida = esFechaISO('2026-11-11');
        const inexistente = esFechaISO('2026-02-30');
        const otroFormato = esFechaISO('11/11/2026');
        const sinRelleno = esFechaISO('2026-1-1');

        // Assert
        expect(valida).toBe(true);
        expect(inexistente).toBe(false);
        expect(otroFormato).toBe(false);
        expect(sinRelleno).toBe(false);
    });

    it('formats ISO dates as dd/mm/aaaa', () => {
        // Act
        const fecha = formatearFecha('2026-11-09');
        const vacia = formatearFecha('');
        const nula = formatearFecha(null);

        // Assert
        expect(fecha).toBe('09/11/2026');
        expect(vacia).toBe('');
        expect(nula).toBe('');
    });

    it('parses ISO and dd/mm/aaaa inputs', () => {
        // Act
        const iso = parsearFecha('2026-11-09');
        const conBarras = parsearFecha('09/11/2026');
        const sinRelleno = parsearFecha('9/11/2026');
        const conGuiones = parsearFecha('09-11-2026');
        const inexistente = parsearFecha('31/02/2026');
        const basura = parsearFecha('cualquier cosa');

        // Assert
        expect(iso).toBe('2026-11-09');
        expect(conBarras).toBe('2026-11-09');
        expect(sinRelleno).toBe('2026-11-09');
        expect(conGuiones).toBe('2026-11-09');
        expect(inexistente).toBeNull();
        expect(basura).toBeNull();
    });

    it('checks a date against an inclusive range', () => {
        // Act
        const enLosBordes = entreRango('2026-11-11', '2026-11-11', '2026-11-20');
        const antesDelMinimo = entreRango('2026-11-10', '2026-11-11', null);
        const despuesDelMaximo = entreRango('2026-11-21', null, '2026-11-20');
        const sinLimites = entreRango('2026-11-11', null, null);

        // Assert
        expect(enLosBordes).toBe(true);
        expect(antesDelMinimo).toBe(false);
        expect(despuesDelMaximo).toBe(false);
        expect(sinLimites).toBe(true);
    });

    it('lists the days of a month including leap years', () => {
        // Act
        const febrero = diasDelMes(2026, 2);
        const febreroBisiesto = diasDelMes(2028, 2);
        const noviembre = diasDelMes(2026, 11);

        // Assert
        expect(febrero).toHaveLength(28);
        expect(febreroBisiesto).toHaveLength(29);
        expect(noviembre).toHaveLength(30);
        expect(noviembre[0]).toBe('2026-11-01');
        expect(noviembre.at(-1)).toBe('2026-11-30');
    });

    it('finds the monday-first weekday index of the first day', () => {
        // Act
        const noviembre = primerDiaSemana(2026, 11);
        const febrero = primerDiaSemana(2026, 2);
        const junio = primerDiaSemana(2026, 6);

        // Assert: November and February start on Sunday, June starts on Monday.
        expect(noviembre).toBe(6);
        expect(febrero).toBe(6);
        expect(junio).toBe(0);
    });

    it('names months and wraps around the year', () => {
        // Act
        const enero = nombreMes(1);
        const noviembre = nombreMes(11);
        const anterior = mesAnterior(2026, 1);
        const siguiente = mesSiguiente(2026, 12);

        // Assert
        expect(enero).toBe('enero');
        expect(noviembre).toBe('noviembre');
        expect(anterior).toEqual({ anio: 2025, mes: 12 });
        expect(siguiente).toEqual({ anio: 2027, mes: 1 });
    });

    it('splits a date and adds days across month and year boundaries', () => {
        // Act
        const partes = partesDeFecha('2026-11-09');
        const mesSiguienteSumado = sumarDias('2026-11-29', 3);
        const anioSiguienteSumado = sumarDias('2026-12-31', 1);
        const diasNegativos = sumarDias('2026-11-11', -11);

        // Assert
        expect(partes).toEqual({ anio: 2026, mes: 11 });
        expect(mesSiguienteSumado).toBe('2026-12-02');
        expect(anioSiguienteSumado).toBe('2027-01-01');
        expect(diasNegativos).toBe('2026-10-31');
    });
});
