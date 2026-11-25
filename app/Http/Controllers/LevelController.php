<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Http\Requests\LevelRequest;
use App\Models\MembershipLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LevelController extends Controller implements HasMiddleware
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
        return view('levels.index', [
            'levels' => MembershipLevel::withCount('members')->orderBy('fee')->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('levels.form', ['level' => new MembershipLevel(['validity_months' => 12])]);
    }

    public function store(LevelRequest $request): RedirectResponse
    {
        MembershipLevel::create($request->validated());

        return redirect()->route('levels.index')->with('success', 'Level created successfully.');
    }

    public function edit(MembershipLevel $level): View
    {
        return view('levels.form', ['level' => $level]);
    }

    public function update(LevelRequest $request, MembershipLevel $level): RedirectResponse
    {
        $level->update($request->validated());

        return redirect()->route('levels.index')->with('success', 'Level updated successfully.');
    }

    public function destroy(MembershipLevel $level): RedirectResponse
    {
        if ($level->members()->exists() || $level->transactions()->exists() || $level->courses()->exists()) {
            return back()->with('error', 'This level is used by members, transactions or e-learning courses and cannot be deleted.');
        }

        $level->delete();

        return redirect()->route('levels.index')->with('success', 'Level deleted successfully.');
    }
}
