@extends('layouts.panel')

@php($editing = $item->exists)

@section('title', $editing ? 'Edit '.$t['singular'] : 'Add '.$t['singular'])
@section('subtitle', $t['help'])

@section('content')
    <form method="POST" action="{{ $editing ? route('manage.structure.update', [$t['key'], $item->id]) : route('manage.structure.store', $t['key']) }}" class="card form-card" novalidate>
        @csrf
        @if ($editing) @method('PUT') @endif

        @foreach ($t['fields'] as $name => $f)
            @php($value = old($name, $item->{$name} instanceof \Illuminate\Support\Carbon ? $item->{$name}->toDateString() : $item->{$name}))

            @if ($f['type'] === 'checkbox')
                <label class="check">
                    <input type="checkbox" name="{{ $name }}" value="1" @checked($value)>
                    <span>{{ $f['label'] }}</span>
                </label>
            @else
                <label class="field">
                    <span>{{ $f['label'] }}@if ($f['required'] ?? false) *@endif</span>
                    @switch($f['type'])
                        @case('textarea')
                            <textarea name="{{ $name }}" rows="3" @error($name) aria-invalid="true" @enderror>{{ $value }}</textarea>
                            @break
                        @case('select')
                            <select name="{{ $name }}" @error($name) aria-invalid="true" @enderror>
                                @unless ($f['required'] ?? false)<option value="">—</option>@endunless
                                @foreach (($f['options'])() as $optValue => $optLabel)
                                    <option value="{{ $optValue }}" @selected((string) $value === (string) $optValue)>{{ $optLabel }}</option>
                                @endforeach
                            </select>
                            @break
                        @default
                            <input type="{{ $f['type'] }}" name="{{ $name }}" value="{{ $value }}" @if ($f['required'] ?? false) required @endif @error($name) aria-invalid="true" @enderror>
                    @endswitch
                    @if ($f['hint'] ?? null)<small class="hint">{{ $f['hint'] }}</small>@endif
                </label>
            @endif
            @error($name) <p class="field-error">{{ $message }}</p> @enderror
        @endforeach

        <div class="form-foot">
            <a href="{{ route('manage.structure.index', $t['key']) }}" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-brick">{{ $editing ? 'Save changes' : 'Add '.$t['singular'] }}</button>
        </div>
    </form>
@endsection
