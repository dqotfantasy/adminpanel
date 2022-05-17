<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\RankCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RankCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $perPage = \request('per_page') ?? 15;
        $query =  RankCategory::query();

        $query->when(\request('type') != 'list', function ($q) {
            $q->with('prizeBreakup');
        });

        $rankCategories = $query->paginate($perPage);
        return apiResponse(true, null, ['rank_categories' => $rankCategories]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|min:2|unique:rank_categories',
            'winner' => 'required|integer',
            'prize_breakup' => 'required|array',
            'prize_breakup.*.from' => 'required|integer|gt:0',
            'prize_breakup.*.to' => 'required|integer|gt:0',
            'prize_breakup.*.percentage' => 'required|gt:0',
        ]);

        $rankPrize = 0;
        $tempFrom = 0;
        $tempTo = 0;
        $totalPlayer = 0;

        foreach ($request->prize_breakup as $i => $breakup) {

            if ($i > 0) {
                if ($tempTo >= $breakup['from']) {
                    return apiResponse(false, 'The previous to field is greater than or equal from field.');
                } else {
                    $tempFrom = $breakup['from'];
                    $tempTo = $breakup['to'];
                }
            } else {
                if ($tempFrom == 0 && $tempTo == 0 && $i == 0) {
                    $tempFrom = $breakup['from'];
                    $tempTo = $breakup['to'];
                }
            }

            if ($breakup['from'] > $breakup['to']) {
                return apiResponse(false, 'The to field must be greater than or equal to from field.');
            }

            $rankPrize += (int)(($breakup['to'] - $breakup['from']) + 1) * $breakup['percentage'];

            $totalPlayer = $breakup['to'];
        }

        if ($rankPrize != 100) {
            return apiResponse(false, "The percentage must be equal to 100%. Current percentage is $rankPrize%");
        }

        if ($totalPlayer != $request->winner) {
            return apiResponse(false, 'Prize breakup can not match with winner field value.');
        }

        $rankCategoryData = [
            'name' => $request->name,
            'winner' => $request->winner
        ];

        $rankCategory = RankCategory::query()->create($rankCategoryData);

        foreach ($request->prize_breakup as $val) {
            $rankLabel = $val['from'] . '-' . $val['to'];

            if ($val['from'] == $val['to']) {
                $rankLabel = $val['from'];
            }

            $ranksArray = [
                'rank' => $rankLabel,
                'from' => $val['from'],
                'to' => $val['to'],
                'percentage' => $val['percentage']
            ];

            $rankCategory->prizeBreakup()->create($ranksArray);
        }

        return apiResponse(true, 'Rank added.');
    }

    /**
     * Display the specified resource.
     *
     * @param RankCategory $rankCategory
     * @return Response
     */
    public function show(RankCategory $rankCategory)
    {
        return apiResponse(true, null, ['rank' => $rankCategory]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param RankCategory $rankCategory
     * @return Response
     */
    public function update(Request $request, RankCategory $rankCategory)
    {
        $request->validate([
            'name' => 'required|min:2|unique:rank_categories,name,' . $rankCategory->id,
            'winner' => 'required|integer',
            'prize_breakup' => 'required|array',
            'prize_breakup.*.from' => 'required|integer|gt:0',
            'prize_breakup.*.to' => 'required|integer|gt:0|lte:winner',
            'prize_breakup.*.percentage' => 'required|gt:0',
        ]);

        $rankPrize = 0;
        $tempFrom = 0;
        $tempTo = 0;
        $totalPlayer = 0;

        foreach ($request->prize_breakup as $i => $breakup) {

            if ($i > 0) {
                if ($tempTo >= $breakup['from']) {
                    return apiResponse(false, 'The previous to field is greater than or equal from field.');
                } else {
                    $tempFrom = $breakup['from'];
                    $tempTo = $breakup['to'];
                }
            } else {
                if ($tempFrom == 0 && $tempTo == 0 && $i == 0) {
                    $tempFrom = $breakup['from'];
                    $tempTo = $breakup['to'];
                }
            }

            if ($breakup['from'] > $breakup['to']) {
                return apiResponse(false, 'The to field must be greater than or equal to from field.');
            }

            $rankPrize += (int)(($breakup['to'] - $breakup['from']) + 1) * $breakup['percentage'];

            $totalPlayer = $breakup['to'];
        }

        if ($rankPrize != 100) {
            return apiResponse(false, "The percentage must be equal to 100%. Current percentage is $rankPrize%");
        }

        if ($totalPlayer != $request->winner) {
            return apiResponse(false, 'Prize breakup can not match with winner field value.');
        }


        $rankCategoryData = [
            'name' => $request->name,
            'winner' => $request->winner
        ];

        $rankCategory->update($rankCategoryData);

        $rankCategory->prizeBreakup()->delete();

        foreach ($request->prize_breakup as $val) {
            $rankLabel = $val['from'] . '-' . $val['to'];

            if ($val['from'] == $val['to']) {
                $rankLabel = $val['from'];
            }

            $ranksArray = [
                'rank' => $rankLabel,
                'from' => $val['from'],
                'to' => $val['to'],
                'percentage' => $val['percentage']
            ];

            $rankCategory->prizeBreakup()->create($ranksArray);
        }

        return apiResponse(true, 'Rank updated.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param RankCategory $rankCategory
     * @return Response
     */
    public function destroy(RankCategory $rankCategory)
    {
        $rankCategory->delete();

        return apiResponse(true, 'Rank removed.');
    }
}
