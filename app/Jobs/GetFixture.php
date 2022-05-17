<?php

namespace App\Jobs;

use App\EntitySport;
use App\Models\Competition;
use App\Models\ContestTemplate;
use App\Models\Fixture;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class GetFixture implements ShouldQueue
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
        $this->queue = 'fixture';
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $api = new EntitySport();
        //Log::info("GET FIXTURE IS RUNNING");

        // https://doc.entitysport.com/#matches-list-api
        $fixtures = $api->getSchedule(
            [
                'date' => now()->toDateString() . '00:00:00_' . now()->addDays(5)->toDateString() . ' 24:00:00',
                'status' => 1,
                'pre_squad' => true,
                'per_page' => 1000
            ]
        );

        foreach ($fixtures as $fixture) {
            DB::transaction(function () use ($fixture) {
                $c = $fixture['competition'];

                // create competition if not exists or update
                $competition = Competition::query()->updateOrCreate([
                    'id' => $c['cid'],
                ], [
                    'title' => $c['title'],
                    'season' => $c['season'],
                    'datestart' => $c['datestart'],
                    'dateend' => $c['dateend'],
                    'category' => $c['category'],
                    'match_format' => $c['match_format'],
                    'status' => $c['status'],
                ]);

                // create fixture if not exists or update
                $total_ings=(strtolower($fixture['format_str'])=='test')?4:2;
                $event = Fixture::query()->updateOrCreate([
                    'id' => $fixture['match_id'],
                ], [
                    'name' => $fixture['title'],
                    'competition_id' => $competition->id,
                    'competition_name' => $competition->title,
                    'season' => $competition->season,
                    'verified' => $fixture['verified'] == 'true',
                    'pre_squad' => $fixture['pre_squad'] == 'true',
                    'teama' => $fixture['teama']['name'],
                    'teama_id' => $fixture['teama']['team_id'],
                    //'teama_image' => $fixture['teama']['logo_url'],
                    //'teama_score' => $fixture['teama']['scores_full'] ?? '',
                    'teama_short_name' => $fixture['teama']['short_name'],

                    'teamb' => $fixture['teamb']['name'],
                    'teamb_id' => $fixture['teamb']['team_id'],
                    //'teamb_image' => $fixture['teamb']['logo_url'],
                    //'teamb_score' => $fixture['teamb']['scores_full'] ?? '',
                    'teamb_short_name' => $fixture['teamb']['short_name'],
                    'inning_number' => $fixture['latest_inning_number'],

                    'format' => $fixture['format'],
                    'format_str' => $fixture['format_str'],
                    'total_innings' => $total_ings,
                    //'starting_at' => Carbon::createFromTimestamp($fixture['timestamp_start'])->toDateTimeString(),
                    'status_note' => $fixture['status_note'],
                ]);

                if ($event->wasRecentlyCreated) {
                    $event->update([
                        'teama_image' => $fixture['teama']['logo_url'],
                        'teamb_image' => $fixture['teamb']['logo_url'],
                        'starting_at' => Carbon::createFromTimestamp($fixture['timestamp_start'])->toDateTimeString(),
                        'ending_at' => Carbon::createFromTimestamp($fixture['timestamp_end'])->toDateTimeString()

                    ]);
                }

                // queue GetSquad, GetLineup and GetPoint job when fixture created.
                if ($event->wasRecentlyCreated) {
                    GetSquad::dispatch($event->id);

                    GetScore::dispatch($event->id)->delay(Carbon::parse($event->starting_at)->addMinute(1));

                    $lineupSchedule = Carbon::parse($event->starting_at)->addMinutes(-45);
                    GetLineup::dispatch($event->id)->delay($lineupSchedule);

                    $updateSchedule = Carbon::parse($event->starting_at)->addMinutes(2);
                    GetPoint::dispatch($event->id)->delay($updateSchedule);

                    //Run dynamic prize breakup calculation cron before 2 hour of match
                    $dynamicSchedule = Carbon::parse($event->starting_at)->addMinutes(-120);
                    CalculateDynamicPrizeBreakup::dispatch($event->id)->delay($dynamicSchedule);

                    //Run cancel contest cron after 5 minute of match start
                    $cancelContestSchedule = Carbon::parse($event->starting_at)->addMinutes(5);
                    CallContestCancel::dispatch($event->id)->delay($cancelContestSchedule);

                    // Auto Add Contest
                    $this->createContests($event,$total_ings);
                }
            });
        }
    }

    /**
     * Auto create contests for new fixtures
     *
     * @param $event
     */
    private function createContests($event,$total_ings)
    {
        $templates = ContestTemplate::query()
            ->where('auto_add', 1)
            ->get();
            foreach ($templates as $template) {
                //for($inning=0;$inning<=$total_ings;$inning++){
                    // if($inning==1){
                    //     continue;
                    // }
                    $contestData = [
                        //'inning_number' => $inning,
                        'inning_number' => 0,
                        'invite_code' => generateRandomString(),
                        'status' => CONTEST_STATUS[1],
                        'contest_template_id'=>$template->id,
                        'contest_category_id' => $template->contest_category_id,
                        'commission' => $template->commission,
                        'total_teams' => $template->total_teams,
                        'entry_fee' => $template->entry_fee,
                        'max_team' => $template->max_team,
                        'prize' => $template->prize,
                        'winner_percentage' => $template->winner_percentage,
                        'is_confirmed' => $template->is_confirmed,
                        'prize_breakup' => $template->prize_breakup,
                        'auto_create_on_full' => $template->auto_create_on_full,
                        'type' => $template->type,
                        'discount' => $template->discount,
                        'bonus' => $template->bonus,
                        'is_mega_contest' => $template->is_mega_contest,
                        'is_dynamic' => $template->is_dynamic,
                        'dynamic_min_team' => $template->dynamic_min_team,

                    ];
                    $contest = $event->contests()->create($contestData);

                    Redis::set("contestSpace:$contest->id",$contest->total_teams);
                //}
            }
    }
}
