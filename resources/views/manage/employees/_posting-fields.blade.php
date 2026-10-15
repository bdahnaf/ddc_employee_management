{{--
    Posting fields shared by "Add employee" and the Transfer dialog.
    Needs $offices and $projects (loaded by a view composer), $startName, optional $withRemarks.
--}}
@php
    $offices ??= \App\Models\Office::orderBy('name')->get();
    $projects ??= \App\Models\Project::orderBy('name')->get();
    $type = old('posting_type', 'hq');
@endphp

<fieldset class="field">
    <legend>Posted at</legend>
    <div class="seg" data-posting-type>
        <label><input type="radio" name="posting_type" value="hq" @checked($type === 'hq')><span>Head Office</span></label>
        <label><input type="radio" name="posting_type" value="project" @checked($type === 'project')><span>Project</span></label>
    </div>
</fieldset>
@error('posting_type') <p class="field-error">{{ $message }}</p> @enderror

<label class="field" data-show-for="hq" @if ($type !== 'hq') hidden @endif>
    <span>Office</span>
    <select name="office_id">
        @foreach ($offices as $o)
            <option value="{{ $o->id }}" @selected((int) old('office_id', $offices->firstWhere('is_headquarters', true)?->id) === $o->id)>{{ $o->name }}</option>
        @endforeach
    </select>
    @if ($offices->isEmpty())<small class="hint">No offices yet. Add one under Manage → Head Office.</small>@endif
</label>
@error('office_id') <p class="field-error">{{ $message }}</p> @enderror

<label class="field" data-show-for="project" @if ($type !== 'project') hidden @endif>
    <span>Project</span>
    <select name="project_id">
        <option value="">Choose a project…</option>
        @foreach ($projects as $p)
            <option value="{{ $p->id }}" @selected((int) old('project_id') === $p->id)>{{ $p->name }}</option>
        @endforeach
    </select>
</label>
@error('project_id') <p class="field-error">{{ $message }}</p> @enderror

<div class="field-pair">
    <label class="field">
        <span>Starts on</span>
        <input type="date" name="{{ $startName }}" value="{{ old($startName, now()->toDateString()) }}" required>
    </label>
    <label class="field">
        <span>Office order no. <em>(optional)</em></span>
        <input type="text" name="order_ref" value="{{ old('order_ref') }}" maxlength="60">
    </label>
</div>
@error($startName) <p class="field-error">{{ $message }}</p> @enderror

<label class="field">
    <span>Role at this posting <em>(optional)</em></span>
    <input type="text" name="role_on_posting" value="{{ old('role_on_posting') }}" maxlength="120" placeholder="e.g. Team Leader, Site Engineer">
</label>

@if ($withRemarks ?? false)
    <label class="field">
        <span>Remarks <em>(optional)</em></span>
        <textarea name="remarks" rows="2" maxlength="1000">{{ old('remarks') }}</textarea>
    </label>
@endif
