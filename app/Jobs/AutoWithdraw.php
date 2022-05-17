<?php

namespace App\Jobs;

use App\Models\BankAccount;
use App\Models\Payment;
use App\Razorpay;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AutoWithdraw implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $id;

    /**
     * Create a new job instance.
     *
     * @param $id
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $razorpay = new Razorpay();
        $payment = Payment::query()->find($this->id);
        $user = $payment->user;
        $bankAccount = BankAccount::query()
            ->where('status', BANK_DETAIL_STATUS[1])
            ->where('user_id', $payment->user_id)
            ->first();

        $account = [
            'account_number' => $razorpay->accountNumber,
            'amount' => abs($payment->amount) * 100,
            'currency' => 'INR',
            'mode' => 'NEFT',
            'purpose' => 'payout',
            'fund_account' => [
                'account_type' => 'bank_account',
                'bank_account' => [
                    'name' => $bankAccount->name,
                    'ifsc' => $bankAccount->ifsc_code,
                    'account_number' => $bankAccount->account_number
                ],
                'contact' => [
                    'name' => is_null($user->name) ? $user->username : $user->name,
                    'email' => $user->email,
                    'contact' => $user->phone,
                    'type' => 'customer',
                    'reference_id' => (string)$user->id
                ],
            ],
            'queue_if_low_balance' => true,
            'reference_id' => $payment->transaction_id,
            'narration' => 'Withdrawal',
        ];

        $payout = $razorpay->withdraw($account);

        $status = $this->getStatus($payout['status']);

        $payment->reference_id = $payout['id'];
        $payment->description = 'Withdrawal ' . $status;
        $payment->save();
    }

    private function getStatus($status)
    {
        if ($status == 'queued' || $status == 'pending' || $status == 'processing' || $status == 'authorized' || $status == 'scheduled') {
            return 'PENDING';
        } elseif ($status == 'processed' || $status == 'captured') {
            return 'SUCCESS';
        } else {
            return 'FAILED';
        }
    }
}
