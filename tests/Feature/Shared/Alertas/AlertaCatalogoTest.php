<?php

namespace Tests\Feature\Shared\Alertas;

use App\Shared\Alertas\Catalogo\CatalogoDeAlertas;
use App\Shared\Alertas\Enums\ModuloDeAlerta;
use Tests\TestCase;

class AlertaCatalogoTest extends TestCase
{
    public function test_every_supported_type_is_well_formed(): void
    {
        // Act
        $tipos = CatalogoDeAlertas::todos();

        // Assert
        $this->assertCount(18, $tipos);

        foreach ($tipos as $clave => $tipo) {
            $this->assertSame($clave, $tipo->clave);
            $this->assertNotSame('', $tipo->etiqueta);
            $this->assertNotEmpty($tipo->rolesPorDefecto, "El tipo {$clave} no tiene roles por defecto.");
            $this->assertNotEmpty($tipo->variables, "El tipo {$clave} no declara variables.");
        }
    }

    public function test_conditions_declare_ranges_that_contain_their_default(): void
    {
        // Act / Assert
        foreach (CatalogoDeAlertas::todos() as $tipo) {
            foreach ($tipo->condiciones as $condicion) {
                $this->assertArrayHasKey('por_defecto', $condicion);
                $this->assertGreaterThanOrEqual($condicion['minimo'], $condicion['por_defecto']);
                $this->assertLessThanOrEqual($condicion['maximo'], $condicion['por_defecto']);
            }
        }
    }

    public function test_types_are_grouped_by_module(): void
    {
        // Act / Assert
        $this->assertCount(5, CatalogoDeAlertas::porModulo(ModuloDeAlerta::Inventario));
        $this->assertCount(7, CatalogoDeAlertas::porModulo(ModuloDeAlerta::Compras));
        $this->assertCount(5, CatalogoDeAlertas::porModulo(ModuloDeAlerta::Produccion));
        $this->assertCount(1, CatalogoDeAlertas::porModulo(ModuloDeAlerta::Predictivo));
        $this->assertNull(CatalogoDeAlertas::obtener('inexistente'));
    }
}
