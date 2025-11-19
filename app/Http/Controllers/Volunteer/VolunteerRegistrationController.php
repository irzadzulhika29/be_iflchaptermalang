<?php

namespace App\Http\Controllers\Volunteer;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\VolunteerRegistration;
use App\Models\User;
use App\Models\ReferralCode;

class VolunteerRegistrationController extends Controller
{
    /**
     * Get profile data untuk pre-filling form (chat form style)
     * Endpoint: GET /api/v1/volunteer/registration/form-data
     */
    public function getFormData()
    {
        try {
            $user = auth()->user();

            // Ambil data dari profile user yang akan digunakan untuk pre-fill form
            // Sesuai dengan field yang ada di chat form
            $formData = [
                'profile_data' => [
                    'email' => $user->email,
                    'name' => $user->name, // Nama Lengkap
                    'phone_number' => $user->phone_number, // No HP (WhatsApp)
                ],
                // Field yang perlu diisi user (tidak ada di profile)
                'required_fields' => [
                    'university' => 'Asal Universitas',
                    'line_id' => 'ID Line',
                    'choice_1' => 'Pilihan 1',
                    'choice_2' => 'Pilihan 2',
                    'google_drive_link' => 'Link Folder Google Drive Berkas Pendaftaran',
                ],
                'message' => 'Gunakan data profile_data untuk pre-fill form chat, user harus mengisi required_fields'
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Profile data retrieved successfully',
                'data' => $formData,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve profile data: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getAvailableEvents()
    {
        try {
            $events = Event::where('status', 'open')
                ->where('category', 'program')
                ->select('id', 'title', 'start_date', 'end_date', 'description', 'event_photo')
                ->orderBy('start_date', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Available events retrieved successfully',
                'data' => $events->map(function ($event) {
                    return [
                        'id' => $event->id,
                        'title' => $event->title,
                        'start_date' => $event->start_date?->format('Y-m-d'),
                        'end_date' => $event->end_date?->format('Y-m-d'),
                        'description' => $event->description,
                        'event_photo' => $event->event_photo,
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve events: ' . $e->getMessage(),
            ], 500);
        }
    }



    /**
     * Submit volunteer registration
     * Endpoint: POST /api/v1/volunteer/registration
     */
    public function register(Request $request)
    {
        try {
            $user = auth()->user();

            $rules = [
                'event_id' => ['required', 'uuid', 'exists:events,id'], // TAMBAH INI
                'name' => ['required', 'string', 'max:255'],
                'phone_number' => ['required', 'string', 'max:20'],
                'username_instagram' => ['required', 'string', 'max:100'],
                'info_source' => ['required', 'string', 'max:255'],
                'motivation' => ['required', 'string', 'max:255'],
                'experience' => ['required', 'string', 'max:255'],
                'has_read_guidebook' => ['required', 'boolean'],
                'is_committed' => ['required', 'boolean'],
                'google_drive_link' => ['required', 'url', 'max:500'],
                'referral_code' => ['nullable', 'string', 'max:50'],
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->messages(),
                ], 422);
            }

            $event = Event::find($request->event_id);
            if (!$event || $event->status !== 'open') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Event not found or not open for registration',
                ], 404);
            }

            // Cek apakah user sudah pernah mendaftar untuk event ini
            $existingRegistration = VolunteerRegistration::where('user_id', $user->id)
                ->where('event_name', 'Close the Gap IFL Chapter Malang 2025')
                ->where('event_year', 2025)
                ->where('status', '!=', VolunteerRegistration::STATUS_CANCELLED)
                ->first();

            if ($existingRegistration) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda sudah terdaftar untuk event ini',
                    'data' => [
                        'registration_id' => $existingRegistration->id,
                        'status' => $existingRegistration->status,
                    ]
                ], 409);
            }

            // Mulai database transaction
            DB::beginTransaction();

            try {
                $originalPrice = 75000;
                $discountAmount = 0;
                $referralCodeUsed = null;
                $referralCodeObject = null;

                if ($request->filled('referral_code')) {
                    $inputCode = strtoupper(trim($request->input('referral_code')));

                    $referralCodeObject = ReferralCode::where('code', $inputCode)
                        ->where('event_id', $event->id)
                        ->first();

                    if (!$referralCodeObject) {
                        DB::rollBack();
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Kode referral tidak valid atau sudah tidak berlaku',
                            'data' => [
                                'invalid_reason' => $referralCodeObject->getInvalidReason(),
                            ]
                        ], 400);
                    }

                    if (!$referralCodeObject->isValid()) {
                        DB::rollBack();
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Kode referral tidak valid atau sudah tidak berlaku',
                            'data' => [
                                'invalid_reason' => $referralCodeObject->getInvalidReason(),
                            ]
                        ], 400);
                    }

                    $discountAmount = $referralCodeObject->calculateDiscountAmount($originalPrice);
                    $referralCodeUsed = $referralCodeObject->code;
                }

                $finalPrice = $originalPrice - $discountAmount;
                // Buat volunteer registration dengan data sesuai form chat
                $registration = VolunteerRegistration::create([
                    'user_id' => $user->id,
                    'event_id' => $event->id,
                    'name' => $request->input('name', $user->name),
                    'phone_number' => $request->input('phone_number', $user->phone_number),
                    'username_instagram' => $request->input('username_instagram'),
                    'info_source' => $request->input('info_source'),
                    'motivation' => $request->input('motivation'),
                    'experience' => $request->input('experience'),
                    'has_read_guidebook' => $request->input('has_read_guidebook'),
                    'is_committed' => $request->input('is_committed'),
                    'google_drive_link' => $request->input('google_drive_link'),
                    'status' => VolunteerRegistration::STATUS_PENDING,
                    'event_name' => $event->title,
                    'event_year' => $event->start_date ? $event->start_date->format('Y') : date('Y'),
                    'referral_code_used' => $referralCodeUsed,
                    'discount_amount' => $discountAmount,
                    'original_price' => $originalPrice,
                    'final_price' => $finalPrice,
                ]);

                if ($referralCodeUsed && $referralCodeObject) {
                    $referralCodeObject->incrementUsedCount();
                }

                // Update user profile jika ada perubahan data (email, name, phone_number)
                $userUpdated = false;
                $updateData = [];

                if ($request->has('name') && $request->input('name') !== $user->name) {
                    $updateData['name'] = $request->input('name');
                    $userUpdated = true;
                }

                if ($request->has('phone_number') && $request->input('phone_number') !== $user->phone_number) {
                    $updateData['phone_number'] = $request->input('phone_number');
                    $userUpdated = true;
                }

                // Note: Email biasanya tidak diupdate karena sudah unik dan terverifikasi

                if ($userUpdated) {
                    $user->update($updateData);
                }

                DB::commit();

                $registration->load('event:id,title,start_date');

                return response()->json([
                    'status' => 'success',
                    'message' => 'Volunteer registration submitted successfully',
                    'data' => [
                        'registration_id' => $registration->id,
                        'event' => [
                            'id' => $registration->event->id,
                            'title' => $registration->event->title,
                            'start_date' => $registration->event->start_date->format('Y-m-d'),
                        ],
                        'name' => $registration->name,
                        'phone_number' => $registration->phone_number,
                        'username_instagram' => $registration->username_instagram,
                        'info_source' => $registration->info_source,
                        'motivation' => $registration->motivation,
                        'experience' => $registration->experience,
                        'has_read_guidebook' => $registration->has_read_guidebook,
                        'is_committed' => $registration->is_committed,
                        'status' => $registration->status,
                        'pricing' => [
                            'original_price' => $registration->original_price,
                            'discount_amount' => $registration->discount_amount,
                            'final_price' => $registration->final_price,
                            'referral_code_used' => $registration->referral_code_used,
                            'has_discount' => $registration->discount_amount > 0,
                        ],
                        'submitted_at' => $registration->created_at->format('Y-m-d H:i:s'),
                    ],
                ], 201);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to submit registration: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get registration status by user
     * Endpoint: GET /api/v1/volunteer/registration/my-registration
     */
    public function getMyRegistration(Request $request)
    {
        try {
            $user = auth()->user();

            $query = VolunteerRegistration::where('user_id', $user->id)
                ->with('event:id,title,start_date,end_date,event_photo');

            if ($request->has('event_id')) {
                $query->where('event_id', $request->event_id);
            }

            $registration = $query->orderBy('created_at', 'desc')->first();

            if (!$registration) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'No registration found',
                    'data' => null,
                ], 200);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Registration retrieved successfully',
                'data' => [
                    'registration_id' => $registration->id,
                    'event' => $registration->event,
                    'name' => $registration->name,
                    'phone_number' => $registration->phone_number,
                    'username_instagram' => $registration->username_instagram,
                    'info_source' => $registration->info_source,
                    'motivation' => $registration->motivation,
                    'experience' => $registration->experience,
                    'has_read_guidebook' => $registration->has_read_guidebook,
                    'is_committed' => $registration->is_committed,
                    'status' => $registration->status,
                    'pricing' => [
                        'original_price' => $registration->original_price,
                        'discount_amount' => $registration->discount_amount,
                        'final_price' => $registration->final_price,
                        'referral_code_used' => $registration->referral_code_used,
                        'has_discount' => $registration->discount_amount > 0,
                    ],
                    'submitted_at' => $registration->created_at->format('Y-m-d H:i:s'),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve registration: ' . $e->getMessage(),
            ], 500);
        }
    }
}
