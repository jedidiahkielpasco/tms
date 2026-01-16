<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TranslationController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\Translation::query();

        if ($request->has('tag')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('name', $request->tag);
            });
        }

        if ($request->has('key')) {
            $query->where('key', 'like', '%' . $request->key . '%');
        }

        if ($request->has('content')) {
            $query->where('content', 'like', '%' . $request->content . '%');
        }
        
        if ($request->has('locale')) {
             $query->where('locale', $request->locale);
        }

        return $query->paginate(50);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'locale' => 'required|string|max:10',
            'key' => 'required|string', // Unique constraint could be added but might be complex with locale
            'content' => 'required|string',
            'tags' => 'array',
            'tags.*' => 'exists:tags,name'
        ]);

        $translation = \App\Models\Translation::create($validated);

        if (isset($validated['tags'])) {
            $tags = \App\Models\Tag::whereIn('name', $validated['tags'])->get();
            $translation->tags()->sync($tags);
        }

        return response()->json($translation, 201);
    }

    public function show($id)
    {
        return \App\Models\Translation::with('tags')->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $translation = \App\Models\Translation::findOrFail($id);

        $validated = $request->validate([
            'locale' => 'sometimes|string|max:10',
            'key' => 'sometimes|string',
            'content' => 'sometimes|string',
            'tags' => 'array',
            'tags.*' => 'exists:tags,name'
        ]);

        $translation->update($validated);

        if (isset($validated['tags'])) {
            $tags = \App\Models\Tag::whereIn('name', $validated['tags'])->get();
            $translation->tags()->sync($tags);
        }

        return response()->json($translation);
    }
}
