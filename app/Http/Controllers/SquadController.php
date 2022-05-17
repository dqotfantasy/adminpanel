<?php

namespace App\Http\Controllers;

use App\Models\Squad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SquadController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param Squad $squad
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request, Squad $squad)
    {
        $validator = Validator::make($request->all(), [
            'is_active' => 'boolean',
            'fantasy_player_rating' => 'numeric',
            'role' => 'in:' . implode(",", POSITIONS),
        ]);

        if ($validator->fails()) {
            return apiResponse(false, $validator->errors()->first());
        }

        $data = $validator->validated();

        if ($request->hasFile('image')) {
            $photoValidator = Validator::make($request->all(), [
                'image' => 'image',
            ]);

            if ($photoValidator->fails()) {
                return apiResponse(false, $photoValidator->errors()->first());
            }

            $path = $request->file('image')->storePublicly('players', 's3');
            $imageFile = Storage::disk('s3')->url($path);

            if (isset($squad->player->image)) {
                $path = $squad->player->image;
                if ($path) {
                    Storage::disk('s3')->delete("players/" . Str::after($path, "/players"));
                }
            }

            $squad->player()->update([
                'image' => $imageFile
            ]);
        }


        $squad->update($data);

        return apiResponse(true, 'Player Updated.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
