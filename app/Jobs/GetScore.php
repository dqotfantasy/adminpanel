<?php

namespace App\Jobs;

use App\EntitySport;
use App\Models\Fixture;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;


class GetScore implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private int $fixtureId;
    private bool $autoSet;
    private int $time_interval;

    /**
     * Create a new job instance.
     *
     * @param $fixtureId
     * @param bool $autoSet
     */
    public function __construct($fixtureId, bool $autoSet = true, int $time_interval = 2)
    {
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
                $api = new EntitySport();

                // https://doc.entitysport.com/#match-scorecard-api
                $scorecard = $api->getScorecard($this->fixtureId);
                if ($scorecard) {

                    Redis::set("scorecard:{$this->fixtureId}", json_encode($scorecard));

                    if($fixture->teama_id == $scorecard['teama']['team_id'] && isset($scorecard['teama']['scores_full']) ){
                        $fixture->teama_score   =   $scorecard['teama']['scores_full'];
                    }
                    if($fixture->teamb_id == $scorecard['teamb']['team_id'] && isset($scorecard['teamb']['scores_full']) ){
                        $fixture->teamb_score = $scorecard['teamb']['scores_full'];
                    }

                    $inninings = !empty($scorecard['innings']) ? $scorecard['innings'] : [];
                    $type = getCricketMatchType($scorecard['format_str']);
                    $minutes = CRICKET_INNING_BREAK[$type];

                    if(!empty($inninings)){
                        foreach($inninings AS $innining) {

                            $number =   $innining['number'];
                            $status =   $innining['status'];

                            if( $number == 1 && $status == 2 && $fixture->completed_inning_number <= 1 ) {
                                if($fixture->completed_inning_number < 1) {
                                    $fixture->completed_inning_number = 1;
                                    $fixture->inning_starting_at = now()->addMinutes($minutes)->format('Y-m-d H:i:s');
                                } else {
                                    if($fixture->inning_starting_at < now() ){
                                        $fixture->inning_number = $number+1;
                                    }
                                }
                            }

                            if($number == 2 && $status == 2 && $fixture->completed_inning_number <= 2){
                                if($fixture->completed_inning_number < 2) {
                                    $fixture->completed_inning_number = 2;
                                    $fixture->inning_starting_at = now()->addMinutes($minutes)->format('Y-m-d H:i:s');
                                } else {
                                    if($fixture->inning_starting_at < now() ){
                                        $fixture->inning_number = $number+1;
                                    }
                                }
                            }

                            if($number == 3 && $status == 2 && $fixture->completed_inning_number <= 3){
                                if($fixture->completed_inning_number < 3) {
                                    $fixture->completed_inning_number = 3;
                                    $fixture->inning_starting_at = now()->addMinutes($minutes)->format('Y-m-d H:i:s');
                                } else {
                                    if($fixture->inning_starting_at < now() ){
                                        $fixture->inning_number = $number+1;
                                    }
                                }
                            }
                        }
                    }

                    //$fixture->inning_number = (isset($scorecard['latest_inning_number'])) ? $scorecard['latest_inning_number'] : 0;
                    $fixture->save();


                }

                if ($this->autoSet) {
                    if ($fixture->status === FIXTURE_STATUS[0] || $fixture->status === FIXTURE_STATUS[1] || $fixture->status === FIXTURE_STATUS[2]) {
                        self::dispatch($this->fixtureId)->delay(now()->addMinutes($this->time_interval));
                    }
                }
            }
        }
    }
}
