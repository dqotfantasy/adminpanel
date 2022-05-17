<?php

namespace App\Jobs;

use App\Models\ReferalDepositDetails;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class AddCommission implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    /**
     * Create a new job instance.
     *
     * @param $id
     */
    public function __construct()
    {
        $this->queue = 'AddCommission';
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
        $today_start = date( 'Y-m-d 00:00:00', strtotime( $today ) );
        $today_end = date( 'Y-m-d 23:59:59', strtotime( $today ) );


        $paidIds = Payment::query()
            ->whereBetween('created_at', [ $today_start, $today_end ])
            ->where('type', PAYMENT_TYPES[10])
            ->pluck('user_id');
            //dd($paidIds);die;

        $influncer_data = ReferalDepositDetails::groupBy('user_id')
            ->selectRaw('sum(amount) as sum, user_id')
            ->whereDate('date', '=', $day_before)
            ->where( 'amount', '>', 0 )
            ->where('is_deposieted', 0)
            ->whereNotIn('user_id', $paidIds)
            ->pluck('sum','user_id');
            //dd($influncer_data);

        DB::transaction(function () use ($influncer_data,$paidIds,$day_before) {

            if( !empty($influncer_data) ) {
                foreach( $influncer_data AS $user_id => $amount ) {
                    //dd($amount);die;

                    if ( $user_id && $amount > 0 ){

                        $payment = [
                            'user_id' => $user_id,
                            'amount' => $amount,
                            'status' => 'SUCCESS',
                            'transaction_id' => 'PRC' . rand(),
                            'description' => 'Promoter Commission Added',
                            'type' => PAYMENT_TYPES[10],
                        ];

                        Payment::query()->create($payment);

                        // Update in user wallet
                        User::query()->where('id', $user_id )->increment('winning_amount', $amount);

                    }

                }

                ReferalDepositDetails::where('is_deposieted', 0)
                ->whereDate('date', '=', $day_before)
                ->where( 'amount', '>', 0 )
                ->whereNotIn('user_id', $paidIds)
                ->update(['is_deposieted' => 1]);
            }

            return true;
        });

    }

}
