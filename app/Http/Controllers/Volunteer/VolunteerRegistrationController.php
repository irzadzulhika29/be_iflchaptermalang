<?php

namespace App\Http\Controllers\Volunteer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\VolunteerRegistration;
use App\Models\User;

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

    /**
     * Submit volunteer registration
     * Endpoint: POST /api/v1/volunteer/registration
     */
    public function register(Request $request)
    {
        try {
            $user = auth()->user();

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

            // Validasi data yang dikirim sesuai form chat
            $rules = [
                // Data dari profile (pre-filled, bisa diubah user)
                'email' => ['required', 'email', 'max:255'],
                'name' => ['required', 'string', 'max:255'], // Nama Lengkap
                'phone_number' => ['required', 'string', 'max:20'], // No HP (WhatsApp) - required untuk chat form
                
                // Data input baru user (wajib diisi)
                'university' => ['required', 'string', 'max:255'], // Asal Universitas
                'line_id' => ['required', 'string', 'max:100'], // ID Line
                'choice_1' => ['required', 'string', 'max:255'], // Pilihan 1
                'choice_2' => ['required', 'string', 'max:255'], // Pilihan 2
                'google_drive_link' => ['required', 'url', 'max:500'], // Link Folder Google Drive - harus URL valid
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->messages(),
                ], 422);
            }

            // Mulai database transaction
            DB::beginTransaction();

            try {
                // Buat volunteer registration dengan data sesuai form chat
                $registration = VolunteerRegistration::create([
                    'user_id' => $user->id,
                    'email' => $request->input('email', $user->email),
                    'name' => $request->input('name', $user->name),
                    'phone_number' => $request->input('phone_number', $user->phone_number),
                    'university' => $request->input('university'),
                    'line_id' => $request->input('line_id'),
                    'choice_1' => $request->input('choice_1'),
                    'choice_2' => $request->input('choice_2'),
                    'google_drive_link' => $request->input('google_drive_link'),
                    'status' => VolunteerRegistration::STATUS_PENDING,
                    'event_name' => 'Close the Gap IFL Chapter Malang 2025',
                    'event_year' => 2025,
                ]);

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

                return response()->json([
                    'status' => 'success',
                    'message' => 'Volunteer registration submitted successfully',
                    'data' => [
                        'registration_id' => $registration->id,
                        'email' => $registration->email,
                        'name' => $registration->name,
                        'phone_number' => $registration->phone_number,
                        'university' => $registration->university,
                        'line_id' => $registration->line_id,
                        'choice_1' => $registration->choice_1,
                        'choice_2' => $registration->choice_2,
                        'status' => $registration->status,
                        'event_name' => $registration->event_name,
                        'event_year' => $registration->event_year,
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
    public function getMyRegistration()
    {
        try {
            $user = auth()->user();

            $registration = VolunteerRegistration::where('user_id', $user->id)
                ->where('event_name', 'Close the Gap IFL Chapter Malang 2025')
                ->where('event_year', 2025)
                ->orderBy('created_at', 'desc')
                ->first();

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
                'data' => $registration,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve registration: ' . $e->getMessage(),
            ], 500);
        }
    }
}

