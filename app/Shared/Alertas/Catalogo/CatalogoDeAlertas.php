<?php

namespace App\Shared\Alertas\Catalogo;

use App\Modules\Usuarios\Enums\Rol;
use App\Shared\Alertas\Enums\ModuloDeAlerta;
use App\Shared\Alertas\Enums\OrigenAlerta;
use App\Shared\Alertas\Enums\SeveridadAlerta;

final class CatalogoDeAlertas
{
    /**
     * @return array<string, TipoDeAlerta>
     */
    public static function todos(): array
    {
        return collect(self::tipos())
            ->keyBy(fn (TipoDeAlerta $tipo): string => $tipo->clave)
            ->all();
    }

    public static function obtener(string $clave): ?TipoDeAlerta
    {
        return self::todos()[$clave] ?? null;
    }

    /**
     * @return array<int, string>
     */
    public static function claves(): array
    {
        return array_keys(self::todos());
    }

    /**
     * Lightweight catalog for UI selects.
     *
     * @return array<int, array{clave: string, etiqueta: string, detalle: string|null}>
     */
    public static function opciones(): array
    {
        return array_values(array_map(
            fn (TipoDeAlerta $tipo): array => [
                'clave' => $tipo->clave,
                'etiqueta' => $tipo->etiqueta,
                'detalle' => $tipo->detalle,
            ],
            self::todos(),
        ));
    }

    /**
     * @return array<int, TipoDeAlerta>
     */
    public static function porModulo(ModuloDeAlerta $modulo): array
    {
        return array_values(array_filter(
            self::todos(),
            fn (TipoDeAlerta $tipo): bool => $tipo->modulo === $modulo,
        ));
    }

    /**
     * @return array<int, TipoDeAlerta>
     */
    private static function tipos(): array
    {
        return [
            new TipoDeAlerta(
                'stock_bajo_minimo',
                'Stock bajo el mínimo',
                'El stock disponible de un artículo cayó por debajo de su mínimo.',
                ModuloDeAlerta::Inventario,
                OrigenAlerta::Condicion,
                SeveridadAlerta::Atencion,
                [Rol::EncargadoStock, Rol::Gerente],
                [],
                ['articulo', 'disponible', 'minimo'],
            ),
            new TipoDeAlerta(
                'articulo_por_vencer',
                'Artículo por vencer',
                'Un artículo con stock vence dentro del plazo configurado.',
                ModuloDeAlerta::Inventario,
                OrigenAlerta::Condicion,
                SeveridadAlerta::Atencion,
                [Rol::EncargadoStock, Rol::SupervisorProduccion],
                [self::entero('dias', 'Días antes del vencimiento', 1, 365, 30)],
                ['articulo', 'fecha_vencimiento', 'dias'],
            ),
            new TipoDeAlerta(
                'articulo_vencido',
                'Artículo vencido',
                'Un artículo con stock superó su fecha de vencimiento.',
                ModuloDeAlerta::Inventario,
                OrigenAlerta::Condicion,
                SeveridadAlerta::Critica,
                [Rol::EncargadoStock, Rol::Gerente],
                [],
                ['articulo', 'fecha_vencimiento', 'stock'],
            ),
            new TipoDeAlerta(
                'ajuste_significativo',
                'Ajuste de inventario significativo',
                'Un ajuste de stock supera el porcentaje configurado.',
                ModuloDeAlerta::Inventario,
                OrigenAlerta::Evento,
                SeveridadAlerta::Atencion,
                [Rol::EncargadoStock, Rol::Gerente],
                [self::decimal('porcentaje', 'Diferencia mínima (%)', 1, 100, 10)],
                ['articulo', 'diferencia', 'stock_anterior', 'stock_nuevo'],
            ),
            new TipoDeAlerta(
                'solicitud_por_vencer',
                'Solicitud de cotización por vencer',
                'Una solicitud enviada vence dentro del plazo configurado.',
                ModuloDeAlerta::Compras,
                OrigenAlerta::Condicion,
                SeveridadAlerta::Atencion,
                [Rol::EncargadoCompras],
                [self::entero('dias', 'Días antes del vencimiento', 1, 30, 3)],
                ['numero', 'fecha_vencimiento', 'dias', 'proveedores'],
            ),
            new TipoDeAlerta(
                'cotizaciones_insuficientes',
                'Cotizaciones insuficientes',
                'Una solicitud está por vencer sin el mínimo de cotizaciones recibidas.',
                ModuloDeAlerta::Compras,
                OrigenAlerta::Condicion,
                SeveridadAlerta::Atencion,
                [Rol::EncargadoCompras],
                [
                    self::entero('dias', 'Días antes del vencimiento', 0, 30, 2),
                    self::entero('minimo', 'Cotizaciones mínimas', 1, 20, 3, 'lt', 'recibidas'),
                ],
                ['numero', 'fecha_vencimiento', 'recibidas', 'minimo', 'dias'],
            ),
            new TipoDeAlerta(
                'oc_sin_confirmar',
                'Orden de compra sin confirmar',
                'Una orden emitida lleva más días de los configurados sin respuesta del proveedor.',
                ModuloDeAlerta::Compras,
                OrigenAlerta::Condicion,
                SeveridadAlerta::Atencion,
                [Rol::EncargadoCompras],
                [self::entero('dias', 'Días sin confirmación', 1, 60, 7)],
                ['numero_oc', 'proveedor', 'dias'],
            ),
            new TipoDeAlerta(
                'recepcion_con_faltante',
                'Recepción con faltante',
                'Se registró una recepción con artículos faltantes.',
                ModuloDeAlerta::Compras,
                OrigenAlerta::Evento,
                SeveridadAlerta::Atencion,
                [Rol::EncargadoCompras, Rol::EncargadoStock],
                [],
                ['numero_oc', 'proveedor', 'articulo', 'recibida', 'pedida'],
            ),
            new TipoDeAlerta(
                'proveedor_critico',
                'Proveedor con desempeño crítico',
                'El puntaje del proveedor en la ventana de ranking está por debajo del mínimo.',
                ModuloDeAlerta::Compras,
                OrigenAlerta::Condicion,
                SeveridadAlerta::Atencion,
                [Rol::EncargadoCompras, Rol::Gerente],
                [self::decimal('puntaje_minimo', 'Puntaje mínimo', 0, 100, 50, 'lte', 'puntaje')],
                ['proveedor', 'puntaje', 'ventana'],
            ),
            new TipoDeAlerta(
                'mail_fallido',
                'Envío de correo fallido',
                'No se pudo enviar una invitación de cotización o una orden de compra.',
                ModuloDeAlerta::Compras,
                OrigenAlerta::Evento,
                SeveridadAlerta::Atencion,
                [Rol::EncargadoCompras, Rol::Administrador],
                [],
                ['numero_oc', 'proveedor', 'error'],
            ),
            new TipoDeAlerta(
                'orden_atrasada',
                'Orden de producción atrasada',
                'La fecha de entrega de una orden pasó y la orden sigue abierta.',
                ModuloDeAlerta::Produccion,
                OrigenAlerta::Condicion,
                SeveridadAlerta::Critica,
                [Rol::SupervisorProduccion, Rol::Gerente],
                [],
                ['orden', 'producto', 'fecha_entrega', 'dias'],
            ),
            new TipoDeAlerta(
                'orden_por_vencer',
                'Orden de producción por vencer',
                'Una orden abierta vence dentro del plazo configurado con avance incompleto.',
                ModuloDeAlerta::Produccion,
                OrigenAlerta::Condicion,
                SeveridadAlerta::Atencion,
                [Rol::SupervisorProduccion, Rol::Gerente],
                [
                    self::entero('dias', 'Días antes de la entrega', 1, 30, 2),
                    self::decimal('avance_maximo', 'Avance máximo (%)', 0, 100, 100, 'lte', 'avance'),
                ],
                ['orden', 'producto', 'fecha_entrega', 'avance'],
            ),
            new TipoDeAlerta(
                'orden_sin_insumos',
                'Orden de producción sin insumos',
                'Se intentó iniciar una orden y faltan insumos reservados.',
                ModuloDeAlerta::Produccion,
                OrigenAlerta::Evento,
                SeveridadAlerta::Atencion,
                [Rol::SupervisorProduccion, Rol::EncargadoStock],
                [],
                ['orden', 'producto', 'insumos'],
            ),
            new TipoDeAlerta(
                'calidad_baja',
                'Calidad por debajo del umbral',
                'El porcentaje de rechazo de una orden supera el umbral configurado.',
                ModuloDeAlerta::Produccion,
                OrigenAlerta::Evento,
                SeveridadAlerta::Atencion,
                [Rol::SupervisorProduccion, Rol::Gerente],
                [self::decimal('rechazo_minimo_pct', 'Rechazo mínimo (%)', 1, 100, 5, 'gte', 'rechazo_pct')],
                ['orden', 'producto', 'rechazo_pct', 'rechazadas', 'unidades', 'etapa', 'motivo'],
                'Etapa: {{etapa}} · {{rechazadas}} de {{unidades}} rechazadas · Motivo: {{motivo}}',
            ),
            new TipoDeAlerta(
                'mantenimiento_vencido',
                'Mantenimiento vencido',
                'Una máquina superó el umbral de uso de su plan de mantenimiento.',
                ModuloDeAlerta::Produccion,
                OrigenAlerta::Condicion,
                SeveridadAlerta::Atencion,
                [Rol::SupervisorProduccion],
                [],
                ['maquinaria', 'desgaste', 'umbral'],
            ),
            new TipoDeAlerta(
                'quiebre_de_stock',
                'Quiebre de stock',
                'Un insumo va a agotarse antes de que llegue la reposición.',
                ModuloDeAlerta::Inventario,
                OrigenAlerta::Condicion,
                SeveridadAlerta::Critica,
                [Rol::EncargadoStock, Rol::Gerente],
                [],
                ['articulo', 'disponible', 'minimo', 'dias_hasta_quiebre', 'fecha_limite_pedido', 'cantidad_sugerida'],
                'Disponible: {{disponible}} · Mínimo: {{minimo}} · Pedir antes del {{fecha_limite_pedido}}',
            ),
            new TipoDeAlerta(
                'licitacion_no_disparada',
                'Licitación no disparada',
                'El riesgo de quiebre llegó a su fecha límite y la licitación automática no pudo enviarse.',
                ModuloDeAlerta::Compras,
                OrigenAlerta::Evento,
                SeveridadAlerta::Atencion,
                [Rol::EncargadoCompras, Rol::Gerente],
                [],
                ['articulo', 'motivo'],
                'Motivo: {{motivo}}',
            ),
            new TipoDeAlerta(
                'cuello_de_botella_predictivo',
                'Cuello de botella predictivo',
                'La fecha proyectada de una orden supera su fecha de entrega según la tasa de rendimiento real.',
                ModuloDeAlerta::Predictivo,
                OrigenAlerta::Condicion,
                SeveridadAlerta::Atencion,
                [Rol::Gerente, Rol::SupervisorProduccion],
                [],
                ['orden', 'producto', 'etapa', 'fecha_entrega', 'fecha_proyectada', 'dias_retraso', 'tasa_rendimiento'],
                'Etapa: {{etapa}} · TRR: {{tasa_rendimiento}} · Proyectada: {{fecha_proyectada}} (entrega: {{fecha_entrega}})',
            ),
        ];
    }

    /**
     * @return array{clave: string, etiqueta: string, tipo: string, minimo: float|int, maximo: float|int, por_defecto: float|int}
     */
    private static function entero(string $clave, string $etiqueta, int $minimo, int $maximo, int $porDefecto, string $operador = 'lte', ?string $variable = null): array
    {
        return [
            'clave' => $clave,
            'etiqueta' => $etiqueta,
            'tipo' => 'integer',
            'operador' => $operador,
            'variable' => $variable ?? $clave,
            'minimo' => $minimo,
            'maximo' => $maximo,
            'por_defecto' => $porDefecto,
        ];
    }

    /**
     * @return array{clave: string, etiqueta: string, tipo: string, minimo: float|int, maximo: float|int, por_defecto: float|int}
     */
    private static function decimal(string $clave, string $etiqueta, float $minimo, float $maximo, float $porDefecto, string $operador = 'gte', ?string $variable = null): array
    {
        return [
            'clave' => $clave,
            'etiqueta' => $etiqueta,
            'tipo' => 'numeric',
            'operador' => $operador,
            'variable' => $variable ?? $clave,
            'minimo' => $minimo,
            'maximo' => $maximo,
            'por_defecto' => $porDefecto,
        ];
    }
}
