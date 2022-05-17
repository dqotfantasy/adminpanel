<?php

namespace App\Http\Controllers;

use App\Exports\PaymentExport;
use App\Models\Payment;
use App\Models\User;
use App\Razorpay;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;



class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        //DB::enableQueryLog();
        $query = Payment::query();
        $query->join('users as u', 'u.id', '=', 'payments.user_id');
        // $query->join('users as u', 'u.id', '=', 'payments.user_id')
        //         ->leftJoin('contests as c','c.id','=','payments.contest_id')
        //         ->leftJoin('contest_categories as cc','cc.id','=','c.contest_category_id')
        //         ->leftJoin('fixtures as f','f.id','=','c.fixture_id');
        $search = request('search');
        $userId = request('user_id');
        $type = request('type');
        $status = request('status');
        $flag = \request('flag');
        $mode = request('mode');
        $perPage = \request('per_page') ?? 15;
        if (isset($search)) {
            $query->where(function ($query) {
                $search = request('search');
                foreach (['u.name', 'u.email', 'u.phone','payments.description'] as $field) {
                    $query->orWhere($field, 'LIKE', '%' . $search . '%');
                }
            });
        }

        if (isset($userId)) {
            $query->where('payments.user_id', $userId);
        }
        if (isset($type)) {
            $query->where('payments.type', $type);
        }
        // if ($mode == 'WITHDRAW') {
        //     $query->whereNull('payments.reference_id');
        // }
        if (isset($status)) {
            $query->where('payments.status', $status);
        }
        if(!empty($flag)){
            if($flag=='MONTH'){
                $query->whereMonth('payments.created_at',Carbon::now()->month);
            }else{
                $query->whereDate('payments.created_at', '>=', now()->toDate());
                $query->whereDate('payments.created_at', '<=', now()->toDate());
            }

        }
        $query->select(['payments.*', 'u.email as email', 'u.phone as phone', 'u.name as name']);
        $query->latest();
        $paginator = $query->paginate($perPage);

        $statuses = [];
        foreach (PAYMENT_TYPES as $item) {
            $statuses[] = ['id' => $item, 'name' => $item];
        }

        $data = [
            'payments' => $paginator->items(),
            'total' => $paginator->total(),
            'statuses' =>$statuses,
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'next_page_url' => $paginator->nextPageUrl(),
            'prev_page_url' => $paginator->previousPageUrl()
        ];

        return apiResponse(true, null, $data);
    }

    public function getExport(Request $request)
    {
        request()->validate([
            'from_date' => 'required|date|before_or_equal:to_date',
            'to_date' => 'required|date|before:tomorrow',
        ]);

        $from = date($request->from_date);
        $to = date($request->to_date);
        $user_id = !empty($request->user_id)?$request->user_id:'';
        $status = !empty($request->status)?$request->status:'';
        $typecontest = !empty($request->typecontest)?$request->typecontest:'';
        //return $from.'====='.$to.'======'.$user_id.'========'.$status.'====='.$typecontest;
        if ($request->type !== 'XLS') {
            return Excel::download(new PaymentExport($from, $to,$user_id,$typecontest,$status), 'payment.csv', \Maatwebsite\Excel\Excel::CSV, [
                'Content-Type' => 'text/csv',
            ]);
        } else {
            return Excel::download(new PaymentExport($from, $to,$user_id,$typecontest,$status), 'payment.xlsx');
        }
    }

    public function update(Request $request, $id)
    {

        $request->validate([
            'status' => 'bail|required|in:' . implode(",", ['cancel', 'send'])
        ]);

        $payment = Payment::find($id);
        if (!$payment) {
            return apiResponse(false, 'Payment not found.');
        }
		include_once("cfpayout.inc.php");

        $user = $payment->user;
        //$uid=$user->id;
        if ($request->status === 'send') {
            $bankAccount = $user->bank;

            return DB::transaction(function () use ($payment, $user, $request) {
                $bankAccount = $user->bank;

                if ($bankAccount) {

                    if ($bankAccount->status != BANK_DETAIL_STATUS[1]) {
                        return apiResponse(false, 'Your bank account details are not verified.');
                    }
                    $authParams["clientId"]		=	PROD_CLIENT_ID;
                    $authParams["clientSecret"]	=	PROD_CLIENT_SECRET;
                    $authParams["stage"]		=	'PROD';
					$payout 		=	new \CfPayout($authParams);

                    $beneficiary	=	[];
                    $beneficiary["beneId"]		=	!empty($bankAccount['beneficiary_id'])?$bankAccount['beneficiary_id']:"UID_".str_replace("-","_",$user->id);
                    $bankAccount['beneficiary_id']=$beneficiary["beneId"];
                    $getbenResponse = $payout->getBeneficiary($beneficiary["beneId"]);
                    if($getbenResponse['subCode']==404 || $getbenResponse['message']=='Beneficiary does not exist'){
                        $beneficiary["name"]		=	!empty($bankAccount['name'])?$bankAccount['name']:$user->name;
                        $beneficiary["email"]		=	$user->email;
                        $beneficiary["phone"]		=	$user->phone;
                        $beneficiary["bankAccount"]	=	$bankAccount['account_number'];
                        $beneficiary["ifsc"]		=	$bankAccount['ifsc_code'];
                        $beneficiary["address1"]	=	$user->address;
                        $addresponse = $payout->addBeneficiary($beneficiary);
                        if($addresponse['status']=='ERROR'){
                            return apiResponse(false, $addresponse['message']);
                        }
                        $bankAccount->save();
                    }else{
                        $beneficiary["beneId"]=$getbenResponse['data']['beneId'];
                    }
                    $transferMode='banktransfer';
                    $transfer["beneId"]			=	$beneficiary["beneId"];
                    $transfer["amount"]			=	abs($payment['amount']);
                    //$transfer["amount"]			=	1;
                    $transfer["transferMode"]	=	$transferMode;
                    $transferId					= 	rand().'_'.$bankAccount['id'];
                    $transfer["transferId"]		=	$transferId;
                    $transfer["remarks"] = "Transfer Request From Admin";
                    $trnResponse = $payout->requestTransfer($transfer);

                    if($trnResponse['status']=='SUCCESS'){
                        $payment['reference_id']=!empty($trnResponse["data"]['referenceId'])?$trnResponse["data"]['referenceId']:'';
					    $payment['description']=!empty($trnResponse["message"])?$trnResponse["message"]:'';
					    $payment['status']=!empty($trnResponse["status"])?$trnResponse["status"]:'';
                        $payment->save();
                    }else if($trnResponse['status']=='PENDING'){
					    $payment['reference_id']=!empty($trnResponse["data"]['referenceId'])?$trnResponse["data"]['referenceId']:'';
					    $payment['description']=!empty($trnResponse["message"])?$trnResponse["message"]:'';
                        $payment->save();
                    }
                    return apiResponse(true, $trnResponse["message"]);
                } else {
                    return apiResponse(false, 'Please update your bank account details.');
                }
            });
        }elseif($request->status === 'cancel'){
            $extraData=$payment['extra'];
            $bankAccount = $user->bank;
            $refAmount=$payment['amount'];
            $bankAccount = $user->bank;
            $payment['description']="REJECTED from admin Side";
			$payment['status']="REJECTED";
            if($payment->save()){
                $user = User::find($user->id);
                $user->winning_amount+=abs($refAmount);
                $user->balance+=abs($refAmount);
                if($user->save()){
                    $payment = [
                        'user_id' => $user->id,
                        'amount' => abs($refAmount),
                        'status' => "SUCCESS",
                        'transaction_id' => 'ADREJ' . rand(),
                        'description' => 'Refund amount because Admin Rejected your request',
                        'type' => PAYMENT_TYPES[4],
                        'extra' => $extraData,
                        'reference_id' => (string)rand(),
                    ];
                    if(Payment::query()->create($payment)){
                        return apiResponse(true, 'Status Updated.');
                    }
                }
            }
        }
         else {
            return apiResponse(true, 'status updated.');
        }
    }

    public function paymentWebhook(Request $request)
    {
        $razorpay = new Razorpay();
        if (!$razorpay->verifySignature($request)) {
            return \response([
                'status' => false
            ]);
        }

        if (isset($request->payload)) {
            if (isset($request->payload['payment'])) {
                $data = $request->payload['payment']['entity'];
                $status = $data['status'];
                $payment = Payment::query()->where('reference_id', $data['id'])->first();
                if ($payment) {
                    DB::transaction(function () use ($payment, $status) {
                        $payment->update([
                            'status' => $this->getStatus($status),
                        ]);
                    });
                } else {
                    if (isset($data['notes']['id'])) {
                        $user = User::query()->find($data['notes']['id']);
                        if ($user) {

                            $amount = $data['amount'] / 100;

                            if ($status == 'authorized' && !$data['captured']) {
                                $razorpay = new Razorpay();
                                $captured = $razorpay->capture($data['id'], $data['amount']);

                                if (is_null($captured)) {
                                    return response([
                                        'status' => false
                                    ]);
                                }

                                DB::transaction(function () use ($user, $amount, $status, $data) {

                                    $user->balance = $user->balance + $amount;
                                    $user->deposited_balance = $user->deposited_balance + $amount;
                                    $user->save();

                                    $payment = [
                                        'user_id' => $user->id,
                                        'amount' => $amount,
                                        'status' => $this->getStatus($status),
                                        'transaction_id' => 'TXN' . rand(),
                                        'description' => 'Deposit',
                                        'type' => PAYMENT_TYPES[0],
                                        'reference_id' => $data['id'],
                                    ];

                                    Payment::query()->create($payment);
                                });
                            }
                        }
                    }
                }
            }
        }

        return response(['status' => true]);
    }

    public function withdrawWebhook(Request $request)
    {
        $razorpay = new Razorpay();
        if (!$razorpay->verifySignature($request)) {
            return \response([
                'status' => false
            ]);
        }
        if (isset($request->payload)) {
            if (isset($request->payload['payout'])) {
                $data = $request->payload['payout']['entity'];

                $payment = Payment::query()->where('reference_id', $data['id'])->where('status', 'PENDING')->first();

                if ($payment) {
                    $status = $data['status'];
                    DB::transaction(function () use ($payment, $status) {
                        $payment->update([
                            'status' => $this->getStatus($status),
                            'description' => 'Withdrawal ' . $this->getStatus($status)
                        ]);
                    });
                }
            }
        }
        return response([
            'status' => true
        ]);
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
