<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PanCard;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PanCardController extends Controller
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

        $panCards = PanCard::query()->where('user_id', $userId)->first();

        return apiResponse(true, null, ['pan_cards' => $panCards]);
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
     * @param PanCard $panCard
     * @return Response
     */
    public function show(PanCard $panCard)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param PanCard $panCard
     * @return Response
     */
    public function update(Request $request, PanCard $panCard)
    {
        if(empty($request->pan_number)){
            $validator = Validator::make($request->all(), [
                'status' => 'bail|required',
                'message' => 'bail|required'
            ]);
        }else{
            $validator = Validator::make($request->all(), [
                'name' => 'bail|required',
                'pan_number' => 'bail|required',
                'date_of_birth' => 'bail|required',
            ]);
        }

        if ($validator->fails()) {
            return apiResponse(false, $validator->errors()->first());
        }

        $existPan = PanCard::where(['pan_number' => $panCard->pan_number, 'status' => 'VERIFIED', 'is_verified' => 1])->first();

        if ($existPan && $request->status == 'VERIFIED') {
            return apiResponse(false, 'Pan card Already Assign to other user');
        }

        if ($request->status == 'VERIFIED') {
            $panCard->is_verified = 1;
        }
        if(empty($request->pan_number)){
            $panCard->status = $request->status;
            $panCard->message = $request->message;
        }else{
            $panCard->name = $request->name;
            $panCard->pan_number = $request->pan_number;
            $panCard->date_of_birth = $request->date_of_birth;
        }
        $panCard->save();

        $user = User::find($panCard->user_id);

        if (isset($user->bank) && isset($user->pan) && $user->bank->status === 'VERIFIED' && $user->pan->status === 'VERIFIED' && $user->email_verified == 1 && $user->phone_verified == 1) {
            $user->document_verified = 1;
        } else {
            $user->document_verified = 0;
        }
        $user->save();


        return apiResponse(true, 'Pan card detail updated.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param PanCard $panCard
     * @return Response
     */
    public function destroy(PanCard $panCard)
    {
        //
    }
}
