<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Leaderboard;

class LeaderboardController extends Controller
{
    public function index()
    {
        $competitionId = \request('competitionId');
        $perPage = \request('per_page') ?? 15;
        $search = \request('search');

        $query = Leaderboard::query()
                    ->from('leaderboards','l')
                    ->leftJoin('users as u','u.id','=','l.user_id')
                    ->Join('user_teams as ut','ut.competition_id','=','l.competition_id')
                    ->Join('fixtures as f','f.id','=','ut.fixture_id')
                    ->Join('user_contests as uc','uc.user_team_id','=','ut.id')
                    ->Join('contests as c','c.id','=','uc.contest_id')
                    ->Join('contest_categories as cc','cc.id','=','c.contest_category_id')
                    ->select(['u.*','l.id as lId','l.total_point','l.rank','f.name as fixture_name','cc.name as category_name','c.entry_fee','c.prize','c.total_teams'])
                    ->where([['c.is_mega_contest', 1],['ut.is_leaderboard',1],['l.competition_id',$competitionId]]);
        if (isset($search)) {

            $query->where(function ($query) {
                $search = request('search');
                foreach (['u.username', 'u.email', 'u.phone'] as $field) {
                    $query->orWhere($field, 'LIKE', '%' . $search . '%');
                }
            });
        }

        //$query->where('l.competition_id',$competitionId)->first();
        //$query->orderBy('c.id');
        $query->orderBy('l.rank');
        $query->groupBy('l.user_id');
        $paginator = $query->paginate($perPage);
        $data = [
            'users' => $paginator->items(),
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
        ];
        return apiResponse(true, null, $data);
    }

    public function userDetail(){
        $leaderboardId = \request('leaderboardId');
        $perPage = \request('per_page') ?? 15;

        $query = Leaderboard::query()
        ->from('leaderboards','l')
        ->leftJoin('users as u','u.id','=','l.user_id')
        ->Join('user_teams as ut','ut.user_id','=','u.id')
        ->Join('fixtures as f','f.id','=','ut.fixture_id')
        ->Join('user_contests as uc','uc.user_team_id','=','ut.id')
        ->Join('contests as c','c.id','=','uc.contest_id')
        ->Join('contest_categories as cc','cc.id','=','c.contest_category_id')
        ->select(['u.*','l.id as lId','ut.total_points','l.rank','f.name as fixture_name','cc.name as category_name','c.entry_fee','c.prize','c.total_teams'])
        ->where([['c.is_mega_contest', 1],['ut.is_leaderboard',1],['l.id',$leaderboardId]]);
        if (isset($search)) {
            $query->where(function ($query) {
                $search = request('search');
                foreach (['u.username', 'u.email', 'u.phone'] as $field) {
                    $query->orWhere($field, 'LIKE', '%' . $search . '%');
                }
            });
        }
        //$query->orderBy('l.rank');
        $query->groupBy('ut.id');
        $paginator = $query->paginate($perPage);
        $data = [
        'users' => $paginator->items(),
        'total' => $paginator->total(),
        'per_page' => $paginator->perPage(),
        'current_page' => $paginator->currentPage(),
        ];
        return apiResponse(true, null, $data);
    }
}
