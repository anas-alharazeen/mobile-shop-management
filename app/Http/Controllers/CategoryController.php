<?php

namespace App\Http\Controllers;

use App\Enums\CategoryType;
use App\Models\Category;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::query()
            ->search($request->search)
            ->type($request->type)
            ->when($request->has('is_active'), function ($query) use ($request) {
                return $query->where('is_active', $request->boolean('is_active'));
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => Category::count(),
            'active' => Category::active()->count(),
            'inactive' => Category::inactive()->count(),
            'types_used' => Category::distinct('type')->count('type'),
        ];

        return Inertia::render('Categories/Index', [
            'categories' => $categories,
            'stats' => $stats,
            'filters' => [
                'search' => $request->search,
                'type' => $request->type,
                'is_active' => $request->has('is_active') ? $request->boolean('is_active') : null,
            ],
            'types' => CategoryType::labels(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Categories/Form', [
            'category' => null,
            'types' => CategoryType::labels(),
            'pageTitle' => 'إضافة فئة جديدة',
        ]);
    }

    public function store(StoreCategoryRequest $request)
    {
        $validated = $request->validated();

        Category::create([
            'name' => trim($validated['name']),
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('categories.index')
            ->with('success', 'تم إضافة الفئة بنجاح');
    }

    public function edit(Category $category)
    {
        return Inertia::render('Categories/Form', [
            'category' => $category,
            'types' => CategoryType::labels(),
            'pageTitle' => 'تعديل الفئة',
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $validated = $request->validated();

        $category->update([
            'name' => trim($validated['name']),
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('categories.index')
            ->with('success', 'تم تحديث الفئة بنجاح');
    }

  // في destroy method:
public function destroy(Category $category)
{
    if ($category->products()->count() > 0) {
        return redirect()->route('categories.index')
            ->with('error', 'لا يمكن حذف هذه الفئة لأنها مرتبطة بمنتجات موجودة.');
    }

    $category->delete();

    return redirect()->route('categories.index')
        ->with('success', 'تم حذف الفئة بنجاح');
}

    public function toggleStatus(Category $category)
    {
        $category->update([
            'is_active' => !$category->is_active
        ]);

        $status = $category->is_active ? 'مفعلة' : 'غير مفعلة';
        return redirect()->route('categories.index')
            ->with('success', "تم تغيير حالة الفئة إلى {$status}");
    }
}
