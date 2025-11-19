<?php

namespace App\Http\Controllers\Volunteer;

use App\Http\Controllers\Controller;
use App\Models\ReferralCode;

class ReferralCodeController extends Controller
{
    /**
     * Validasi kode referral sebelum submit
     * GET /api/v1/volunteer/referral-code/validate/{code}
     */
    public function validateReferralCode(string $code)
    {
        $code = strtoupper(trim($code));

        $referralCode = ReferralCode::where('code', $code)
            ->where('event_name', 'We Care Them 2025')
            ->first();

        if (!$referralCode) {
            return response()->json([
                'status' => 'error',
                'message' => 'Referral code not found',
                'valid' => false,
            ], 404);
        }

        $isValid = $referralCode->isValid();

        if (!$isValid) {
            return response()->json([
                'status' => 'error',
                'valid' => false,
                'message' => $referralCode->getInvalidReason(),
            ], 400);
        }

        // Calculate preview discount
        $originalPrice = 75000; // TODO: Change to event price
        $discountAmount = $referralCode->calculateDiscount($originalPrice);

        return response()->json([
            'status' => 'success',
            'valid' => true,
            'message' => 'Referral code is valid!',
            'data' => [
                'code' => $referralCode->code,
                'description' => $referralCode->description,
                'discount_type' => $referralCode->discount_type,
                'discount_value' => $referralCode->discount_value,
                'discount_text' => $referralCode->discount_text,
                'preview' => [
                    'original_price' => $originalPrice,
                    'discount_amount' => $discountAmount,
                    'final_price' => $originalPrice - $discountAmount,
                    'you_save' => $discountAmount,
                ],
                'remaining_usage' => $referralCode->remaining_usage,
                'valid_until' => $referralCode->valid_until?->format('d M Y H:i'),
            ],
        ]);
    }
}
