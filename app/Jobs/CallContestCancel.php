<?php

namespace App\Jobs;

use App\Models\Fixture;
use App\Models\Contest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class CallContestCancel implements ShouldQueue
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
    public function __construct($fixtureId, bool $autoSet = true, int $time_interval = 10)
    {
        $this->queue = 'CallContestCancel';
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
            //->where('status', FIXTURE_STATUS[1])
            ->first();

        if ($fixture) {

            if( ($fixture->status == FIXTURE_STATUS[0]) || (strtotime($fixture->starting_at) >= time())  ) {
                if ($this->autoSet) {
                    self::dispatch($this->fixtureId)->delay(now()->addMinutes($this->time_interval));
                }
            } else {

                $inning_number  =   $fixture->inning_number;

                $contests = Contest::query()
                ->where('fixture_id', $this->fixtureId)
                ->whereIn('inning_number', [0,$inning_number])
                ->where('status', CONTEST_STATUS[1])
                ->get();

                foreach ($contests AS $contest) {
                    $joined = $contest->joined;
                    if ($contest->is_confirmed) {
                        if ( $contest->is_dynamic  ) {
                            if ( $contest->joined->count() >= $contest->dynamic_min_team  ) {
                                // Nothing
                            } else{
                                Redis::sadd("contest_in_cancelling", $contest->id);
                                ContestCancel::dispatch($contest->id);
                            }
                        } else {
                            // Nothing
                        }
                    } else {
                        if ( count($joined) === $contest->total_teams ) {
                            // Nothing
                        } else {
                            Redis::sadd("contest_in_cancelling", $contest->id);
                            ContestCancel::dispatch($contest->id);
                        }
                    }
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
