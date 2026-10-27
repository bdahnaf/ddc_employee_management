@extends('layouts.panel')

@section('title', $t['title'])
@section('subtitle', $t['help'])

@section('actions')
    <a href="{{ route('manage.structure.create', $t['key']) }}" class="btn btn-brick">Add {{ $t['singular'] }}</a>
@endsection

@section('content')
    <nav class="subtabs" aria-label="Organisation">
        @foreach ($types as $key => $type)
            <a href="{{ route('manage.structure.index', $key) }}" @class(['is-on' => $key === $t['key']])>{{ $type['title'] }}</a>
        @endforeach
    </nav>

    <form method="GET" class="filters" role="search">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search {{ strtolower($t['title']) }}" aria-label="Search">
        <button type="submit" class="btn btn-ghost">Search</button>
        @if (request('q'))<a href="{{ route('manage.structure.index', $t['key']) }}" class="link">Clear</a>@endif
    </form>

    <div class="card table-card">
        <table class="table">
            <thead>
                <tr>
                    @foreach ($t['columns'] as $label)<th>{{ $label }}</th>@endforeach
                    <th><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    <tr>
                        @foreach ($t['columns'] as $col => $label)
                            @php($value = data_get($item, $col))
                            <td>
                                @if ($loop->first && $t['directory'])
                                    <a href="{{ route('directory', ['place' => $t['directory'].':'.$item->id]) }}" class="strong-link" title="See staff">{{ $value }}</a>
                                @elseif ($col === 'is_active')
                                    <span class="state state-{{ $value ? 'on' : 'off' }}">{{ $value ? 'Active' : 'Inactive' }}</span>
                                @elseif ($col === 'status')
                                    <span class="chip chip-{{ $value }}">{{ \App\Models\Project::STATUSES[$value] ?? $value }}</span>
                                @else
                                    {{ $value ?? '—' }}
                                @endif
                            </td>
                        @endforeach
                        <td class="row-actions">
                            <a href="{{ route('manage.structure.edit', [$t['key'], $item->id]) }}" class="link">Edit</a>
                            <form method="POST" action="{{ route('manage.structure.destroy', [$t['key'], $item->id]) }}" data-confirm="Remove {{ $item->name ?? $item->title }}?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="link link-danger">Remove</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ count($t['columns']) + 1 }}" class="empty">
                        No {{ strtolower($t['title']) }} yet. <a href="{{ route('manage.structure.create', $t['key']) }}" class="link">Add the first one</a>.
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $items->links('partials.pagination') }}
@endsection
