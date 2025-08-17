<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    // GET /categories
    public function index()
    {
        $categories = Category::with('status')->get();
        return response()->json($categories);
    }

    // GET /categories/{id}
    public function show($id)
    {
        $category = Category::with('status')->find($id);

        if (!$category) {
            return response()->json(['message' => 'Categoría no encontrada'], 404);
        }

        return response()->json($category);
    }

    // POST /categories
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'abreviatura' => 'nullable|string|max:50',
            'descripcion' => 'nullable|string',
            'grupo' => 'nullable|string|max:100',
            'estados_id' => 'required|exists:statuses,id',
        ]);

        $category = Category::create($validated);

        return response()->json($category, 201);
    }

    // PUT /categories/{id}
    public function update(Request $request, $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['message' => 'Categoría no encontrada'], 404);
        }

        $validated = $request->validate([
            'nombre' => 'sometimes|required|string|max:255',
            'abreviatura' => 'sometimes|nullable|string|max:50',
            'descripcion' => 'sometimes|nullable|string',
            'grupo' => 'sometimes|nullable|string|max:100',
            'estados_id' => 'sometimes|required|exists:statuses,id',
        ]);

        $category->update($validated);

        return response()->json($category);
    }

    // DELETE /categories/{id}
    public function destroy($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['message' => 'Categoría no encontrada'], 404);
        }

        $category->delete();

        return response()->json(['message' => 'Categoría eliminada correctamente']);
    }
}
