<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DataCollector extends Job
{
    const BLOCKCHAIN_EDNPOINT = 'https://blockchain.info/ticker';

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $response = HTTP::get(self::BLOCKCHAIN_EDNPOINT);

        if (!$response->ok()) {
            Log::error('Failed to download bitcoin information from blockchain.info');
            return;
        }

        Log::info('Successfully download bitcoin information from blockchain.info');
        $jsonString = trim(preg_replace('/\s+/', ' ', $response->body()));
        file_put_contents(storage_path('app/bitcoin.jsonl'), $jsonString . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
