<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class UserMatchController extends Controller
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
        $validator = Validator::make($request->all(), [
            'status' => 'bail|required',
            'message' => 'bail|required'
        ]);

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

        $bankAccount->status = $request->status;
        $bankAccount->save();

        $user = User::find($bankAccount->user_id);

        if ($request->status == 'VERIFIED') {
            $user->bank_update_count = $user->bank_update_count + 1;
        }

        // if (isset($user->bank) && isset($user->pan) && $user->bank->status === 'VERIFIED' && $user->pan->status === 'VERIFIED' && $request->email_verified == 1 && $request->phone_verified == 1) {
        if (isset($user->bank) && isset($user->pan) && $user->bank->status === 'VERIFIED' && $user->pan->status === 'VERIFIED') {
            $user->document_verified = 1;
        } else {
            $user->document_verified = 0;
        }
        $user->save();

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
