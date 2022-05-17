<?php

namespace App\Jobs;

use App\Models\Fixture;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class CalculateEarnings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * Create a new job instance.
     *
     * @param $fixtureId
     * @param bool $autoSet
     */
    public function __construct()
    {
        $this->queue = 'CalculateEarnings';
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        $today		= date('Y-m-d');
		$day_before = date( 'Y-m-d', strtotime( $today . ' -1 day' ) );
        $day_before_start   = date( 'Y-m-d 00:00:00', strtotime( $day_before ) );
        $day_before_end     = date( 'Y-m-d 23:59:59', strtotime( $day_before ) );

        $fixtures = Fixture::query()
        ->selectRaw('id,name,competition_id,competition_name,starting_at')
        ->whereBetween('starting_at', [ $day_before_start, $day_before_end ])
        ->where( 'verified',  1 )
        ->where( 'is_active',  1 )
        ->where('status', 'COMPLETED')
        ->get();


        if(!empty($fixtures) ) {
            foreach ($fixtures AS $fixture) {
                $store_payment_data=$payment_data_all='';
                for($i=1;$i<=2;$i++){
                    if($i==1){
                        $relativedata = $fixture->user_contests_with_where;
                    }elseif($i==2){
                        $relativedata = $fixture->all_user_contests_with_where;
                    }
                    $total_winning_distributed =  $relativedata->sum('prize');

                    $used_cash_bonus =  $relativedata->sum(function ($product) {
                        $array = json_decode($product['payment_data'],true);
                        return $array['cash_bonus'];
                    });

                    $used_winning_amount =  $relativedata->sum(function ($product) {
                        $array = json_decode($product['payment_data'],true);
                        return $array['wining_amount'];
                    });

                    $used_deposited_balance =  $relativedata->sum(function ($product) {
                        $array = json_decode($product['payment_data'],true);
                        return $array['deposited_balance'];
                    });

                    $total_amount = ($used_cash_bonus+$used_winning_amount+$used_deposited_balance);

                    $payment_data = [];
                    $payment_data['used_cash_bonus']            = $used_cash_bonus;
                    $payment_data['used_winning_amount']        = $used_winning_amount;
                    $payment_data['used_deposited_balance']     = $used_deposited_balance;
                    $payment_data['total_amount']               = $total_amount;
                    $payment_data['total_winning_distributed']  = $total_winning_distributed;
                    if($i==1){
                        $store_payment_data=$payment_data;
                        //$fixture->update([ 'payment_data' => $payment_data ]);
                    }elseif($i==2){
                        $payment_data_all=$payment_data;
                        //$fixture->update([ 'payment_data_all' => $payment_data ]);
                    }
                }
                $fixture->update(['payment_data' => $store_payment_data,'payment_data_all' => $payment_data_all]);


            }
        }

    }
}
