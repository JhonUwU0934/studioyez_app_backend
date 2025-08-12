<?php
// app/Models/Producto.php (VERSIÓN TEMPORAL SIN TIMEOUT)
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Producto extends Model
{
    protected $fillable = [
        'codigo',
        'denominacion',
        'imagen',
        'existente_en_almacen',
        'precio_por_mayor',
        'precio_por_unidad',
    ];

    // RELACIONES EXISTENTES (mantener todas)
    public function inventario()
    {
        return $this->hasOne(Inventario::class);
    }

    public function ingresosDeMercancia()
    {
        return $this->hasMany(IngresoDeMercancia::class);
    }

    public function ventas()
    {
        return $this->hasMany(Ventas::class);
    }

    public function devolucionesClienteAlmacen()
    {
        return $this->hasMany(DevolucionClienteAlmacen::class);
    }

    public function devolucionesAlmacenFabrica()
    {
        return $this->hasMany(DevolucionAlmacenFabrica::class);
    }

    public function ventaProducto()
    {
        return $this->belongsTo(VentaProducto::class, 'id_producto', 'id')->withTimestamps();
    }

    // NUEVAS RELACIONES CON VERIFICACIÓN DE SEGURIDAD
    public function imagenes()
    {
        // Solo definir la relación si la tabla existe
        if ($this->checkTableExists('producto_imagenes') && class_exists('App\Models\ProductoImagen')) {
            return $this->hasMany('App\Models\ProductoImagen')->orderBy('orden');
        }
        // Retornar una relación vacía si no existe
        return $this->hasMany(self::class)->where('id', 0); // Nunca retornará resultados
    }

    public function variantes()
    {
        if ($this->checkTableExists('producto_variantes') && class_exists('App\Models\ProductoVariante')) {
            return $this->hasMany('App\Models\ProductoVariante');
        }
        return $this->hasMany(self::class)->where('id', 0);
    }

    public function variantesActivas()
    {
        if ($this->checkTableExists('producto_variantes') && class_exists('App\Models\ProductoVariante')) {
            return $this->hasMany('App\Models\ProductoVariante')->where('activo', true);
        }
        return $this->hasMany(self::class)->where('id', 0);
    }

    // MÉTODOS AUXILIARES CON VERIFICACIÓN DE SEGURIDAD
    public function tieneVariantes()
    {
        try {
            if (!$this->checkTableExists('producto_variantes')) {
                return false;
            }
            return $this->variantes()->count() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function coloresDisponibles()
    {
        try {
            if (!$this->checkTableExists('producto_variantes') || !$this->checkTableExists('colores')) {
                return collect([]);
            }
            
            return $this->variantes()
                ->with('color')
                ->whereNotNull('color_id')
                ->distinct('color_id')
                ->get()
                ->pluck('color')
                ->filter();
        } catch (\Exception $e) {
            return collect([]);
        }
    }

    public function tallasDisponibles()
    {
        try {
            if (!$this->checkTableExists('producto_variantes') || !$this->checkTableExists('tallas')) {
                return collect([]);
            }
            
            return $this->variantes()
                ->with('talla')
                ->whereNotNull('talla_id')
                ->distinct('talla_id')
                ->get()
                ->pluck('talla')
                ->filter()
                ->sortBy('orden');
        } catch (\Exception $e) {
            return collect([]);
        }
    }

    private function checkTableExists($tableName)
    {
        try {
            return Schema::hasTable($tableName);
        } catch (\Exception $e) {
            return false;
        }
    }
}