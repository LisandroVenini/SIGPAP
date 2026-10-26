<?php

namespace App\Modules\Usuarios\Models;

use Illuminate\Database\Eloquent\Model;

class ParametroGlobal extends Model
{
    /**
     * @var string
     */
    protected $table = 'parametros_globales';

    /**
     * @var array<int, string>
     */
    protected $fillable = ['nombre', 'valor', 'categoria'];
}
