@extends('layouts.app')
@section('title', 'Staff Accounts')

@section('content')
<div class="page-head">
    <div><span class="eyebrow">Access control</span><h1>Staff Accounts</h1><p class="muted">Create accounts and decide what each person is allowed to do.</p></div>
    <a href="{{ route('users.create') }}" class="btn">+ Add Account</a>
</div>

<div class="role-grid">
    @foreach ($roles as $key => $role)
        <div class="role-card">
            <span class="role-pill role-{{ $key }}">{{ $role['label'] }}</span>
            <p>{{ $role['description'] }}</p>
            <small>{{ $users->where('role', $key)->count() }} account(s)</small>
        </div>
    @endforeach
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th class="right">Actions</th></tr></thead>
            <tbody>
            @foreach ($users as $u)
                <tr>
                    <td><strong>{{ $u->name }}</strong>@if ($u->is(auth()->user())) <span class="badge badge-active" style="margin-left:.3rem">You</span>@endif</td>
                    <td>{{ $u->email }}</td>
                    <td><span class="role-pill role-{{ $u->role }}">{{ $u->roleLabel() }}</span></td>
                    <td><x-status-badge :status="$u->is_active ? 'active' : 'inactive'" /></td>
                    <td class="right nowrap">
                        <a href="{{ route('users.edit', $u) }}" class="btn btn-light btn-sm">Edit</a>
                        @unless ($u->is(auth()->user()))
                            <form method="POST" action="{{ route('users.toggle-active', $u) }}" class="inline"
                                  data-confirm="{{ $u->is_active ? 'Deactivate this account? They will be signed out.' : 'Allow this account to sign in again?' }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-warning btn-sm">{{ $u->is_active ? 'Deactivate' : 'Activate' }}</button>
                            </form>
                        @endunless
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="card" style="margin-top:1.4rem">
    <h2>What each role can do</h2>
    <div class="table-wrap">
        <table class="perm-table">
            <thead>
                <tr><th>Permission</th>@foreach ($roles as $key => $role)<th class="center">{{ $role['label'] }}</th>@endforeach</tr>
            </thead>
            <tbody>
            @foreach (collect(config('rbac.permissions'))->reject(fn ($label, $perm) => str_starts_with($perm, 'portal.') || str_starts_with($perm, 'learning.')) as $perm => $label)
                <tr>
                    <td>{{ $label }}</td>
                    @foreach ($roles as $key => $role)
                        <td class="center">
                            @if (in_array('*', $role['permissions'], true) || in_array($perm, $role['permissions'], true))
                                <span class="perm-yes" title="Allowed">&#10003;</span>
                            @else
                                <span class="perm-no" title="Not allowed">&ndash;</span>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
