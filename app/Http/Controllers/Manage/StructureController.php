<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Posting;
use App\Support\StructureTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * One controller for the organisation records HR maintains
 * (divisions, departments, offices, projects, designations).
 * What each type contains is described in App\Support\StructureTypes.
 *
 * URLs: /manage/{type}, /manage/{type}/create, /manage/{type}/{id}/edit, …
 */
class StructureController extends Controller
{
    public function index(Request $request, string $type): View
    {
        $t = StructureTypes::get($type);

        $nameColumn = array_key_exists('title', $t['fields']) ? 'title' : 'name';

        $items = $t['model']::query()
            ->with($t['with'] ?? [])
            ->withCount($t['with_count'] ?? [])
            ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->where($nameColumn, 'like', "%{$term}%"))
            ->orderBy($nameColumn)
            ->paginate(25)
            ->withQueryString();

        // "Staff here now" for offices and projects = current postings
        if ($column = $t['current_count'] ?? null) {
            $counts = Posting::current()->whereIn($column, $items->pluck('id'))
                ->selectRaw("{$column} as place_id, count(*) as total")->groupBy($column)->pluck('total', 'place_id');
            $items->each(fn ($m) => $m->setAttribute('current_count', $counts[$m->id] ?? 0));
        }

        return view('manage.structure.index', ['t' => $t, 'items' => $items, 'types' => StructureTypes::all()]);
    }

    public function create(string $type): View
    {
        $t = StructureTypes::get($type);
        $defaults = ['is_active' => true, 'is_headquarters' => true, 'status' => 'ongoing'];

        return view('manage.structure.form', ['t' => $t, 'item' => new $t['model'](array_intersect_key($defaults, $t['fields']))]);
    }

    public function store(Request $request, string $type): RedirectResponse
    {
        $t = StructureTypes::get($type);
        $item = $t['model']::create($this->validated($request, $t));

        ActivityLog::record("{$t['singular']}.created", "Added {$t['singular']} {$this->label($item)}", $item);

        return redirect()->route('manage.structure.index', $type)->with('status', ucfirst($t['singular'])." \"{$this->label($item)}\" added.");
    }

    public function edit(string $type, int $id): View
    {
        $t = StructureTypes::get($type);

        return view('manage.structure.form', ['t' => $t, 'item' => $t['model']::findOrFail($id)]);
    }

    public function update(Request $request, string $type, int $id): RedirectResponse
    {
        $t = StructureTypes::get($type);
        $item = $t['model']::findOrFail($id);
        $item->update($this->validated($request, $t, $id));

        ActivityLog::record("{$t['singular']}.updated", "Updated {$t['singular']} {$this->label($item)}", $item);

        return redirect()->route('manage.structure.index', $type)->with('status', 'Changes saved.');
    }

    public function destroy(string $type, int $id): RedirectResponse
    {
        $t = StructureTypes::get($type);
        $item = $t['model']::findOrFail($id);

        if ($reason = ($t['blocked_by'])($item)) {
            return back()->withErrors(['account' => "Can't remove \"{$this->label($item)}\": {$reason}"]);
        }

        $item->delete();
        ActivityLog::record("{$t['singular']}.deleted", "Removed {$t['singular']} {$this->label($item)}", $item);

        return back()->with('status', ucfirst($t['singular'])." \"{$this->label($item)}\" removed.");
    }

    private function validated(Request $request, array $t, ?int $id = null): array
    {
        $data = $request->validate(($t['rules'])($id));

        // Unchecked checkboxes are not sent by the browser.
        foreach ($t['fields'] as $name => $field) {
            if ($field['type'] === 'checkbox') {
                $data[$name] = $request->boolean($name);
            }
        }

        return $data;
    }

    private function label(Model $item): string
    {
        return $item->name ?? $item->title ?? "#{$item->id}";
    }
}
