<?php

namespace App\Jobs;

use App\Models\Contest;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;


class ContestCancel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 3000;

    private $contestId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($contestId)
    {
        $this->contestId = $contestId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $contest = Contest::query()->find($this->contestId);

        if ($contest && ( $contest->status == CONTEST_STATUS[1] || $contest->status == CONTEST_STATUS[2] ) ) {
            $joined = $contest->joined;

            DB::transaction(function () use ($contest, $joined) {
                $contest->update(['status' => CONTEST_STATUS[4]]);
                foreach ($joined as $item) {
                    //$paymentData = collect($item->payment_data);
                    if(is_array($item->payment_data)){
                        $paymentData = collect($item->payment_data);
                    }else{
                        $paymentData = collect(json_decode($item->payment_data));
                    }
                    $cashBonus = $paymentData->get('cash_bonus');
                    $winningAmount = $paymentData->get('wining_amount');
                    $depositedBalance = $paymentData->get('deposited_balance');

                    $extra = ['entry_fee'=>$contest->entry_fee,'fixture_name'=>$contest->fixture->teama_short_name.' vs '.$contest->fixture->teamb_short_name,'category_name'=>$contest->category->name];
                    $extra = json_encode($extra);

                    $payment = [
                        'user_id' => $item['user_id'],
                        'amount' => ($cashBonus + $winningAmount + $depositedBalance),
                        'status' => 'SUCCESS',
                        'transaction_id' => 'CRF' . rand(),
                        'description' => 'Refunded',
                        'type' => PAYMENT_TYPES[4],
                        'contest_id' => $contest->id,
                        'extra' => $extra
                    ];

                    Payment::query()->create($payment);

                    if ($item->payment_data) {
                        User::query()->where('id', $item['user_id'])->increment('cash_bonus', $cashBonus);
                        User::query()->where('id', $item['user_id'])->increment('winning_amount', $winningAmount);
                        User::query()->where('id', $item['user_id'])->increment('deposited_balance', $depositedBalance);
                        User::query()->where('id', $item['user_id'])->increment('balance', ($cashBonus + $winningAmount + $depositedBalance));
                    }
                }

                Redis::srem("contest_in_cancelling", $this->contestId);
            });
        }
    }
}
