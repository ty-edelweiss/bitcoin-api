<?php

namespace App\Traits;

trait LineSignature {

    public function verifySignature(Request $request)
    {
        $hash = hash_hmac('sha256', $request->getContent(), env('LINE_CHANNEL_SECRET'), true);
        $signature = base64_encode($hash);
        return $request->header('X-Line-Signature') === $signature;
    }
}