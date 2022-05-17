<?php

namespace App\Http\Controllers;

use App\Models\Fixture;
use App\Models\PrivateContest;
use App\Models\Squad;
use App\Models\UserTeam;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PrivateContestController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $fixtureId = \request('fixture_id');
        $query = PrivateContest::query()
            ->from('private_contests', 'c')
            ->leftJoin('fixtures as f', 'f.id', '=', 'c.fixture_id')
            ->leftJoin('users as ccc', 'ccc.id', '=', 'c.user_id')
            ->selectRaw("CONCAT(fixtures.name) as fixture")
            ->select(['c.*', 'ccc.username as user', DB::raw('(CONCAT(f.teama," VS ",f.teamb)) as fixture'), DB::raw('(SELECT COUNT(uc.id) FROM user_private_contests as uc WHERE uc.private_contest_id=c.id) as joined')]);

        if (isset($fixtureId) && $fixtureId != null) {
            $query->where('c.fixture_id', $fixtureId);
        }

        if (isset($status) && $status != null) {
            $query->where('c.status', $status);
        }

        $query->latest();
        $contests = $query->get();

        $data = [];

        $data['contests'] = $contests;

        $statuses = [];

        foreach (CONTEST_STATUS as $key => $item) {
            if ($key < 2)
                $statuses[] = ['id' => $item, 'name' => $item];
        }

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
        return apiResponse(true, 'PrivateContest added.');
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param $id
     * @return Response
     */
    public function show(Request $request, $id)
    {
        $contest = PrivateContest::findOrFail($id);
        $contest->load(['fixture']);
        $contest->loadCount('joined');
        return apiResponse(true, null, ['contest' => $contest]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param PrivateContest $contest
     * @return Response
     */
    public function update(Request $request, PrivateContest $contest)
    {
        return apiResponse(true, 'PrivateContest updated.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param PrivateContest $contest
     * @return Response
     */
    public function destroy(PrivateContest $contest)
    {
        //
    }

    public function fixtures()
    {
        $fixtures = Fixture::query()
            ->where('is_active', 1)
            ->where('status', FIXTURE_STATUS[0])
            ->whereDate('starting_at', '>=', now()->toDate())
            ->orderBy('starting_at')
            ->get(['id', 'teama', 'teamb', 'name']);
        return apiResponse(true, null, ['fixtures' => $fixtures]);
    }

    public function userTeams()
    {
        \request()->validate([
            'contest_id' => 'required'
        ]);

        $perPage = \request('per_page') ?? 100;

        $userTeams = UserTeam::query()
            ->from('user_teams')
            ->whereHas('userPrivateContests', function ($builder) {
                $builder->where('private_contest_id', \request('contest_id'));
            })
            ->join('user_private_contests', function ($join) {
                $join->on('user_teams.id', '=', 'user_private_contests.user_team_id')
                    ->where('user_private_contests.contest_id', \request('contest_id'));
            })
            ->leftJoin('users as u', 'u.id', '=', 'user_teams.user_id')
            ->select(['user_teams.*', 'u.username as username', 'user_private_contests.rank', 'user_private_contests.prize'])
            ->paginate($perPage);

        $contest = PrivateContest::query()->findOrFail(\request('contest_id'));

        $squads = Squad::query()
            ->where('fixture_id', $contest->fixture_id)
            ->leftJoin('players as p', 'p.id', '=', 'squads.player_id')
            ->select(['role', 'fantasy_player_rating', 'p.image', 'p.name', 'squads.player_id as id', 'player_id'])
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
}
