<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'locale' => 'required|string',
        ]);

        $query = \App\Models\Translation::query()
            ->where('locale', $request->locale)
            ->select('key', 'content');

        if ($request->has('tags')) {
            $tags = explode(',', $request->tags);
            $query->whereHas('tags', function ($q) use ($tags) {
                $q->whereIn('name', $tags);
            });
        }

        // Efficiently finding the latest update time for the matching records
        // This query must be fast.
        $lastModified = $query->max('updated_at');

        // If no records, just proceed (or return empty with now())
        $lastModifiedTime = $lastModified ? \Carbon\Carbon::parse($lastModified) : now();
        
        // Generate ETag based on locale, tags, and last modification
        // This ensures if data changes, ETag changes.
        // We include query params in hash to differentiate requests.
        $etag = md5($request->fullUrl() . $lastModifiedTime->timestamp);

        // Check for 304 Not Modified
        // Laravel's built-in caching support can handle this somewhat, but explicit control is better here.
        if (trim($request->header('If-None-Match'), '"') === $etag || 
            ($request->header('If-Modified-Since') && \Carbon\Carbon::parse($request->header('If-Modified-Since'))->gte($lastModifiedTime))) {
            return response()->noContent(304);
        }

        // Using pluck for direct key-value pair generation which is fastest for JSON
        $translations = $query->pluck('content', 'key');

        return response()->json($translations)
            ->setEtag($etag)
            ->setLastModified($lastModifiedTime)
            ->header('Cache-Control', 'public, max-age=60, s-maxage=300'); // Cache for 1 min in browser, 5 min in CDN
    }
}
