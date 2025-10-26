<?php

namespace App\Traits;

trait TokenResponse
{
    protected function respondWithToken($userId, $token)
    {
        return [
            'user' => $userId,
            'token' => [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth()->factory()->getTTL() * 60,
            ],
        ];
    }
}
