<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Tags\Tag;

class TagController extends Controller
{
    /**
     * Get all available tags for monitors
     */
    public function index()
    {
        $tags = Tag::all(['id', 'name', 'type']);

        return response()->json([
            'tags' => $tags->map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'type' => $tag->type,
                ];
            }),
        ]);
    }

    /**
     * Search tags by name
     */
    public function search(Request $request)
    {
        $search = $request->input('search', '');

        $tags = Tag::where('name', 'like', "%{$search}%")
            ->limit(10)
            ->get(['id', 'name', 'type']);

        return response()->json([
            'tags' => $tags->map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'type' => $tag->type,
                ];
            }),
        ]);
    }
}
