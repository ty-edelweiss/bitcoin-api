<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HTTPStatus;
use LINE\LineBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder as LineTextMessageBuilder;

use App\Tratis\LineSignature;

class LineController extends Controller
{
    use LineSignature;

    const WEBHOOK_ENVET_MESSAGE = 'message';

    private $bot;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $httpClient = new CurlHTTPClient(env('LINE_BOT_CHANNEL_SECRET'));
        $this->bot = new LINEBot($httpClient, ['channelSecret' => env('LINE_BOT_CHANNEL_SECRET')]);
    }

    public function get(Request $request)
    {
        $fp = fopen(storage_path('app/bitcoin.jsonl'), 'r');
 
        $data = [];
        while (!feof($fp)) {
            $line = fgets($fp);
            $data[] = json_decode($line, true);
        }

        fclose($fp);

        return response(HTTPStatus::HTTP_OK)->json(array_pop($data));
    }

    public function post(Request $request)
    {
        if (!$this->verifySignature($request)) {
            return response(HTTPStatus::HTTP_FORBIDDEN)->json();
        }

        $events = $request->input('events');

        foreach ($events as $event) {
            if ($event['type'] !== self::WEBHOOK_ENVET_MESSAGE) {
                continue;
            }

            $textMessageBuilder = new LineTextMessageBuilder('hello');
            $response = $this->bot->replyMessage($event['replyToken'], $textMessageBuilder);

            if ($response->getHTTPStatus() !== HTTPStatus::HTTP_OK) {
            }
        }

        return response(HTTPStatus::HTTP_OK)->json();
    }
}
