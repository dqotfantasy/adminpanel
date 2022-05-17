<?php

namespace App\Http\Controllers;

use App\Models\FantasyPoint;
use App\Models\FantasyPointCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FantasyPointController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $query = FantasyPoint::query()
            ->from('fantasy_points', 'fp')
            ->leftJoin('fantasy_point_categories as fpc', 'fpc.id', '=', 'fp.fantasy_point_category_id');
        $query->select(['fp.*', 'fpc.name as category']);

        $fantasyPoints = $query->get();

        $categories = FantasyPointCategory::query()->with(['fantasy_points' => function ($query) {
            $type = \request('type');
            if (isset($type)) {
                $query->where('type', $type);
            }
        }])->get();

        return apiResponse(true, null, ['fantasy_points' => $fantasyPoints, 'types' => CRICKET_TYPES, 'data' => $categories]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param FantasyPoint $fantasyPoint
     * @return Response
     */
    public function show(FantasyPoint $fantasyPoint)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param FantasyPoint $fantasyPoint
     * @return Response
     */
    public function update(Request $request, FantasyPoint $fantasyPoint)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param FantasyPoint $fantasyPoint
     * @return Response
     */
    public function destroy(FantasyPoint $fantasyPoint)
    {
        //
    }

    public function types()
    {
        return apiResponse(true, '', ['types' => CRICKET_TYPES]);
    }
}
