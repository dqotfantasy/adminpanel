<?php

namespace App\Jobs;

use App\Models\Fixture;
use App\Models\UserContest;
use App\Models\UserPrivateContest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use phpDocumentor\Reflection\Types\Null_;

class SetUserTeamTotal implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 600;

    private $fixtureId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($fixtureId)
    {
        $this->fixtureId = $fixtureId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        $fixture = Fixture::query()
            ->with('squads')
            ->where('id', $this->fixtureId)
            ->first();

        if ($fixture) {

            DB::transaction(function () use ($fixture) {

                $squads = collect($fixture->squads);

                $fixture
                    ->user_teams()
                    ->lazyById()
                    ->each(function ($team) use ($squads) {
                        if (!is_array($team->players)) {
                            $team->players = json_decode($team->players);
                        }

                        $teamName = $team->name;

                        $total = $squads
                            ->whereIn('player_id', $team->players)
                            ->sum(function ($item) use ($team) {
                                $multiplier = 1;
                                if ($team->master_player_id == $item['player_id']) {
                                    $multiplier = 3;
                                } elseif ($team->captain_id == $item['player_id']) {
                                    $multiplier = 2;
                                } elseif ($team->vice_captain_id == $item['player_id']) {
                                    $multiplier = 1.5;
                                }
                                if(is_null($team->inning_number) || $team->inning_number==0){
                                    return $item['total_points'] * $multiplier;
                                }

                                if($team->inning_number==1 && !is_null($item['first_inning'])){
                                    $inning_data=json_decode($item['first_inning'],true);
                                    if(!empty($inning_data['total_points'])){
                                        return $inning_data['total_points'] * $multiplier;
                                    } else{
                                        return 0;
                                    }

                                }
                                if($team->inning_number==2 && !is_null($item['second_inning'])){
                                    $inning_data=json_decode($item['second_inning'],true);
                                    if(!empty($inning_data['total_points'])){
                                        return $inning_data['total_points'] * $multiplier;
                                    } else{
                                        return 0;
                                    }
                                }
                                if($team->inning_number==3 && !is_null($item['third_inning'])){
                                    $inning_data=json_decode($item['third_inning'],true);
                                    if(!empty($inning_data['total_points'])){
                                        return $inning_data['total_points'] * $multiplier;
                                    } else{
                                        return 0;
                                    }
                                }
                                if($team->inning_number==4 && !is_null($item['fourth_inning'])){
                                    $inning_data=json_decode($item['fourth_inning'],true);
                                    if(!empty($inning_data['total_points'])){
                                        return $inning_data['total_points'] * $multiplier;
                                    } else{
                                        return 0;
                                    }
                                }

                            });

                            $joinedTeams = UserContest::query()->with('user:id,name,photo,username,id')->where('user_team_id', $team->id)->get();
                            $this->setPrevRank($joinedTeams, $total, 'contest_id', $teamName);

                            $joinedPrivateTeams = UserPrivateContest::query()->with('user:id,name,photo,username,id')->where('user_team_id', $team->id)->get();
                            $this->setPrevRank($joinedPrivateTeams, $total, 'private_contest_id', $teamName);


                        $team->update(['total_points' => $total]);
                    });
            });

        }
    }

    private function setPrevRank($teams, $total, $field = 'contest_id',$teamName)
    {
        //echo $teamName;die;
        foreach ($teams as $joined) {
            $key = "leaderboard:" . $joined->$field;
            $memberKey = "$key:member_data";

            Redis::zadd($key, $total, $joined->user_team_id);

            $data = Redis::hget($memberKey, $joined->user_team_id);
            $score = Redis::zscore($key, $joined->user_team_id);

            if (is_numeric($score)) {

                $rank = Redis::zcount($key, "(" . $score, '+inf');

                if ($data) {
                    $data = json_decode($data, true);
                    
                    $data['id'] = $joined->user->id;
                    $data['username'] = $joined->user->username;
                    $data['teamName'] = $teamName;
                    $data['photo'] = $joined->user->photo;
                    $data['prevRank'] = ($rank + 1);
                    $data['private'] = false;
                    $data['prize'] = $joined->prize;
                }

                Redis::hset($memberKey, $joined->user_team_id, json_encode($data));

                $joined->update(['rank' => ($rank + 1)]);
            }
        }
    }
}
