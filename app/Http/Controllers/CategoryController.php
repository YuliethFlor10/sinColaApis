<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    // Listar todas las categorías con relaciones
    public function index()
    {
        return Category::with(['status', 'business'])->get();
    }

    // Crear una nueva categoría
    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'abreviatura' => 'nullable|string|max:50',
            'descripcion' => 'nullable|string',
            'grupo' => 'nullable|string|max:100',
            'negocios' => 'required|exists:businesses,_id',
            'estados' => 'required|exists:statuses,_id',
        ]);

        $category = Category::create($data);
        return response()->json($category->load(['status', 'business']), 201);
    }

    // Mostrar una categoría específica
    public function show($id)
    {
        return Category::with(['status', 'business'])->findOrFail($id);
    }

    // Actualizar una categoría existente
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $data = $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'abreviatura' => 'nullable|string|max:50',
            'descripcion' => 'nullable|string',
            'grupo' => 'nullable|string|max:100',
            'negocios' => 'sometimes|exists:businesses,_id',
            'estados' => 'sometimes|exists:statuses,_id',
        ]);

        $category->update($data);
        return response()->json($category->load(['status', 'business']));
    }

    // Eliminar una categoría
    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return response()->json(['message' => 'Categoría eliminada correctamente']);
    }
}
