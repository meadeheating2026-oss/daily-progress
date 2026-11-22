<?php

namespace App\Http\Controllers;

use App\Models\WorkItemCategory as Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    // عرض كل الفئات
    public function index()
    {
        $categories = Category::all();
        return view('categories.index', compact('categories'));
    }

    // صفحة إنشاء فئة جديدة
    public function create()
    {
        return view('categories.create');
    }

    // حفظ فئة جديدة في الداتابيز
public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
    ]);

    $category = \App\Models\WorkItemCategory::create([
        'name' => $request->name,
    ]);

    return redirect()->route('categories.index')->with('success','category created successfully.');
}



    // صفحة تعديل الفئة
    public function edit($id)
    {
        $category = Category::findOrFail($id);
        return view('categories.edit', compact('category'));
    }

    // تحديث الفئة في الداتابيز
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category = Category::findOrFail($id);
        $category->update(['name' => $request->name]);

        return redirect()->route('categories.index')->with('success', 'Category updated successfully.');
    }

    // حذف الفئة
    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Category deleted successfully.');
    }
}
