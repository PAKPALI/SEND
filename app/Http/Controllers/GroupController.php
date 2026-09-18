<?php

namespace App\Http\Controllers;

use App\Models\ContactGroup;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function index(Request $request)
    {
        $groups = $request->user()->groups()->withCount('contacts')->with('contacts:id,name,phone')->latest()->get();
        $contacts = $request->user()->contacts()->orderBy('name')->get();
        return view('groups.index', compact('groups', 'contacts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100'], 'description' => ['nullable', 'string', 'max:180'], 'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'], 'contacts' => ['array'], 'contacts.*' => ['integer']]);
        $group = $request->user()->groups()->create(collect($data)->only(['name', 'description', 'color'])->all());
        $group->contacts()->sync($request->user()->contacts()->whereIn('id', $data['contacts'] ?? [])->pluck('id'));
        return back()->with('success', 'Groupe créé.');
    }

    public function update(Request $request, ContactGroup $group)
    {
        abort_unless($group->user_id === $request->user()->id, 403);
        $data = $request->validate(['name' => ['required', 'string', 'max:100'], 'description' => ['nullable', 'string', 'max:180'], 'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'], 'contacts' => ['array'], 'contacts.*' => ['integer']]);
        $group->update(collect($data)->only(['name', 'description', 'color'])->all());
        $group->contacts()->sync($request->user()->contacts()->whereIn('id', $data['contacts'] ?? [])->pluck('id'));
        return back()->with('success', 'Groupe mis à jour.');
    }

    public function destroy(Request $request, ContactGroup $group)
    {
        abort_unless($group->user_id === $request->user()->id, 403);
        if ($group->campaigns()->exists()) return back()->withErrors(['group' => 'Ce groupe est utilisé par une campagne et ne peut pas être supprimé.']);
        $group->delete();
        return back()->with('success', 'Groupe supprimé.');
    }
}
