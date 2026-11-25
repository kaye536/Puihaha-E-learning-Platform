@extends('layouts.app')
@section('title', 'Members')

@section('content')
<div class="page-head">
    <div><h1>Members</h1><p class="muted">Search and manage registered members.</p></div>
    @can('members.manage')
    <a href="{{ route('members.create') }}" class="btn">+ Register Member</a>
    @endcan
</div>

<div class="card">
    <form method="GET" action="{{ route('members.index') }}" class="filters">
        <div class="form-group">
            <label for="q">Search</label>
            <input type="text" id="q" name="q" value="{{ request('q') }}" placeholder="Name, membership no. or email">
        </div>
        <div class="form-group">
            <label for="category">Category</label>
            <select id="category" name="category">
                <option value="">All</option>
                @foreach ($categories as $c)
                    <option value="{{ $c->id }}" @selected(request('category') == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="level">Level</label>
            <select id="level" name="level">
                <option value="">All</option>
                @foreach ($levels as $l)
                    <option value="{{ $l->id }}" @selected(request('level') == $l->id)>{{ $l->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">All</option>
                @foreach (\App\Models\Member::STATUSES as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <div class="actions">
            <button type="submit" class="btn">Filter</button>
            <a href="{{ route('members.index') }}" class="btn btn-light">Reset</a>
        </div>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
            <tr><th>Membership No.</th><th>Name</th><th>Category</th><th>Level</th><th>Expiry</th><th>Status</th><th class="right">Actions</th></tr>
            </thead>
            <tbody>
            @forelse ($members as $member)
                <tr>
                    <td class="mono nowrap">{{ $member->membership_number }}</td>
                    <td><a href="{{ route('members.show', $member) }}">{{ $member->full_name }}</a><div class="small muted">{{ $member->email }}</div></td>
                    <td>{{ $member->category->name }}</td>
                    <td>{{ $member->level->name }}</td>
                    <td class="nowrap">{{ $member->membership_expiry?->format('M d, Y') ?? '—' }}</td>
                    <td><x-status-badge :status="$member->status" /></td>
                    <td class="right nowrap">
                        <a href="{{ route('members.show', $member) }}" class="btn btn-light btn-sm">View</a>
                        @can('members.manage')
                        <a href="{{ route('members.edit', $member) }}" class="btn btn-light btn-sm">Edit</a>
                        <form method="POST" action="{{ route('members.toggle-status', $member) }}" class="inline"
                              data-confirm="{{ $member->isInactive() ? 'Re-activate this member?' : 'Deactivate this member?' }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-warning btn-sm">{{ $member->isInactive() ? 'Activate' : 'Deactivate' }}</button>
                        </form>
                        <form method="POST" action="{{ route('members.destroy', $member) }}" class="inline"
                              data-confirm="Permanently delete this member? This cannot be undone.">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty">No members found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $members->links() }}
</div>
@endsection
