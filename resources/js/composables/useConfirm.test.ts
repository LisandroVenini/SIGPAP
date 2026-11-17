import { beforeEach, describe, expect, it } from 'vitest';
import {
    confirmar,
    estadoDeConfirmacion,
    reiniciarConfirmacion,
    resolverConfirmacion,
} from './useConfirm';

const estado = estadoDeConfirmacion();

describe('useConfirm', () => {
    beforeEach(() => {
        reiniciarConfirmacion();
    });

    it('opens the dialog with the given options and the default labels', async () => {
        // Act
        const respuesta = confirmar({ titulo: 'Anular orden', mensaje: '¿Anular la OC?' });

        // Assert
        expect(estado.abierto).toBe(true);
        expect(estado.titulo).toBe('Anular orden');
        expect(estado.mensaje).toBe('¿Anular la OC?');
        expect(estado.confirmar).toBe('Confirmar');
        expect(estado.cancelar).toBe('Cancelar');
        expect(estado.peligro).toBe(false);

        // Act
        resolverConfirmacion(true);

        // Assert
        await expect(respuesta).resolves.toBe(true);
        expect(estado.abierto).toBe(false);
    });

    it('resolves false when the user cancels', async () => {
        // Arrange
        const respuesta = confirmar({ titulo: 'Eliminar', mensaje: '¿Seguro?', peligro: true });

        // Act
        resolverConfirmacion(false);

        // Assert
        await expect(respuesta).resolves.toBe(false);
    });

    it('resolves a pending confirmation as false when another one opens', async () => {
        // Arrange
        const primera = confirmar({ titulo: 'Uno', mensaje: 'A' });

        // Act
        const segunda = confirmar({ titulo: 'Dos', mensaje: 'B' });

        // Assert
        await expect(primera).resolves.toBe(false);
        expect(estado.titulo).toBe('Dos');

        resolverConfirmacion(true);
        await expect(segunda).resolves.toBe(true);
    });
});
