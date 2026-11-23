import { describe, it, expect } from 'vitest';
import {
    columnasDe,
    diasEntre,
    lunesDe,
    numeroSemana,
    posicionarSegmento,
    rangoVisible,
} from './gantt';

describe('cronograma gantt helpers', () => {
    it('counts days between two dates', () => {
        // Act
        const adelante = diasEntre('2026-11-01', '2026-11-15');
        const atras = diasEntre('2026-11-15', '2026-11-01');

        // Assert
        expect(adelante).toBe(14);
        expect(atras).toBe(-14);
    });

    it('finds the monday of the week, including when the date is a sunday', () => {
        // Act
        const miercoles = lunesDe('2026-11-11');
        const lunes = lunesDe('2026-11-09');
        const domingo = lunesDe('2026-11-15');

        // Assert
        expect(miercoles).toBe('2026-11-09');
        expect(lunes).toBe('2026-11-09');
        expect(domingo).toBe('2026-11-09');
    });

    it('builds the day columns with today and weekend flags', () => {
        // Arrange / Act
        const columnas = columnasDe('2026-11-09', 7, '2026-11-11');

        // Assert
        expect(columnas).toHaveLength(7);
        expect(columnas[0]).toMatchObject({ fecha: '2026-11-09', etiqueta: 'LUN', numero: 9 });
        expect(columnas[2]).toMatchObject({ fecha: '2026-11-11', esHoy: true });
        expect(columnas[5].esFinDeSemana).toBe(true);
        expect(columnas[6].esFinDeSemana).toBe(true);
        expect(columnas[1].esFinDeSemana).toBe(false);
    });

    it('places a segment fully inside the window', () => {
        // Arrange
        const segmento = { etapa: 'Corte', inicio: '2026-11-10', fin: '2026-11-12', dias: 3 };

        // Act
        const barra = posicionarSegmento(segmento, '2026-11-09', 7);

        // Assert
        expect(barra).toEqual({
            columna: 2,
            span: 3,
            recortadaInicio: false,
            recortadaFin: false,
        });
    });

    it('clips a segment that starts before the window', () => {
        // Arrange
        const segmento = { etapa: 'Corte', inicio: '2026-11-01', fin: '2026-11-10', dias: 10 };

        // Act
        const barra = posicionarSegmento(segmento, '2026-11-09', 7);

        // Assert
        expect(barra).toEqual({
            columna: 1,
            span: 2,
            recortadaInicio: true,
            recortadaFin: false,
        });
    });

    it('clips a segment that ends after the window', () => {
        // Arrange
        const segmento = { etapa: 'Lijado', inicio: '2026-11-14', fin: '2026-11-20', dias: 7 };

        // Act
        const barra = posicionarSegmento(segmento, '2026-11-09', 7);

        // Assert
        expect(barra).toEqual({
            columna: 6,
            span: 2,
            recortadaInicio: false,
            recortadaFin: true,
        });
    });

    it('returns null when the segment is outside the window', () => {
        // Arrange
        const segmento = { etapa: 'Armado', inicio: '2026-12-01', fin: '2026-12-03', dias: 3 };

        // Act
        const barra = posicionarSegmento(segmento, '2026-11-09', 7);

        // Assert
        expect(barra).toBeNull();
    });

    it('keeps a single day segment as one column', () => {
        // Arrange
        const segmento = { etapa: 'Armado', inicio: '2026-11-11', fin: '2026-11-11', dias: 1 };

        // Act
        const barra = posicionarSegmento(segmento, '2026-11-09', 7);

        // Assert
        expect(barra).toMatchObject({ columna: 3, span: 1 });
    });

    it('reports iso week numbers and the visible range label', () => {
        // Act
        const primeraSemana = numeroSemana('2026-01-01');
        const lunes = numeroSemana('2026-11-09');
        const domingo = numeroSemana('2026-11-15');
        const rango = rangoVisible('2026-11-09', 7);

        // Assert
        expect(primeraSemana).toBe(1);
        expect(lunes).toBe(domingo);
        expect(rango).toBe('09/11 → 15/11');
    });
});
