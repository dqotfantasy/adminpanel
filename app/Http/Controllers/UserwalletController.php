<?php

namespace App\Http\Controllers;

use App\Models\State;
use App\Models\User;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserwalletController extends Controller
{
    public function index()
    {
        $query = User::query();
        $query->with(['bank', 'pan']);
        $search = \request('search');
        $verified = \request('verified');
        $perPage = \request('per_page') ?? 15;
        if (isset($search)) {
            $query->where('name', 'LIKE', '%' . $search . '%');
            foreach (['username', 'email', 'phone'] as $field) {
                $query->orWhere($field, 'LIKE', '%' . $search . '%');
            }

        }

        $query->where('role', 'user');

        $paginator = $query->paginate($perPage);

        //$paginator->getCollection()->makeVisible(['is_locked']);

        $data = [
            'users' => $paginator->items(),
            'role_id' => roleId(),
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
        ];

        return apiResponse(true, null, $data);
    }

    /**
     * Display the specified resource.
     *
     * @param User $user
     * @return Response
     */
    public function show(User $user)
    {
        $user->makeVisible(['is_locked']);
        $states = State::where('is_active', true)->orderBy('name')->get();
        return apiResponse(true, null, ["user" => $user, 'states' => $states]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param User $user
     * @return Response
     */
    public function update(Request $request, User $user,$uid)
    {

        $action=$request->action;
        $amountValue=0;
        $description='';

        $query = User::query()->findOrFail($uid);
        if(!empty($request->deposited_balance)){
            $amountValue=$request->change_amount;

            if($action=='add'){
                $description="Admin added in Deposit wallet";
                $query->deposited_balance+=$amountValue;
            }else{
                if($query->deposited_balance==0){
                    return apiResponse(false, "User does't has sufficient Amount.");
                }
                $description="Admin deducted from Deposite wallet";
                $query->deposited_balance-=$amountValue;
            }
        }elseif(!empty($request->winning_amount)){
            $amountValue=$request->change_amount;
            if($action=='add'){
                $description="Admin added in Winning wallet";
                $query->winning_amount+=$amountValue;
            }else{
                if($query->winning_amount==0){
                    return apiResponse(false, "User does't has sufficient balance.");
                }
                $description="Admin deducted from Winning wallet";

                $query->winning_amount-=$amountValue;
            }

        }elseif(!empty($request->cash_bonus)){
            $amountValue=$request->change_amount;
            if($action=='add'){
                $query->cash_bonus+=$amountValue;
                $description="Admin added in Bonus wallet";

            }else{
                if($query->cash_bonus==0){
                    return apiResponse(false, "User does't has sufficient balance.");
                }
                $description="Admin deducted from Bonus wallet";
                $query->cash_bonus-=$amountValue;
            }

        }
        if($action=='add' && $amountValue!=0){
            $query->balance+=$amountValue;
        }else{
            $query->balance-=$amountValue;
        }

        if($query->update()){

            $payment=new Payment;

            $payment->user_id=$uid;
            if($action=='add'){
                $payment->amount="+".$amountValue;
                $payment->transaction_id='AAd' . rand();
                $payment->description=$description;
                $payment->status='SUCCESS';
                $payment->type=PAYMENT_TYPES[5];

            }else{
                $payment->amount="-".$amountValue;
                $payment->transaction_id='ADED' . rand();
                $payment->description=$description;
                $payment->status='SUCCESS';
                $payment->type=PAYMENT_TYPES[6];

            }
            $payment->save();

        }
        return apiResponse(true, 'User Wallet updated.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param User $user
     * @return Response
     */
    public function destroy(User $user)
    {
        //
    }

}
