<?php

namespace App\Traits;

use Illuminate\Http\Request;

trait BotSignature {

    public function verifySignature(Request $request)
    {
        $hash = hash_hmac('sha256', $request->getContent(), env('LINE_BOT_CHANNEL_SECRET'), true);
        $signature = base64_encode($hash);
        return $request->header('X-Line-Signature') === $signature;
    }
}