<?php

namespace App\Jobs;

use App\EntitySport;
use Illuminate\Support\Facades\Redis;
use App\Models\Fixture;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

use Carbon\Carbon;



class GetLineup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private int $fixtureId;

    private bool $autoSet;

    private int $time_interval;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * Create a new job instance.
     *
     * @param $fixtureId
     * @param bool $autoSet
     */
    public function __construct($fixtureId, bool $autoSet = true, int $time_interval = 1)
    {
        $this->queue = 'lineup';
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
            ->where('status', FIXTURE_STATUS[0])
            ->first();

        if ($fixture) {

            if(strtotime($fixture->starting_at) <= time()) {
                $fixture->inning_number = 1;
                $fixture->status = FIXTURE_STATUS[1];
                $fixture->save();

                if( $fixture->lineup_announced ) {
                   $fixture->squads()->where('playing11',1)->update(['playing11_point'=>'4','total_points'=>'4']);
                }
            }

            // https://doc.entitysport.com/#match-squads-api
            $api = new EntitySport();
            $lineup = $api->getLineup($fixture);

            /* DB::transaction(function () use ($fixture, $lineup) {
                //Moved outside
            }); */

            if(!empty($lineup)) {

                $lineup_1 = $lineup_2 = false;
                if (isset($lineup['teama'])) {
                    $teama = $lineup['teama'];
                    $lineup_1 = $this->updateSquad($fixture, $teama['squads']);
                }

                if (isset($lineup['teamb'])) {
                    $teamb = $lineup['teamb'];
                    $lineup_2 = $this->updateSquad($fixture, $teamb['squads']);
                }

                if(!$fixture->lineup_announced){

                    if (!$fixture->lineup_announced && $lineup_1 && $lineup_2) {
                        $fixture->lineup_announced = true;
                        $fixture->save();
                    }

                    //Send Lineup Notification
                    if( $fixture->lineup_announced ) {
                        $is_sent = Redis::get('notification_send:'.$fixture->id);
                        if (!$is_sent || is_null($is_sent)) {
                            Log::info("notification sent".$fixture->id);
                            Redis::set('notification_send:'.$fixture->id,1);
                            $notification = new Notification();
                            $notification->type = "GAMEPLAY";
                            $notification->subject = "Lineup announced for ".$fixture->teama." vs ".$fixture->teamb;
                            $notification->message = "Make Your Own Squad At ".SettingData('short_name')." Now! Checkout the leaderboad to join the contest.";
                            $notification->image ='';
                            $notification->save();
                            SendNotification::dispatch($notification)->delay(now()->addSeconds(30));
                        }
                    }
                }
            }

            // queue GetLineup job if lineup is not announced.
            //!$fixture->lineup_announced &&
            if ($this->autoSet) {
                self::dispatch($this->fixtureId)->delay(now()->addMinutes($this->time_interval));
            }

        }
    }

    /**
     * Update squad for both team.
     *
     * @param Fixture $fixture
     * @param $squads
     * @return Fixture
     */
    private function updateSquad(Fixture $fixture, $squads)
    {
        $lineup_announced = false;
        if (count($squads) > 0) {

            foreach ($squads as $squad) {

                $isPlaying = false;
                $isSubstituted = false;

                if (isset($squad['playing11'])) {
                    $isPlaying = $squad['playing11'] == 'true';
                }

                if (isset($squad['substitute'])) {
                    $isSubstituted = $squad['substitute'] == 'true';
                }

                if($isPlaying){
                    $lineup_announced = true;
                }

                $fixture
                    ->squads()
                    ->where('player_id', $squad['player_id'])
                    ->update([
                        'playing11' => $isPlaying,
                        'substitute' => $isSubstituted
                    ]);
            }

        }

        return $lineup_announced;
    }

}
