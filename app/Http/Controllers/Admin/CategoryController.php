<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

// CRUD kategori dengan pola yang sama seperti ProductController di Modul 12
class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('reports')->orderBy('name')->get();

        return view('admin.categories.index', ['categories' => $categories]);
    }

    public function create()
    {
        return view('admin.categories.form', [
            'title' => 'Tambah',
            'category' => new Category(['icon' => 'box-seam']),
            'route' => route('admin.categories.store'),
            'method' => 'POST',
        ]);
    }

    public function store(Request $request)
    {
        $request->merge(['slug' => Str::slug((string) $request->input('name'))]);
        $validated = $request->validate([
            'name' => 'required|string|min:3|max:40|unique:categories,name',
            'slug' => 'unique:categories,slug',
            'icon' => 'required|string|max:40|regex:/^[a-z0-9-]+$/',
        ], ['slug.unique' => 'Nama ini terlalu mirip dengan kategori yang sudah ada.']);

        Category::create($validated);

        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil ditambahkan');
    }

    public function edit(Category $category)
    {
        return view('admin.categories.form', [
            'title' => 'Edit',
            'category' => $category,
            'route' => route('admin.categories.update', $category),
            'method' => 'PUT',
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $request->merge(['slug' => Str::slug((string) $request->input('name'))]);
        $validated = $request->validate([
            'name' => 'required|string|min:3|max:40|unique:categories,name,'.$category->id,
            'slug' => 'unique:categories,slug,'.$category->id,
            'icon' => 'required|string|max:40|regex:/^[a-z0-9-]+$/',
        ], ['slug.unique' => 'Nama ini terlalu mirip dengan kategori yang sudah ada.']);

        $category->update($validated);

        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil diperbarui');
    }

    public function destroy(Category $category)
    {
        if ($category->reports()->exists()) {
            return back()->with('error', 'Kategori yang sudah dipakai laporan tidak bisa dihapus.');
        }

        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil dihapus');
    }
}
