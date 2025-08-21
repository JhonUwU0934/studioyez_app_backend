<?php
namespace App\Http\Controllers\Api;

use App\Models\IngresoDeMercancia;
use App\Models\Producto;
use App\Models\ProductoVariante;
use App\Http\Resources\IngresoDeMercanciaResource;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IngresoDeMercanciaController extends Controller
{
    public function index()
    {
        try {
            $ingresos = IngresoDeMercancia::with([
                'producto',
                'productoVariante' => function($query) {
                    $query->with(['producto', 'color', 'talla']);
                }
            ])->latest('created_at')->get();

            return response()->json([
                'success' => true,
                'data' => IngresoDeMercanciaResource::collection($ingresos)
            ]);

        } catch (\Exception $e) {
            Log::error('Error en IngresoMercanciaController@index: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al cargar ingresos de mercancía',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            Log::info('Datos recibidos para ingreso:', $request->all());

            $data = $request->validate([
                'producto_id' => 'nullable|exists:productos,id',
                'producto_variante_id' => 'nullable|exists:producto_variantes,id',
                'fecha' => 'required|date',
                'cantidad_de_ingreso' => 'required|integer|min:1',
            ]);

            // Validación: debe tener producto_id O producto_variante_id, pero no ambos vacíos
            if (empty($data['producto_id']) && empty($data['producto_variante_id'])) {
                return response()->json([
                    'error' => 'Debe especificar un producto o una variante de producto'
                ], 422);
            }

            // Si se especifica variante, obtener el producto_id automáticamente
            if (!empty($data['producto_variante_id'])) {
                $variante = ProductoVariante::find($data['producto_variante_id']);
                if (!$variante) {
                    return response()->json([
                        'error' => 'La variante especificada no existe'
                    ], 422);
                }
                
                // Opcional: mantener producto_id por referencia
                $data['producto_id'] = $variante->producto_id;
            }

            DB::beginTransaction();

            // Generar código único
            $ultimoCodigo = IngresoDeMercancia::max('codigo') ?? 0;
            $data['codigo'] = $ultimoCodigo + 1;

            // Crear el ingreso
            $ingreso = IngresoDeMercancia::create($data);

            // Actualizar stock según el tipo de ingreso
            if (!empty($data['producto_variante_id'])) {
                // Actualizar stock de la variante específica
                $variante = ProductoVariante::find($data['producto_variante_id']);
                $stockAnterior = $variante->existente_en_almacen;
                $variante->existente_en_almacen += $data['cantidad_de_ingreso'];
                $variante->save();
                
                Log::info('Stock actualizado en variante: ' . $variante->id . 
                         ' - Stock anterior: ' . $stockAnterior . 
                         ' - Cantidad ingresada: ' . $data['cantidad_de_ingreso'] .
                         ' - Nuevo stock: ' . $variante->existente_en_almacen);
            } else {
                // Actualizar stock del producto base
                $producto = Producto::find($data['producto_id']);
                $stockAnterior = (int) $producto->existente_en_almacen;
                $producto->existente_en_almacen = $stockAnterior + $data['cantidad_de_ingreso'];
                $producto->save();
                
                Log::info('Stock actualizado en producto base: ' . $producto->id . 
                         ' - Stock anterior: ' . $stockAnterior . 
                         ' - Cantidad ingresada: ' . $data['cantidad_de_ingreso'] .
                         ' - Nuevo stock: ' . $producto->existente_en_almacen);
            }

            DB::commit();

            // Cargar el ingreso con sus relaciones para la respuesta
            $ingreso->load([
                'producto',
                'productoVariante' => function($query) {
                    $query->with(['producto', 'color', 'talla']);
                }
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ingreso de mercancía registrado exitosamente',
                'data' => new IngresoDeMercanciaResource($ingreso)
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollback();
            Log::error('Error de validación: ' . json_encode($e->errors()));
            return response()->json([
                'error' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al crear ingreso de mercancía: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al crear el ingreso de mercancía',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $ingreso = IngresoDeMercancia::with([
                'producto',
                'productoVariante' => function($query) {
                    $query->with(['producto', 'color', 'talla']);
                }
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => new IngresoDeMercanciaResource($ingreso)
            ]);

        } catch (\Exception $e) {
            Log::error('Error en IngresoMercanciaController@show: ' . $e->getMessage());
            return response()->json([
                'error' => 'Ingreso de mercancía no encontrado',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    // Método para obtener variantes de un producto específico
    public function getVariantesByProducto($productoId)
    {
        try {
            $variantes = ProductoVariante::where('producto_id', $productoId)
                ->where('activo', true)
                ->with(['color', 'talla'])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $variantes->map(function($variante) {
                    return [
                        'id' => $variante->id,
                        'sku' => $variante->sku,
                        'existente_en_almacen' => $variante->existente_en_almacen,
                        'precio_por_mayor' => $variante->precio_por_mayor,
                        'precio_por_unidad' => $variante->precio_por_unidad,
                        'color' => $variante->color ? [
                            'id' => $variante->color->id,
                            'nombre' => $variante->color->nombre,
                            'codigo_hex' => $variante->color->codigo_hex,
                        ] : null,
                        'talla' => $variante->talla ? [
                            'id' => $variante->talla->id,
                            'nombre' => $variante->talla->nombre,
                        ] : null,
                        'nombre_display' => $this->getVarianteDisplay($variante),
                    ];
                })
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener variantes: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al cargar variantes',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function getVarianteDisplay($variante)
    {
        $display = $variante->sku ?: 'Sin SKU';
        $detalles = [];
        
        if ($variante->color) {
            $detalles[] = $variante->color->nombre;
        }
        
        if ($variante->talla) {
            $detalles[] = $variante->talla->nombre;
        }
        
        if (!empty($detalles)) {
            $display .= ' (' . implode(' - ', $detalles) . ')';
        }
        
        $display .= ' - Stock: ' . $variante->existente_en_almacen;
        
        return $display;
    }

    public function update(Request $request, $id)
    {
        try {
            $ingreso = IngresoDeMercancia::findOrFail($id);
            
            $data = $request->validate([
                'fecha' => 'sometimes|required|date',
                'cantidad_de_ingreso' => 'sometimes|required|integer|min:1',
            ]);

            // Solo permitir actualizar fecha y cantidad, no el producto/variante
            $ingreso->update($data);

            // Cargar relaciones para la respuesta
            $ingreso->load([
                'producto',
                'productoVariante' => function($query) {
                    $query->with(['producto', 'color', 'talla']);
                }
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ingreso actualizado exitosamente',
                'data' => new IngresoDeMercanciaResource($ingreso)
            ]);

        } catch (\Exception $e) {
            Log::error('Error al actualizar ingreso: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al actualizar el ingreso',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $ingreso = IngresoDeMercancia::findOrFail($id);
            
            DB::beginTransaction();
            
            // Revertir el stock antes de eliminar
            if ($ingreso->producto_variante_id && $ingreso->productoVariante) {
                $variante = $ingreso->productoVariante;
                $variante->existente_en_almacen -= $ingreso->cantidad_de_ingreso;
                $variante->save();
            } elseif ($ingreso->producto_id && $ingreso->producto) {
                $producto = $ingreso->producto;
                $producto->existente_en_almacen -= $ingreso->cantidad_de_ingreso;
                $producto->save();
            }
            
            $ingreso->delete();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Ingreso eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al eliminar ingreso: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al eliminar el ingreso',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}