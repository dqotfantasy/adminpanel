<?php

namespace App\Jobs;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;


class PendingPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 300;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->queue = 'pendingpayment';
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $paymentData = Payment::query()
            ->whereNotNull('reference_id')
            ->where([['type', 'WITHDRAW'],['status','PENDING']])
            ->limit(100)
            ->get();

        include_once(app_path()."/Http/Controllers/cfpayout.inc.php");
        //include_once("cfpayout.inc.php");
        $newData = $leaderboardData = [];
        $authParams["clientId"]        =    PROD_CLIENT_ID;
        $authParams["clientSecret"]    =    PROD_CLIENT_SECRET;
        $authParams["stage"]        =    'PROD';
        $payout 		=	new \CfPayout($authParams);
        foreach ($paymentData as $value) {
            $payment = Payment::find($value['id']);
			$checktran = array('referenceId'=>$payment['reference_id']);
			$response = $payout->getTransferRequest($checktran);
            if ( $response["status"] == "SUCCESS" || $response["subCode"] == "200" ) {

                $responseData = (isset($response['data']['transfer']) && !empty($response['data']['transfer'])) ? $response['data']['transfer'] : [];

                if( isset($responseData['status']) &&  $responseData['status'] == 'SUCCESS' ){
                    $payment['status']	=	'SUCCESS';
                    $payment['extra']	=	json_encode(array('utr'=>$responseData['utr']));
                    $payment->save();
                }
            }
        }
    }
}
