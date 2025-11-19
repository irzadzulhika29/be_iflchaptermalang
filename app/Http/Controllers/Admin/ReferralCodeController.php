<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReferralCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReferralCodeController extends Controller
{
    /**
     * Get all referral codes
     * GET /api/v1/admin/referral-codes
     */
    public function index(Request $request)
    {
        $query = ReferralCode::query();

        // Filter by event year
        if ($request->has('event_year')) {
            $query->where('event_year', $request->event_year);
        }

        // Filter by status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search by code or description
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $referralCodes = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $referralCodes->map(function ($code) {
                return [
                    'id' => $code->id,
                    'code' => $code->code,
                    'description' => $code->description,
                    'discount_type' => $code->discount_type,
                    'discount_value' => $code->discount_value,
                    'discount_text' => $code->discount_text,
                    'max_usage' => $code->max_usage,
                    'used_count' => $code->used_count,
                    'remaining_usage' => $code->remaining_usage,
                    'is_active' => $code->is_active,
                    'is_valid' => $code->isValid(),
                    'valid_from' => $code->valid_from?->format('Y-m-d H:i:s'),
                    'valid_until' => $code->valid_until?->format('Y-m-d H:i:s'),
                    'event_name' => $code->event_name,
                    'event_year' => $code->event_year,
                    'created_at' => $code->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $code->updated_at->format('Y-m-d H:i:s'),
                ];
            }),
        ]);
    }

    /**
     * Get single referral code
     * GET /api/v1/admin/referral-codes/{id}
     */
    public function show(string $id)
    {
        $referralCode = ReferralCode::with(['usedByRegistrations' => function ($query) {
            $query->select('id', 'name', 'email', 'referral_code_used', 'discount_amount', 'created_at')
                ->orderBy('created_at', 'desc');
        }])->find($id);

        if (!$referralCode) {
            return response()->json([
                'status' => 'error',
                'message' => 'Referral code not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $referralCode->id,
                'code' => $referralCode->code,
                'description' => $referralCode->description,
                'discount_type' => $referralCode->discount_type,
                'discount_value' => $referralCode->discount_value,
                'discount_text' => $referralCode->discount_text,
                'max_usage' => $referralCode->max_usage,
                'used_count' => $referralCode->used_count,
                'remaining_usage' => $referralCode->remaining_usage,
                'is_active' => $referralCode->is_active,
                'is_valid' => $referralCode->isValid(),
                'valid_from' => $referralCode->valid_from,
                'valid_until' => $referralCode->valid_until,
                'event_name' => $referralCode->event_name,
                'event_year' => $referralCode->event_year,
                'created_at' => $referralCode->created_at,
                'updated_at' => $referralCode->updated_at,
                'used_by' => $referralCode->usedByRegistrations,
            ],
        ]);
    }

    /**
     * Create new referral code
     * POST /api/v1/admin/referral-codes
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => ['required', 'string', 'max:50', 'unique:referral_codes,code'],
            'description' => ['nullable', 'string', 'max:255'],
            'discount_type' => ['required', 'in:percentage,fixed'],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'max_usage' => ['nullable', 'integer', 'min:1'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after:valid_from'],
            'is_active' => ['boolean'],
            'event_name' => ['nullable', 'string', 'max:255'],
            'event_year' => ['nullable', 'integer', 'min:2024'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->messages(),
            ], 422);
        }

        // Validasi discount value
        if ($request->discount_type === 'percentage' && $request->discount_value > 100) {
            return response()->json([
                'status' => 'error',
                'message' => 'Discount percentage tidak boleh lebih dari 100%',
            ], 422);
        }

        $referralCode = ReferralCode::create([
            'code' => strtoupper(trim($request->code)),
            'description' => $request->description,
            'discount_type' => $request->discount_type,
            'discount_value' => $request->discount_value,
            'max_usage' => $request->max_usage,
            'valid_from' => $request->valid_from,
            'valid_until' => $request->valid_until,
            'is_active' => $request->is_active ?? true,
            'event_name' => $request->event_name ?? 'Close the Gap IFL Chapter Malang 2025',
            'event_year' => $request->event_year ?? 2025,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Referral code created successfully',
            'data' => $referralCode,
        ], 201);
    }

    /**
     * Update referral code
     * PUT/PATCH /api/v1/admin/referral-codes/{id}
     */
    public function update(Request $request, string $id)
    {
        $referralCode = ReferralCode::find($id);

        if (!$referralCode) {
            return response()->json([
                'status' => 'error',
                'message' => 'Referral code not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'code' => ['sometimes', 'string', 'max:50', 'unique:referral_codes,code,' . $id],
            'description' => ['nullable', 'string', 'max:255'],
            'discount_type' => ['sometimes', 'in:percentage,fixed'],
            'discount_value' => ['sometimes', 'numeric', 'min:0'],
            'max_usage' => ['nullable', 'integer', 'min:1'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date'],
            'is_active' => ['boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->messages(),
            ], 422);
        }

        // Validasi discount value
        if (
            $request->has('discount_type') && $request->discount_type === 'percentage'
            && $request->has('discount_value') && $request->discount_value > 100
        ) {
            return response()->json([
                'status' => 'error',
                'message' => 'Discount percentage tidak boleh lebih dari 100%',
            ], 422);
        }

        $updateData = $request->only([
            'code',
            'description',
            'discount_type',
            'discount_value',
            'max_usage',
            'valid_from',
            'valid_until',
            'is_active',
        ]);

        // Uppercase code if provided
        if (isset($updateData['code'])) {
            $updateData['code'] = strtoupper(trim($updateData['code']));
        }

        $referralCode->update($updateData);

        return response()->json([
            'status' => 'success',
            'message' => 'Referral code updated successfully',
            'data' => $referralCode->fresh(),
        ]);
    }

    /**
     * Delete referral code
     * DELETE /api/v1/admin/referral-codes/{id}
     */
    public function destroy(string $id)
    {
        $referralCode = ReferralCode::find($id);

        if (!$referralCode) {
            return response()->json([
                'status' => 'error',
                'message' => 'Referral code not found',
            ], 404);
        }

        // Cek apakah sudah digunakan
        if ($referralCode->used_count > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak dapat menghapus kode referral yang sudah digunakan. Silakan nonaktifkan saja.',
            ], 400);
        }

        $referralCode->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Referral code deleted successfully',
        ]);
    }

    /**
     * Toggle active status
     * PATCH /api/v1/admin/referral-codes/{id}/toggle-active
     */
    public function toggleActive(string $id)
    {
        $referralCode = ReferralCode::find($id);

        if (!$referralCode) {
            return response()->json([
                'status' => 'error',
                'message' => 'Referral code not found',
            ], 404);
        }

        $referralCode->update(['is_active' => !$referralCode->is_active]);

        return response()->json([
            'status' => 'success',
            'message' => 'Referral code status updated',
            'data' => [
                'id' => $referralCode->id,
                'code' => $referralCode->code,
                'is_active' => $referralCode->is_active,
            ],
        ]);
    }
}
