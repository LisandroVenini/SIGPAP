<?php

use App\Modules\Produccion\Controllers\EtapaController;
use App\Modules\Produccion\Controllers\HojaDeRutaController;
use App\Modules\Produccion\Controllers\MaquinariaController;
use App\Modules\Produccion\Controllers\OperarioController;
use App\Modules\Produccion\Controllers\OrdenDeProduccionController;
use App\Modules\Produccion\Controllers\ParteDiarioController;
use App\Modules\Produccion\Controllers\PlanificacionController;
use App\Modules\Produccion\Controllers\RecetaController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')
    ->prefix('produccion')
    ->name('produccion.')
    ->group(function (): void {
        Route::middleware('auth')->group(function (): void {
            Route::get('planificaciones', [PlanificacionController::class, 'index'])->name('planificaciones.index');
            Route::post('planificaciones', [PlanificacionController::class, 'store'])->name('planificaciones.store');
            Route::put('planificaciones/{planificacion}', [PlanificacionController::class, 'update'])->name('planificaciones.update');
            Route::delete('planificaciones/{planificacion}', [PlanificacionController::class, 'destroy'])->name('planificaciones.destroy');

            Route::get('ordenes-de-produccion', [OrdenDeProduccionController::class, 'index'])->name('ordenes-de-produccion.index');
            Route::post('ordenes-de-produccion', [OrdenDeProduccionController::class, 'store'])->name('ordenes-de-produccion.store');
            Route::match(['put', 'patch'], 'ordenes-de-produccion/{orden}', [OrdenDeProduccionController::class, 'update'])->name('ordenes-de-produccion.update');
            Route::post('ordenes-de-produccion/{orden}/anular', [OrdenDeProduccionController::class, 'anular'])
                ->whereNumber('orden')
                ->name('ordenes-de-produccion.anular');
            Route::put('ordenes-de-produccion/{orden}/recursos', [OrdenDeProduccionController::class, 'asignar'])
                ->whereNumber('orden')
                ->name('ordenes-de-produccion.recursos.asignar');
            Route::post('ordenes-de-produccion/{orden}/iniciar', [OrdenDeProduccionController::class, 'iniciar'])
                ->whereNumber('orden')
                ->name('ordenes-de-produccion.iniciar');
            Route::post('ordenes-de-produccion/{orden}/calidad', [OrdenDeProduccionController::class, 'registrarCalidad'])
                ->whereNumber('orden')
                ->name('ordenes-de-produccion.calidad');
            Route::post('ordenes-de-produccion/{orden}/cerrar', [OrdenDeProduccionController::class, 'cerrar'])
                ->whereNumber('orden')
                ->name('ordenes-de-produccion.cerrar');
            Route::post('ordenes-de-produccion/{orden}/posponer', [OrdenDeProduccionController::class, 'posponer'])
                ->whereNumber('orden')
                ->name('ordenes-de-produccion.posponer');
            Route::post('ordenes-de-produccion/{orden}/reanudar', [OrdenDeProduccionController::class, 'reanudar'])
                ->whereNumber('orden')
                ->name('ordenes-de-produccion.reanudar');
            Route::post('ordenes-de-produccion/{orden}/partes', [ParteDiarioController::class, 'store'])
                ->whereNumber('orden')
                ->name('ordenes-de-produccion.partes.store');

            Route::get('etapas', [EtapaController::class, 'index'])->name('etapas.index');
            Route::post('etapas', [EtapaController::class, 'store'])->name('etapas.store');
            Route::put('etapas/{etapa}', [EtapaController::class, 'update'])->name('etapas.update');
            Route::delete('etapas/{etapa}', [EtapaController::class, 'destroy'])->name('etapas.destroy');

            Route::get('operarios', [OperarioController::class, 'index'])->name('operarios.index');
            Route::post('operarios', [OperarioController::class, 'store'])->name('operarios.store');
            Route::put('operarios/{operario}', [OperarioController::class, 'update'])->name('operarios.update');
            Route::delete('operarios/{operario}', [OperarioController::class, 'destroy'])->name('operarios.destroy');

            Route::get('maquinarias', [MaquinariaController::class, 'index'])->name('maquinarias.index');
            Route::post('maquinarias', [MaquinariaController::class, 'store'])->name('maquinarias.store');
            Route::put('maquinarias/{maquinaria}', [MaquinariaController::class, 'update'])
                ->whereNumber('maquinaria')
                ->name('maquinarias.update');
            Route::delete('maquinarias/{maquinaria}', [MaquinariaController::class, 'destroy'])
                ->whereNumber('maquinaria')
                ->name('maquinarias.destroy');

            Route::get('hoja-de-ruta', [HojaDeRutaController::class, 'index'])->name('hoja-de-ruta.index');
            Route::get('hoja-de-ruta/{articulo}', [HojaDeRutaController::class, 'show'])
                ->whereNumber('articulo')
                ->name('hoja-de-ruta.show');
            Route::post('hoja-de-ruta/{articulo}/etapas', [HojaDeRutaController::class, 'store'])
                ->whereNumber('articulo')
                ->name('hoja-de-ruta.etapas.store');
            Route::delete('hoja-de-ruta/{articulo}/etapas/{etapa}', [HojaDeRutaController::class, 'destroy'])
                ->whereNumber('articulo')
                ->whereNumber('etapa')
                ->name('hoja-de-ruta.etapas.destroy');
            Route::put('hoja-de-ruta/{articulo}/etapas/{etapa}/mover', [HojaDeRutaController::class, 'mover'])
                ->whereNumber('articulo')
                ->whereNumber('etapa')
                ->name('hoja-de-ruta.etapas.mover');

            Route::post('hoja-de-ruta/{articulo}/receta/componentes', [RecetaController::class, 'store'])
                ->whereNumber('articulo')
                ->name('hoja-de-ruta.receta.componentes.store');
            Route::delete('hoja-de-ruta/{articulo}/receta/componentes/{componente}', [RecetaController::class, 'destroy'])
                ->whereNumber('articulo')
                ->name('hoja-de-ruta.receta.componentes.destroy');
        });
    });
