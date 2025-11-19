<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\Controller;
use App\Models\Sdg;
use Illuminate\Http\Request;

class SdgController extends Controller
{
    public function index()
    {

        try {
            $sdgs = Sdg::orderBy('sort_order', 'asc')
                ->orderBy('name', 'asc')
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Get all sdgs success',
                'data' => $sdgs
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $sdg = Sdg::with(['events' => function ($query) {
                $query->where('status', 'open')
                    ->select('events.id', 'events.title', 'events.start_date', 'events.event_photo');
            }])->find($id);

            if (!$sdg) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'SDG not found',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'SDG retrieved successfully',
                'data' => $sdg,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve SDG: ' . $e->getMessage(),
            ], 500);
        }
    }
}
