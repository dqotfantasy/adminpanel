<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class StateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $states = State::orderBy('name')->get();

        return apiResponse(true, null, ['states' => $states]);
    }

    /**
     * Display the specified resource.
     *
     * @param State $state
     * @return Response
     */
    public function show(State $state)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param State $state
     * @return Response
     */
    
    public function update(Request $request, State $state)
    {
        // if (can('user')) {
        //     return apiResponse(false, 'Invalid request.');
        // }
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:3',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return apiResponse(false, $validator->errors()->first());
        }

        $state->update($validator->validated());

        return apiResponse(true, 'Updated.');
    }
}
