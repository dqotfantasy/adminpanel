<?php

namespace App\Jobs;

use App\Models\Contest;
use App\Models\Payment;
use App\Models\PrivateContest;
use App\Models\Setting;
use App\Models\Tds;
use App\Models\User;
use App\Models\UserContest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

use Illuminate\Support\Facades\Log;



class ContestProcess implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 3000;

    private $contestId;

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
        $contest = Contest::query()->where('id', $this->contestId)->where('status', CONTEST_STATUS[2])->first();

        if ($contest) {

            $inning_number  =   $contest->fixture->inning_number;
            $check_inning   =   [0,$inning_number];
            $contest_inning_number  =   $contest->inning_number;

            /* if(!in_array($contest_inning_number,$check_inning)) {
                return;
            } */

            $joined = $contest->joined;

            $isMatchCanceled = $contest->fixture->status == FIXTURE_STATUS[4];

            if ($contest->is_confirmed && !$isMatchCanceled) {
                if ( $contest->is_dynamic  ) {
                    if ( $contest->joined->count() >= $contest->dynamic_min_team  ) {
                        $this->distributePrize($contest, true);
                    } else{
                        Redis::sadd("contest_in_cancelling", $contest->id);
                        ContestCancel::dispatch($contest->id);
                    }
                } else {
                    $this->distributePrize($contest);
                }
            } else {
                if ((count($joined) === $contest->total_teams) && !$isMatchCanceled) {
                    $this->distributePrize($contest);
                } else {
                    Redis::sadd("contest_in_cancelling", $contest->id);
                    ContestCancel::dispatch($contest->id);
                }
            }
        }
    }

    private function distributePrize($contest, $isDynamic = false)
    {
        $this->updateRank($contest);
        Contest::query()->where(['id' => $contest->id])->update(['status' => CONTEST_STATUS[3]]);
        if ($contest->type !== 'PRACTICE') {
            if ($isDynamic) {
                if ( $contest->total_teams > $contest->joined->count()  ) {
                    $contest->prize_breakup = $contest->new_prize_breakup;
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
                                $affected = UserContest::query()
                                    ->where(['user_id' => $l['user_id'], 'contest_id' => $contest->id, 'user_team_id' => $l['user_team_id']])
                                    ->whereNull('prize')
                                    ->update(['prize' => $prize]);

                                if ($contest->type !== 'PRACTICE' && $affected > 0 ) {


                                    $extra = ['entry_fee'=>$contest->entry_fee,'fixture_name'=>$contest->fixture->teama_short_name.' vs '.$contest->fixture->teamb_short_name,'category_name'=>$contest->category->name];
                                    $extra = json_encode($extra);

                                    $payment = [
                                        'user_id' => $l['user_id'],
                                        'amount' => $prize,
                                        'status' => 'SUCCESS',
                                        'transaction_id' => 'CWN' . rand(),
                                        'description' => 'Contest won',
                                        'type' => PAYMENT_TYPES[3],
                                        'contest_id' => $contest->id,
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
            }
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

    private function updateRank(Contest $contest)
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

}
