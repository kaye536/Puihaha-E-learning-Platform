@extends('layouts.app')
@section('title', 'Membership Levels')

@section('content')
<div class="page-head">
    <div><h1>Membership Levels</h1><p class="muted">Fees, validity periods and benefits for Basic, Silver, Gold and Platinum.</p></div>
    @can('catalog.manage')
    <a href="{{ route('levels.create') }}" class="btn">+ Add Level</a>
    @endcan
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Name</th><th>Fee</th><th>Validity</th><th>Benefits</th><th>Members</th><th class="right">Actions</th></tr></thead>
            <tbody>
            @forelse ($levels as $level)
                <tr>
                    <td><strong>{{ $level->name }}</strong></td>
                    <td class="nowrap">@money($level->fee)</td>
                    <td class="nowrap">{{ $level->validity_months }} {{ \Illuminate\Support\Str::plural('month', $level->validity_months) }}</td>
                    <td>{{ $level->benefits ?: '—' }}</td>
                    <td>{{ $level->members_count }}</td>
                    <td class="right nowrap">
                        <a href="{{ route('members.index', ['level' => $level->id]) }}" class="btn btn-light btn-sm">Members</a>
                        @can('catalog.manage')
                        <a href="{{ route('levels.edit', $level) }}" class="btn btn-light btn-sm">Edit</a>
                        <form method="POST" action="{{ route('levels.destroy', $level) }}" class="inline"
                              data-confirm="Delete the level '{{ $level->name }}'?">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">No levels yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $levels->links() }}
</div>
@endsection
