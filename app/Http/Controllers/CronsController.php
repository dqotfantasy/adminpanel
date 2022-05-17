<?php

namespace App\Http\Controllers;

use App\Models\Leaderboard;
use App\Models\UserTeam;



use App\Models\Competition;
use App\Models\ContestTemplate;
use App\Models\Fixture;
use App\Models\PrivateContest;
use App\Models\Player;
use App\Models\Squad;
use App\Models\UserContest;
use App\Models\UserPrivateContest;
use App\Models\Contest;
use App\Models\ReferalDepositDetails;
use App\Models\Payment;
use App\Models\User;
use App\Models\Setting;
use App\Models\Tds;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use App\Jobs\GetLineup;
use App\Jobs\SetUserTeamTotal;
use App\Jobs\ContestProcess;
use App\Jobs\PrivateContestProcess;
use App\Models\FantasyPoint;

use App\Models\State;
use App\Models\BankAccount;
use App\Models\PanCard;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserRegistered;
use App\Mail\VerifyEmail;

use App\EntitySport;
use App\Jobs\CalculateDynamicPrizeBreakup;
use App\Jobs\CallContestCancel;
use App\Jobs\GetPoint;
use App\Jobs\GetScore;
use App\Jobs\GetSquad;
use App\Models\Job;

class CronsController extends Controller
{

    private int $fixtureId;
    private bool $autoSet = false;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 60000;

    private function getuuid()
    {
        $uuid =  uuid();
        $checkExists = User::find($uuid);
        if (!empty($checkExists)) {
            $this->getuuid();
        }
        return $uuid;
    }

    private function getdummyphone()
    {
        $rphone =  '123' . rand(1000000, 9999999);
        $isExists = User::where('phone', $rphone)->exists();
        if ($isExists) {
            $this->getdummyphone();
        }
        return $rphone;
    }

    public function Mergeuserdata()
    {

        ini_set('max_execution_time', 300); //5 minutes

        $old_users = DB::table('xxold_users')
            ->where(['role_id' => 2])
            ->get();
        //dd($old_users);die;

        $states = State::query()->pluck('id', 'name');


        foreach ($old_users as $old_user) {

            //dd($old_user);

            $uuid =  $this->getuuid();

            $promte_type = ($old_user->promte_type == 3) ? 0 : $old_user->promte_type;
            if (empty($promte_type)) {
                $promte_type = 0;
            }

            $phone = substr($old_user->phone, 0, 10);

            if ($old_user->is_sys_user) {
                $phone = $this->getdummyphone();
            }


            $user = new User();
            $user->id                   =   $uuid;
            $user->old_user_id          =   $old_user->id;
            $user->name                 =   $old_user->first_name . ' ' . $old_user->last_name;
            $user->username             =   $old_user->team_name;
            $user->email                =   $old_user->email;
            $user->password             =   $old_user->password;
            $user->date_of_birth        =   date('Y-m-d', strtotime($old_user->date_of_bith));
            $user->photo                =   '';
            $user->gender               =   ($old_user->gender) ? 'M' : 'F';
            $user->phone                =   $phone;
            $user->address              =   $old_user->address;
            $user->city                 =   (!empty($old_user->city)) ? $old_user->city : '';
            $user->state_id             =   (isset($states[$old_user->state])) ? $states[$old_user->state] : 37;
            $user->winning_amount       =   $old_user->winning_balance;
            $user->deposited_balance    =   $old_user->cash_balance;
            $user->cash_bonus           =   $old_user->bonus_amount;
            $user->balance              =   ($old_user->winning_balance + $old_user->cash_balance + $old_user->bonus_amount);



            $user->phone_verified       =   ($old_user->otp_verified) ? 1 : 0;
            $user->email_verified       =   ($old_user->email_verified) ? 1 : 0;
            $user->is_locked            =   !$old_user->status;
            $user->is_username_update   =   $old_user->is_updated;
            $user->referral_code        =   (!empty($old_user->refer_id)) ? $old_user->refer_id : generateRandomString();
            $user->role                 =   'user';
            $user->promoter_type        =   $promte_type;
            $user->is_sys_user          =   $old_user->is_sys_user;
            $user->app_version          =   0;

            $user->save();
        }
    }

    public function Mergebankdata()
    {

        ini_set('max_execution_time', 300); //5 minutes

        $old_users = DB::table('xxbank_details')
            ->where(['is_verified' => 1])
            ->get();
        //dd($old_users);die;


        foreach ($old_users as $old_user) {

            $new_user_id = DB::table('users')
                ->where(['old_user_id' => $old_user->user_id])
                ->first();
            //dd($new_user_id->id);


            $user = new BankAccount();
            $user->user_id              =   $new_user_id->id;
            $user->name                 =   '';
            $user->account_number       =   $old_user->account_number;
            $user->branch               =   $old_user->branch;
            $user->ifsc_code            =   $old_user->ifsc_code;
            $user->bankName             =   $old_user->bank_name;
            $user->photo                =   '';
            $user->state_id             =   37;
            $user->status               =   'VERIFIED';
            $user->beneficiary_id       =   $old_user->beneficiary_id;

            $user->save();
        }
    }

    public function Mergepandata()
    {

        ini_set('max_execution_time', 300); //5 minutes

        $old_users = DB::table('xxpen_aadhar_card')
            ->where(['is_verified' => 1])
            ->get();
        //dd($old_users);die;


        foreach ($old_users as $old_user) {

            $new_user_id = DB::table('users')
                ->where(['old_user_id' => $old_user->user_id])
                ->first();

            $user = new PanCard();
            $user->user_id              =   $new_user_id->id;
            $user->name                 =   $old_user->pan_name;
            $user->pan_number           =   $old_user->pan_card;
            $user->date_of_birth        =   date('Y-m-d', strtotime($old_user->date_of_birth));
            $user->is_verified          =   1;
            $user->photo                =   '';
            $user->status               =   'VERIFIED';
            $user->save();
        }
    }


    public function Mergereferdata()
    {

        ini_set('max_execution_time', 1500); //5 minutes

        $old_users = DB::table('referal_code_details')
            //->where(['id'=>1])
            ->get();
        //dd($old_users);die;


        foreach ($old_users as $old_user) {

            $refered_by_user_id = DB::table('users')
                ->where(['old_user_id' => $old_user->refered_by])
                ->first();

            $new_user_id = User::query()
                ->where(['old_user_id' => $old_user->user_id])
                ->first();

            if (!empty($new_user_id)) {
                $new_user_id->referral_id          =   $refered_by_user_id->id;
                $new_user_id->referral_amount      =   100;
                $new_user_id->is_deposit           =   1;
                $new_user_id->update();
            }
        }
    }


    public function GetFixture()
    {

        echo config('mail.from.name') . "<br>";
        echo config('mail.from.address');
        $user = User::query()->find('05aa87b2-ab93-4642-9c5b-98b341b04c00');

        // Mail::send('emails.users.registered', [], function ($message) {
        //     //dd($message);
        //     $message->from('noreply@fantasysquad.in', 'Laravel');
        //     $message->subject('test');
        //     $message->to('riyaz3068@gmail.com');
        // });
        //echo $user->email;die;

        $d = Mail::to($user)
                        ->queue(new UserRegistered($user));
                        //echo "finee";die;
        dd($d);
        echo 'Success';
        die;

        echo 'Off';
        die;
        $api = new EntitySport();

        // https://doc.entitysport.com/#matches-list-api
        $fixtures = $api->getSchedule(
            [
                //'date' => now()->toDateString() . '00:00:00_' . now()->addDays()->toDateString() . ' 24:00:00',
                'date' => now()->toDateString() . '_' . now()->addDays(3)->toDateString() . '',
                'status' => 3,
                'pre_squad' => true,
                'per_page' => 10
            ]
        );

        //echo '<pre>';
        //print_r($fixtures);die;

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
                    //                'teama_image' => $fixture['teama']['logo_url'],
                    'teama_score' => $fixture['teama']['scores_full'] ?? '',
                    'teama_short_name' => $fixture['teama']['short_name'],

                    'teamb' => $fixture['teamb']['name'],
                    'teamb_id' => $fixture['teamb']['team_id'],
                    //                'teamb_image' => $fixture['teamb']['logo_url'],
                    'teamb_score' => $fixture['teamb']['scores_full'] ?? '',
                    'teamb_short_name' => $fixture['teamb']['short_name'],

                    'format' => $fixture['format'],
                    'format_str' => $fixture['format_str'],
                    'starting_at' => Carbon::createFromTimestamp($fixture['timestamp_start'])->toDateTimeString(),
                    'status_note' => $fixture['status_note'],
                ]);

                if ($event->wasRecentlyCreated) {
                    $event->update([
                        'teama_image' => $fixture['teama']['logo_url'],
                        'teamb_image' => $fixture['teamb']['logo_url']
                    ]);
                }

                // queue GetSquad, GetLineup and GetPoint job when fixture created.
                if ($event->wasRecentlyCreated) {
                    /* GetSquad::dispatch($event->id);

                    GetScore::dispatch($event->id)->delay(Carbon::parse($event->starting_at)->addMinute());

                    $lineupSchedule = Carbon::parse($event->starting_at)->addMinutes(-30);
                    GetLineup::dispatch($event->id)->delay($lineupSchedule);

                    $updateSchedule = Carbon::parse($event->starting_at)->addMinutes();
                    GetPoint::dispatch($event->id)->delay($updateSchedule); */

                    // Auto Add Contest
                    $this->createContests($event);
                }
            });
        }
    }

    public function GetLineup($fixtureId = null)
    {

        echo 'Off';
        die;
        /* $fixture = Fixture::query()
            ->where('id', $fixtureId)
            ->first();

        $lineupSchedule = Carbon::parse($fixture->starting_at)->addMinutes(1);
        GetLineup::dispatch($fixture->id)->delay($lineupSchedule);die; */

        $this->fixtureId = $fixtureId;
        $fixture = Fixture::query()
            ->where('id', $fixtureId)
            //->where('starting_at', '>', now())
            //->where('status', FIXTURE_STATUS[0])
            ->first();

        if ($fixture) {

            $api = new EntitySport();

            if (strtotime($fixture->starting_at) <= time()) {
                $fixture->status = FIXTURE_STATUS[1];
                $fixture->save();

                if ($fixture->lineup_announced) {
                    $fixture->squads()->where('playing11', 1)->update(['playing11_point' => '4', 'total_points' => '4']);
                }
            }

            // https://doc.entitysport.com/#match-squads-api
            $lineup = $api->getLineup($fixture);

            DB::transaction(function () use ($fixture, $lineup) {
                $lineup_1 = $lineup_2 = false;
                if (!$fixture->lineup_announced) {

                    if (isset($lineup['teama'])) {
                        $teama = $lineup['teama'];
                        $lineup_1 = $this->updateSquad($fixture, $teama['squads']);
                    }

                    if (isset($lineup['teamb'])) {
                        $teamb = $lineup['teamb'];
                        $lineup_2 = $this->updateSquad($fixture, $teamb['squads']);
                    }


                    if (!$fixture->lineup_announced && $lineup_1 && $lineup_2) {
                        $fixture->lineup_announced = true;
                    }


                    //Send Lineup Notification
                    if ($fixture->lineup_announced) {
                    }


                    $fixture->save();
                }

                // queue GetLineup job if lineup is not announced.
                //!$fixture->lineup_announced &&
                if ($this->autoSet) {
                    self::dispatch($this->fixtureId)->delay(now()->addMinutes($this->time_interval));
                }
            });
        }
    }

    public function GetPoint($fixtureId = null)
    {

        //echo 'Off1';die;
        //echo get_dummy_inning_json();die;



        $this->fixtureId = $fixtureId;
        //SetUserTeamTotal::dispatch($this->fixtureId);
        //echo 'done';die;
        $fixture = Fixture::query()
            ->where('id', $this->fixtureId)
            ->first();

        if ($fixture) {

            SetUserTeamTotal::dispatch($this->fixtureId);
            echo 'Done';
            die;

            //$api = new EntitySport();
            // https://doc.entitysport.com/#match-fantasy-points-api
            //$update = $api->getFantasyPoints($this->fixtureId);

            $json   = Redis::get("scorecard:{$this->fixtureId}");
            $update = json_decode($json, true);

            dd($update);

            DB::transaction(function () use ($fixture, $update) {

                // Entity Api code value
                // 1.Scheduled, 2.Completed, 3.Live, 4.Abandoned, canceled, no result

                // MM11 Code value
                // 0.NOT STARTED, 1.LIVE, 2.IN REVIEW, 3.COMPLETED, 4.CANCELED

                if (!is_null($update)) {

                    $isVerified = $update['verified'] == 'true';
                    $isMatchCompleted = $isVerified && ($update['status'] == 2 || $update['status'] == 4);

                    if ($this->autoSet) {
                        if ($fixture->status != FIXTURE_STATUS[3]) {

                            //$this->updatePoints($fixture, $update);

                            $status = $this->getStatus($update['status']);
                            $fixture->update([
                                'pre_squad'   => $update['pre_squad'] == 'true',
                                'status'      => $status,
                                'verified'    => $isVerified,
                                'status_note' => $update['status_note'],
                            ]);

                            SetUserTeamTotal::dispatch($this->fixtureId);

                            self::dispatch($this->fixtureId)->delay(now()->addMinutes($this->time_interval));
                        }
                    }



                    // when fixture is_verified true and status 2 or 4
                    if ($isMatchCompleted && $fixture->status != FIXTURE_STATUS[3]) {

                        //running job for declare contest prize
                        if ($fixture->allow_prize_distribution) {

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
                                //echo time();die;
                                $fixture->update(['status' => FIXTURE_STATUS[3]]);
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

    public function GetScore($fixtureId = null)
    {
        // //echo 'Off';die;
        $this->fixtureId = $fixtureId;
        $fixture = Fixture::query()
            ->where('id', $this->fixtureId)
            ->first();

        if ($fixture) {

            $api = new EntitySport();

            // https://doc.entitysport.com/#match-scorecard-api
            $scorecard = $api->getScorecard($this->fixtureId);

            // echo '<pre>';
            // print_r($scorecard['latest_inning_number']);
            // die;

            if ($scorecard) {
                Redis::set("scorecard:{$this->fixtureId}", json_encode($scorecard));
            }

            // if ($this->autoSet) {
            //     if ($fixture->status === FIXTURE_STATUS[0] || $fixture->status === FIXTURE_STATUS[1] || $fixture->status === FIXTURE_STATUS[2]) {
            //         self::dispatch($this->fixtureId)->delay(now()->addMinutes(2));
            //     }
            // }
        }
    }

    public function GetSquad($fixtureId = null)
    {
        echo 'Off';
        die;
        $this->fixtureId = $fixtureId;
        $fixture = Fixture::query()
            ->where('id', $fixtureId)
            //->where('starting_at', '>', now())
            //->where('status', FIXTURE_STATUS[0])
            ->first();
        //echo '<pre>';
        //print_r($fixture);die;
        if ($fixture) {

            if ($fixture->pre_squad) {
                $api = new EntitySport();

                // https://doc.entitysport.com/#fantasy-match-roaster-api
                $squads = $api->getSquads($fixture);

                DB::transaction(function () use ($fixture, $squads) {

                    foreach ($squads as $squad) {
                        if (isset($squad['players']) && isset($squad['team_id'])) {
                            $lastPlayedPlayers = collect([]);

                            if (isset($squad['last_match_played'])) {
                                $lastPlayedPlayers = collect($squad['last_match_played']);
                            }

                            foreach ($squad['players'] as $player) {
                                if (isset($player['pid'])) {
                                    $date = $player['birthdate'] ?? null;
                                    $playerId = $player['pid'];
                                    $role = $this->getRole($player['playing_role']);

                                    if (!is_null($date) && $date == '0000-00-00') {
                                        $date = null;
                                    }

                                    // Create player if not exists or update
                                    $p = Player::query()->firstOrCreate([
                                        'id' => $playerId
                                    ], [
                                        'name' => $player['title'],
                                        'short_name' => $player['short_name'],
                                        'birthdate' => $date,
                                        'nationality' => $player['nationality'] ?? null,
                                        'batting_style' => $player['batting_style'] ?? null,
                                        'bowling_style' => $player['bowling_style'] ?? null,
                                        'country' => $player['country'] ?? null,
                                    ]);


                                    if ($p->wasRecentlyCreated) {
                                        $p->update([
                                            'image' => $player['thumb_url']
                                        ]);
                                    }

                                    if ($p->wasChanged() || $p->wasRecentlyCreated) {
                                        if (!$p->wasRecentlyCreated) {
                                            unset($p['image']);
                                        }
                                    }

                                    // Create squad entry if not exists or update
                                    $s = Squad::query()->updateOrCreate([
                                        'player_id' => $playerId,
                                        'fixture_id' => $this->fixtureId,
                                    ], [
                                        'last_played' => $lastPlayedPlayers->where('player_id', $playerId)->count(),
                                        'team_id' => $squad['team_id'],
                                    ]);

                                    if ($s->wasRecentlyCreated) {
                                        $pastFixtureIds = Fixture::query()
                                            ->where('id', '!=', $this->fixtureId)
                                            ->whereNotNull('season')
                                            ->where('season', $fixture->season)
                                            ->pluck('id');

                                        $pastPoints = Squad::query()
                                            ->whereIn('fixture_id', $pastFixtureIds)
                                            ->where('player_id', $playerId)
                                            ->sum('total_points');

                                        $s->update([
                                            'fantasy_player_rating' => $player['fantasy_player_rating'] ?? 0,
                                            'role' => $role,
                                            'series_point' => count($pastFixtureIds) == 0 ? 0 : ($pastPoints / count($pastFixtureIds))
                                        ]);
                                    }
                                }
                            }
                        }
                    }

                    $fixture->update(['last_squad_update' => now()]);

                    return true;
                });
            }

            if ($this->autoSet) {
                $nextUpdate = now()->addMinutes(30);
                self::dispatch($this->fixtureId)->delay($nextUpdate);
            }
        }
    }

    public function SetUserTeamTotal($fixtureId = null)
    {
        echo 'Off';
        die;
        $this->fixtureId = $fixtureId;
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

                                return $item['total_points'] * $multiplier;
                            });

                        $joinedTeams = UserContest::query()->with('user:id,name,photo')->where('user_team_id', $team->id)->get();
                        $this->setPrevRank($joinedTeams, $total);

                        $joinedPrivateTeams = UserPrivateContest::query()->with('user:id,name,photo')->where('user_team_id', $team->id)->get();
                        $this->setPrevRank($joinedPrivateTeams, $total, 'private_contest_id');

                        $team->update(['total_points' => $total]);
                    });
            });
        }
    }

    public function ContestProcess($contestId = null)
    {
        echo 'Off';
        die;
        $contest = Contest::query()->where('id', $contestId)->where('status', CONTEST_STATUS[2])->first();

        if ($contest) {

            $inning_number  =   $contest->fixture->inning_number;
            $check_inning   =   [0, $inning_number];
            $contest_inning_number  =   $contest->inning_number;

            if (!in_array($contest_inning_number, $check_inning)) {
                return;
            }

            $joined = $contest->joined;

            $isMatchCanceled = $contest->fixture->status == FIXTURE_STATUS[4];



            if ($contest->is_confirmed && !$isMatchCanceled) {
                if ($contest->is_dynamic) {
                    if ($contest->joined->count() >= $contest->dynamic_min_team) {
                        $this->distributePrize($contest, true);
                    } else {
                        Redis::sadd("contest_in_cancelling", $contest->id);
                        //ContestCancel::dispatch($contest->id);
                    }
                } else {
                    $this->distributePrize($contest);
                }
            } else {
                if ((count($joined) === $contest->total_teams) && !$isMatchCanceled) {
                    $this->distributePrize($contest);
                } else {
                    Redis::sadd("contest_in_cancelling", $contest->id);
                    //ContestCancel::dispatch($contest->id);
                }
            }
        }
    }

    public function redisData($fixtureId)
    {
        $fixture = Contest::query()
            ->where('fixture_id', $fixtureId)
            ->pluck('total_teams','id')->toArray();
            if(!empty($fixture)){
                foreach($fixture as $contest_id=>$total_team){
                    $usercontest = UserContest::query()
                    ->where('contest_id', $contest_id)->count();
                    $pending_spot='';
                    if($usercontest<$total_team){
                        $pending_spot=$total_team-$usercontest;
                    }
                    $redis_spot=Redis::get("contestSpace:$contest_id");
                    if($redis_spot!=$pending_spot){
                        Redis::set("contestSpace:$contest_id", $pending_spot);
                    }
                }
            }else{
                echo "fixture data not get.";die;
            }
    }

    public function fixtureCronSet($fixtureId)
    {
        $event = Fixture::query()
            ->where('id', $fixtureId)
            ->first();
            if(!empty($event)){
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
            }

    }

    public function fixtureCronStaticTime($fixtureId)
    {
        $event = Fixture::query()
            ->where('id', $fixtureId)
            ->first();
            if(!empty($event)){
                    $current_time=now();
                    $lineupSchedule = Carbon::parse($current_time)->addMinutes(1);
                    GetLineup::dispatch($event->id)->delay($lineupSchedule);

                    //Run cancel contest cron after 5 minute of match start
                    $cancelContestSchedule = Carbon::parse($current_time)->addMinutes(2);
                    CallContestCancel::dispatch($event->id)->delay($cancelContestSchedule);

                    //Run dynamic prize breakup calculation cron before 2 hour of match
                    $dynamicSchedule = Carbon::parse($current_time)->addMinutes(3);
                    CalculateDynamicPrizeBreakup::dispatch($event->id)->delay($dynamicSchedule);

                    GetScore::dispatch($event->id)->delay(Carbon::parse($current_time)->addMinute(4));

                    $updateSchedule = Carbon::parse($current_time)->addMinutes(5);
                    GetPoint::dispatch($event->id)->delay($updateSchedule);

            }

    }

    public function lineupSet()
    {
        $fixture = Fixture::query()
            ->where([['is_active',1],['status',FIXTURE_STATUS[0]]])
            ->whereDate('starting_at', '<', now())
            ->pluck('id','id')->toArray();
        foreach($fixture as $value){
            $query=Job::query()->where([['payload','LIKE','%'.$value.'%'],['queue','lineup']])->count();
            if(!$query){
                GetLineup::dispatch($value)->delay(now()->addSeconds(1));
                $this->GetScore($value);
                //GetScore::dispatch($value)->delay(now()->addSeconds(4));

            }
        }
        echo "Cron Set";die;
    }



    public function PrivateContestProcess($fixtureId = null)
    {
        echo 'Off';
        die;
    }

    public function ContestCancel($fixtureId = null)
    {
        echo 'Off';
        die;
    }


    public function CalculateDynamicPrizeBreakup($fixtureId = null)
    {
        echo 'Off';
        die;
        $this->fixtureId = $fixtureId;
        $contests = Contest::query()->where('fixture_id', $this->fixtureId)->where('status', CONTEST_STATUS[1])->where('is_dynamic', 1)->get();

        foreach ($contests as $contest) {
            $winners = 0;
            foreach ($contest->prize_breakup as $value) {
                $winners = $value['to'];
            }
            $team_joined = $contest->joined->count(); //40;//
            $new_prize_breakup = $this->calculation($contest->total_teams, $contest->entry_fee, $winners, $contest->prize, $team_joined, $contest->commission, $contest->prize_breakup);

            if ($new_prize_breakup) {
                Contest::query()->where(['id' => $contest->id])->update(['new_prize_breakup' => $new_prize_breakup]);
            }
        }
    }

    public function calculation($total_teams, $entry_fee, $winners, $prize, $coming_user, $commission_per, $prizeBreakup)
    {
        $wining_percentage = (($winners / $total_teams) * 100);

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


    public function GenerateCommission($payment_id = null)
    {
        echo 'Off';
        die;
        $payment = Payment::query()->find($payment_id);
        $user       =   $payment->user;
        $referredby =   $user->referredby;

        if (!empty($referredby)) {

            $user_id = $user->id;

            $refered_by_upper_level = $user->refered_by_upper_level;
            if (empty($refered_by_upper_level)) {
                $refered_by_upper_level = $this->getUpperUser($user_id);
                $user->refered_by_upper_level = $refered_by_upper_level;
                $user->save();
            }


            if (!empty($refered_by_upper_level)) {
                $refered_by_upper_level_array = json_decode($refered_by_upper_level);
                foreach ($refered_by_upper_level_array as $key => $value) {

                    $deposited_amount = $payment->amount;

                    if ($deposited_amount > 0) {

                        if ($value) {
                            $influncer_data = User::query()
                                ->select('id', 'promoter_type')
                                ->where('id', $value)
                                ->where('is_locked', 0)
                                ->first();

                            if (!empty($influncer_data)) {

                                if ($key == 1) {
                                    if ($influncer_data->promoter_type == 1) { // Master
                                        $percentage = 5;
                                    } else if ($influncer_data->promoter_type == 2) { //Promoter
                                        $percentage = 5;
                                    } else { // User
                                        $percentage = 2;
                                    }
                                } else if ($key == 2) {
                                    if ($influncer_data->promoter_type == 1) { // Master
                                        $percentage = 3;
                                    } else if ($influncer_data->promoter_type == 2) { //Promoter
                                        $percentage = 0;
                                    } else { // User
                                        $percentage = 0;
                                    }
                                }

                                if ($percentage > 0) {
                                    $user_comission = round(($percentage / 100) * $deposited_amount, 2);
                                    $entity    =    new ReferalDepositDetails;
                                    $entity->user_id                =    $value;
                                    $entity->earn_by                =    $user_id;
                                    $entity->deposited_amount        =    $deposited_amount;
                                    $entity->payment_id                =    $payment->id;
                                    $entity->referal_level            =    $key;
                                    $entity->referal_percentage        =    $percentage;
                                    $entity->amount                    =    $user_comission;
                                    $entity->date                    =    date('Y-m-d');
                                    $entity->save();
                                }
                            }
                        }
                    }
                }
            }
        }

        echo 'Done';
    }

    public function AddCommission()
    {
        echo 'Off';
        die;

        $today        = date('Y-m-d');
        $day_before = date('Y-m-d', strtotime($today . ' -1 day'));
        $today_start = date('Y-m-d 00:00:00', strtotime($today));
        $today_end = date('Y-m-d 23:59:59', strtotime($today));


        $paidIds = Payment::query()
            ->whereBetween('created_at', [$today_start, $today_end])
            ->where('type', PAYMENT_TYPES[10])
            ->pluck('user_id');
        //dd($paidIds);die;

        $influncer_data = ReferalDepositDetails::groupBy('user_id')
            ->selectRaw('sum(amount) as sum, user_id')
            ->whereDate('date', '=', $day_before)
            ->where('amount', '>', 0)
            ->where('is_deposieted', 0)
            ->whereNotIn('user_id', $paidIds)
            ->pluck('sum', 'user_id');
        //dd($influncer_data);

        DB::transaction(function () use ($influncer_data, $paidIds, $day_before) {

            if (!empty($influncer_data)) {
                foreach ($influncer_data as $user_id => $amount) {
                    //dd($amount);die;

                    if ($user_id && $amount > 0) {

                        $payment = [
                            'user_id' => $user_id,
                            'amount' => $amount,
                            'status' => 'SUCCESS',
                            'transaction_id' => 'PRC' . rand(),
                            'description' => 'Promoter Commission Added',
                            'type' => PAYMENT_TYPES[10],
                        ];

                        Payment::query()->create($payment);

                        // Update in user wallet
                        User::query()->where('id', $user_id)->increment('winning_amount', $amount);
                    }
                }

                ReferalDepositDetails::where('is_deposieted', 0)
                    ->whereDate('date', '=', $day_before)
                    ->where('amount', '>', 0)
                    ->whereNotIn('user_id', $paidIds)
                    ->update(['is_deposieted' => 1]);
            }

            return true;
        });
    }

    public function CalculateEarnings()
    {
        //echo 'Off';die;

        $today        = date('Y-m-d');
        $day_before = date('Y-m-d', strtotime($today . ' -1 day'));
        $day_before_start   = date('Y-m-d 00:00:00', strtotime($day_before));
        $day_before_end     = date('Y-m-d 23:59:59', strtotime($day_before));
        //echo $day_before_start;
        $fixtures = Fixture::query()
            ->selectRaw('id,name,competition_id,competition_name,starting_at')
            ->whereBetween('starting_at', [$day_before_start, $day_before_end])
            ->where('verified',  1)
            ->where('is_active',  1)
            ->where('status', 'COMPLETED')
            ->get();


        if (!empty($fixtures)) {
            foreach ($fixtures as $fixture) {
                $store_payment_data = $payment_data_all = '';
                for ($i = 1; $i <= 2; $i++) {
                    if ($i == 1) {
                        $relativedata = $fixture->user_contests_with_where;
                    } elseif ($i == 2) {
                        $relativedata = $fixture->all_user_contests_with_where;
                    }
                    $total_winning_distributed =  $relativedata->sum('prize');

                    $used_cash_bonus =  $relativedata->sum(function ($product) {
                        $array = json_decode($product['payment_data'], true);
                        return $array['cash_bonus'];
                    });

                    $used_winning_amount =  $relativedata->sum(function ($product) {
                        $array = json_decode($product['payment_data'], true);
                        return $array['wining_amount'];
                    });

                    $used_deposited_balance =  $relativedata->sum(function ($product) {
                        $array = json_decode($product['payment_data'], true);
                        return $array['deposited_balance'];
                    });

                    $total_amount = ($used_cash_bonus + $used_winning_amount + $used_deposited_balance);

                    $payment_data = [];
                    $payment_data['used_cash_bonus']            = $used_cash_bonus;
                    $payment_data['used_winning_amount']        = $used_winning_amount;
                    $payment_data['used_deposited_balance']     = $used_deposited_balance;
                    $payment_data['total_amount']               = $total_amount;
                    $payment_data['total_winning_distributed']  = $total_winning_distributed;
                    if ($i == 1) {
                        $store_payment_data = $payment_data;
                        //$fixture->update([ 'payment_data' => $payment_data ]);
                    } elseif ($i == 2) {
                        $payment_data_all = $payment_data;
                        //$fixture->update([ 'payment_data_all' => $payment_data ]);
                    }
                }
                $fixture->update(['payment_data' => $store_payment_data, 'payment_data_all' => $payment_data_all]);
            }
        }
        echo "fine";
        die;
    }

    public $refered_upper_users = [];
    public $refered_upper_level = 0;
    public $refered_upper_maxlevel = 2;

    public function getUpperUser($user_id)
    {


        $this->refered_upper_level++;
        if ($this->refered_upper_level > $this->refered_upper_maxlevel) {
            $refered_upper_users = $this->refered_upper_users;

            if (!empty($refered_upper_users)) {
                $refered_upper_users_json = json_encode($refered_upper_users);
            } else {
                $refered_upper_users_json = '';
            }
            $this->refered_upper_level = 0;
            $this->refered_upper_users = [];
            return $refered_upper_users_json;
        }

        $user = User::query()
            ->where('id', $user_id)
            ->first();

        if (!empty($user)) {
            $referral_id = $user->referral_id;
            $this->refered_upper_users[$this->refered_upper_level] = $referral_id;
            return $this->getUpperUser($referral_id);
        } else {
            $refered_upper_users = $this->refered_upper_users;
            if (!empty($refered_upper_users)) {
                $refered_upper_users_json = json_encode($refered_upper_users);
            } else {
                $refered_upper_users_json = '';
            }
            $this->refered_upper_level = 0;
            $this->refered_upper_users = [];
            return $refered_upper_users_json;
        }
    }

    /**
     * Auto create contests for new fixtures
     *
     * @param $event
     */
    private function createContests($event)
    {
        $templates = ContestTemplate::query()
            ->where('auto_add', 1)
            ->get();

        foreach ($templates as $template) {
            $contestData = [
                'contest_category_id' => $template->contest_category_id,
                'invite_code' => generateRandomString(),
                'status' => CONTEST_STATUS[1],
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
                'is_mega_contest' => $template->is_mega_contest,
                'discount' => $template->discount,
                'bonus' => $template->bonus,
            ];

            $contest = $event->contests()->create($contestData);

            Redis::set("contestSpace:$contest->id", (string)$contest->total_teams);
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

                if ($isPlaying) {
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

    /**
     * Get role name
     *
     * @param $code
     * @return mixed
     */
    private function getRole($code)
    {
        $data = [
            [
                'id' => 'bat',
                'name' => POSITIONS[1]
            ],
            [
                'id' => 'bowl',
                'name' => POSITIONS[3]
            ],
            [
                'id' => 'all',
                'name' => POSITIONS[2]
            ],
            [
                'id' => 'wk',
                'name' => POSITIONS[0]
            ],
            [
                'id' => 'wkbat',
                'name' => POSITIONS[0]
            ]
        ];

        return collect($data)->firstWhere('id', $code)['name'];
    }

    private function setPrevRank($teams, $total, $field = 'contest_id')
    {
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

    private function get_prize_value($counter, $pb)
    {
        $prize = 0;
        foreach ($pb as $pbe) {
            if ($counter >= $pbe['from'] && $counter <= $pbe['to']) {
                $prize = $pbe['prize'];
            }
        }
        return $prize;
    }

    private function distributePrize($contest, $isDynamic = false)
    {
        $this->updateRank($contest);

        if ($contest->type !== 'PRACTICE') {
            if ($isDynamic) {
                if ($contest->total_teams > $contest->joined->count()) {
                    $contest->prize_breakup = $contest->new_prize_breakup;
                }
            }
            if (count($contest->prize_breakup) > 0) {

                $tdsSetting = Setting::query()->firstWhere('key', 'tds_deduction');
                $tdsPercentage = $tdsSetting->value;

                $pb = $contest->prize_breakup;
                $toalWinner =  max(array_column($pb, 'to'));

                $leaderboard = $contest->joined()->whereBetween('rank', [1, $toalWinner])->orderBy('rank')->get();

                $rank_counter_winning = [];
                $counter = 1;
                if (!empty($leaderboard) && count($leaderboard) > 0) {

                    foreach ($leaderboard as $leader) {
                        $rank = $leader->rank;
                        $rank_counter_winning[$rank]['rank'] = $rank;
                        $rank_counter_winning[$rank]['counter'][] = $counter;
                        $prize = $this->get_prize_value($counter, $pb);
                        if ($prize) {
                            $rank_counter_winning[$rank]['prize'][] = $prize;
                        }
                        $counter++;
                    }

                    $tdsAmount = 0;
                    foreach ($leaderboard as $l) {

                        $rank = $l['rank'];
                        $usercount = count($rank_counter_winning[$rank]['counter']);
                        $prizesum  = array_sum($rank_counter_winning[$rank]['prize']);
                        $prize = $prizesum / $usercount;
                        if ($prize > 10000) {
                            $tdsAmount = $prize * $tdsPercentage / 100;
                            $prize = $prize - $tdsAmount;
                        }

                        DB::transaction(function () use ($l, $contest, $prize, $tdsAmount) {
                            UserContest::query()
                                ->where(['user_id' => $l['user_id'], 'contest_id' => $contest->id, 'user_team_id' => $l['user_team_id']])
                                ->update(['prize' => $prize]);

                            if ($contest->type !== 'PRACTICE') {
                                $payment = [
                                    'user_id' => $l['user_id'],
                                    'amount' => $prize,
                                    'status' => 'SUCCESS',
                                    'transaction_id' => 'CWN' . rand(),
                                    'description' => 'Contest won',
                                    'type' => PAYMENT_TYPES[3],
                                    'contest_id' => $contest->id
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

        Contest::query()->where(['id' => $contest->id])->update(['status' => CONTEST_STATUS[3]]);
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


    private function updatePoints($fixture, $update)
    {

        $innining = !empty($update['innings']) ? $update['innings'] : '';
        $matchType = !empty($update['format_str']) ? $update['format_str'] : '';
        $data = array();

        $this->fetchfantasypoint($matchType);

        $jsonInningData = array();
        if (!empty($innining)) {

            $live_inning = count($innining);
            $fixture->update([
                'inning_number' => $live_inning,
            ]);

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
                            'balls_faced' => $batsmenData['balls_faced'],
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



            $playing11_point = $runs = $runs_point = $fours = $fours_point = $sixes = $sixes_point = $century_half_century = $century_half_century_point = $strike_rate = $strike_rate_point = $duck = $duck_point = $wicket = $wicket_point = $maiden_over = $maiden_over_point = $economy_rate = $economy_rate_point = $catch = $catch_point = $runoutstumping = $runoutstumping_point = $bonus_point = $balls_faced = $stumping = $runout_thrower = $runout_catcher = $runout_direct_hit = $overs = $bowledcount = $lbwcount = $total_points = 0;

            $inlineup = $fixture->squads()->where(['player_id' => $player_id, 'playing11' => '1'])->exists();

            $first_inning = $second_inning = $third_inning = $fourth_inning = [];
            foreach ($playerValue as $inning_number => $pdata) {

                $pdata['inlineup'] = $inlineup;
                $pdata['matchType'] = $matchType;
                $pdata['inning_number'] = $inning_number;

                if ($inning_number == 1) {
                    $first_inning = $this->generateinningdata($pdata);
                }
                if ($inning_number == 2) {
                    $second_inning = $this->generateinningdata($pdata);
                }
                if ($inning_number == 3) {
                    $third_inning = $this->generateinningdata($pdata);
                }
                if ($inning_number == 4) {
                    $fourth_inning = $this->generateinningdata($pdata);
                }

                $runs += (isset($pdata['runs'])) ? $pdata['runs'] : 0;
                $fours += (isset($pdata['fours'])) ? $pdata['fours'] : 0;
                $sixes += (isset($pdata['sixes'])) ? $pdata['sixes'] : 0;
                $balls_faced += (isset($pdata['balls_faced'])) ? $pdata['balls_faced'] : 0;
                $strike_rate += (isset($pdata['strike_rate'])) ? $pdata['strike_rate'] : 0;
                $duck += (isset($pdata['duck'])) ? $pdata['duck'] : 0;
                $wicket += (isset($pdata['wicket'])) ? $pdata['wicket'] : 0;
                $maiden_over += (isset($pdata['maiden_over'])) ? $pdata['maiden_over'] : 0;
                $catch += (isset($pdata['catch'])) ? $pdata['catch'] : 0;
                $stumping += (isset($pdata['stumping'])) ? $pdata['stumping'] : 0;
                $runout_thrower += (isset($pdata['runout_thrower'])) ? $pdata['runout_thrower'] : 0;
                $runout_catcher += (isset($pdata['runout_catcher'])) ? $pdata['runout_catcher'] : 0;
                $runout_direct_hit += (isset($pdata['runout_direct_hit'])) ? $pdata['runout_direct_hit'] : 0;
                $economy_rate += (isset($pdata['economy_rate'])) ? $pdata['economy_rate'] : 0;
                $overs += (isset($pdata['overs'])) ? $pdata['overs'] : 0;
                $bowledcount += (isset($pdata['bowledcount'])) ? $pdata['bowledcount'] : 0;
                $lbwcount += (isset($pdata['lbwcount'])) ? $pdata['lbwcount'] : 0;
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


            /* if($inlineup){
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

            if ($runs > 49 && $runs < 100) {
                $century_half_century = 1;
                $century_half_century_point = $century_half_century * $this->getpointvalue('bt 50-99');
            }
            if ($runs > 99) {
                $century_half_century = 1;
                $century_half_century_point = $century_half_century * $this->getpointvalue('gt 99');
            }

            $strike_rate_point = 0;
            $economy_rate_point = 0;
            $bonus_point = 0;

            //Bouled / LPW Bonus
            $bonus_point += $bowledcount * $point_bowled_bonus;
            $bonus_point += $lbwcount * $point_bowled_bonus;

            if(($matchType=='Test' || $matchType=='First-class')){
               if($wicket==4){
                 $bonus_point += $point_four_wicket;
               }
               if($wicket >= 5){
                $bonus_point += $point_five_wicket;
               }
            }elseif($matchType=='ODI'){

                if($wicket==4){
                    $bonus_point += $point_four_wicket;
                  }
                  if($wicket >= 5){
                   $bonus_point += $point_five_wicket;
                  }

                  if($catch >= 3){
                    $bonus_point += $point_third_catch;
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
                if($wicket==3){
                    $bonus_point += $point_three_wicket;
                  }
                if($wicket==4){
                    $bonus_point += $point_four_wicket;
                  }
                  if($wicket >= 5){
                   $bonus_point += $point_five_wicket;
                  }

                  if($catch >= 3){
                    $bonus_point += $point_third_catch;
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

                if($wicket==2){
                    $bonus_point += $point_two_wicket;
                  }

                  if($wicket >= 3){
                   $bonus_point += $point_three_wicket;
                  }

                  if($catch >= 3){
                    $bonus_point += $point_third_catch;
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

            $total_points = $playing11_point + $runs_point + $fours_point + $sixes_point + $century_half_century_point + $strike_rate_point + $duck_point + $wicket_point + $maiden_over_point + $economy_rate_point + $catch_point + $runoutstumping_point + $bonus_point;

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
                'bonus_point' => $bonus_point,
                'total_points' => $total_points,

                'first_inning' => $first_inning,
                'second_inning' => $second_inning,
                'third_inning' => $third_inning,
                'fourth_inning' => $fourth_inning,
            ];

            $fixture->squads()->where('player_id', $player_id)->update($data); */
        }
    }


    private function generateinningdata($pdata = [])
    {


        $matchType = '';
        $inlineup = $inning_number = $playing11_point = $runs = $runs_point = $fours = $fours_point = $sixes = $sixes_point = $century_half_century = $century_half_century_point = $strike_rate = $strike_rate_point = $duck = $duck_point = $wicket = $wicket_point = $maiden_over = $maiden_over_point = $economy_rate = $economy_rate_point = $catch = $catch_point = $runoutstumping = $runoutstumping_point = $bonus_point = $balls_faced = $stumping = $runout_thrower = $runout_catcher = $runout_direct_hit = $overs = $bowledcount = $lbwcount = $total_points = 0;

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


        if (!empty($pdata)) {
            $inlineup = $pdata['inlineup'];
            $matchType = $pdata['matchType'];
            $inning_number = $pdata['inning_number'];

            $runs = (isset($pdata['runs'])) ? $pdata['runs'] : 0;
            $fours = (isset($pdata['fours'])) ? $pdata['fours'] : 0;
            $sixes = (isset($pdata['sixes'])) ? $pdata['sixes'] : 0;
            $balls_faced = (isset($pdata['balls_faced'])) ? $pdata['balls_faced'] : 0;
            $strike_rate = (isset($pdata['strike_rate'])) ? $pdata['strike_rate'] : 0;
            $duck = (isset($pdata['duck'])) ? $pdata['duck'] : 0;
            $wicket = (isset($pdata['wicket'])) ? $pdata['wicket'] : 0;
            $maiden_over = (isset($pdata['maiden_over'])) ? $pdata['maiden_over'] : 0;
            $catch = (isset($pdata['catch'])) ? $pdata['catch'] : 0;
            $stumping = (isset($pdata['stumping'])) ? $pdata['stumping'] : 0;
            $runout_thrower = (isset($pdata['runout_thrower'])) ? $pdata['runout_thrower'] : 0;
            $runout_catcher = (isset($pdata['runout_catcher'])) ? $pdata['runout_catcher'] : 0;
            $runout_direct_hit = (isset($pdata['runout_direct_hit'])) ? $pdata['runout_direct_hit'] : 0;
            $economy_rate = (isset($pdata['economy_rate'])) ? $pdata['economy_rate'] : 0;
            $overs = (isset($pdata['overs'])) ? $pdata['overs'] : 0;
            $bowledcount = (isset($pdata['bowledcount'])) ? $pdata['bowledcount'] : 0;
            $lbwcount = (isset($pdata['lbwcount'])) ? $pdata['lbwcount'] : 0;
        }

        if ($inlineup) {
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
        $bonus_point = 0;

        //Bouled / LPW Bonus
        $bonus_point += $bowledcount * $point_bowled_bonus;
        $bonus_point += $lbwcount * $point_bowled_bonus;

        if (($matchType == 'Test' || $matchType == 'First-class')) {
            if ($runs > 49 && $runs < 100) {
                $century_half_century = 1;
                $century_half_century_point = $century_half_century * $this->getpointvalue('bt 50-99');
            }
            if ($runs > 99) {
                $century_half_century = 1;
                $century_half_century_point = $century_half_century * $this->getpointvalue('gt 99');
            }

            if ($wicket == 4) {
                $bonus_point += $point_four_wicket;
            }
            if ($wicket >= 5) {
                $bonus_point += $point_five_wicket;
            }
        } elseif ($matchType == 'ODI') {

            if ($runs > 49 && $runs < 100) {
                $century_half_century = 1;
                $century_half_century_point = $century_half_century * $this->getpointvalue('bt 50-99');
            }
            if ($runs > 99) {
                $century_half_century = 1;
                $century_half_century_point = $century_half_century * $this->getpointvalue('gt 99');
            }

            if ($wicket == 4) {
                $bonus_point += $point_four_wicket;
            }
            if ($wicket >= 5) {
                $bonus_point += $point_five_wicket;
            }

            if ($catch >= 3) {
                $bonus_point += $point_third_catch;
            }

            if ($balls_faced >= 20) { //V Strike rate
                if ($strike_rate < 30) {
                    $strike_rate_point = $strike_rate_point + $this->getpointvalue('lt 30');
                }
                if (($strike_rate >= 30) && ($strike_rate <= 39.99)) {
                    $strike_rate_point = $strike_rate_point + $this->getpointvalue('bt 30-39.99');
                }
                if (($strike_rate >= 40) && ($strike_rate <= 50)) {
                    $strike_rate_point = $strike_rate_point + $this->getpointvalue('bt 40-50');
                }

                if (($strike_rate >= 100) && ($strike_rate <= 120)) {
                    $strike_rate_point = $strike_rate_point + $this->getpointvalue('bt 100-120');
                }
                if (($strike_rate > 120) && ($strike_rate <= 140)) {
                    $strike_rate_point = $strike_rate_point + $this->getpointvalue('bt 120.01-140');
                }
                if (($strike_rate > 140)) {
                    $strike_rate_point = $strike_rate_point + $this->getpointvalue('bt 140');
                }
            }

            //Economic rate
            if ($overs >= 5) {
                if ($economy_rate < 2.5) {
                    $economy_rate_point = $economy_rate_point + $this->getpointvalue('bt 2.5');
                }
                if (($economy_rate >= 2.5) && ($economy_rate <= 3.49)) {
                    $economy_rate_point = $economy_rate_point + $this->getpointvalue('bt 2.5-3.49');
                }
                if (($economy_rate >= 3.5) && ($economy_rate <= 4.5)) {
                    $economy_rate_point = $economy_rate_point + $this->getpointvalue('bt 3.5-4.5');
                }

                if (($economy_rate >= 7) && ($economy_rate <= 8)) {
                    $economy_rate_point = $economy_rate_point + $this->getpointvalue('bt 7-8');
                }
                if (($economy_rate >= 8.1) && ($economy_rate <= 9)) {
                    $economy_rate_point = $economy_rate_point + $this->getpointvalue('bt 8.01-9');
                }
                if ($economy_rate > 9) {
                    $economy_rate_point = $economy_rate_point + $this->getpointvalue('gt 9');
                }
            }
        } elseif ($matchType == 'T20') {

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

            if ($wicket == 3) {
                $bonus_point += $point_three_wicket;
            }
            if ($wicket == 4) {
                $bonus_point += $point_four_wicket;
            }
            if ($wicket >= 5) {
                $bonus_point += $point_five_wicket;
            }

            if ($catch >= 3) {
                $bonus_point += $point_third_catch;
            }

            if ($balls_faced >= 10) {
                if ($strike_rate < 50) {
                    $strike_rate_point = $strike_rate_point + $this->getpointvalue('lt 50');
                }
                if (($strike_rate >= 50) && ($strike_rate <= 59.99)) {
                    $strike_rate_point = $strike_rate_point + $this->getpointvalue('bt 50-59.99');
                }
                if (($strike_rate >= 60) && ($strike_rate <= 70)) {
                    $strike_rate_point = $strike_rate_point + $this->getpointvalue('bt 50-59.99');
                }

                if (($strike_rate >= 130) && ($strike_rate <= 150)) {
                    $strike_rate_point = $strike_rate_point + $this->getpointvalue('bt 130-150');
                }
                if (($strike_rate > 150) && ($strike_rate <= 170)) {
                    $strike_rate_point = $strike_rate_point + $this->getpointvalue('bt 150.01-170');
                }
                if (($strike_rate > 170)) {
                    $strike_rate_point = $strike_rate_point + $this->getpointvalue('lt 170');
                }
            }

            //Economic rate
            if ($overs >= 2) {
                if ($economy_rate < 5) {
                    $economy_rate_point = $economy_rate_point + $this->getpointvalue('lt 5');
                }
                if (($economy_rate >= 5) && ($economy_rate <= 5.99)) {
                    $economy_rate_point = $economy_rate_point + $this->getpointvalue('bt 5-5.99');
                }
                if (($economy_rate >= 6) && ($economy_rate <= 7)) {
                    $economy_rate_point = $economy_rate_point + $this->getpointvalue('bt 6-7');
                }

                if (($economy_rate >= 10) && ($economy_rate <= 11)) {
                    $economy_rate_point = $economy_rate_point + $this->getpointvalue('bt 10-11');
                }
                if (($economy_rate >= 11.01) && ($economy_rate <= 12)) {
                    $economy_rate_point = $economy_rate_point + $this->getpointvalue('bt 11.01-12');
                }
                if ($economy_rate > 12) {
                    $economy_rate_point = $economy_rate_point + $this->getpointvalue('gt 12');
                }
            }
        } elseif ($matchType == 'T10') {

            if ($runs > 29 && $runs < 50) {
                $century_half_century = 1;
                $century_half_century_point = $century_half_century * $this->getpointvalue('bt 30-49');
            }

            if ($runs > 49) {
                $century_half_century = 1;
                $century_half_century_point = $century_half_century * $this->getpointvalue('gt 49');
            }

            if ($wicket == 2) {
                $bonus_point += $point_two_wicket;
            }

            if ($wicket >= 3) {
                $bonus_point += $point_three_wicket;
            }

            if ($catch >= 3) {
                $bonus_point += $point_third_catch;
            }

            if ($balls_faced >= 5) {
                if ($strike_rate < 60) {
                    $strike_rate_point = $strike_rate_point + $this->getpointvalue('lt 60');
                }
                if (($strike_rate >= 60) && ($strike_rate <= 69.99)) {
                    $strike_rate_point = $strike_rate_point + $this->getpointvalue('bt 60-69.99');
                }
                if (($strike_rate >= 70) && ($strike_rate <= 80)) {
                    $strike_rate_point = $strike_rate_point + $this->getpointvalue('bt 70-80');
                }

                if (($strike_rate >= 150) && ($strike_rate <= 170)) {
                    $strike_rate_point = $strike_rate_point + $this->getpointvalue('bt 150-170');
                }
                if (($strike_rate > 170) && ($strike_rate <= 190)) {
                    $strike_rate_point = $strike_rate_point + $this->getpointvalue('bt 170.01-190');
                }
                if (($strike_rate > 190)) {
                    $strike_rate_point = $strike_rate_point + $this->getpointvalue('bt 190');
                }
            }

            //Economic rate
            if ($overs >= 1) {
                if ($economy_rate < 7) {
                    $economy_rate_point = $economy_rate_point + $this->getpointvalue('lt 7');
                }
                if (($economy_rate >= 7) && ($economy_rate <= 7.99)) {
                    $economy_rate_point = $economy_rate_point + $this->getpointvalue('bt 7-7.99');
                }
                if (($economy_rate >= 8) && ($economy_rate <= 9)) {
                    $economy_rate_point = $economy_rate_point + $this->getpointvalue('bt 8-9');
                }

                if (($economy_rate >= 14) && ($economy_rate <= 15)) {
                    $economy_rate_point = $economy_rate_point + $this->getpointvalue('bt 14-15');
                }
                if (($economy_rate >= 15.01) && ($economy_rate <= 16)) {
                    $economy_rate_point = $economy_rate_point + $this->getpointvalue('bt 15.01-16');
                }
                if ($economy_rate > 16) {
                    $economy_rate_point = $economy_rate_point + $this->getpointvalue('gt 16');
                }
            }
        }

        $total_points = $playing11_point + $runs_point + $fours_point + $sixes_point + $century_half_century_point + $strike_rate_point + $duck_point + $wicket_point + $maiden_over_point + $economy_rate_point + $catch_point + $runoutstumping_point + $bonus_point;

        $data = [
            'playing11_point' => $playing11_point,
            'runs' => $runs,
            'runs_point' => $runs_point,
            'fours' => $fours,
            'fours_point' => $fours_point,
            'sixes' => $sixes,
            'sixes_point' => $sixes_point,
            'century_half_century' => $century_half_century,
            'century_half_century_point' => $century_half_century_point,
            'strike_rate' => $strike_rate,
            'strike_rate_point' => $strike_rate_point,
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
            'bonus_point' => $bonus_point,
            'total_points' => $total_points
        ];

        if ($inning_number) {
            return json_encode($data);
        } else {
            return $data;
        }
    }


    private array $fantasy_points_array = [];
    private function fetchfantasypoint($type)
    {

        $fantasy_points = FantasyPoint::query()
            ->where('type', $type)
            ->get();
        if ($fantasy_points) {
            $fantasy_points_array = [];
            $fantasy_points_object = json_encode($fantasy_points);
            $fantasy_points_object = json_decode($fantasy_points_object, true);
            foreach ($fantasy_points_object as $key => $value) {
                $fantasy_points_array[$value['code']] = $value;
            }
            $this->fantasy_points_array = $fantasy_points_array;
        }
    }


    private function getpointvalue($code)
    {

        return (isset($this->fantasy_points_array[$code]['point'])) ? $this->fantasy_points_array[$code]['point'] : 0;
    }

    public function leaderboard()
    {
        //echo "leaderboard";die;
        $yesterday = date("Y-m-d", strtotime('-1 days'));

        //$fixture = Fixture::query()
        $fixture = Competition::query()
            ->from('competitions', 'cmp')
            ->join('fixtures as f', 'f.competition_id', '=', 'cmp.id')
            ->join('user_contests as uc', 'uc.fixture_id', '=', 'f.id')
            ->join('user_teams as ut', 'ut.id', '=', 'uc.user_team_id')
            ->join('contests as c', 'c.id', '=', 'uc.contest_id')
            ->select('uc.contest_id', 'uc.user_team_id', 'uc.id', 'ut.user_id', 'ut.total_points', 'cmp.id as cmpId')
            ->where([['c.is_mega_contest', 1], ['cmp.is_leaderboard', 1], ['ut.is_leaderboard', 0]])
            ->whereDate('f.ending_at', '=', $yesterday)
            ->orderBy('ut.total_points', 'DESC')
            ->groupBy(['uc.contest_id', 'ut.user_id'])
            ->get();

        $uniqueContestId = $leaderboardData = [];
        $name = '';
        foreach ($fixture as $value) {
            $dummyPoint = '';
            if (!empty($value['user_team_id'])) {
                $leaderboardData[$value['contest_id']][$value['user_id']] = array("total_points" => $value['total_points'], "user_team_id" => $value['user_team_id'], "user_id" => $value['user_id'], 'cmpetition_id' => $value['cmpId']);
                $uniqueContestId[$value['contest_id']] = $value['contest_id'];
            }
        }
        echo "<pre>";
        print_r($leaderboardData);
        die;

        // $megaContestData = [];
        // if(!empty($uniqueContestId)){
        //     foreach ($uniqueContestId as $value) {
        //         $valueArr = [];
        //         if (!empty($leaderboardData[$value])) {
        //             foreach ($leaderboardData[$value] as $cValue) {
        //                 //echo "<pre>";print_r($cValue);echo "</pre>";
        //                 if (!empty($cValue)) {
        //                     $use_team = UserTeam::find($cValue['user_team_id']);
        //                     $use_team->is_leaderboard = 1;
        //                     if ($use_team->save()) {
        //                         $leaderboard = Leaderboard::where([
        //                             'competition_id' => $cValue['cmpetition_id'],
        //                             'user_id' => $cValue['user_id']
        //                         ])->first();
        //                         if (!empty($leaderboard)) {
        //                             $leaderboard->total_point += $cValue['total_points'];
        //                             $leaderboard->save();
        //                         } else {
        //                             $leaderboard = new Leaderboard;
        //                             $leaderboard->competition_id = $cValue['cmpetition_id'];
        //                             $leaderboard->user_id = $cValue['user_id'];
        //                             $leaderboard->total_point = $cValue['total_points'];
        //                             $leaderboard->save();
        //                         }
        //                     }
        //                     //$valueArr[]=$cValue;
        //                 }
        //             }
        //         }
        //         // if (!empty($valueArr)) {
        //         //     $megaContestData[$value['contest_id']] = $valueArr;
        //         // }

        //     }
        // }
        echo "fine";
        die;
        //echo "<pre>";print_r($megaContestData);die;
        // if (!empty($megaContestData)) {
        //     foreach ($megaContestData as $uValue) {
        //         foreach($uValue as $userpoints){
        //             $use_team = UserTeam::find($userpoints['user_team_id']);
        //             $use_team->is_leaderboard = 1;
        //             if ($use_team->save()) {
        //                 $leaderboard = Leaderboard::where([
        //                     'competition_id' => $userpoints['cmpetition_id'],
        //                     'user_id' => $userpoints['user_id']
        //                 ])->first();
        //                 if (!empty($leaderboard)) {
        //                     $leaderboard->total_point += $userpoints['total_points'];
        //                     $leaderboard->save();
        //                 } else {
        //                     $leaderboard = new Leaderboard;
        //                     $leaderboard->competition_id = $userpoints['cmpetition_id'];
        //                     $leaderboard->user_id = $userpoints['user_id'];
        //                     $leaderboard->total_point = $userpoints['total_points'];
        //                     $leaderboard->save();
        //                 }
        //             }
        //         }
        //     }
        // }

        // $rankleaderboard = Leaderboard::query()
        //     ->orderBy('competition_id')
        //     ->orderBy('total_point', 'Desc')->get();
        // $total_point = $cmp_id = '';
        // $ranks = 0;
        // foreach ($rankleaderboard as $rValue) {
        //     if ($cmp_id != $rValue['competition_id']) {
        //         $ranks = 0;
        //     }
        //     if ($rValue['total_point'] == $total_point) {
        //         $rank = $ranks;
        //     } else {
        //         $ranks++;
        //         $rank = $ranks;
        //     }
        //     $total_point = $rValue['total_point'];
        //     $cmp_id = $rValue['competition_id'];
        //     $rankUpdateLead = Leaderboard::find($rValue['id']);
        //     $rankUpdateLead->rank = $ranks;
        //     $rankUpdateLead->save();
        // }
    }
}
