<?php

namespace App\Jobs;

use App\Models\Fixture;
use App\Models\Contest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class CalculateDynamicPrizeBreakup implements ShouldQueue
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
        $this->queue = 'CalculateDynamicPrizeBreakup';
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
            //->where('starting_at', '>', now())
            //->where('status', FIXTURE_STATUS[0])
            ->first();

        if ($fixture) {

            $contests = Contest::query()->where('fixture_id', $this->fixtureId)->where('status', CONTEST_STATUS[1])->where('is_dynamic', 1)->get();

            foreach ($contests AS $contest) {
                $winners = 0;
                foreach ($contest->prize_breakup as $value) {
                    $winners = $value['to'];
                }
                $team_joined = $contest->joined->count(); //40;//

                if($team_joined < $contest->total_teams) {
                    $new_prize_breakup = $this->calculation($contest->total_teams, $contest->entry_fee, $winners, $contest->prize, $team_joined, $contest->commission, $contest->prize_breakup);
                } else {
                    $new_prize_breakup = $contest->prize_breakup;
                }

                if ($new_prize_breakup) {
                    Contest::query()->where(['id' => $contest->id])->update(['new_prize_breakup' => $new_prize_breakup]);
                }

            }

            if($fixture->status == FIXTURE_STATUS[0]){
                self::dispatch($this->fixtureId)->delay(now()->addMinutes($this->time_interval));
            }

        }
    }

    public function calculation($total_teams, $entry_fee, $winners, $prize, $coming_user, $commission_per, $prizeBreakup)
    {
        $wining_percentage = (($winners / $total_teams) * 100);
        $wining_prize_percentage = (($prize / ($total_teams*$entry_fee)) * 100);

        if ($coming_user == $total_teams) {
            $new_prize = $prize;
            $new_winners = $winners;
        } else {
            //$new_prize = $entry_fee * $coming_user;
            $new_prize = (int)(($wining_prize_percentage * ($entry_fee * $coming_user)) / 100);
            $new_winners = (int)(($wining_percentage * $coming_user) / 100);
        }

        $newRank = [];
        $totalAmount = 0;
        foreach (array_reverse($prizeBreakup) as $rank) {
            if ($rank['from'] <= $new_winners) {
                if ($rank['to'] > $new_winners) {
                    $rank['to'] = $rank['to'] - ($rank['to'] - $new_winners);
                }

                $newPrize = (($new_prize * $rank['percentage']) / 100);
                $totalUser = $rank['to'] - $rank['from'];
                $totalPrize = $newPrize * ($totalUser + 1);
                $totalAmount += $totalPrize;

                if ($rank['from'] == '1') {
                    $firstPrize = ($new_prize - $totalAmount);
                    $firstPer = (($firstPrize / $new_prize) * 100);
                    $newRank[] = array(
                        'rank' => $rank['from'] . '-' . $rank['to'],
                        'from' => (int)$rank['from'],
                        'to' => (int)$rank['to'],
                        'percentage' => $rank['percentage'] + $firstPer,
                        'prize' => $totalPrize + $firstPrize
                    );
                } else {
                    $newRank[] = array(
                        'rank' => $rank['from'] . '-' . $rank['to'],
                        'from' => (int)$rank['from'],
                        'to' => (int)$rank['to'],
                        'percentage' => $rank['percentage'],
                        'prize' => $newPrize
                    );
                }
            }
        }
        return array_reverse($newRank);
    }
}
