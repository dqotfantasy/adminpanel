<?php

namespace App\Jobs;

use App\EntitySport;
use App\Models\Fixture;
use App\Models\FantasyPoint;
use App\Models\Contest;
use App\Models\PrivateContest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class GetPoint implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private int $fixtureId;
    private bool $autoSet;
    private int $time_interval;
    private array $fantasy_points_array = [];

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 600;

    /**
     * Create a new job instance.
     *
     * @param $fixtureId
     * @param bool $autoSet
     */
    public function __construct($fixtureId, bool $autoSet = true, int $time_interval = 6)
    {
        $this->queue = 'point';
        $this->fixtureId = $fixtureId;
        $this->autoSet = $autoSet;
        $this->time_interval = $time_interval;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        $fixture = Fixture::query()
            ->where('id', $this->fixtureId)
            ->first();

        if ($fixture) {

            if( ($fixture->status == FIXTURE_STATUS[0]) || (strtotime($fixture->starting_at) >= time())  ) {
                if ($this->autoSet) {
                    self::dispatch($this->fixtureId)->delay(now()->addMinutes($this->time_interval));
                }
            } else {
                //$api = new EntitySport();
                // https://doc.entitysport.com/#match-fantasy-points-api
                //$update = $api->getFantasyPoints($this->fixtureId);

                $json   = Redis::get("scorecard:{$this->fixtureId}");
                $update = json_decode($json, true);

                DB::transaction(function () use ($fixture, $update) {

                    // Entity Api code value
                    // 1.Scheduled, 2.Completed, 3.Live, 4.Abandoned, canceled, no result

                    // MM11 Code value
                    // 0.NOT STARTED, 1.LIVE, 2.IN REVIEW, 3.COMPLETED, 4.CANCELED

                    if (!is_null($update)) {

                        $isVerified = $update['verified'] == 'true';
                        $isMatchCompleted = $isVerified && ($update['status'] == 2 || $update['status'] == 4);
                        $isMatchCanceled = $isVerified && ($update['status'] == 4);

                        if ($this->autoSet) {
                            if ( $fixture->status != FIXTURE_STATUS[3] ) {

                                $this->updatePoints($fixture, $update);

                                $status = $this->getStatus($update['status']);
                                $fixture->update([
                                    'pre_squad'   => $update['pre_squad'] == 'true',
                                    'status'      => $status,
                                    'verified'    => $isVerified,
                                    'status_note' => $update['status_note'],
                                ]);

                                /* $pupdated = $fixture->squads()->where('total_points','>', 0)->exists();
                                if($pupdated){
                                    SetUserTeamTotal::dispatch($this->fixtureId);
                                } */

                                self::dispatch($this->fixtureId)->delay(now()->addMinutes($this->time_interval));
                            }

                            $pupdated = $fixture->squads()->where('total_points','>', 0)->exists();
                            if($pupdated){
                                SetUserTeamTotal::dispatch($this->fixtureId);
                            }
                        }



                        // when fixture is_verified true and status 2 or 4
                        if ($isMatchCompleted && $fixture->status != FIXTURE_STATUS[3]) {

                            //running job for declare contest prize
                            if( (!$isMatchCanceled && $fixture->allow_prize_distribution) || ($isMatchCanceled && $fixture->cancel_allow) ) {

                                //Update Master Team
                                $fixture->squads()
                                ->limit(11)
                                ->orderByDesc('total_points')
                                ->update(['in_dream_team' => true]);

                                $fixture->contests()->where('status', CONTEST_STATUS[1])->update(['status' => CONTEST_STATUS[2]]);

                                $fixture->private_contests()->where('status', CONTEST_STATUS[1])->update(['status' => CONTEST_STATUS[2]]);

                                foreach ($fixture->contests()->where('status', CONTEST_STATUS[2])->get(['id'])->pluck('id') as $contestId) {
                                    ContestProcess::dispatch($contestId);
                                }

                                foreach ($fixture->private_contests()->where('status', CONTEST_STATUS[2])->get(['id'])->pluck('id') as $contestId) {
                                    PrivateContestProcess::dispatch($contestId);
                                }

                                $isContestExists = Contest::where('fixture_id', $this->fixtureId)->where('status', 'IN REVIEW')->exists();
                                $isPrivateExists = PrivateContest::where('fixture_id', $this->fixtureId)->where('status', 'IN REVIEW')->exists();

                                if (!$isContestExists && !$isPrivateExists) {
                                    if($isMatchCanceled){
                                        $fixupdate = ['status' => FIXTURE_STATUS[3],'is_cancelled' => 1];
                                    } else {
                                        $fixupdate = ['status' => FIXTURE_STATUS[3]];
                                    }
                                    $fixture->update($fixupdate);
                                }
                            }
                        }
                    } else {
                        if ($this->autoSet) {
                            self::dispatch($this->fixtureId)->delay(now()->addMinutes($this->time_interval));
                        }
                    }
                });
            }
        }
    }

    /**
     * Update point for squad
     *
     * @param $fixture
     * @param $players
     */

    private function updatePoints($fixture, $update)
    {

        $innining = !empty($update['innings']) ? $update['innings'] : '';
        $matchType = !empty($update['format_str']) ? $update['format_str'] : '';
        $data = array();

        $this->fetchfantasypoint($matchType);

        $jsonInningData = array();
        if (!empty($innining)) {

            //$live_inning = count($innining);
            /* $fixture->update([
                'inning_number' => $live_inning,
            ]); */

            foreach ($innining as $inKey => $inningdata) {

                if (is_array($inningdata) && $inningdata['batsmen']) {
                    foreach ($inningdata['batsmen'] as $key => $batsmenData) {

                        $duck = 0;
                        if ($batsmenData['how_out'] != 'Not out' && $batsmenData['runs'] == '0' && $batsmenData['role'] != 'bowl') {
                            $duck = 1;
                        }

                        $jsonInningData[$batsmenData['batsman_id']][$inningdata['number']] = [
                            'inning_number' => $inningdata['number'],
                            'runs' => $batsmenData['runs'],
                            'fours' => $batsmenData['fours'],
                            'sixes' => $batsmenData['sixes'],
                            'balls_faced'=> $batsmenData['balls_faced'],
                            'strike_rate' => $batsmenData['strike_rate'],
                            'duck' => $duck,
                            //'wicket' => 0,
                            //'maiden_over' => 0,
                            //'catch' => 0,
                            //'stumping' => 0,
                            //'runout_thrower' => 0,
                            //'runout_catcher' => 0,
                            //'runout_direct_hit' => 0,
                            //'economy_rate' => 0,
                            //'overs' => 0,
                            //'bowledcount' => 0,
                            //'lbwcount' => 0,
                        ];
                    }
                }



                if (is_array($inningdata) && $inningdata['bowlers']) {

                    foreach ($inningdata['bowlers'] as $key => $bowlersData) {

                    $jsonInningData[$bowlersData['bowler_id']][$inningdata['number']] = [

                        'inning_number' => $inningdata['number'],
                        //'runs' => 0,
                        //'fours' => 0,
                        //'sixes' => 0,
                        //'balls_faced'=> 0,
                        //'strike_rate' => 0,
                        //'duck' => 0,
                        'wicket' => $bowlersData['wickets'],
                        'maiden_over' => $bowlersData['maidens'],
                        //'catch' => 0,
                        //'stumping' => 0,
                        //'runout_thrower' => 0,
                        //'runout_catcher' => 0,
                        //'runout_direct_hit' => 0,
                        'economy_rate' => $bowlersData['econ'],
                        'overs' => $bowlersData['overs'],
                        'bowledcount' => $bowlersData['bowledcount'],
                        'lbwcount' => $bowlersData['lbwcount'],

                    ];

                    }
                }

                if (is_array($inningdata) && $inningdata['fielder']) {
                    foreach ($inningdata['fielder'] as $key => $fielderData) {

                        $jsonInningData[$fielderData['fielder_id']][$inningdata['number']]['catch'] = $fielderData['catches'];
                        $jsonInningData[$fielderData['fielder_id']][$inningdata['number']]['stumping'] = $fielderData['stumping'];
                        $jsonInningData[$fielderData['fielder_id']][$inningdata['number']]['runout_thrower'] = $fielderData['runout_thrower'];
                        $jsonInningData[$fielderData['fielder_id']][$inningdata['number']]['runout_catcher'] = $fielderData['runout_catcher'];
                        $jsonInningData[$fielderData['fielder_id']][$inningdata['number']]['runout_direct_hit'] = $fielderData['runout_direct_hit'];
                    }
                }
            }
        }


        foreach ($jsonInningData as $player_id => $playerValue) {

            $playing11_point = $runs=$runs_point=$fours=$fours_point=$sixes=$sixes_point= $century_half_century = $century_half_century_point = $strike_rate = $strike_rate_point = $duck = $duck_point = $wicket = $wicket_point = $maiden_over = $maiden_over_point = $economy_rate = $economy_rate_point = $catch = $catch_point = $runoutstumping = $runoutstumping_point = $bonus_point = $balls_faced = $stumping = $runout_thrower = $runout_catcher = $runout_direct_hit = $overs = $bowledcount = $lbwcount = $total_points = 0;

            $inlineup=$fixture->squads()->where(['player_id'=>$player_id,'playing11'=>'1'])->exists();

            $first_inning = $second_inning = $third_inning = $fourth_inning = get_dummy_inning_json();
            foreach ($playerValue as $inning_number => $pdata) {

                $pdata['inlineup'] = $inlineup;
                $pdata['matchType'] = $matchType;
                $pdata['inning_number'] = $inning_number;

                if($inning_number == 1){$first_inning = $this->generateinningdata($pdata);}
                if($inning_number == 2){$second_inning = $this->generateinningdata($pdata);}
                if($inning_number == 3){$third_inning = $this->generateinningdata($pdata);}
                if($inning_number == 4){$fourth_inning = $this->generateinningdata($pdata);}

                $runs += (!empty($pdata['runs'])) ? $pdata['runs'] : 0;
                $fours += (!empty($pdata['fours'])) ? $pdata['fours'] : 0;
                $sixes += (!empty($pdata['sixes'])) ? $pdata['sixes'] : 0;
                $balls_faced += (!empty($pdata['balls_faced'])) ? $pdata['balls_faced'] : 0;
                $strike_rate += (!empty($pdata['strike_rate'])) ? $pdata['strike_rate'] : 0;
                $duck += (!empty($pdata['duck'])) ? $pdata['duck'] : 0;
                $wicket += (!empty($pdata['wicket'])) ? $pdata['wicket'] : 0;
                $maiden_over += (!empty($pdata['maiden_over'])) ? $pdata['maiden_over'] : 0;
                $catch += (!empty($pdata['catch'])) ? $pdata['catch'] : 0;
                $stumping += (!empty($pdata['stumping'])) ? $pdata['stumping'] : 0;
                $runout_thrower += (!empty($pdata['runout_thrower'])) ? $pdata['runout_thrower'] : 0;
                $runout_catcher += (!empty($pdata['runout_catcher'])) ? $pdata['runout_catcher'] : 0;
                $runout_direct_hit += (!empty($pdata['runout_direct_hit'])) ? $pdata['runout_direct_hit'] : 0;
                $economy_rate += (!empty($pdata['economy_rate'])) ? $pdata['economy_rate'] : 0;
                $overs += (!empty($pdata['overs'])) ? $pdata['overs'] : 0;
                $bowledcount += (!empty($pdata['bowledcount'])) ? $pdata['bowledcount'] : 0;
                $lbwcount += (!empty($pdata['lbwcount'])) ? $pdata['lbwcount'] : 0;
            }

            $fulldata = [];
            $fulldata['inlineup'] = $inlineup;
            $fulldata['matchType'] = $matchType;
            $fulldata['inning_number'] = 0;

            $fulldata['runs'] = $runs;
            $fulldata['fours'] = $fours;
            $fulldata['sixes'] = $sixes;
            $fulldata['balls_faced'] = $balls_faced;
            $fulldata['strike_rate'] = $strike_rate;
            $fulldata['duck'] = $duck;
            $fulldata['wicket'] = $wicket;
            $fulldata['maiden_over'] = $maiden_over;
            $fulldata['catch'] = $catch;
            $fulldata['stumping'] = $stumping;
            $fulldata['runout_thrower'] = $runout_thrower;
            $fulldata['runout_catcher'] = $runout_catcher;
            $fulldata['runout_direct_hit'] = $runout_direct_hit;
            $fulldata['economy_rate'] = $economy_rate;
            $fulldata['overs'] = $overs;
            $fulldata['bowledcount'] = $bowledcount;
            $fulldata['lbwcount'] = $lbwcount;

            $data = $this->generateinningdata($fulldata);

            $data['first_inning'] = $first_inning;
            $data['second_inning'] = $second_inning;
            $data['third_inning'] = $third_inning;
            $data['fourth_inning'] = $fourth_inning;
            $fixture->squads()->where('player_id', $player_id)->update($data);
        }
    }

    private function generateinningdata($pdata = []){


        $matchType = '';
        $inlineup = $inning_number = $playing11_point = $runs=$runs_point=$fours=$fours_point=$sixes=$sixes_point= $century_half_century = $century_half_century_point = $strike_rate = $strike_rate_point = $duck = $duck_point = $wicket = $wicket_point = $maiden_over = $maiden_over_point = $economy_rate = $economy_rate_point = $catch = $catch_point = $runoutstumping = $runoutstumping_point = $bonus_point = $balls_faced = $stumping = $runout_thrower = $runout_catcher = $runout_direct_hit = $overs = $bowledcount = $lbwcount = $total_points = 0;

        $point_p11 = $this->getpointvalue('p11');
        $point_run  = $this->getpointvalue('run');
        $point_four = $this->getpointvalue('four');
        $point_six  = $this->getpointvalue('six');
        $point_duck = $this->getpointvalue('duck');
        $point_wicket = $this->getpointvalue('wicket');
        $point_maiden_over = $this->getpointvalue('maiden_over');
        $point_catch = $this->getpointvalue('catch');
        $point_stump_runout_direct = $this->getpointvalue('stumped_runout_direct');
        $point_runout_thrower = $this->getpointvalue('run_out_thrower');
        $point_runout_catcher = $this->getpointvalue('run_out_catcher');
        $point_bowled_bonus = $this->getpointvalue('bowled_bonus');

        //
        $point_two_wicket = $this->getpointvalue('two_wicket');
        $point_three_wicket = $this->getpointvalue('three_wicket');
        $point_four_wicket = $this->getpointvalue('four_wicket');
        $point_five_wicket = $this->getpointvalue('five_wicket');
        $point_third_catch = $this->getpointvalue('third_catch');


        if(!empty($pdata)){
            $inlineup = $pdata['inlineup'];
            $matchType = $pdata['matchType'];
            $inning_number = $pdata['inning_number'];

            $runs = (!empty($pdata['runs'])) ? $pdata['runs'] : 0;
            $fours = (!empty($pdata['fours'])) ? $pdata['fours'] : 0;
            $sixes = (!empty($pdata['sixes'])) ? $pdata['sixes'] : 0;
            $balls_faced = (!empty($pdata['balls_faced'])) ? $pdata['balls_faced'] : 0;
            $strike_rate = (!empty($pdata['strike_rate'])) ? $pdata['strike_rate'] : 0;
            $duck = (!empty($pdata['duck'])) ? $pdata['duck'] : 0;
            $wicket = (!empty($pdata['wicket'])) ? $pdata['wicket'] : 0;
            $maiden_over = (!empty($pdata['maiden_over'])) ? $pdata['maiden_over'] : 0;
            $catch = (!empty($pdata['catch'])) ? $pdata['catch'] : 0;
            $stumping = (!empty($pdata['stumping'])) ? $pdata['stumping'] : 0;
            $runout_thrower = (!empty($pdata['runout_thrower'])) ? $pdata['runout_thrower'] : 0;
            $runout_catcher = (!empty($pdata['runout_catcher'])) ? $pdata['runout_catcher'] : 0;
            $runout_direct_hit = (!empty($pdata['runout_direct_hit'])) ? $pdata['runout_direct_hit'] : 0;
            $economy_rate = (!empty($pdata['economy_rate'])) ? $pdata['economy_rate'] : 0;
            $overs = (!empty($pdata['overs'])) ? $pdata['overs'] : 0;
            $bowledcount = (!empty($pdata['bowledcount'])) ? $pdata['bowledcount'] : 0;
            $lbwcount = (!empty($pdata['lbwcount'])) ? $pdata['lbwcount'] : 0;
        }

        if($inlineup){
            $playing11_point = $point_p11;
        }
        $runs_point  = $runs * $point_run;
        $fours_point = $fours * $point_four;
        $sixes_point = $sixes * $point_six;
        $duck_point = $duck * $point_duck;
        $wicket_point = $wicket * $point_wicket;
        $maiden_over_point = $maiden_over * $point_maiden_over;
        $catch_point = $catch * $point_catch;

        $stumping_point = $stumping * $point_stump_runout_direct;
        $runout_thrower_point = $runout_thrower * $point_runout_thrower;
        $runout_catcher_point = $runout_catcher * $point_runout_catcher;
        $runout_direct_hit_point = $runout_direct_hit * $point_stump_runout_direct;

        $runoutstumping = $stumping + $runout_thrower + $runout_catcher + $runout_direct_hit;
        $runoutstumping_point = $stumping_point + $runout_thrower_point + $runout_catcher_point + $runout_direct_hit_point;



        $strike_rate_point = 0;
        $economy_rate_point = 0;



        $lbw_bowled_bonus = 0;
        $wicket_bonus = 0;
        $catch_bonus = 0;
        $bonus_point = 0;

        //Bouled / LPW Bonus
        $lbw_bowled_bonus += $bowledcount * $point_bowled_bonus;
        $lbw_bowled_bonus += $lbwcount * $point_bowled_bonus;

        if(($matchType=='Test' || $matchType=='First-class')){
            if ($runs > 49 && $runs < 100) {
                $century_half_century = 1;
                $century_half_century_point = $century_half_century * $this->getpointvalue('bt 50-99');
            }
            if ($runs > 99) {
                $century_half_century = 1;
                $century_half_century_point = $century_half_century * $this->getpointvalue('gt 99');
            }

            if($wicket==4){
             $wicket_bonus += $point_four_wicket;
            }
            if($wicket >= 5){
             $wicket_bonus += $point_five_wicket;
            }
        }elseif($matchType=='ODI'){

            if ($runs > 49 && $runs < 100) {
                $century_half_century = 1;
                $century_half_century_point = $century_half_century * $this->getpointvalue('bt 50-99');
            }
            if ($runs > 99) {
                $century_half_century = 1;
                $century_half_century_point = $century_half_century * $this->getpointvalue('gt 99');
            }

            if($wicket==4){
              $wicket_bonus += $point_four_wicket;
            }

            if($wicket >= 5){
               $wicket_bonus += $point_five_wicket;
            }

            if($catch >= 3){
               $catch_bonus += $point_third_catch;
            }

            if($balls_faced >= 20){ //V Strike rate
                if( $strike_rate < 30){
                    $strike_rate_point = $strike_rate_point + $this->getpointvalue('lt 30');
                }
                if( ($strike_rate >= 30) && ($strike_rate <= 39.99)){
                    $strike_rate_point = $strike_rate_point + $this->getpointvalue('bt 30-39.99');
                }
                if( ($strike_rate >= 40) && ($strike_rate <= 50)){
                    $strike_rate_point = $strike_rate_point + $this->getpointvalue('bt 40-50');
                }

                if( ($strike_rate >= 100) && ($strike_rate <= 120)){
                    $strike_rate_point = $strike_rate_point + $this->getpointvalue('bt 100-120');
                }
                if( ($strike_rate > 120) && ($strike_rate <= 140)){
                    $strike_rate_point = $strike_rate_point + $this->getpointvalue('bt 120.01-140');
                }
                if( ($strike_rate > 140) ){
                    $strike_rate_point = $strike_rate_point + $this->getpointvalue('bt 140');
                }
            }

            //Economic rate
            if($overs >= 5){
                if($economy_rate < 2.5) {
                    $economy_rate_point = $economy_rate_point + $this->getpointvalue('bt 2.5');
                }
                if(($economy_rate >= 2.5) && ($economy_rate <= 3.49)) {
                    $economy_rate_point = $economy_rate_point + $this->getpointvalue('bt 2.5-3.49');
                }
                if(($economy_rate >= 3.5) && ($economy_rate <= 4.5)) {
                    $economy_rate_point = $economy_rate_point + $this->getpointvalue('bt 3.5-4.5');
                }

                if(($economy_rate >= 7) && ($economy_rate <= 8)){
                    $economy_rate_point = $economy_rate_point + $this->getpointvalue('bt 7-8');
                }
                if(($economy_rate >= 8.1) && ($economy_rate <= 9)){
                    $economy_rate_point = $economy_rate_point + $this->getpointvalue('bt 8.01-9');
                }
                if($economy_rate > 9){
                    $economy_rate_point = $economy_rate_point + $this->getpointvalue('gt 9');
                }
            }

        }elseif($matchType=='T20'){

            if ($runs > 29 && $runs < 50) {
                $century_half_century = 1;
                $century_half_century_point = $century_half_century * $this->getpointvalue('gt 30');
            }

            if ($runs > 49 && $runs < 100) {
                $century_half_century = 1;
                $century_half_century_point = $century_half_century * $this->getpointvalue('bt 50-99');
            }

            if ($runs > 99) {
                $century_half_century = 1;
                $century_half_century_point = $century_half_century * $this->getpointvalue('gt 99');
            }

            if($wicket==3){
                $wicket_bonus += $point_three_wicket;
              }
            if($wicket==4){
                $wicket_bonus += $point_four_wicket;
              }
              if($wicket >= 5){
               $wicket_bonus += $point_five_wicket;
              }

              if($catch >= 3){
                $catch_bonus += $point_third_catch;
               }

            if($balls_faced >= 10){
                if($strike_rate < 50){
                    $strike_rate_point = $strike_rate_point + $this->getpointvalue('lt 50');
                }
                if(($strike_rate >= 50) && ($strike_rate <= 59.99)){
                    $strike_rate_point = $strike_rate_point + $this->getpointvalue('bt 50-59.99');
                }
                if( ($strike_rate >= 60) && ($strike_rate <= 70 ) ){
                    $strike_rate_point = $strike_rate_point + $this->getpointvalue('bt 50-59.99');
                }

                if( ($strike_rate >= 130) && ($strike_rate <= 150)){
                    $strike_rate_point = $strike_rate_point + $this->getpointvalue('bt 130-150');
                }
                if(($strike_rate > 150) && ($strike_rate <= 170)){
                    $strike_rate_point = $strike_rate_point + $this->getpointvalue('bt 150.01-170');
                }
                if( ($strike_rate > 170) ){
                    $strike_rate_point = $strike_rate_point + $this->getpointvalue('lt 170');
                }
            }

            //Economic rate
            if($overs >= 2){
                if($economy_rate < 5) {
                    $economy_rate_point = $economy_rate_point + $this->getpointvalue('lt 5');
                }
                if(($economy_rate >= 5) && ($economy_rate <= 5.99)) {
                    $economy_rate_point = $economy_rate_point + $this->getpointvalue('bt 5-5.99');
                }
                if(($economy_rate >= 6) && ($economy_rate <= 7)) {
                    $economy_rate_point = $economy_rate_point + $this->getpointvalue('bt 6-7');
                }

                if(($economy_rate >= 10) && ($economy_rate <= 11)){
                    $economy_rate_point = $economy_rate_point + $this->getpointvalue('bt 10-11');
                }
                if(($economy_rate >= 11.01) && ($economy_rate <= 12)){
                    $economy_rate_point = $economy_rate_point + $this->getpointvalue('bt 11.01-12');
                }
                if( $economy_rate > 12 ){
                    $economy_rate_point = $economy_rate_point + $this->getpointvalue('gt 12');
                }
            }
        }elseif($matchType=='T10'){

            if ($runs > 29 && $runs < 50) {
                $century_half_century = 1;
                $century_half_century_point = $century_half_century * $this->getpointvalue('bt 30-49');
            }

            if ($runs > 49) {
                $century_half_century = 1;
                $century_half_century_point = $century_half_century * $this->getpointvalue('gt 49');
            }

            if($wicket==2){
                $wicket_bonus += $point_two_wicket;
              }

              if($wicket >= 3){
               $wicket_bonus += $point_three_wicket;
              }

              if($catch >= 3){
                $catch_bonus += $point_third_catch;
               }

            if($balls_faced >= 5){
                if($strike_rate < 60){
                    $strike_rate_point = $strike_rate_point + $this->getpointvalue('lt 60');
                }
                if(($strike_rate >= 60) && ($strike_rate <= 69.99)){
                    $strike_rate_point = $strike_rate_point + $this->getpointvalue('bt 60-69.99');
                }
                if(($strike_rate >= 70) && ($strike_rate <= 80)){
                    $strike_rate_point = $strike_rate_point + $this->getpointvalue('bt 70-80');
                }

                if( ($strike_rate >= 150) && ($strike_rate <= 170)){
                    $strike_rate_point = $strike_rate_point + $this->getpointvalue('bt 150-170');
                }
                if(($strike_rate > 170) && ($strike_rate <= 190)){
                    $strike_rate_point = $strike_rate_point + $this->getpointvalue('bt 170.01-190');
                }
                if( ($strike_rate > 190) ){
                    $strike_rate_point = $strike_rate_point + $this->getpointvalue('bt 190');
                }
            }

            //Economic rate
            if($overs >= 1){
                if($economy_rate < 7) {
                    $economy_rate_point = $economy_rate_point + $this->getpointvalue('lt 7');
                }
                if(($economy_rate >= 7) && ($economy_rate <= 7.99)) {
                    $economy_rate_point = $economy_rate_point + $this->getpointvalue('bt 7-7.99');
                }
                if(($economy_rate >= 8) && ($economy_rate <= 9)) {
                    $economy_rate_point = $economy_rate_point + $this->getpointvalue('bt 8-9');
                }

                if(($economy_rate >= 14) && ($economy_rate <= 15)){
                    $economy_rate_point = $economy_rate_point + $this->getpointvalue('bt 14-15');
                }
                if(($economy_rate >= 15.01) && ($economy_rate <= 16)){
                    $economy_rate_point = $economy_rate_point + $this->getpointvalue('bt 15.01-16');
                }
                if( $economy_rate > 16 ){
                    $economy_rate_point = $economy_rate_point + $this->getpointvalue('gt 16');
                }
            }
        }

        $total_points = $playing11_point + $runs_point + $fours_point + $sixes_point + $century_half_century_point + $strike_rate_point + $duck_point + $wicket_point + $maiden_over_point + $economy_rate_point + $catch_point + $runoutstumping_point + $bonus_point + $lbw_bowled_bonus + $wicket_bonus + $catch_bonus;

        $data = [
            'playing11_point' => $playing11_point,
            'runs' => $runs,
            'runs_point' => $runs_point,
            'fours' => $fours,
            'fours_point' => $fours_point,
            'sixes' => $sixes,
            'sixes_point' => $sixes_point,
            'century_half_century'=>$century_half_century,
            'century_half_century_point' => $century_half_century_point,
            'strike_rate' => $strike_rate,
            'strike_rate_point'=>$strike_rate_point,
            'duck' => $duck,
            'duck_point' => $duck_point,
            'wicket' => $wicket,
            'wicket_point' => $wicket_point,
            'maiden_over' => $maiden_over,
            'maiden_over_point' => $maiden_over_point,
            'economy_rate' => $economy_rate,
            'economy_rate_point' => $economy_rate_point,
            'catch' => $catch,
            'catch_point' => $catch_point,
            'runoutstumping' => $runoutstumping,
            'runoutstumping_point' => $runoutstumping_point,
            'balls_faced' => $balls_faced,
            'overs_bowled' => $overs,
            'lbw_bowled_bonus' => $lbw_bowled_bonus,
            'wicket_bonus' => $wicket_bonus,
            'catch_bonus' => $catch_bonus,
            'bonus_point' => $bonus_point,
            'total_points' => $total_points
        ];

        if($inning_number){
            return json_encode($data);
        } else{
            return $data;
        }

    }

    private function fetchfantasypoint($type)
    {

        $type = getCricketMatchType($type);
        $fantasy_points = FantasyPoint::query()
            ->where('type', $type)
            ->get();
        if ($fantasy_points) {
            $fantasy_points_array = [];
            $fantasy_points_object = json_encode($fantasy_points);
            $fantasy_points_object = json_decode($fantasy_points_object,true);
            foreach($fantasy_points_object AS $key => $value) {
                $fantasy_points_array[$value['code']] = $value;
            }
            $this->fantasy_points_array = $fantasy_points_array;
        }
    }

    private function getpointvalue($code)
    {

        return (isset($this->fantasy_points_array[$code]['point'])) ? $this->fantasy_points_array[$code]['point'] : 0;
    }

    private function getStatus($code)
    {

        // Entity Api code value  1.Scheduled, 2.Completed, 3.Live, 4.Abandoned, canceled, no result
        // MM11 Code value        0.NOT STARTED, 1.LIVE, 2.IN REVIEW, 3.COMPLETED, 4.CANCELED

        $data = [
            [
                'id' => 1,
                'name' => FIXTURE_STATUS[0]
            ],
            [
                'id' => 2,
                'name' => FIXTURE_STATUS[2]
            ],
            [
                'id' => 3,
                'name' => FIXTURE_STATUS[1]
            ],
            [
                'id' => 4,
                'name' => FIXTURE_STATUS[4]
            ]
        ];

        return collect($data)->firstWhere('id', $code)['name'];
    }
}
