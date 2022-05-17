<?php

namespace App\Http\Controllers;

use App\Models\ContestCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ContestCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $perPage = \request('per_page') ?? 15;
        $query = ContestCategory::query();
        $query->withCount('contests');
        $categories = $query->paginate($perPage);

        return apiResponse(true, null, ['contest_categories' => $categories]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|unique:contest_categories',
            'tagline' => 'required|unique:contest_categories',
            'sequence_by' => 'required|unique:contest_categories',
            'is_active' => 'boolean',
        ]);
        $query=ContestCategory::where('sequence_by',$request->sequence_by)->exists();
        if($query){
            return apiResponse(false, 'Sequence alredy save please enter another sequence number.');
        }else{
            ContestCategory::query()->create($data);
            return apiResponse(true, 'Contest category added.');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param ContestCategory $contestCategory
     * @return Response
     */
    public function show(ContestCategory $contestCategory)
    {
        return apiResponse(true, null, ['contest_category' => $contestCategory]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param ContestCategory $contestCategory
     * @return Response
     */
    public function update(Request $request, ContestCategory $contestCategory)
    {
        $data = $request->validate([
            'name' => 'required|unique:contest_categories,id,' . $contestCategory->id,
            'tagline' => 'required|unique:contest_categories,id,' . $contestCategory->id,
            'sequence_by' => 'required|unique:contest_categories,id,' . $contestCategory->id,
            'is_active' => 'boolean',
        ]);

        $query=ContestCategory::where('sequence_by',$request->sequence_by)->exists();
        if($query && $request->sequence_by!=$contestCategory->sequence_by){
            return apiResponse(false, 'Sequence alredy save please enter another sequence number.');
        }else{
            $contestCategory->update($data);
            return apiResponse(true, 'Contest category updated.');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param ContestCategory $contestCategory
     * @return Response
     */
    public function destroy(ContestCategory $contestCategory)
    {
        $contestCategory->loadExists('contests');

        if ($contestCategory->contest_exists) {
            return apiResponse(false, 'Contest category can not be removed.');
        } else {
            $contestCategory->delete();
            return apiResponse(true, 'Contest category removed.');
        }
    }
}
