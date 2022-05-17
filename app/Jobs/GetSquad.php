<?php

namespace App\Jobs;

use App\EntitySport;
use App\Models\Fixture;
use App\Models\Player;
use App\Models\Squad;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class GetSquad implements ShouldQueue
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
    public $timeout = 600;

    /**
     * Create a new job instance.
     *
     * @param $fixtureId
     * @param bool $autoSet
     */
    public function __construct($fixtureId, bool $autoSet = true, int $time_interval = 30)
    {
        $this->queue = 'squad';
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
            ->where('starting_at', '>', now())
            ->where('status', FIXTURE_STATUS[0])
            ->first();

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
                                            'series_point' => count($pastFixtureIds) == 0 ? 0 : ($pastPoints / count($pastFixtureIds)),
                                            'first_inning' => get_dummy_inning_json(),
                                            'second_inning' => get_dummy_inning_json(),
                                            'third_inning' => get_dummy_inning_json(),
                                            'fourth_inning' => get_dummy_inning_json(),
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
                $nextUpdate = now()->addMinutes($this->time_interval);
                self::dispatch($this->fixtureId)->delay($nextUpdate);
            }
        }
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
}
