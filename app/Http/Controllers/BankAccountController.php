<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class BankAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {

        $validator = Validator::make(\request()->all(), [
            'user_id' => 'bail|required',
        ]);

        if ($validator->fails()) {
            return apiResponse(false, $validator->errors()->first());
        }
        $userId = \request('user_id');

        $bankAccounts = BankAccount::query()->where('user_id', $userId)->first();

        return apiResponse(true, null, ['bank_account' => $bankAccounts]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
    }

    /**
     * Display the specified resource.
     *
     * @param BankAccount $bankAccount
     * @return Response
     */
    public function show(BankAccount $bankAccount)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param BankAccount $bankAccount
     * @return Response
     */
    public function update(Request $request, BankAccount $bankAccount)
    {
        if(empty($request->account_number)){
            $validator = Validator::make($request->all(), [
                'status' => 'bail|required',
                'message' => 'bail|required'
            ]);
        }else{
            $validator = Validator::make($request->all(), [
                'account_number' => 'bail|required',
                'ifsc_code' => 'bail|required',
                'name' => 'bail|required',
                'bankName' => 'bail|required',
                'branch' => 'bail|required',
            ]);
        }
        if ($validator->fails()) {
            return apiResponse(false, $validator->errors()->first());
        }

        $existBank = BankAccount::where([
            'account_number' => $bankAccount->account_number,
            'branch' => $bankAccount->branch,
            'ifsc_code' => $bankAccount->ifsc_code,
            'status' => 'VERIFIED'
        ])->first();


        if ($existBank && $request->status == 'VERIFIED') {
            return apiResponse(false, 'Bank already assign to other user');
        }
        if(empty($request->account_number)){
            $bankAccount->status = $request->status;
            $bankAccount->message = $request->message;
        }else{
            $bankAccount->account_number = $request->account_number;
            $bankAccount->ifsc_code = $request->ifsc_code;
            $bankAccount->name = $request->name;
            $bankAccount->bankName = $request->bankName;
            $bankAccount->branch = $request->branch;
        }
        $bankAccount->save();

        $user = User::find($bankAccount->user_id);

        if ($request->status == 'VERIFIED') {
            $user->bank_update_count = $user->bank_update_count + 1;
        }

        if (isset($user->bank) && isset($user->pan) && $user->bank->status === 'VERIFIED' && $user->pan->status === 'VERIFIED' && $user->email_verified == 1 && $user->phone_verified == 1) {
            $user->document_verified = 1;
        } else {
            $user->document_verified = 0;
        }
        $user->save();

        if($request->status == 'REJECTED'){
		    include_once("cfpayout.inc.php");
            $authParams["clientId"]		=	PROD_CLIENT_ID;
            $authParams["clientSecret"]	=	PROD_CLIENT_SECRET;
            $authParams["stage"]		=	'PROD';
            $payout 		=	new \CfPayout($authParams);
            //$beneficiary["beneId"]		=	str_replace("-","_",$bankAccount->user_id);
            $beneficiary["beneId"]		=	!empty($bankAccount->beneficiary_id)?$bankAccount->beneficiary_id:"UID_".str_replace("-","_",$bankAccount->user_id);
            $payout->removeBeneficiary($beneficiary["beneId"]);
        }

        return apiResponse(true, 'Bank account detail updated.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param BankAccount $bankAccount
     * @return Response
     */
    public function destroy(BankAccount $bankAccount)
    {
        //
    }
}
