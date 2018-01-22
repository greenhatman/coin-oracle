<?php

namespace App\Console\Commands;

use App\Ticker;
use Illuminate\Console\Command;

class PollExchanges extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'poll:tickers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get ticker data from exchanges to save in the database. ' .
    'Send notification alerts when price move exceeds threshold.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $luno = new \ccxt\luno();
        $results = $luno->fetch_markets();

        foreach ($results as $result) {
            $ticker = new Ticker();
            $ticker->exchange_id = 1;
            $ticker->symbol = $result['symbol'];
            $ticker->base = $result['base'];
            $ticker->quote = $result['quote'];
            $ticker->price = $result['info']['last_trade'];
            $ticker->save();

            $previousTicker = Ticker::whereExchangeId($ticker->exchange_id)
                ->whereSymbol($ticker->symbol)
                ->whereBase($ticker->base)
                ->whereQuote($ticker->quote)
                ->where('id', '<', $ticker->id)
                ->first();

            $priceChangePercentage = ($previousTicker->price - $ticker->price)
                / $previousTicker->price * 100;
            if (abs($priceChangePercentage) > env('WARNING_THRESHOLD_PERCENTAGE')) {
                echo "Price change for {$ticker->symbol} changed {$priceChangePercentage}% from {$previousTicker->price} to {$ticker->price} \n";
            }
        }

    }
}
