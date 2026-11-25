@extends('layouts.app')
@section('title', 'Membership Categories')

@section('content')
<div class="page-head">
    <div><h1>Membership Categories</h1><p class="muted">Student, Regular, Senior Citizen, Corporate and more.</p></div>
    @can('catalog.manage')
    <a href="{{ route('categories.create') }}" class="btn">+ Add Category</a>
    @endcan
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Name</th><th>Description</th><th>Members</th><th class="right">Actions</th></tr></thead>
            <tbody>
            @forelse ($categories as $category)
                <tr>
                    <td><strong>{{ $category->name }}</strong></td>
                    <td>{{ $category->description ?: '—' }}</td>
                    <td>{{ $category->members_count }}</td>
                    <td class="right nowrap">
                        <a href="{{ route('members.index', ['category' => $category->id]) }}" class="btn btn-light btn-sm">Members</a>
                        @can('catalog.manage')
                        <a href="{{ route('categories.edit', $category) }}" class="btn btn-light btn-sm">Edit</a>
                        <form method="POST" action="{{ route('categories.destroy', $category) }}" class="inline"
                              data-confirm="Delete the category '{{ $category->name }}'?">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="empty">No categories yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $categories->links() }}
</div>
@endsection
