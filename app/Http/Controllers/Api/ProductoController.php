<?php
// app/Http/Controllers/Api/ProductoController.php (VERSIÓN ULTRA SEGURA)
namespace App\Http\Controllers\Api;

use App\Models\Producto;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductoResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use App\Models\ProductoVariante;

class ProductoController extends Controller
{
    public function index()
    {
        try {
            // CARGAR PRODUCTOS CON RELACIONES igual que en SHOW
            $productos = Producto::with([
                'imagenes' => function ($query) {
                    $query->orderBy('orden');
                },
                'variantes' => function ($query) {
                    $query->with(['color', 'talla']);
                }
            ])->latest('created_at')->get();

            return ProductoResource::collection($productos);

        } catch (\Exception $e) {
            \Log::error('Error en ProductoController@index: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al cargar productos',
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ], 500);
        }
    }


    public function store(Request $request)
    {
        try {
            \Log::info('=== DEBUGGING REQUEST ===');
            \Log::info('Content-Type header:', [$request->header('Content-Type')]);
            \Log::info('Accept header:', [$request->header('Accept')]);
            \Log::info('Request method:', [$request->method()]);
            \Log::info('Raw input:', [$request->getContent()]);
            \Log::info('All request data:', $request->all());
            \Log::info('JSON data:', $request->json()->all());
            \Log::info('Input codigo:', [$request->input('codigo')]);
            \Log::info('Input denominacion:', [$request->input('denominacion')]);
            \Log::info('=== END DEBUGGING ===');

            $request->merge([
                'existente_en_almacen' => str_replace(['.', ','], '', $request->input('existente_en_almacen')),
                'precio_por_mayor' => str_replace(['.', ','], '', $request->input('precio_por_mayor')),
                'precio_por_unidad' => str_replace(['.', ','], '', $request->input('precio_por_unidad'))
            ]);

            // VALIDACIÓN CORREGIDA
            $data = $request->validate([
                'codigo' => 'required|string',
                'denominacion' => 'required|string',
                'imagen' => 'nullable|string',
                'existente_en_almacen' => 'nullable|string',
                'precio_por_mayor' => 'nullable|string',
                'precio_por_unidad' => 'nullable|string',
                
                // ✅ CORRECCIÓN: Cambiar required_with por required
                'galeria' => 'nullable|array',
                'galeria.*.imagen' => 'required|string',  // Cambio aquí
                'galeria.*.alt_text' => 'nullable|string',
                'galeria.*.orden' => 'nullable|integer',
                
                'variantes' => 'nullable|array',
                'variantes.*.color_id' => 'nullable|exists:colores,id',
                'variantes.*.talla_id' => 'nullable|exists:tallas,id',
                // ✅ CORRECCIÓN: Hacer SKU único solo si no está vacío
                'variantes.*.sku' => 'nullable|string',  // Removemos unique por ahora
                'variantes.*.existente_en_almacen' => 'nullable|integer',
                'variantes.*.precio_por_mayor' => 'nullable|string',
                'variantes.*.precio_por_unidad' => 'nullable|string',
                'variantes.*.imagen_variante' => 'nullable|string',
            ]);

            DB::beginTransaction();
            
            // 1. Crear el producto base
            $producto = Producto::create([
                'codigo' => $data['codigo'],
                'denominacion' => $data['denominacion'],
                'imagen' => $data['imagen'] ?? null,
                'existente_en_almacen' => $data['existente_en_almacen'] ?? null,
                'precio_por_mayor' => $data['precio_por_mayor'] ?? null,
                'precio_por_unidad' => $data['precio_por_unidad'] ?? null,
            ]);

            \Log::info('Producto creado con ID: ' . $producto->id);

            // 2. Crear galería de imágenes si se proporcionó
            if (isset($data['galeria']) && is_array($data['galeria'])) {
                foreach ($data['galeria'] as $imagenData) {
                    $imagen = $producto->imagenes()->create([
                        'imagen' => $imagenData['imagen'],
                        'alt_text' => $imagenData['alt_text'] ?? null,
                        'orden' => $imagenData['orden'] ?? 0,
                    ]);
                    \Log::info('Imagen creada: ' . $imagen->id);
                }
            }

            // 3. Crear variantes si se proporcionaron
            if (isset($data['variantes']) && is_array($data['variantes'])) {
                foreach ($data['variantes'] as $varianteData) {
                    // ✅ VALIDACIÓN MANUAL DE SKU ÚNICO
                    if (!empty($varianteData['sku'])) {
                        $existingSku = ProductoVariante::where('sku', $varianteData['sku'])->first();
                        if ($existingSku) {
                            throw new \Exception('El SKU "' . $varianteData['sku'] . '" ya existe');
                        }
                    }

                    $variante = $producto->variantes()->create([
                        'color_id' => $varianteData['color_id'] ?? null,
                        'talla_id' => $varianteData['talla_id'] ?? null,
                        'sku' => $varianteData['sku'] ?? null,
                        'existente_en_almacen' => $varianteData['existente_en_almacen'] ?? 0,
                        'precio_por_mayor' => str_replace(['.', ','], '', $varianteData['precio_por_mayor'] ?? null),
                        'precio_por_unidad' => str_replace(['.', ','], '', $varianteData['precio_por_unidad'] ?? null),
                        'imagen_variante' => $varianteData['imagen_variante'] ?? null,
                        'activo' => true,
                    ]);
                    \Log::info('Variante creada: ' . $variante->id . ' - Color: ' . $variante->color_id . ' - Talla: ' . $variante->talla_id);
                }
            }

            DB::commit();
            
            // 4. Cargar el producto con todas sus relaciones para la respuesta
            $producto->load(['imagenes', 'variantes.color', 'variantes.talla']);
            
            return new ProductoResource($producto);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollback();
            \Log::error('Error de validación: ' . json_encode($e->errors()));
            return response()->json([
                'error' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error al crear el producto: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'error' => 'Error al crear el producto',
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            // CARGAR PRODUCTO CON TODAS SUS RELACIONES
            $producto = Producto::with([
                'imagenes' => function($query) {
                    $query->orderBy('orden');
                },
                'variantes' => function($query) {
                    $query->with(['color', 'talla']);
                }
            ])->findOrFail($id);

            return new ProductoResource($producto);
            
        } catch (\Exception $e) {
            \Log::error('Error en ProductoController@show: ' . $e->getMessage());
            return response()->json([
                'error' => 'Producto no encontrado',
                'message' => $e->getMessage()
            ], 404);
        }
    }


    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            if (strpos($user->name, 'Asesor') === 0) {
                return new JsonResponse(['message' => 'No tienes permiso para ejecutar esta acción.'], 403);
            }

            $request->merge([
                'existente_en_almacen' => str_replace(['.', ','], '', $request->input('existente_en_almacen')),
                'precio_por_mayor' => str_replace(['.', ','], '', $request->input('precio_por_mayor')),
                'precio_por_unidad' => str_replace(['.', ','], '', $request->input('precio_por_unidad'))
            ]);

            // VALIDACIÓN COMPLETA incluyendo variantes y galería
            $data = $request->validate([
                'codigo' => 'required|string',
                'denominacion' => 'required|string',
                'imagen' => 'nullable|string',
                'existente_en_almacen' => 'nullable|string',
                'precio_por_mayor' => 'nullable|string',
                'precio_por_unidad' => 'nullable|string',
                
                // NUEVOS CAMPOS PARA ACTUALIZAR GALERÍA
                'galeria' => 'nullable|array',
                'galeria.*.id' => 'nullable|exists:producto_imagenes,id',
                'galeria.*.imagen' => 'required_with:galeria|string',
                'galeria.*.alt_text' => 'nullable|string',
                'galeria.*.orden' => 'nullable|integer',
                'galeria.*.eliminar' => 'nullable|boolean',
                
                // NUEVOS CAMPOS PARA ACTUALIZAR VARIANTES
                'variantes' => 'nullable|array',
                'variantes.*.id' => 'nullable|exists:producto_variantes,id',
                'variantes.*.color_id' => 'nullable|exists:colores,id',
                'variantes.*.talla_id' => 'nullable|exists:tallas,id',
                'variantes.*.sku' => 'nullable|string',
                'variantes.*.existente_en_almacen' => 'nullable|integer',
                'variantes.*.precio_por_mayor' => 'nullable|string',
                'variantes.*.precio_por_unidad' => 'nullable|string',
                'variantes.*.imagen_variante' => 'nullable|string',
                'variantes.*.activo' => 'nullable|boolean',
                'variantes.*.eliminar' => 'nullable|boolean',
            ]);
            
            $producto = Producto::findOrFail($id);

            DB::beginTransaction();

            // 1. ACTUALIZAR DATOS BÁSICOS DEL PRODUCTO
            $producto->update([
                'codigo' => $data['codigo'],
                'denominacion' => $data['denominacion'],
                'imagen' => $data['imagen'] ?? $producto->imagen,
                'existente_en_almacen' => $data['existente_en_almacen'] ?? $producto->existente_en_almacen,
                'precio_por_mayor' => $data['precio_por_mayor'] ?? $producto->precio_por_mayor,
                'precio_por_unidad' => $data['precio_por_unidad'] ?? $producto->precio_por_unidad,
            ]);

            \Log::info('Producto actualizado: ' . $producto->id);

            // 2. ACTUALIZAR GALERÍA DE IMÁGENES
            if (isset($data['galeria']) && is_array($data['galeria'])) {
                foreach ($data['galeria'] as $imagenData) {
                    
                    // ELIMINAR imagen si está marcada para eliminar
                    if (!empty($imagenData['eliminar']) && !empty($imagenData['id'])) {
                        $producto->imagenes()->where('id', $imagenData['id'])->delete();
                        \Log::info('Imagen eliminada: ' . $imagenData['id']);
                        continue;
                    }
                    
                    // ACTUALIZAR imagen existente
                    if (!empty($imagenData['id'])) {
                        $imagen = $producto->imagenes()->find($imagenData['id']);
                        if ($imagen) {
                            $imagen->update([
                                'imagen' => $imagenData['imagen'],
                                'alt_text' => $imagenData['alt_text'] ?? $imagen->alt_text,
                                'orden' => $imagenData['orden'] ?? $imagen->orden,
                            ]);
                            \Log::info('Imagen actualizada: ' . $imagen->id);
                        }
                    }
                    // CREAR nueva imagen
                    else {
                        $nuevaImagen = $producto->imagenes()->create([
                            'imagen' => $imagenData['imagen'],
                            'alt_text' => $imagenData['alt_text'] ?? null,
                            'orden' => $imagenData['orden'] ?? 0,
                        ]);
                        \Log::info('Nueva imagen creada: ' . $nuevaImagen->id);
                    }
                }
            }

            // 3. ACTUALIZAR VARIANTES
            if (isset($data['variantes']) && is_array($data['variantes'])) {
                foreach ($data['variantes'] as $varianteData) {
                    
                    // ELIMINAR variante si está marcada para eliminar
                    if (!empty($varianteData['eliminar']) && !empty($varianteData['id'])) {
                        $producto->variantes()->where('id', $varianteData['id'])->delete();
                        \Log::info('Variante eliminada: ' . $varianteData['id']);
                        continue;
                    }
                    
                    // Validar SKU único (excluyendo la variante actual si existe)
                    $skuQuery = ProductoVariante::where('sku', $varianteData['sku'] ?? '');
                    if (!empty($varianteData['id'])) {
                        $skuQuery->where('id', '!=', $varianteData['id']);
                    }
                    if ($skuQuery->exists()) {
                        throw new \Exception('El SKU "' . $varianteData['sku'] . '" ya existe');
                    }
                    
                    // ACTUALIZAR variante existente
                    if (!empty($varianteData['id'])) {
                        $variante = $producto->variantes()->find($varianteData['id']);
                        if ($variante) {
                            $variante->update([
                                'color_id' => $varianteData['color_id'] ?? $variante->color_id,
                                'talla_id' => $varianteData['talla_id'] ?? $variante->talla_id,
                                'sku' => $varianteData['sku'] ?? $variante->sku,
                                'existente_en_almacen' => $varianteData['existente_en_almacen'] ?? $variante->existente_en_almacen,
                                'precio_por_mayor' => str_replace(['.', ','], '', $varianteData['precio_por_mayor'] ?? $variante->precio_por_mayor),
                                'precio_por_unidad' => str_replace(['.', ','], '', $varianteData['precio_por_unidad'] ?? $variante->precio_por_unidad),
                                'imagen_variante' => $varianteData['imagen_variante'] ?? $variante->imagen_variante,
                                'activo' => $varianteData['activo'] ?? $variante->activo,
                            ]);
                            \Log::info('Variante actualizada: ' . $variante->id);
                        }
                    }
                    // CREAR nueva variante
                    else {
                        $nuevaVariante = $producto->variantes()->create([
                            'color_id' => $varianteData['color_id'] ?? null,
                            'talla_id' => $varianteData['talla_id'] ?? null,
                            'sku' => $varianteData['sku'] ?? null,
                            'existente_en_almacen' => $varianteData['existente_en_almacen'] ?? 0,
                            'precio_por_mayor' => str_replace(['.', ','], '', $varianteData['precio_por_mayor'] ?? null),
                            'precio_por_unidad' => str_replace(['.', ','], '', $varianteData['precio_por_unidad'] ?? null),
                            'imagen_variante' => $varianteData['imagen_variante'] ?? null,
                            'activo' => $varianteData['activo'] ?? true,
                        ]);
                        \Log::info('Nueva variante creada: ' . $nuevaVariante->id);
                    }
                }
            }

            DB::commit();
            
            // 4. CARGAR EL PRODUCTO ACTUALIZADO CON TODAS SUS RELACIONES
            $producto->load([
                'imagenes' => function($query) {
                    $query->orderBy('orden');
                },
                'variantes' => function($query) {
                    $query->with(['color', 'talla']);
                }
            ]);
            
            return new ProductoResource($producto);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollback();
            \Log::error('Error de validación en update: ' . json_encode($e->errors()));
            return response()->json([
                'error' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error en ProductoController@update: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al actualizar el producto',
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = Auth::user();
            if (strpos($user->name, 'Asesor') === 0) {
                return new JsonResponse(['message' => 'No tienes permiso para ejecutar esta acción.'], 403);
            }

            $producto = Producto::findOrFail($id);
            $producto->delete();
            return response()->json(['message' => 'Producto eliminado correctamente']);
            
        } catch (\Exception $e) {
            \Log::error('Error en ProductoController@destroy: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al eliminar el producto',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}