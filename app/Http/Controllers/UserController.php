<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

/** Staff accounts and their roles. Administrators only (permission: users.manage). */
class UserController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:users.manage')];
    }

    public function index(): View
    {
        return view('users.index', [
            // Member-portal logins are managed from the member's own page, not here.
            'users' => User::where('role', '!=', 'member')->orderByDesc('is_active')->orderBy('name')->get(),
            'roles' => User::staffRoles(),
        ]);
    }

    public function create(): View
    {
        return view('users.form', ['user' => new User(['role' => config('rbac.default_role'), 'is_active' => true])]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'role' => $request->validated('role'),
            'password' => $request->validated('password'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('users.index')
            ->with('success', "Account created for {$user->name} as {$user->roleLabel()}.");
    }

    public function edit(User $user): View
    {
        abort_if($user->isMember(), 404);

        return view('users.form', ['user' => $user]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        abort_if($user->isMember(), 404);

        $role = $request->validated('role');
        $active = $request->boolean('is_active');

        if ($error = $this->protectedChange($request->user(), $user, $role, $active)) {
            return back()->withInput()->withErrors(['role' => $error]);
        }

        $data = [
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'role' => $role,
            'is_active' => $active,
        ];

        if (filled($request->validated('password'))) {
            $data['password'] = $request->validated('password');
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', "Account for {$user->name} updated.");
    }

    public function toggleActive(User $user): RedirectResponse
    {
        abort_if($user->isMember(), 404);

        if ($error = $this->protectedChange(request()->user(), $user, $user->role, ! $user->is_active)) {
            return back()->with('error', $error);
        }

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', $user->is_active
            ? "{$user->name} can sign in again."
            : "{$user->name} has been deactivated and can no longer sign in.");
    }

    /**
     * Guard rails so the system can never be left without a working administrator:
     * you cannot change your own role or deactivate yourself, and the last active
     * administrator cannot be demoted or deactivated.
     */
    private function protectedChange(User $actor, User $target, string $newRole, bool $newActive): ?string
    {
        if ($actor->is($target) && ($newRole !== $target->role || ! $newActive)) {
            return 'You cannot change your own role or deactivate your own account. Ask another administrator.';
        }

        $losesAdmin = $target->isAdmin() && $target->is_active && ($newRole !== 'admin' || ! $newActive);

        if ($losesAdmin && User::where('role', 'admin')->where('is_active', true)->whereKeyNot($target->id)->doesntExist()) {
            return 'This is the last active administrator. Create or promote another administrator first.';
        }

        return null;
    }
}
