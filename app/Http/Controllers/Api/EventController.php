<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    /**
     * Public list of active sessions for the marketing site. Pass ?featured=1
     * for the curated homepage set (max 3).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Event::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('starts_at');

        if ($request->boolean('featured')) {
            $query->where('is_featured', true)->limit(3);
        }

        $events = $query->get()->map->toCard()->values();

        return response()->json(['events' => $events])
            ->header('Cache-Control', 'public, max-age=60');
    }
}
