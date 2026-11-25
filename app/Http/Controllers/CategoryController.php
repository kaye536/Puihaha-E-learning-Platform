<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Http\Requests\CategoryRequest;
use App\Models\MembershipCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller implements HasMiddleware
{
    /** Role-based access control: see config/rbac.php for which roles hold each permission. */
    public static function middleware(): array
    {
        return [
            new Middleware('can:catalog.view', only: ['index']),
            new Middleware('can:catalog.manage', only: ['create', 'store', 'edit', 'update', 'destroy']),
        ];
    }

    public function index(): View
    {
        return view('categories.index', [
            'categories' => MembershipCategory::withCount('members')->orderBy('name')->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('categories.form', ['category' => new MembershipCategory()]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        MembershipCategory::create($request->validated());

        return redirect()->route('categories.index')->with('success', 'Category created successfully.');
    }

    public function edit(MembershipCategory $category): View
    {
        return view('categories.form', ['category' => $category]);
    }

    public function update(CategoryRequest $request, MembershipCategory $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()->route('categories.index')->with('success', 'Category updated successfully.');
    }

    public function destroy(MembershipCategory $category): RedirectResponse
    {
        if ($category->members()->exists()) {
            return back()->with('error', 'This category still has members assigned to it and cannot be deleted.');
        }

        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Category deleted successfully.');
    }
}
