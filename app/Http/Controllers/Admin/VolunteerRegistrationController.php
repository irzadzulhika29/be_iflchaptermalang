<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VolunteerRegistration;
use App\Models\Event;
use Illuminate\Http\Request;

class VolunteerRegistrationController extends Controller
{
    /**
     * Get all volunteer registrations dengan filter
     * GET /api/v1/admin/volunteer-registrations
     */
    public function index(Request $request)
    {
        try {
            $query = VolunteerRegistration::with([
                'user:id,name,email',
                'event:id,title,start_date,end_date'
            ]);

            // Filter by event
            if ($request->has('event_id')) {
                $query->where('event_id', $request->event_id);
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by event year
            if ($request->has('event_year')) {
                $query->where('event_year', $request->event_year);
            }

            // Search by name or email
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('username_instagram', 'like', "%{$search}%")
                        ->orWhere('info_source', 'like', "%{$search}%")
                        ->orWhere('motivation', 'like', "%{$search}%")
                        ->orWhere('experience', 'like', "%{$search}%");
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $registrations = $query->paginate($perPage);

            // Summary statistics
            $stats = [
                'total' => VolunteerRegistration::count(),
                'pending' => VolunteerRegistration::where('status', 'pending')->count(),
                'approved' => VolunteerRegistration::where('status', 'approved')->count(),
                'rejected' => VolunteerRegistration::where('status', 'rejected')->count(),
                'cancelled' => VolunteerRegistration::where('status', 'cancelled')->count(),
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Volunteer registrations retrieved successfully',
                'stats' => $stats,
                'data' => $registrations,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve registrations: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get detail single registration
     * GET /api/v1/admin/volunteer-registrations/{id}
     */
    public function show(string $id)
    {
        try {
            $registration = VolunteerRegistration::with([
                'user:id,name,email,phone_number',
                'event:id,title,start_date,end_date,event_photo'
            ])->find($id);

            if (!$registration) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Registration not found',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Registration detail retrieved successfully',
                'data' => [
                    'registration_id' => $registration->id,
                    'user' => $registration->user,
                    'event' => $registration->event,
                    'name' => $registration->name,
                    'phone_number' => $registration->phone_number,
                    'username_instagram' => $registration->username_instagram,
                    'info_source' => $registration->info_source,
                    'motivation' => $registration->motivation,
                    'experience' => $registration->experience,
                    'has_read_guidebook' => $registration->has_read_guidebook,
                    'is_committed' => $registration->is_committed,
                    'google_drive_link' => $registration->google_drive_link,
                    'status' => $registration->status,
                    'pricing' => [
                        'original_price' => $registration->original_price,
                        'discount_amount' => $registration->discount_amount,
                        'final_price' => $registration->final_price,
                        'referral_code_used' => $registration->referral_code_used,
                        'has_discount' => $registration->discount_amount > 0,
                    ],
                    'submitted_at' => $registration->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $registration->updated_at->format('Y-m-d H:i:s'),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve registration: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update status registration (approve/reject)
     * PATCH /api/v1/admin/volunteer-registrations/{id}/status
     */
    public function updateStatus(Request $request, string $id)
    {
        try {
            $registration = VolunteerRegistration::find($id);

            if (!$registration) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Registration not found',
                ], 404);
            }

            $request->validate([
                'status' => 'required|in:pending,approved,rejected,cancelled',
            ]);

            $registration->update([
                'status' => $request->status,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Registration status updated successfully',
                'data' => [
                    'registration_id' => $registration->id,
                    'name' => $registration->name,
                    'status' => $registration->status,
                    'updated_at' => $registration->updated_at->format('Y-m-d H:i:s'),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk update status
     * POST /api/v1/admin/volunteer-registrations/bulk-update-status
     */
    public function bulkUpdateStatus(Request $request)
    {
        try {
            $request->validate([
                'registration_ids' => 'required|array',
                'registration_ids.*' => 'uuid|exists:volunteer_registrations,id',
                'status' => 'required|in:pending,approved,rejected,cancelled',
            ]);

            $updated = VolunteerRegistration::whereIn('id', $request->registration_ids)
                ->update(['status' => $request->status]);

            return response()->json([
                'status' => 'success',
                'message' => "{$updated} registrations updated successfully",
                'data' => [
                    'updated_count' => $updated,
                    'new_status' => $request->status,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to bulk update: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get registrations by event
     * GET /api/v1/admin/events/{eventId}/volunteer-registrations
     */
    public function getByEvent(Request $request, string $eventId)
    {
        try {
            $event = Event::find($eventId);

            if (!$event) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Event not found',
                ], 404);
            }

            $query = VolunteerRegistration::with('user:id,name,email')
                ->where('event_id', $eventId);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $registrations = $query->orderBy('created_at', 'desc')->get();

            // Stats per event
            $stats = [
                'total' => $registrations->count(),
                'pending' => $registrations->where('status', 'pending')->count(),
                'approved' => $registrations->where('status', 'approved')->count(),
                'rejected' => $registrations->where('status', 'rejected')->count(),
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Event registrations retrieved successfully',
                'event' => [
                    'id' => $event->id,
                    'title' => $event->title,
                    'start_date' => $event->start_date?->format('Y-m-d'),
                ],
                'stats' => $stats,
                'data' => $registrations,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve registrations: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export registrations to CSV (optional, bisa untuk download)
     * GET /api/v1/admin/volunteer-registrations/export
     */
    public function export(Request $request)
    {
        try {
            $query = VolunteerRegistration::with(['user', 'event']);

            if ($request->has('event_id')) {
                $query->where('event_id', $request->event_id);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $registrations = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Export data retrieved',
                'data' => $registrations,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to export: ' . $e->getMessage(),
            ], 500);
        }
    }
}
