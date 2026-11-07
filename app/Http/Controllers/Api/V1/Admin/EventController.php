<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function toggleFeatured(Request $request, Event $event): JsonResponse
    {
        $this->authorize('markAsFeatured', $event);

        $isFeatured = $request->has('is_featured')
            ? $request->boolean('is_featured')
            : ! $event->is_featured;

        $event->update(['is_featured' => $isFeatured]);

        return response()->json([
            'message' => $isFeatured ? 'تم تمييز الفعالية بنجاح.' : 'تمت إزالة تمييز الفعالية بنجاح.',
            'data' => new EventResource($event->load(['organizer', 'venue', 'images'])),
        ]);
    }
}
