<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder as LINETextMessageBuilder;
use Symfony\Component\HttpFoundation\Response as HTTPStatus;

use App\Traits\BotSignature;
use App\Traits\JsonLineConverter;

class BotController extends Controller
{
    use BotSignature;
    use JsonLineConverter;

    const WEBHOOK_ENVET_MESSAGE = 'message';

    private $bot;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $httpClient = new CurlHTTPClient(env('LINE_BOT_CHANNEL_ACCESS_TOKEN'));
        $this->bot = new LINEBot($httpClient, ['channelSecret' => env('LINE_BOT_CHANNEL_SECRET')]);
    }

    public function get(Request $request)
    {
        return response()->json([], HTTPStatus::HTTP_OK);
    }

    public function post(Request $request)
    {
        if (!$this->verifySignature($request)) {
            Log::error('Failed to verify signature passed by provider');
            return response()->json([], HTTPStatus::HTTP_FORBIDDEN);
        }

        $events = $request->input('events');

        $data = $this->loadJsonLine(storage_path('app/bitcoin.jsonl'));
        $latestData = Arr::last($data);

        Log::info('Webhook Request: ', $events);

        foreach ($events as $event) {
            if ($event['type'] !== self::WEBHOOK_ENVET_MESSAGE) {
                continue;
            }

            $currency = Str::upper(Arr::get($event, 'message.text'));
            $currencyData = Arr::get($latestData, $currency);

            if (is_null($currencyData)) {
                $message = 'Non currency data';
            } else {
                $buyPrice = Arr::get($currencyData, 'buy');
                $sellPrice = Arr::get($currencyData, 'sell');
                $symbol = Arr::get($currencyData, 'symbol');

                $message = "Buy: ${symbol}${buyPrice}\nSell: ${symbol}${sellPrice}";
            }

            $textMessageBuilder = new LINETextMessageBuilder($message);
            $response = $this->bot->replyMessage($event['replyToken'], $textMessageBuilder);

            if ($response->getHTTPStatus() === HTTPStatus::HTTP_OK) {
                Log::info('Successfully replied to user: ', compact('message'));
            } else {
                Log::error('Failed to reply to user: ', ['error' => $response->getRawBody()]);
            }
        }

        return response()->json([], HTTPStatus::HTTP_OK);
    }
}
