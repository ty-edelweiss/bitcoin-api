<?php

namespace App\Jobs;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder as LINETextMessageBuilder;
use Symfony\Component\HttpFoundation\Response as HTTPStatus;

use App\Traits\JsonLineConverter;

class Notification extends Job
{
    use JsonLineConverter;

    const CURRENCY = 'JPY';

    private $bot;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        $httpClient = new CurlHTTPClient(env('LINE_BOT_CHANNEL_ACCESS_TOKEN'));
        $this->bot = new LINEBot($httpClient, ['channelSecret' => env('LINE_BOT_CHANNEL_SECRET')]);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $data = $this->loadJsonLine(storage_path('app/bitcoin.jsonl'));
        $latestData = Arr::last($data);

        $currencyData = Arr::get($latestData, self::CURRENCY);

        if (is_null($currencyData)) {
            $message = 'Non currency data';
        } else {
            $buyPrice = Arr::get($currencyData, 'buy');
            $sellPrice = Arr::get($currencyData, 'sell');
            $symbol = Arr::get($currencyData, 'symbol');

            $message = "Buy: ${symbol}${buyPrice}\nSell: ${symbol}${sellPrice}";
        }

        $textMessageBuilder = new LINETextMessageBuilder($message);
        $response = $this->bot->pushMessage(env('LINE_BOT_SUBSCRIBER_ID'), $textMessageBuilder);

        if ($response->getHTTPStatus() === HTTPStatus::HTTP_OK) {
            Log::info('Successfully notified user: ', compact('message'));
        } else {
            Log::error('Failed to notify user: ', ['error' => $response->getRawBody()]);
        }
    }
}
