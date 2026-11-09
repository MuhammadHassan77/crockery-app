<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Http\Controllers\Traits\SelectFieldTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    use SelectFieldTrait;

    public function store(Request $request)
    {
        try {
            $request->validate(['name' => ['required', 'string', 'max:100', 'unique:categories,name']]);

            $category = Category::create([
                'name' => $request->name,
                'slug' => Str::slug($request->name). md5(uniqid(rand(), true)),
            ]);

            return response()->json($category);
        } catch (\Throwable $e) {
            return $this->handleException($request, $e, 'Unable to create category. Please try again.', null, 422);
        }
    }

    /**
     * Search categories for Select2 dropdown
     * Supports search by name with pagination
     */
    public function search(Request $request)
    {
        $request->validate([
            'q'    => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1']
        ]);

        $query = $this->sanitizeSearchQuery($request->q);
        $page = $request->page ?? 1;

        // Build search query
        $categories = Category::where('name', 'like', "%$query%")
            ->where('is_active', true)
            ->orderBy('name')
            ->select('id', 'name');

        // Format the results using custom formatter
        $results = $this->formatSelectResults($categories, $page, function ($category) {
            return [
                'id'   => $category->id,
                'text' => $category->name
            ];
        });

        return response()->json($results);
    }
}
