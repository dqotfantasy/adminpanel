<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use Illuminate\Http\Request;
use App\Models\Fixture;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Redis;



class CompetitionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $perPage = \request('per_page') ?? 15;
        $status = \request('status');
        $search = \request('search');

        $query = Competition::query();
        if (isset($search)) {
            $query->where('title', 'LIKE', '%' . $search . '%');
        }
        if (isset($status)) {
            $query->whereIn('status', explode(",", $status));
        }
        $competitios = $query->paginate($perPage);

        $statuses = [];

        foreach (COMPETITION_STATUS as $key => $item) {
            if ($key < 2)
                $statuses[] = ['id' => $item, 'name' => $item];
        }
        $data = [];
        $data['competitios'] = $competitios;

        $data['statuses'] = $statuses;
        return apiResponse(true, null, $data);
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
     * @return Response
     */
    public function show(Competition $competition)
    {
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function update(Request $request, Competition $competition)
    {
        if (empty(request('leaderboard_text'))) {
            $data = $request->validate([
                'prize_breakup' => 'bail|required|array',
                'prize_breakup.*.from' => 'required|gt:0',
                'prize_breakup.*.to' => 'required|gt:0',
                'prize_breakup.*.prize' => 'required|gt:0',
                //'prize_breakup.photo' => 'image',
            ]);
            $msg = 'Prize breakup updated.';
            $newArr=[];
            foreach ($data['prize_breakup'] as $key=>$value) {
                $getnullVal= json_encode($value['photo'],true);

                if (!empty($value['photo']) && $getnullVal!='[null]') {
                    if(!stripos($value['photo'],'amazonaws')){
                        if(is_array($value['photo'])){
                            $value['photo']=$value['photo'][0];
                        }
                        $path=$value['photo']->storePublicly('teams', 's3');
                        $value['photo'] = Storage::disk('s3')->url($path);

                        if (isset($competition->prize_breakup[$key]['photo'])) {
                            $path = $competition->prize_breakup[$key]['photo'];
                            if ($path) {
                                Storage::disk('s3')->delete("teams/" . Str::after($path, "/teams"));
                            }
                        }
                    }else{
                        $value['photo'] = $value['photo'];

                    }


                }else{
                    $value['photo'] = '';
                }
                $newArr[]=$value;
            }
            $data['prize_breakup']=$newArr;
        } else {
            $data = $request->validate([
                'is_leaderboard' => 'bail',
                'is_active' => 'bail'
            ]);

            if(!$request->is_active){
                $query = Fixture::where('competition_id',$competition->id)->update(['is_active'=>0]);
                // $disabledseries = Redis::get('disabledseries:');
                // $disabledArr=json_decode($disabledseries,true);
                // if(empty($disabledArr)){
                //     $disabledArr=[];
                // }

                // array_push($disabledArr,$competition->id);
                // $competitionJson=json_encode($disabledArr);
                // return $competitionJson;
                // Redis::set('disabledseries:',$competitionJson);
                //return $competitionJson;
            }else{
                $query = Fixture::where('competition_id',$competition->id)->update(['is_active'=>1]);

                // $disabledseries = Redis::get('disabledseries:');
                // $disabledArr=json_decode($disabledseries,true);
                // if(empty($disabledArr)){
                //     $disabledArr=[];
                // }

                // if (($key = array_search($competition->id, $disabledArr)) !== false) {
                //     unset($disabledArr[$key]);
                // }
                // $competitionJson=json_encode($disabledArr);
                // return $competitionJson;

                // Redis::set('disabledseries:',$competitionJson);
                //return $competitionJson;

            }
            $msg = 'Competition leaderboard status updated.';
        }
        //return $data;
        $competition->update($data);
        return apiResponse(true, $msg);

        // $data = $request->validate([
        //     'is_active' => 'required|boolean',
        // ]);

        // $competition->update($data);

        // return apiResponse(true, 'Competition status updated.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(Competition $competition)
    {
        $competition->loadExists('fixtures');

        if ($competition->fixtures_exists) {
            return apiResponse(false, 'Competition can not be removed.');
        } else {
            $competition->delete();
            return apiResponse(true, 'Competition removed.');
        }
    }

    public function liveCompetition()
    {
        $competition = Competition::query()
            ->select(['id', 'title as name'])
            ->whereDate('dateend', '>=', Carbon::today()->toDateString())
            ->get(['id', 'name']);

        return apiResponse(true, null, ['competitions' => $competition]);
    }
}
