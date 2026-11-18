import { describe, it, expect } from 'vitest';
import { formatearCantidad } from './numeros';

describe('numeros helpers', () => {
    it('formats integers and decimals with Argentine separators', () => {
        expect(formatearCantidad(1234.5)).toBe('1.234,5');
        expect(formatearCantidad('10')).toBe('10');
    });

    it('falls back to zero for empty or invalid values', () => {
        expect(formatearCantidad(null)).toBe('0');
        expect(formatearCantidad(undefined)).toBe('0');
        expect(formatearCantidad('no-numerico')).toBe('0');
    });
});
