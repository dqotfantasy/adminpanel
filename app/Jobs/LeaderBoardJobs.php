<?php

namespace App\Jobs;

use App\Models\Competition;
use App\Models\UserTeam;
use App\Models\Leaderboard;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;


class LeaderBoardJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 300;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->queue = 'leaderboard';
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info("Leaderboard IS RUNNING");

        $yesterday = date("Y-m-d", strtotime('-1 days'));
        //$fixture = Fixture::query()
        $fixture = Competition::query()
            ->from('competitions', 'cmp')
            ->join('fixtures as f', 'f.competition_id', '=', 'cmp.id')
            ->join('user_contests as uc', 'uc.fixture_id', '=', 'f.id')
            ->join('user_teams as ut', 'ut.id', '=', 'uc.user_team_id')
            ->join('contests as c', 'c.id', '=', 'uc.contest_id')
            ->select('uc.contest_id', 'uc.user_team_id', 'uc.id', 'ut.user_id', 'ut.total_points', 'cmp.id as cmpId')
            ->where([['c.is_mega_contest', 1], ['cmp.is_leaderboard', 1], ['ut.is_leaderboard', 0]])
            ->whereDate('f.ending_at', '=', $yesterday)
            ->orderBy('ut.total_points')
            ->orderBy('cmp.id')
            ->orderBy('f.id')
            ->orderBy('c.id')
            ->orderBy('ut.id')
            ->get();

        $uniqueContestId = $leaderboardData = [];
        foreach ($fixture as $value) {
            $dummyPoint = '';
            if (!empty($value['user_team_id'])) {
                $leaderboardData[$value['contest_id']][$value['user_id']] = array("total_points" => $value['total_points'], "user_team_id" => $value['user_team_id'], "user_id" => $value['user_id'], 'cmpetition_id' => $value['cmpId']);
                $uniqueContestId[$value['contest_id']] = $value['contest_id'];
            }
        }
        //echo "<pre>";print_r($leaderboardData);die;
        //array manage mega contest with particular user and team
        $megaContestData = [];
        if(!empty($uniqueContestId)){
            foreach ($uniqueContestId as $value) {
                $valueArr = [];
                if (!empty($leaderboardData[$value])) {
                    foreach ($leaderboardData[$value] as $cValue) {
                        if (!empty($cValue)) {
                            $use_team = UserTeam::find($cValue['user_team_id']);
                            $use_team->is_leaderboard = 1;
                            if ($use_team->save()) {
                                $leaderboard = Leaderboard::where([
                                    'competition_id' => $cValue['cmpetition_id'],
                                    'user_id' => $cValue['user_id']
                                ])->first();
                                if (!empty($leaderboard)) {
                                    $leaderboard->total_point += $cValue['total_points'];
                                    $leaderboard->save();
                                } else {
                                    $leaderboard = new Leaderboard;
                                    $leaderboard->competition_id = $cValue['cmpetition_id'];
                                    $leaderboard->user_id = $cValue['user_id'];
                                    $leaderboard->total_point = $cValue['total_points'];
                                    $leaderboard->save();
                                }
                            }
                        }
                    }
                }
            }
        }
        $rankleaderboard = Leaderboard::query()
            ->orderBy('competition_id')
            ->orderBy('total_point', 'Desc')->get();
        $total_point = $cmp_id = '';
        $ranks = 0;
        foreach ($rankleaderboard as $rValue) {
            if ($cmp_id != $rValue['competition_id']) {
                $ranks = 0;
            }
            if ($rValue['total_point'] == $total_point) {
                $rank = $ranks;
            } else {
                $ranks++;
                $rank = $ranks;
            }
            $total_point = $rValue['total_point'];
            $cmp_id = $rValue['competition_id'];
            $rankUpdateLead = Leaderboard::find($rValue['id']);
            $rankUpdateLead->rank = $ranks;
            $rankUpdateLead->save();
        }
    }
}
