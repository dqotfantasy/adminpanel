<?php

namespace App\Http\Controllers;

use App\Models\Fixture;
use App\Jobs\GetLineup;
use App\Models\Contest;
use App\Models\UserTeam;
use App\Models\Competition;
use App\Models\Squad;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class FixtureController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $cquery = Competition::select('title')->get();


        $user_id = \request('user_id');
        $perPage = \request('per_page') ?? 15;
        $fromDate = \request('from_date');
        $toDate = \request('to_date');
        $status = \request('status');
        $default_status = \request('default_status');
        $search = \request('search');


        if(empty($user_id)){
            $query = Fixture::query();
            $query->withCount(['contests', 'user_teams']);

            $competition_name = \request('competitionStatus');
            $perPage = \request('per_page') ?? 15;
            if (isset($search)) {
                $query->where('name', 'LIKE', '%' . $search . '%');
            }
            if(!empty($status) && $status=='NOT STARTED'){
                $query->where('status', $status);
            }else{
                if (!empty($status) && in_array($status,explode(",",$default_status))) {
                    $query->where('status', $status);
                }else{
                    $query->whereIn('status', explode(",", $default_status));
                }
            }
            if (isset($competition_name)) {

                $query->where('competition_name',$competition_name);
            }
            // return $competition_name."ttt";die;
            // return apiResponse(true, null, $competition_name);


            if (isset($fromDate) && isset($toDate)) {
                \request()->validate([
                    'from_date' => 'before_or_equal:to_date'
                ]);

                $query->whereDate('starting_at', '>=', $fromDate);
                $query->whereDate('starting_at', '<=', $toDate);
            } elseif (isset($fromDate)) {
                \request()->validate([
                    'from_date' => 'date'
                ]);

                $query->whereDate('starting_at', '>=', $fromDate);
            } elseif (isset($toDate)) {
                \request()->validate([
                    'to_date' => 'date'
                ]);

                $query->whereDate('starting_at', '<=', $toDate);
            }
            if(!empty($default_status) && $default_status=='COMPLETED,CANCELED'){
                $query->orderBy('starting_at','DESC');
            }else{
                $query->orderBy('starting_at');
            }
            $fixtures = $query->paginate($perPage);
        }
        else{
            $query = Fixture::join('contests', 'contests.fixture_id', '=', 'fixtures.id')
                        ->join('user_contests', 'user_contests.contest_id', '=', 'contests.id')
                        ->where('user_contests.user_id',$user_id);
            //$query->user_contests()->where('user_id',$user_id);
            $query->withCount(['contests', 'user_teams']);
            if (isset($search)) {
                $query->where('fixtures.name', 'LIKE', '%' . $search . '%');
            }


            if (isset($fromDate) && isset($toDate)) {
                \request()->validate([
                    'from_date' => 'before_or_equal:to_date'
                ]);

                $query->whereDate('fixtures.starting_at', '>=', $fromDate);
                $query->whereDate('fixtures.starting_at', '<=', $toDate);
            } elseif (isset($fromDate)) {
                \request()->validate([
                    'from_date' => 'date'
                ]);

                $query->whereDate('fixtures.starting_at', '>=', $fromDate);
            } elseif (isset($toDate)) {
                \request()->validate([
                    'to_date' => 'date'
                ]);

                $query->whereDate('fixtures.starting_at', '<=', $toDate);
            }

            if (isset($status)) {
                $query->whereIn('fixtures.status', explode(",", $status));
            }
            // if (!empty($status)) {
            //     $query->where('fixtures.status', $status);
            // }else{
            //     $query->where('fixtures.status', $default_status);
            // }
            $fixtures = $query->paginate($perPage);

        }



        $data['fixtures'] = $fixtures;

        $statuses = [];
        foreach (FIXTURE_STATUS as $item) {
            $statuses[] = ['id' => $item, 'name' => $item];
        }
        $competitionStatus=[];
        foreach($cquery as $cValue){
            $competitionStatus[]=['id' => $cValue['title'], 'name' => $cValue['title']];
        }
        $data['statuses'] = $statuses;
        $data['competitionStatus'] = $competitionStatus;

        $types = [];
        foreach (CRICKET_TYPES as $item) {
            $types[] = ['id' => $item, 'name' => $item];
        }
        $data['types'] = $types;

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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param Fixture $fixture
     * @return Response
     */
    public function show(Request $request, Fixture $fixture)
    {
        $fixture->load('squads.player');

        foreach ($fixture["squads"] as $squad) {
            $stat = [];

            $stat[] = ['event' => 'Starting 11', 'actual' => $squad->playing11 ? 'Yes' : 'No', 'points' => $squad->playing11_point];
            $stat[] = ['event' => 'Runs', 'actual' => $squad->runs, 'points' => $squad->runs_point];
            $stat[] = ['event' => '4\'s', 'actual' => $squad->fours, 'points' => $squad->fours_point];
            $stat[] = ['event' => '6\'s', 'actual' => $squad->sixes, 'points' => $squad->sixes_point];
            $stat[] = ['event' => 'S/R', 'actual' => $squad->strike_rate, 'points' => $squad->strike_rate_point];
            $stat[] = ['event' => '30/50/100', 'actual' => $squad->century_half_century, 'points' => $squad->century_half_century_point];
            $stat[] = ['event' => 'Duck', 'actual' => $squad->duck ? 'Yes' : 'No', 'points' => $squad->duck_point];
            $stat[] = ['event' => 'Wkts', 'actual' => $squad->wicket, 'points' => $squad->wicket_point];
            $stat[] = ['event' => 'Maiden Over', 'actual' => $squad->maiden_over, 'points' => $squad->maiden_over_point];
            $stat[] = ['event' => 'E/R', 'actual' => $squad->economy_rate, 'points' => $squad->economy_rate_point];
            $stat[] = ['event' => 'Bonus', 'actual' => 0, 'points' => 0];
            $stat[] = ['event' => 'Catch', 'actual' => $squad->catch, 'points' => $squad->catch_point];
            $stat[] = ['event' => 'Run Out/Stumping', 'actual' => $squad->runoutstumping_point, 'points' => $squad->runoutstumping_point];
            $stat[] = ['event' => 'Bonus', 'actual' => $squad->bonus_point, 'points' => $squad->bonus_point];
            $stat[] = ['event' => 'Total', 'actual' => '', 'points' => $squad->total_points];

            $squad['stat'] = $stat;
        }

        $positions = [];
        foreach (POSITIONS as $p) {
            $positions[] = ['id' => $p, 'name' => $p];
        }

        $fixture['positions'] = $positions;


        return apiResponse(true, null, ['fixture' => $fixture]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Fixture $fixture
     * @return Response
     */
    public function update(Request $request, Fixture $fixture)
    {
        $starting_at = \request('starting_at');
        if(!empty($starting_at) && !isset($request->cancel_allow)){
            if(strtotime($fixture->starting_at)!=strtotime($starting_at)){
                $data['starting_at'] = $starting_at;
                $data['status'] = FIXTURE_STATUS[0];
                if(strtotime($fixture->starting_at)<strtotime($starting_at)){
                    GetLineup::dispatch($fixture->id,true);
                }
            }
        }else{
            if($request->hasFile('teama_image') || $request->hasFile('teamb_image')){
                if ($request->hasFile('teama_image')) {
                    $request->validate([
                        'teama_image' => 'image',
                    ]);

                    $path = $request->file('teama_image')->storePublicly('teams', 's3');
                    $data['teama_image'] = Storage::disk('s3')->url($path);

                    if (isset($fixture->teama_image)) {
                        $path = $fixture->teama_image;
                        if ($path) {
                            Storage::disk('s3')->delete("teams/" . Str::after($path, "/teams"));
                        }
                    }
                }

                if ($request->hasFile('teamb_image')) {
                    $request->validate([
                        'teamb_image' => 'image',
                    ]);

                    $path = $request->file('teamb_image')->storePublicly('teams', 's3');
                    $data['teamb_image'] = Storage::disk('s3')->url($path);

                    if (isset($fixture->teamb_image)) {
                        $path = $fixture->teamb_image;
                        if ($path) {
                            Storage::disk('s3')->delete("teams/" . Str::after($path, "/teams"));
                        }
                    }
                }
            }else{
                $data = $request->validate([
                    'is_active' => 'boolean',
                    'allow_prize_distribution' => 'boolean',
                    'cancel_allow' => 'boolean'
                    //'status' => 'boolean',
                ]);

                if(!empty($request->status)){
                    $data = $request->validate([
                        'status' => 'bail|required',
                        'is_active' => 'boolean',
                        'allow_prize_distribution' => 'boolean',
                        'cancel_allow' => 'boolean'
                        // 'is_active' => 'boolean',
                        // 'cancel_allow' => 'boolean'
                    ]);
                }

                if(!$request->allow_prize_distribution){
                    $data['allow_prize_distribution']=1;
                }else{
                    $data['allow_prize_distribution']=0;
                }

                if(!$request->cancel_allow){
                    $data['cancel_allow']=1;
                }else{
                    $data['cancel_allow']=0;
                }
            }
        }
        $message="Fixture updated.";
        //return $data;
        if(!empty($data)){
            $fixture->update($data);
        }

        return apiResponse(true, $message);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Fixture $fixture
     * @return Response
     */
    public function destroy(Fixture $fixture)
    {
        //
    }

    public function winners()
    {
        $query = Fixture::query();
        $query->withCount(['contests', 'user_teams']);

        $search = \request('search');
        $fromDate = \request('from_date');
        $toDate = \request('to_date');
        $perPage = \request('per_page') ?? 15;
        if (isset($search)) {
            $query->where('name', 'LIKE', '%' . $search . '%');
        }

        $query->where('is_active', 1);
        $query->where('status', FIXTURE_STATUS[3]);
        // $query->whereDate('starting_at', '>=', now()->toDate());

        // if (isset($fromDate) && isset($toDate)) {
        //     \request()->validate([
        //         'from_date' => 'before_or_equal:to_date'
        //     ]);

        //     $query->whereDate('starting_at', '>=', $fromDate);
        //     $query->whereDate('starting_at', '<=', $toDate);
        // } elseif (isset($fromDate)) {
        //     \request()->validate([
        //         'from_date' => 'date'
        //     ]);

        //     $query->whereDate('starting_at', '>=', $fromDate);
        // } elseif (isset($toDate)) {
        //     \request()->validate([
        //         'to_date' => 'date'
        //     ]);

        //     $query->whereDate('starting_at', '<=', $toDate);
        // }

        $query->orderBy('starting_at');

        $fixtures = $query->paginate($perPage);

        return apiResponse(true, null, ['fixtures' => $fixtures]);
    }

    public function contests(Request $request, $id)
    {
        $query = Contest::query();
        $query->with(['joined' => function ($q) {
            $q->with('user_teams');
            $q->orderBy('rank', 'asc');
            $q->limit('10');
        }]);
        if ($id) {
            $query->where('fixture_id', $id);
        }

        $query->where('status', 'COMPLETED');
        $query->where('is_mega_contest', '1');

        $fromDate = \request('from_date');
        $toDate = \request('to_date');

        if (isset($fromDate) && isset($toDate)) {
            \request()->validate([
                'from_date' => 'before_or_equal:to_date'
            ]);

            $query->whereDate('created_at', '>=', $fromDate);
            $query->whereDate('created_at', '<=', $toDate);
        } elseif (isset($fromDate)) {
            \request()->validate([
                'from_date' => 'date'
            ]);

            $query->whereDate('created_at', '>=', $fromDate);
        } elseif (isset($toDate)) {
            \request()->validate([
                'to_date' => 'date'
            ]);

            $query->whereDate('created_at', '<=', $toDate);
        }

        $query->orderBy('created_at', 'desc');

        $contests = $query->get();

        // foreach ($contests as $contest) {
        //     $contest->user_teams = $this->userTeams($contest->id);
        // }
        return apiResponse(true, null, ['contests' => $contests]);
    }

    public function userTeams($contestId)
    {
        $userTeams = UserTeam::query()
            ->from('user_teams')
            ->whereHas('userContests', function ($builder) use ($contestId) {
                $builder->where('contest_id', $contestId);
                $builder->orderBy('rank', 'asc');
                $builder->limit('10');
            })
            ->leftJoin('users as u', 'u.id', '=', 'user_teams.user_id')
            ->latest()
            ->select(['user_teams.*', 'u.username as username'])
            ->get();


        foreach ($userTeams as $key => $val) {
            $userTeams[$key]['players'] = Squad::query()
                ->select(['role', 'fantasy_player_rating', 'p.image', 'p.name', 'squads.player_id as id'])
                ->from('squads')
                ->leftJoin('players as p', 'p.id', '=', 'squads.player_id')
                ->groupBy('squads.player_id')
                ->whereIn('squads.player_id', $val->players)
                ->get();
        }
        return $userTeams;
    }

    public function fixtureget()
    {
        $fixtures = Fixture::query()
            ->where('is_active', 1)
            ->where('status', FIXTURE_STATUS[0])
            ->whereDate('starting_at', '>=', now()->toDate())
            ->select(['id', 'teama', 'teamb','format_str', DB::raw('CONCAT(name," ",starting_at) AS names')])
            ->orderBy('starting_at')
            ->get();
        return apiResponse(true, null, ['fixtures' => $fixtures]);
    }
}
