<?php

namespace App\Http\Controllers;

use App\Models\Tds;
use Illuminate\Http\Request;

class TdsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $perPage = \request('per_page') ?? 15;
        $data = Tds::query()
            ->with('payment')
            ->with('user:id,name,username,email')
            ->paginate($perPage);

        return apiResponse(true, null, $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
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
     * @param \App\Models\Tds $tds
     * @return \Illuminate\Http\Response
     */
    public function show(Tds $tds)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Tds $tds
     * @return \Illuminate\Http\Response
     */
    public function edit(Tds $tds)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Tds $tds
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Tds $tds)
    {
        $data = $request->validate([
            'is_settled' => 'required|boolean',
            'note' => 'nullable'
        ]);

        $tds->update($data);

        return apiResponse(true, 'Data updated.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Tds $tds
     * @return \Illuminate\Http\Response
     */
    public function destroy(Tds $tds)
    {
        //
    }
}
