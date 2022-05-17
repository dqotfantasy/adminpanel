<?php

namespace App\Http\Controllers;

use App\Jobs\ContestCancel;
use App\Models\Contest;
use App\Models\ContestCategory;
use App\Models\ContestTemplate;
use App\Models\Fixture;
use App\Models\Squad;
use App\Models\UserTeam;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use App\Jobs\CalculateDynamicPrizeBreakup;
use Illuminate\Support\Facades\Validator;

class ContestController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $fixtureId = \request('fixture_id');
        $userId = \request('user_id');
        $status = \request('status');
        $mode = \request('mode');
        $perPage = \request('per_page') ?? 15;
        if(!empty($userId)){
            $query = Contest::query()
                ->from('contests', 'c')
                ->leftJoin('fixtures as f', 'f.id', '=', 'c.fixture_id')
                ->leftJoin('contest_categories as cc', 'cc.id', '=', 'c.contest_category_id')
                ->leftJoin('user_contests', 'user_contests.contest_id', '=', 'c.id')
                ->selectRaw("CONCAT(fixtures.name) as fixture")
                ->select(['c.*', 'cc.name as category', DB::raw('(CONCAT(f.teama," VS ",f.teamb)) as fixture'), DB::raw('(SELECT COUNT(uc.id) FROM user_contests as uc WHERE uc.contest_id=c.id) as joined'), 'f.status as fixture_status']);
            $query->where('user_contests.user_id', $userId);

        }else{
            $query = Contest::query()
                ->from('contests', 'c')
                ->leftJoin('fixtures as f', 'f.id', '=', 'c.fixture_id')
                ->leftJoin('contest_categories as cc', 'cc.id', '=', 'c.contest_category_id')
                ->selectRaw("CONCAT(fixtures.name) as fixture")
                ->select(['c.*', 'cc.name as category', DB::raw('(CONCAT(f.teama," VS ",f.teamb)) as fixture'), DB::raw('(SELECT COUNT(uc.id) FROM user_contests as uc WHERE uc.contest_id=c.id) as joined'), 'f.status as fixture_status']);
        }
        // if(!empty($mode)){

        //     $query = Contest::query()
        //         ->from('contests', 'c')
        //         ->leftJoin('fixtures as f', 'f.id', '=', 'c.fixture_id')
        //         ->leftJoin('contest_categories as cc', 'cc.id', '=', 'c.contest_category_id')
        //         ->leftJoin('user_contests as uc', 'uc.contest_id', '=', 'c.id')
        //         ->selectRaw("CONCAT(fixtures.name) as fixture")
        //         ->select(['c.*', 'cc.name as category', DB::raw('(CONCAT(f.teama," VS ",f.teamb)) as fixture'), DB::raw('(SELECT COUNT(uc.id) FROM user_contests as uc WHERE uc.contest_id=c.id) as joined'), 'f.status as fixture_status']);

        // }

        if (isset($fixtureId) && $fixtureId != null) {
            $query->where('c.fixture_id', $fixtureId);
        }

        if (isset($status) && $status != null && $status != "IN ACTIVE" && $status != "Today Join") {
            $query->where('c.status', $status);
        }
        if(isset($status) && $status == "Today Join"){
            $query->leftjoin('user_contests as uc', 'uc.contest_id', '=', 'c.id')->whereDate('uc.created_at','=',now());
        }
        if (isset($status) && $status == "IN ACTIVE") {
            $query->where('c.status','!=',"LIVE");
        }

        $query->latest();
        $contests = $query->paginate($perPage);
        $data = [];
        $role_id=roleId();
        $data['role_id'] = $role_id;
        $data['contests'] = $contests;

        $statuses = [];
        $statusesList = [];
        foreach (CONTEST_STATUS as $key => $item) {
            // if($role_id==2 && $key==1){
            //     continue;
            // }
            if ($key < 2) {
                $statuses[] = ['id' => $item, 'name' => $item];
            }
            $statusesList[] = ['id' => $item, 'name' => $item];
        }
        $data['statuses'] = $statuses;
        $data['statuseslist'] = $statusesList;

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


        $fixture = Fixture::query()->findOrFail($request->fixture_id);

        if (!$fixture) {
            return apiResponse(false, 'Fixture not found');
        }

        if ($fixture->status != FIXTURE_STATUS[0]) {
            return apiResponse(false, 'Match started');
        }

        if (isset($request->type) && $request->type) {
            if ($request->type == 'PRACTICE') {
                $data = $request->validate([
                    'fixture_id' => 'bail|required',
                    'contest_category_id' => 'bail|required|exists:contest_categories,id',
                    'total_teams' => 'bail|required|integer|min:2',
                    'is_confirmed' => 'bail|required|boolean',
                    //'entry_fee' => 'min:0',
                    'max_team' => 'bail|required|integer|min:1',
                    'is_dynamic' => 'bail|integer',
                    //'prize' => 'bail|integer',
                    'inning_number' => 'bail|integer',
                    'auto_create_on_full' => 'required|boolean',
                    'type' => 'bail|required',
                    'bonus' => 'bail|required|numeric',
                    'is_mega_contest' => 'bail|required|boolean',
                    'status' => 'required|in:' . implode(",", [CONTEST_STATUS[0], CONTEST_STATUS[1]]),
                    'dynamic_min_team' => 'bail|integer|min:0',
                ]);
            } else {
                $data = $request->validate([
                    'fixture_id' => 'bail|required',
                    'contest_category_id' => 'bail|required|exists:contest_categories,id',
                    'total_teams' => 'bail|required|integer|min:2',
                    'is_confirmed' => 'bail|required|boolean',
                    'entry_fee' => 'bail|required|integer',
                    'max_team' => 'bail|required|integer|min:1',
                    'prize' => 'bail|integer',
                    'inning_number' => 'bail|integer',
                    'is_dynamic' => 'bail|integer',
                    'auto_create_on_full' => 'required|boolean',
                    'type' => 'bail|required',
                    'discount' => 'required_if:type,DISCOUNT|integer',
                    'bonus' => 'bail|required|numeric',
                    'is_mega_contest' => 'bail|required|boolean',
                    'prize_breakup' => 'bail|required|array',
                    'prize_breakup.*.from' => 'required|gt:0',
                    'prize_breakup.*.to' => 'required|gt:0|lte:total_teams',
                    'prize_breakup.*.prize' => 'required|gt:0',
                    'status' => 'required|in:' . implode(",", [CONTEST_STATUS[0], CONTEST_STATUS[1]]),
                    'dynamic_min_team' => 'bail|integer|min:0',
                ]);
            }
        } else {
            return apiResponse(false, 'Type Field is requied');
        }
        //return print_r($data);die;


        $entryFee = $request->entry_fee;
        $totalTeams = $request->total_teams;
        $total = $entryFee * $totalTeams;
        $prize = $request->prize;

        if ($prize > $total) {
            return apiResponse(false, 'Invalid prize value.');
        }

        $rankPrize = 0;
        $lastWinner = 0;
        if ($request->type !== 'PRACTICE') {
            foreach ($request->prize_breakup as $breakup) {
                if ($breakup['from'] > $breakup['to']) {
                    return apiResponse(false, 'The to field must be greater than or equal to from field.');
                }

                $rankPrize += (($breakup['to'] - $breakup['from']) + 1) * $breakup['prize'];
                //return $breakup['prize'].'==='.$rankPrize." > ".$prize;
                if ($rankPrize > $prize) {
                    return apiResponse(false, 'Invalid prize value.');
                }
                $lastWinner = $breakup['to'];
            }
            if ($rankPrize < $prize) {
                return apiResponse(false, 'Invalid prize breakup.');
            }
            $data['winner_percentage'] = (100 * $lastWinner) / $totalTeams;
            $data['commission'] = $request->commission;
        } else {
            $data['winner_percentage'] = 0;
            $data['entry_fee'] = 0;
            $data['prize'] = 0;
            $data['commission'] = 0;
            $data['prize_breakup'] = [];
        }
        $data['invite_code'] = generateRandomString();
        if($data['is_dynamic']==0){
            $data['dynamic_min_team']=0;
        }
        $contest = Contest::query()->create($data);

        Redis::set("contestSpace:$contest->id", $contest->total_teams);
        $megacontestprize=Contest::query()->where([['fixture_id',$request->fixture_id],['is_mega_contest',1]])->max('prize');
        $fixture->update(['mega_value' => $megacontestprize]);
        // if ($fixture->mega_value < $contest->prize) {
        //     $fixture->update(['mega_value' => $data['prize']]);;
        // }
        // here to be added redis key contestSpace:ContestId with value of 2
        return apiResponse(true, 'Contest added.');
    }

    /**
     * Display the specified resource.
     *
     * @param Contest $contest
     * @return Response
     */
    public function show(Contest $contest)
    {
        $contest->load(['category', 'fixture']);
        $contest->loadCount('joined');
        return apiResponse(true, null, ['contest' => $contest]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Contest $contest
     * @return Response
     */
    public function update(Request $request, Contest $contest)
    {
        // if ($contest->joined()->count() > 0) {
        //     return apiResponse(false, 'Team joined in this contests.');
        // }

        if (isset($request->type) && $request->type) {
            if ($request->type == 'PRACTICE') {
                $validator = Validator::make($request->all(), [
                    'fixture_id' => 'bail|required',
                    'contest_category_id' => 'bail|required|exists:contest_categories,id',
                    'total_teams' => 'bail|required|integer',
                    'is_confirmed' => 'bail|required|boolean',
                    'type' => 'bail|required',
                    'bonus' => 'bail|required|numeric',
                    'is_mega_contest' => 'bail|required|boolean',
                    //'entry_fee' => 'bail|required|integer',
                    'max_team' => 'bail|required|integer|min:1',
                    'inning_number' => 'bail|integer',
                    'is_dynamic' => 'bail|integer',
                    //'prize' => 'bail|integer',
                    'status' => 'bail|required|in:' . implode(",", [CONTEST_STATUS[0], CONTEST_STATUS[1]]),
                    'dynamic_min_team' => 'bail|integer|min:0'
                ]);
            } else {
                $validator = Validator::make($request->all(), [
                    'fixture_id' => 'bail|required',
                    'contest_category_id' => 'bail|required|exists:contest_categories,id',
                    'total_teams' => 'bail|required|integer',
                    'is_confirmed' => 'bail|required|boolean',
                    'type' => 'bail|required',
                    'discount' => 'required_if:type,DISCOUNT|integer',
                    'bonus' => 'bail|required|numeric',
                    'is_mega_contest' => 'bail|required|boolean',
                    'entry_fee' => 'bail|required|integer',
                    'max_team' => 'bail|required|integer|min:1',
                    'inning_number' => 'bail|integer',
                    'is_dynamic' => 'bail|integer',
                    'prize' => 'bail|integer',
                    'prize_breakup' => 'bail|required|array',
                    'prize_breakup.*.from' => 'required|gt:0',
                    'prize_breakup.*.to' => 'required|gt:0|lte:total_teams',
                    'prize_breakup.*.prize' => 'required|gt:0',
                    'status' => 'bail|required|in:' . implode(",", [CONTEST_STATUS[0], CONTEST_STATUS[1]]),
                    'dynamic_min_team' => 'bail|integer|min:0'
                ]);
            }
        } else {
            return apiResponse(false, 'Type Field is requied');
        }

        $fixture = Fixture::find($request->fixture_id);

        if (!$fixture) {
            return apiResponse(false, 'Fixture not found');
        }

        if ($fixture->status != FIXTURE_STATUS[0] && $request->is_dynamic) {
            CalculateDynamicPrizeBreakup::dispatch($request->fixture_id,true);

        }

        if ($validator->fails()) {
            return apiResponse(false, $validator->errors()->first());
        }

        $entryFee = $request->entry_fee;
        $totalTeams = $request->total_teams;
        $total = $entryFee * $totalTeams;
        $prize = $request->prize;


        if ($prize > $total) {
            return apiResponse(false, 'Invalid prize value.');
        }

        $update = $validator->validated();

        $rankPrize = 0;
        $lastWinner = 0;
        if ($request->type !== 'PRACTICE') {
            foreach ($request->prize_breakup as $breakup) {
                if ($breakup['from'] > $breakup['to']) {
                    return apiResponse(false, 'To field must be greater than or equal to From field.');
                }

                $rankPrize += (($breakup['to'] - $breakup['from']) + 1) * $breakup['prize'];

                if ($rankPrize > $prize) {
                    return apiResponse(false, 'Invalid prize value.');
                }

                $lastWinner = $breakup['to'];
            }
            if ($rankPrize < $prize) {
                return apiResponse(false, 'Invalid prize breakup.');
            }
            $update['winner_percentage'] = (100 * $lastWinner) / $totalTeams;
            $update['commission'] = $request->commission;

        } else {
            $update['winner_percentage'] = 0;
            $update['prize_breakup'] = [];
            $update['commission'] = 0;
            $update['entry_fee'] = 0;
            $update['prize'] = 0;

        }
        if($update['is_dynamic']==0){
            $update['dynamic_min_team']=0;
        }
        //return print_r($update);

        $contest->update($update);

        if ($contest->wasChanged(['total_teams'])) {
            Redis::set("contestSpace:$contest->id", (string)$contest->total_teams);
        }
        $megacontestprize=Contest::query()->where([['fixture_id',$request->fixture_id],['is_mega_contest',1]])->max('prize');
        $fixture->update(['mega_value' => $megacontestprize]);

        // if ($fixture->mega_value < $contest->prize) {
        //     $fixture->update(['mega_value' => $prize]);
        // }

        return apiResponse(true, 'Contest updated.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Contest $contest
     * @return Response
     */
    public function destroy(Contest $contest)
    {
        return apiResponse(true, 'Contest removed.');
    }

    public function fixtures()
    {
        $fixtures = Fixture::query()
            ->where('is_active', 1)
            ->where('status', FIXTURE_STATUS[0])
            ->whereDate('starting_at', '>=', now()->toDate())
            ->orderBy('starting_at')
            ->get(['id', 'teama', 'teamb', 'name','format_str']);
        return apiResponse(true, null, ['fixtures' => $fixtures]);
    }

    public function categories()
    {
        $contestCategories = ContestCategory::all(['id', 'name']);
        return apiResponse(true, null, ['contest_categories' => $contestCategories]);
    }

    public function contestTemplate()
    {
        $contestTemplate = ContestTemplate::all();
        return apiResponse(true, null, ['contestTemplate' => $contestTemplate]);
    }

    public function userTeams()
    {
        \request()->validate([
            'contest_id' => 'required'
        ]);

        $perPage = \request('per_page') ?? 100;
        $user_id = \request('user_id');
        $search = \request('search');
        if(!empty($user_id)){
            $userTeams = UserTeam::query()
                ->from('user_teams')
                ->whereHas('userContests', function ($builder) {
                    $builder->where('contest_id', \request('contest_id'));
                })
                ->where('user_teams.user_id','=',$user_id)
                ->join('user_contests', function ($join) {
                    $join->on('user_teams.id', '=', 'user_contests.user_team_id')
                        ->where('user_contests.contest_id', \request('contest_id'));
                })
                ->leftJoin('users as u', 'u.id', '=', 'user_teams.user_id')
                ->select(['user_teams.*', 'u.username as username', 'user_contests.rank', 'user_contests.prize'])
                ->orderBy('rank')
                ->paginate($perPage);
        }else{
            $query = UserTeam::query()
                ->from('user_teams')
                ->whereHas('userContests', function ($builder) {
                    $builder->where('contest_id', \request('contest_id'));
                })
                ->join('user_contests', function ($join) {
                    $join->on('user_teams.id', '=', 'user_contests.user_team_id')
                        ->where('user_contests.contest_id', \request('contest_id'));
                })
                ->leftJoin('users as u', 'u.id', '=', 'user_teams.user_id');
                if (isset($search)) {
                    $query->where('u.username', 'LIKE', '%' . $search . '%');
                    // foreach (['u.name','u.username'] as $field) {
                    //     $query->orWhere($field, 'LIKE', '%' . $search . '%');
                    // }
                }

                $userTeams=$query->select(['user_teams.*', 'u.username as username', 'user_contests.rank', 'user_contests.prize'])->orderBy('rank')
                ->paginate($perPage);
        }
        $contest = Contest::query()->findOrFail(\request('contest_id'));

        $squads = Squad::query()
            ->where('fixture_id', $contest->fixture_id)
            ->leftJoin('players as p', 'p.id', '=', 'squads.player_id')
            ->select(['role', 'fantasy_player_rating', 'total_points', 'p.image', 'p.name', 'squads.player_id as id', 'player_id'])
            ->get();

        $userTeams->getCollection()->transform(function ($team, $key) use ($squads) {
            $val = $team;
            if (!is_array($val->players)) {
                $val->players = json_decode($val->players);
            }
            $val->players = $squads->whereIn('player_id', $val->players)->values();

            return $val;
        });

        return apiResponse(true, null, $userTeams);
    }

    public function contestCancel(Request $request, $id)
    {
        if (Redis::sismember("contest_in_cancelling", $id)) {
            return apiResponse(true, 'Contest cancellation is in progress.');
        }

        $contest = Contest::query()->where('id', $id)->where('status', CONTEST_STATUS[1])->first();

        if (!$contest) {
            return apiResponse(false, 'Contest is not live.');
        }

        Redis::sadd("contest_in_cancelling", $id);
        ContestCancel::dispatch($contest->id);

        return apiResponse(true, 'Contest cancelled.');
    }
}
