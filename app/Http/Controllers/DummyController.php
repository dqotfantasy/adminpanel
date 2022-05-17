<?php


namespace App\Http\Controllers;

use App\Models\FantasyPoint;
use App\EntitySport;
use App\Models\Fixture;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

use App\Jobs\GetPoint;
use App\Jobs\GetSquad;
use App\Jobs\GetLineup;
use App\Jobs\GetScore;


class DummyController extends Controller
{


    public $fixtureId='';
    public $autoSet=false;

    public function manualyQueue($fixtureid=null){
        $fixture = Fixture::query()
            ->where('id', $fixtureid)
            ->first();
        // echo $fixture->starting_at."tt";
        //     echo "<pre>";print_r($fixture);die;
        $fixture->starting_at='2022-01-07 15:05:00';

            //GetSquad::dispatch($fixtureid);

            if(GetScore::dispatch($fixtureid)->delay(Carbon::parse($fixture->starting_at)->addMinute()))
            {
                echo "ok";
            }

            // $lineupSchedule = Carbon::parse($fixture->starting_at)->addMinutes(-30);
            // GetLineup::dispatch($fixtureid)->delay($lineupSchedule);

            // $updateSchedule = Carbon::parse($fixture->starting_at)->addMinutes();
            // GetPoint::dispatch($fixtureid)->delay($updateSchedule);
             echo "fine";
            // Auto Add Contest
    }


    public function getpoints($fixtureId=null){
        echo phpinfo();
        echo $fixtureId;die;
        $fixture = Fixture::query()
            ->where('id', $fixtureId)
            //            ->where('starting_at', '>', now())
            ->first();
            $this->fixtureId=$fixtureId;
        //echo "<pre>";print_r($fixture);echo "</pre>";die;
        //Log::info(json_encode($fixture));
        if ($fixture) {

            $api = new EntitySport();

            // https://doc.entitysport.com/#match-fantasy-points-api
            //$update = $api->getFantasyPoints($fixtureId);
            //echo "<pre>tttt";print_r($update);echo "</pre>";die;
            $json=Redis::get("scorecard:{$fixtureId}");
            $update=json_decode($json,true);
        //echo "<pre>tt";print_r($json);echo "</pre>";die;

            DB::transaction(function () use ($fixture, $update) {

                if (!is_null($update)) {

                    $this->updatePoints($fixture,$update);

                    $isVerified = $update['verified'] == 'true';
                    $isMatchCompleted = $isVerified && ($update['status'] == 2 || $update['status'] == 4);

                    if ($isMatchCompleted) {
                        $fixture->squads()
                            ->limit(11)
                            ->orderByDesc('total_points')
                            ->update(['in_dream_team' => true]);
                    }

                    $status = $this->getStatus($update['status']);

                    $fixture->update([
                        'pre_squad' => $update['pre_squad'] == 'true',
                        'status' => $status,
                        'verified' => $isVerified,
                        'teama_score' => $update['teama']['scores_full'] ?? '',
                        'teamb_score' => $update['teamb']['scores_full'] ?? '',
                        'status_note' => $update['status_note'],
                    ]);

                    // https://doc.entitysport.com/#match-status-codes
                    // if ($this->autoSet) {
                    //     if (!$isVerified || $update['status'] == 1 || $update['status'] == 3) {
                    //         self::dispatch($fixtureId)->delay(now()->addMinute());
                    //     }
                    // }

                    // SetUserTeamTotal::dispatch($fixtureId);

                    // // when fixture is_verified true and status 2 or 4
                    // if ($isMatchCompleted) {

                    //     $fixture->contests()->where('status', CONTEST_STATUS[1])->update(['status' => CONTEST_STATUS[2]]);
                    //     $fixture->private_contests()->where('status', CONTEST_STATUS[1])->update(['status' => CONTEST_STATUS[2]]);

                    //     //running job for declare contest
                    //     foreach ($fixture->contests()->where('status', CONTEST_STATUS[2])->get(['id'])->pluck('id') as $contestId) {
                    //         ContestProcess::dispatch($contestId);
                    //     }
                    //     foreach ($fixture->private_contests()->where('status', CONTEST_STATUS[2])->get(['id'])->pluck('id') as $contestId) {
                    //         PrivateContestProcess::dispatch($contestId);
                    //     }
                    // }

                } else {
                    // if ($this->autoSet) {
                    //     self::dispatch($fixtureId)->delay(now()->addMinutes(4));
                    // }
                }
            });
        }
    }

    public function getscore($fixtureId=null){
        $fixture = Fixture::query()
            ->where('id', $fixtureId)

            ->first();

        if ($fixture) {

            $api = new EntitySport();

            // https://doc.entitysport.com/#match-scorecard-api
            $scorecard = $api->getScorecard($fixtureId);
            //echo "ttttT".json_encode($scorecard);die;
            if ($scorecard) {
                Redis::set("scorecard:{$fixtureId}", json_encode($scorecard));
                echo "<pre>";print_r($scorecard);echo "</pre>";
                echo "redis set";
            }

            // if ($this->autoSet) {
            //     if ($fixture->status === FIXTURE_STATUS[0] || $fixture->status === FIXTURE_STATUS[1] || $fixture->status === FIXTURE_STATUS[2]) {
            //         self::dispatch($fixtureId)->delay(now()->addMinutes(2));
            //     }
            // }
        }
    }

    private function updatePoints($fixture,$arr)
    {
        // $json=Redis::get("scorecard:{$this->fixtureId}");
        // $arr=json_decode($json,true);
        //echo "<pre>";print_r($arr);die;
        Log::info("Update Point JSON");
        $innining=!empty($arr['innings'])?$arr['innings']:'';
        $matchType=!empty($arr['format_str'])?$arr['format_str']:'';
        $data=array();
        $jsonPoints=$this->staticPoinSystem($matchType);

        $jsonInningData=array();

        if(!empty($innining)){
            Log::info("Updating... Inning JSON");
            $live_inning=count($innining);
            $fixture->update([
                'inning_number'=>$live_inning,
            ]);

            foreach ($innining as $inKey=>$inningdata) {

                if(is_array($inningdata) && $inningdata['batsmen']){
                    foreach($inningdata['batsmen'] as $key=>$batsmenData){
                        $jsonInningData[$batsmenData['batsman_id']][$inningdata['number']]=array('inning_number'=>$inningdata['number'],'runs'=>$batsmenData['runs'],'fours'=>$batsmenData['fours'],'sixes'=>$batsmenData['sixes'],'wicket'=>0,'maiden_over'=>0,'stumping'=>0,'catch'=>0,'runout_thrower'=>0,'runout_catcher'=>0,'runout_direct_hit'=>0);
                    }
                }

                if(is_array($inningdata) && $inningdata['bowlers']){

                    foreach($inningdata['bowlers'] as $key=>$bowlersData){

                        $jsonInningData[$bowlersData['bowler_id']][$inningdata['number']]=array('inning_number'=>$inningdata['number'],'wicket'=>$bowlersData['wickets'],'maiden_over'=>$bowlersData['maidens'],'catch'=>0,'runout_thrower'=>0,'stumping'=>0,'runout_catcher'=>0,'runout_direct_hit'=>0,'runs'=>0,'fours'=>0,'sixes'=>0);
                    }

                }

                if(is_array($inningdata) && $inningdata['fielder']){
                    foreach($inningdata['fielder'] as $key=>$fielderData){

                        $jsonInningData[$fielderData['fielder_id']][$inningdata['number']]=array('inning_number'=>$inningdata['number'],'catch'=>$fielderData['catches'],'runout_thrower'=>$fielderData['runout_thrower'],'runout_catcher'=>$fielderData['runout_catcher'],'stumping'=>$fielderData['stumping'],'runout_direct_hit'=>$fielderData['runout_direct_hit'],'runs'=>0,'fours'=>0,'sixes'=>0,'wicket'=>0,'maiden_over'=>0);
                    }
                }
            }
        }
        //echo "<pre>";print_r($jsonInningData);die;
        foreach($jsonInningData as $pkey=>$playerValue){
            $fours=$sixes=$wicket=$maiden_over=$catch=$runout_thrower=$runout_catcher=$runout_direct_hit=$runs=$stumping=0;
            foreach($playerValue as $key=>$pdata){
                $runs+=$pdata['runs'];
                $fours+=$pdata['fours'];
                $sixes+=$pdata['sixes'];
                $wicket+=$pdata['wicket'];
                $maiden_over+=$pdata['maiden_over'];
                $catch+=$pdata['catch'];
                $runout_thrower+=$pdata['runout_thrower'];
                $runout_catcher+=$pdata['runout_catcher'];
                $runout_direct_hit+=$pdata['runout_direct_hit'];
                $stumping+=$pdata['stumping'];
            }
            $runpoint=$runs*$this->getpointsToArray($jsonPoints,'run');
            $fourpoint=$fours*$this->getpointsToArray($jsonPoints,'four');
            $sixpoint=$sixes*$this->getpointsToArray($jsonPoints,'six');
            $halfcenturypoin=$centurypoin=0;
            if($runs>50 && $runs<99){
                $halfcenturypoin=1*$this->getpointsToArray($jsonPoints,'bt 50-99');
            }
            if($runs>100){
                $centurypoin=1*$this->getpointsToArray($jsonPoints,'gt 99');
            }
            $wicketspoint=$wicket*$this->getpointsToArray($jsonPoints,'wicket');
            $maidenspoint=$maiden_over*$this->getpointsToArray($jsonPoints,'maiden_over');

            $catchespoint=$catch*$this->getpointsToArray($jsonPoints,'catch');
            $runout_throwerpoint=$runout_thrower*$this->getpointsToArray($jsonPoints,'Run Out (Thrower)');
            $runout_catcherpoint=$runout_catcher*$this->getpointsToArray($jsonPoints,'Run Out (Catcher)');
            $runout_direct_hitpoint=$runout_direct_hit*$this->getpointsToArray($jsonPoints,'Stumping/ Run Out (direct)');
            $stumpingpoint=$stumping*$this->getpointsToArray($jsonPoints,'Stumping/ Run Out (direct)');

            $data = [
                'runs' => $runs,
                'runs_point' => $runpoint,
                'century_half_century_point' => $halfcenturypoin + $centurypoin,
                'fours' => $fours,
                'fours_point' => $fourpoint,
                'sixes' => $sixes,
                'sixes_point' => $sixpoint,
                'wicket' => $wicket,
                'wicket_point' => $wicketspoint,
                'maiden_over_point' => $maidenspoint,
                'maiden_over' => $maiden_over,
                'catch' => $catch,
                'catch_point' => $catchespoint,
                'runoutstumping_point' => $runout_throwerpoint+$runout_catcherpoint+$runout_direct_hitpoint+$stumpingpoint,
                'total_points'=>$runpoint+$fourpoint+$sixpoint+$wicketspoint+$maidenspoint+$catchespoint+$runout_throwerpoint+$runout_catcherpoint+$runout_direct_hitpoint+$stumpingpoint
            ];

            $fixture->squads()->where('player_id', $pkey)->update($data);
            for($in=1;$in<=4;$in++){
                if(!empty($playerValue[$in])){
                    $playerValue[$in]['runs_point']=$playerValue[$in]['runs']*$this->getpointsToArray($jsonPoints,'run');
                    $playerValue[$in]['fours_point']=$playerValue[$in]['fours']*$this->getpointsToArray($jsonPoints,'four');
                    $playerValue[$in]['sixes_point']=$playerValue[$in]['sixes']*$this->getpointsToArray($jsonPoints,'six');

                    $playerValue[$in]['wicket_point']=$playerValue[$in]['wicket']*$this->getpointsToArray($jsonPoints,'wicket');
                    $playerValue[$in]['maiden_over_point']=$playerValue[$in]['maiden_over']*$this->getpointsToArray($jsonPoints,'maiden_over');

                    $playerValue[$in]['catch_point']=$playerValue[$in]['catch']*$this->getpointsToArray($jsonPoints,'catch');
                    $runout_throwerpoint=$playerValue[$in]['runout_thrower']*$this->getpointsToArray($jsonPoints,'Run Out (Thrower)');

                    $runout_catcherpoint=$playerValue[$in]['runout_catcher']*$this->getpointsToArray($jsonPoints,'Run Out (Catcher)');

                    $runout_direct_hitpoint=$playerValue[$in]['runout_direct_hit']*$this->getpointsToArray($jsonPoints,'Stumping/ Run Out (direct)');
                    $stumpingpoint=$playerValue[$in]['stumping']*$this->getpointsToArray($jsonPoints,'Stumping/ Run Out (direct)');

                    $playerValue[$in]['runoutstumping_point']=$runout_throwerpoint+$runout_catcherpoint+$runout_direct_hitpoint+$stumpingpoint;

                    $halfcenturypoin=$centurypoin=0;
                    if($playerValue[$in]['runs']>50 && $playerValue[$in]['runs']<99){
                        $halfcenturypoin=1*$this->getpointsToArray($jsonPoints,'bt 50-99');
                    }
                    if($playerValue[$in]['runs']>100){
                        $centurypoin=1*$this->getpointsToArray($jsonPoints,'gt 99');
                    }
                    $playerValue[$in]['century_half_century_point']=$halfcenturypoin+$centurypoin;

                    $playerValue[$in]['total_point']=$playerValue[$in]['runs_point']+$playerValue[$in]['fours_point']+$playerValue[$in]['sixes_point']+$playerValue[$in]['wicket_point']+$playerValue[$in]['maiden_over_point']+$playerValue[$in]['runoutstumping_point']+$playerValue[$in]['catch_point'];

                    $inning_data=json_encode($playerValue[$in]);
                }else{
                    $live_inning=0;
                    $defaultInning=array('inning_number'=>$in,'runs'=>0,'fours'=>0,'sixes'=>0,'wicket'=>0,'maiden_over'=>0,'stumping'=>0,'catch'=>0,'runout_thrower'=>0,'runout_catcher'=>0,'runout_direct_hit'=>0,'runs_point'=>0,'fours_point'=>0,'sixes_point'=>0,'wicket_point'=>0,'maiden_over_point'=>0,'catch_point'=>0,'runoutstumping_point'=>0,'total_point'=>0);
                    $inning_data=json_encode($defaultInning);
                }
                //echo "<pre>";print_r($inning_data);die;
                if($in==1){
                    $getinning=$fixture->squads()->where('player_id',$pkey)->first();
                    $getinning->first_inning=$inning_data;
                    $getinning->save();
                }
                if($in==2){
                    $getinning=$fixture->squads()->where('player_id',$pkey)->first();
                    $getinning->second_inning=$inning_data;
                    $getinning->save();
                }
                if($in==3){
                    $getinning=$fixture->squads()->where('player_id',$pkey)->first();
                    $getinning->third_inning=$inning_data;
                    $getinning->save();
                }
                if($in==4){
                    $getinning=$fixture->squads()->where('player_id',$pkey)->first();
                    $getinning->fourth_inning=$inning_data;
                    $getinning->save();
                }
            }

        }
    }

    private function getpointsToArray($jsondata,$code){
        $pointar=json_decode($jsondata,true);
        $vPoint=1;
        foreach($pointar as $value){
            if($value['code']==$code || $value['name']==$code){
                $vPoint=$value['point'];
                break;
            }
        }
        return $vPoint;
    }

    private function staticPoinSystem($type){

        $fixture = FantasyPoint::query()
            ->where('type',$type)
            ->get();
        if($fixture){
            return json_encode($fixture);
        }else{
            return 0;
        }
    }
    private function getStatus($code)
    {
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
