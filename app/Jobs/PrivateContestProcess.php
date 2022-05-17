<?php

namespace App\Jobs;

use App\Models\Contest;
use App\Models\Payment;
use App\Models\PrivateContest;
use App\Models\Setting;
use App\Models\Tds;
use App\Models\User;
use App\Models\UserPrivateContest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class PrivateContestProcess implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 3000;

    private string $contestId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($contestId)
    {
        $this->contestId = $contestId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $contest = PrivateContest::query()->where('id', $this->contestId)->where('status', CONTEST_STATUS[2])->first();

        if ($contest) {
            $joined = $contest->joined;

            $isMatchCanceled = $contest->fixture->status == FIXTURE_STATUS[4];

            if ($contest->is_confirmed && !$isMatchCanceled) {
                if ($contest->total_teams > $joined->count()) {
                    $this->distributePrize($contest, true);
                } else {
                    $this->distributePrize($contest);
                }
            } else {
                if ((count($joined) === $contest->total_teams) && !$isMatchCanceled) {
                    $this->distributePrize($contest);
                } else {
                    Redis::sadd("private_contest_in_cancelling", $contest->id);
                    PrivateContestCancel::dispatch($contest->id);
                }
            }
        }
    }

    private function distributePrize($contest, $isConfirm = false)
    {
        $this->updateRank($contest);
        if ($isConfirm) {
            $winners = 0;
            foreach ($contest->prize_breakup as $value) {
                $winners = $value['to'];
            }
            $contest->prize_breakup = $this->calculation($contest->total_teams, $contest->entry_fee, $winners, $contest->prize, $contest->joined->count(), $contest->commission, $contest->prize_breakup);
            if ($contest->prize_breakup) {
                PrivateContest::query()->where(['id' => $contest->id])->update(['new_prize_breakup' => $contest->prize_breakup]);
            }
        }

        if (is_array($contest->prize_breakup) && count($contest->prize_breakup) > 0) {

            $tdsSetting = Setting::query()->firstWhere('key', 'tds_deduction');
            $tdsPercentage = $tdsSetting->value;

            $pb = $contest->prize_breakup;
            $toalWinner =  max(array_column($pb, 'to'));

            $leaderboard = $contest->joined()->whereBetween('rank', [1, $toalWinner])->orderBy('rank')->get();

            $rank_counter_winning = [];
            $counter = 1;
            if(!empty($leaderboard) && count($leaderboard) > 0) {

                foreach($leaderboard AS $leader){
                    $rank = $leader->rank;
                    $rank_counter_winning[$rank]['rank'] = $rank;
                    $rank_counter_winning[$rank]['counter'][] = $counter;
                    $prize = $this->get_prize_value($counter,$pb);
                    if($prize){
                        $rank_counter_winning[$rank]['prize'][] = $prize;
                    }
                    $counter++;
                }

                $tdsAmount = 0;
                foreach ($leaderboard as $l) {

                    $rank = $l['rank'];
                    if(isset($rank_counter_winning[$rank])){

                        $usercount = count($rank_counter_winning[$rank]['counter']);
                        $prizesum  = array_sum($rank_counter_winning[$rank]['prize']);
                        $prize = $prizesum/$usercount;
                        if ($prize > 10000) {
                            $tdsAmount = $prize * $tdsPercentage / 100;
                            $prize = $prize - $tdsAmount;
                        }

                        DB::transaction(function () use ($l, $contest, $prize, $tdsAmount) {
                            UserPrivateContest::query()->where('user_id', $l['user_id'])
                                ->where('private_contest_id', $contest->id)
                                ->where('user_team_id', $l['user_team_id'])
                                ->update(['prize' => $prize]);

                            if ($contest->type !== 'PRACTICE') {
                                $extra = ['entry_fee'=>$contest->entry_fee,'fixture_name'=>$contest->fixture->teama_short_name.' vs '.$contest->fixture->teamb_short_name,'category_name'=>''];
                                $extra = json_encode($extra);

                                $payment = [
                                    'user_id' => $l['user_id'],
                                    'amount' => $prize,
                                    'status' => 'SUCCESS',
                                    'transaction_id' => 'CWN' . rand(),
                                    'description' => 'Contest won',
                                    'type' => PAYMENT_TYPES[3],
                                    'private_contest_id' => $contest->id,
                                    'extra' => $extra
                                ];

                                $p = Payment::query()->create($payment);

                                if ($tdsAmount > 0) {
                                    Tds::query()->firstOrCreate([
                                        'user_id' => $l['user_id'],
                                        'payment_id' => $p->id,
                                        'amount' => $tdsAmount,
                                    ]);
                                }

                                User::query()->where('id', $l['user_id'])->increment('winning_amount', $prize);
                                User::query()->where('id', $l['user_id'])->increment('balance', $prize);
                            }
                        });
                    }
                }
            }

            PrivateContest::query()->where(['id' => $contest->id])->update(['status' => CONTEST_STATUS[3]]);
        }
    }

    private function get_prize_value($counter,$pb){
        $prize = 0;
        foreach($pb AS $pbe){
            if($counter >= $pbe['from'] && $counter <= $pbe['to'] ){
                $prize = $pbe['prize'];
            }
        }
        return $prize;
    }

    private function updateRank(PrivateContest $contest)
    {
        $key = "leaderboard:" . $contest->id;
        $contest->joined()->chunkById(100, function ($teams) use ($key) {
            $ids = collect($teams)->pluck('user_team_id')->toArray();
            $data = collect(rankedInList($key, $ids));
            foreach ($teams as $team) {
                $rank = $data->firstWhere('member', $team->user_team_id)['rank'];
                $team->update(['rank' => $rank]);
            }
        });
    }

    public function calculation($total_teams, $entry_fee, $winners, $prize, $coming_user, $commission_per, $prizeBreakup)
    {
        $wining_percentage = (($winners / $total_teams) * 100); // %

        if ($coming_user == $total_teams) {
            $new_prize = $prize;
            $new_winners = $winners;
        } else {
            $new_prize = $entry_fee * $coming_user;
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
