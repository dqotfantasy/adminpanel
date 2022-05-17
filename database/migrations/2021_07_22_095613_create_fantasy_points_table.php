<?php

use App\Models\FantasyPointCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFantasyPointsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fantasy_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fantasy_point_category_id')->constrained();
            $table->string('name');
            $table->string('code');
            $table->string('postfix')->nullable();
            $table->string('note')->nullable();
            $table->string('point');
            $table->enum('type', CRICKET_TYPES);
            $table->timestamps();
        });

        $categories = FantasyPointCategory::all();
        $this->insertOdiPoints($categories);
        $this->insertT20Points($categories);
        $this->insertT10Points($categories);
        $this->insertTestPoints($categories);
    }

    private function insertOdiPoints($categories)
    {
        $type = 'ODI';
        foreach ($categories as $category) {
            if ($category->id === 1) {
                $points = [
                    [
                        'name' => 'Every run scored',
                        'code' => 'run',
                        'postfix' => null,
                        'note' => null,
                        'point' => 1,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Every boundary hit',
                        'code' => 'four',
                        'postfix' => null,
                        'note' => null,
                        'point' => 1,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Every six-hit',
                        'code' => 'six',
                        'postfix' => null,
                        'note' => null,
                        'point' => 2,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Half Century (50 runs scored by a batsman in a single innings)',
                        'code' => 'bt 50-99',
                        'postfix' => null,
                        'note' => null,
                        'point' => 4,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Century (100 runs scored by a batsman in a single innings)',
                        'code' => 'gt 99',
                        'postfix' => null,
                        'note' => null,
                        'point' => 8,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Dismissal for a Duck (only for batsmen, wicket-keepers and all-rounders)',
                        'code' => 'duck',
                        'postfix' => null,
                        'note' => null,
                        'point' => -3,
                        'type' => $type,
                    ],
                ];

                $category->fantasy_points()->createMany($points);
            } else if ($category->id === 2) {
                $points = [
                    [
                        'name' => 'Every wicket taken (excluding run out)',
                        'code' => 'wicket',
                        'postfix' => null,
                        'note' => null,
                        'point' => 25,
                        'type' => $type,
                    ],
                    [
                        'name' => '4 wickets',
                        'code' => 'four_wicket',
                        'postfix' => null,
                        'note' => null,
                        'point' => 4,
                        'type' => $type,
                    ],
                    [
                        'name' => '5 wickets',
                        'code' => 'five_wicket',
                        'postfix' => null,
                        'note' => null,
                        'point' => 8,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Maiden over',
                        'code' => 'maiden_over',
                        'postfix' => null,
                        'note' => null,
                        'point' => 4,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Bonus (LBW / Bowled)',
                        'code' => 'stumped',
                        'postfix' => null,
                        'note' => null,
                        'point' => 8,
                        'type' => $type,
                    ],
                ];

                $category->fantasy_points()->createMany($points);
            } else if ($category->id === 3) {
                $points = [
                    [
                        'name' => 'Catch taken',
                        'code' => 'catch',
                        'postfix' => null,
                        'note' => null,
                        'point' => 8,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Caught & Bowled',
                        'code' => 'bowled',
                        'postfix' => null,
                        'note' => null,
                        'point' => 33,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Stumping/ Run Out (direct)',
                        'code' => 'stumped',
                        'postfix' => null,
                        'note' => null,
                        'point' => 12,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Run Out (Thrower)',
                        'code' => 'stumped',
                        'postfix' => null,
                        'note' => null,
                        'point' => 6,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Run Out (Catcher)',
                        'code' => 'stumped',
                        'postfix' => null,
                        'note' => null,
                        'point' => 6,
                        'type' => $type,
                    ],
                    [
                        'name' => '3 Catch Bonus',
                        'code' => 'third_catch',
                        'postfix' => null,
                        'note' => null,
                        'point' => 4,
                        'type' => $type,
                    ]
                ];

                $category->fantasy_points()->createMany($points);
            } else if ($category->id === 4) {
                $points = [
                    [
                        'name' => 'Captain',
                        'code' => 'captain',
                        'postfix' => 'x',
                        'note' => null,
                        'point' => 2,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Vice-Captain',
                        'code' => 'vice_captain',
                        'postfix' => 'x',
                        'note' => null,
                        'point' => 1.5,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Being a part of the starting XI',
                        'code' => 'p11',
                        'postfix' => null,
                        'note' => null,
                        'point' => 4,
                        'type' => $type,
                    ],
                ];

                $category->fantasy_points()->createMany($points);
            } else if ($category->id === 5) {
                $points = [
                    [
                        'name' => 'Minimum balls faced by a player to be applicable',
                        'code' => 'lt 3.2',
                        'postfix' => null,
                        'note' => null,
                        'point' => '20 balls',
                        'type' => $type,
                    ],
                    [
                        'name' => 'Between 2.5 and 3.49 runs per over',
                        'code' => 'bt 2.5-3.49',
                        'postfix' => null,
                        'note' => null,
                        'point' => 4,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Between 3.5 - 4.5 runs per over',
                        'code' => 'bt 3.5-4.5',
                        'postfix' => null,
                        'note' => null,
                        'point' => 2,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Below 2.5 runs per over',
                        'code' => 'bt 2.5',
                        'postfix' => null,
                        'note' => null,
                        'point' => 6,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Between 7 and 8 runs per over',
                        'code' => 'bt 7-8',
                        'postfix' => null,
                        'note' => null,
                        'point' => -2,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Between 8.01 and 9 runs per over',
                        'code' => 'bt 8.01-9',
                        'postfix' => null,
                        'note' => null,
                        'point' => -4,
                        'type' => $type,
                    ],
                    [
                        'name' => ' Above 9 runs per over',
                        'code' => 'gt 9',
                        'postfix' => null,
                        'note' => null,
                        'point' => -6,
                        'type' => $type,
                    ],
                ];

                $category->fantasy_points()->createMany($points);
            } else if ($category->id === 6) {
                $points = [
                    [
                        'name' => 'Minimum balls faced by a player to be applicable',
                        'code' => 'lt 20',
                        'postfix' => null,
                        'note' => null,
                        'point' => '20 balls',
                        'type' => $type,
                    ],
                    [
                        'name' => 'Between 40 and 50 runs per 100 balls',
                        'code' => 'bt 40-50',
                        'postfix' => null,
                        'note' => null,
                        'point' => -2,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Between 30 and 39.99 runs per 100 balls',
                        'code' => 'bt 30-39.99',
                        'postfix' => null,
                        'note' => null,
                        'point' => -4,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Below 30 runs per 100 balls',
                        'code' => 'lt 30',
                        'postfix' => null,
                        'note' => null,
                        'point' => -6,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Between 100 and 120 runs per 100 balls',
                        'code' => 'bt 100-120',
                        'postfix' => null,
                        'note' => null,
                        'point' => 2,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Between 120.01 and 140 runs per 100 balls',
                        'code' => 'bt 120.01-140',
                        'postfix' => null,
                        'note' => null,
                        'point' => 4,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Above 140 runs per 100 balls',
                        'code' => 'bt 140',
                        'postfix' => null,
                        'note' => null,
                        'point' => 6,
                        'type' => $type,
                    ],
                ];

                $category->fantasy_points()->createMany($points);
            }
        }
    }

    private function insertT20Points($categories)
    {
        $type = 'T20';
        foreach ($categories as $category) {
            if ($category->id === 1) {
                $points = [
                    [
                        'name' => 'Every run scored',
                        'code' => 'run',
                        'postfix' => null,
                        'note' => null,
                        'point' => 1,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Every boundary hit',
                        'code' => 'four',
                        'postfix' => null,
                        'note' => null,
                        'point' => 1,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Every six-hit',
                        'code' => 'six',
                        'postfix' => null,
                        'note' => null,
                        'point' => 2,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Half Century (50 runs scored by a batsman in a single innings)',
                        'code' => 'bt 50-99',
                        'postfix' => null,
                        'note' => null,
                        'point' => 8,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Century (100 runs scored by a batsman in a single innings)',
                        'code' => 'gt 99',
                        'postfix' => null,
                        'note' => null,
                        'point' => 16,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Dismissal for a Duck (only for batsmen, wicket-keepers and all-rounders)',
                        'code' => 'duck',
                        'postfix' => null,
                        'note' => null,
                        'point' => -2,
                        'type' => $type,
                    ],
                    [
                        'name' => '30 run Bonus',
                        'code' => 'gt 30',
                        'postfix' => null,
                        'note' => null,
                        'point' => 4,
                        'type' => $type,
                    ],
                ];

                $category->fantasy_points()->createMany($points);
            } else if ($category->id === 2) {
                $points = [
                    [
                        'name' => 'Every wicket taken (excluding run out)',
                        'code' => 'wicket',
                        'postfix' => null,
                        'note' => null,
                        'point' => 25,
                        'type' => $type,
                    ],
                    [
                        'name' => '3 wickets',
                        'code' => 'third_wicket',
                        'postfix' => null,
                        'note' => null,
                        'point' => 4,
                        'type' => $type,
                    ],
                    [
                        'name' => '4 wickets',
                        'code' => 'four_wicket',
                        'postfix' => null,
                        'note' => null,
                        'point' => 8,
                        'type' => $type,
                    ],
                    [
                        'name' => '5 wickets',
                        'code' => 'five_wicket',
                        'postfix' => null,
                        'note' => null,
                        'point' => 16,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Maiden over',
                        'code' => 'maiden_over',
                        'postfix' => null,
                        'note' => null,
                        'point' => 12,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Bonus (LBW / Bowled)',
                        'code' => 'bowled',
                        'postfix' => null,
                        'note' => null,
                        'point' => 8,
                        'type' => $type,
                    ],
                ];

                $category->fantasy_points()->createMany($points);
            } else if ($category->id === 3) {
                $points = [
                    [
                        'name' => 'Catch taken',
                        'code' => 'catch',
                        'postfix' => null,
                        'note' => null,
                        'point' => 8,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Caught & Bowled',
                        'code' => 'bowled',
                        'postfix' => null,
                        'note' => null,
                        'point' => 33,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Stumping/ Run Out (direct)',
                        'code' => 'stumped',
                        'postfix' => null,
                        'note' => null,
                        'point' => 12,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Run Out (Thrower)',
                        'code' => 'stumped',
                        'postfix' => null,
                        'note' => null,
                        'point' => 6,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Run Out (Catcher)',
                        'code' => 'stumped',
                        'postfix' => null,
                        'note' => null,
                        'point' => 6,
                        'type' => $type,
                    ],
                    [
                        'name' => '3 Catch Bonus',
                        'code' => 'third_catch',
                        'postfix' => null,
                        'note' => null,
                        'point' => 4,
                        'type' => $type,
                    ]
                ];

                $category->fantasy_points()->createMany($points);
            } else if ($category->id === 4) {
                $points = [
                    [
                        'name' => 'Captain',
                        'code' => 'captain',
                        'postfix' => 'x',
                        'note' => null,
                        'point' => 2,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Vice-Captain',
                        'code' => 'vice_captain',
                        'postfix' => 'x',
                        'note' => null,
                        'point' => 1.5,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Being a part of the starting XI',
                        'code' => 'p11',
                        'postfix' => null,
                        'note' => null,
                        'point' => 4,
                        'type' => $type,
                    ],
                ];

                $category->fantasy_points()->createMany($points);
            } else if ($category->id === 5) {
                $points = [
                    [
                        'name' => 'Minimum overs bowled by player to be applicable',
                        'code' => 'lt 4',
                        'postfix' => null,
                        'note' => null,
                        'point' => '2 overs',
                        'type' => $type,
                    ],
                    [
                        'name' => 'Below 5 runs per over',
                        'code' => 'lt 5',
                        'postfix' => null,
                        'note' => null,
                        'point' => 6,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Between 5 and 5.99 runs per over',
                        'code' => 'bt 5-5.99',
                        'postfix' => null,
                        'note' => null,
                        'point' => 4,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Between 6 and 7 runs per over',
                        'code' => 'bt 6-7',
                        'postfix' => null,
                        'note' => null,
                        'point' => 2,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Between 10 and 11 runs per over',
                        'code' => 'bt 10-11',
                        'postfix' => null,
                        'note' => null,
                        'point' => -2,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Between 11.01 and 12 runs per over',
                        'code' => 'bt 11.01-12',
                        'postfix' => null,
                        'note' => null,
                        'point' => -4,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Above 12 runs per over',
                        'code' => 'gt 12',
                        'postfix' => null,
                        'note' => null,
                        'point' => -6,
                        'type' => $type,
                    ],
                ];

                $category->fantasy_points()->createMany($points);
            } else if ($category->id === 6) {
                $points = [
                    [
                        'name' => 'Minimum balls faced by a player to be applicable',
                        'code' => 'bt 60-70',
                        'postfix' => null,
                        'note' => null,
                        'point' => '10 balls',
                        'type' => $type,
                    ],
                    [
                        'name' => 'Between 60 and 70 runs per 100 balls',
                        'code' => 'bt 60-70',
                        'postfix' => null,
                        'note' => null,
                        'point' => -2,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Between 50 and 59.99 runs per 100 balls',
                        'code' => 'bt 50-59.99',
                        'postfix' => null,
                        'note' => null,
                        'point' => -4,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Below 50 runs per 100 balls',
                        'code' => 'lt 50',
                        'postfix' => null,
                        'note' => null,
                        'point' => -6,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Between 130 and 150 runs per 100 balls',
                        'code' => 'bt 130-150',
                        'postfix' => null,
                        'note' => null,
                        'point' => 2,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Between 150.01 and 170 runs per 100 balls',
                        'code' => 'bt 150.01-170',
                        'postfix' => null,
                        'note' => null,
                        'point' => 4,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Above 170 runs per 100 balls',
                        'code' => 'lt 170',
                        'postfix' => null,
                        'note' => null,
                        'point' => 6,
                        'type' => $type,
                    ],
                ];

                $category->fantasy_points()->createMany($points);
            }
        }
    }

    private function insertT10Points($categories)
    {
        $type = 'T10';
        foreach ($categories as $category) {
            if ($category->id === 1) {
                $points = [
                    [
                        'name' => 'Every run scored',
                        'code' => 'run',
                        'postfix' => null,
                        'note' => null,
                        'point' => 1,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Every boundary hit',
                        'code' => 'four',
                        'postfix' => null,
                        'note' => null,
                        'point' => 1,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Every six-hit',
                        'code' => 'six',
                        'postfix' => null,
                        'note' => null,
                        'point' => 2,
                        'type' => $type,
                    ],
                    [
                        'name' => '30 runs scored by a batsman in a single innings',
                        'code' => 'bt 30-49',
                        'postfix' => null,
                        'note' => null,
                        'point' => 8,
                        'type' => $type,
                    ],
                    [
                        'name' => '50 runs scored by a batsman in a single innings',
                        'code' => 'gt 49',
                        'postfix' => null,
                        'note' => null,
                        'point' => 16,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Dismissal for a Duck (only for batsmen, wicket-keepers and all-rounders)',
                        'code' => 'duck',
                        'postfix' => null,
                        'note' => null,
                        'point' => -2,
                        'type' => $type,
                    ],
                ];

                $category->fantasy_points()->createMany($points);
            } else if ($category->id === 2) {
                $points = [
                    [
                        'name' => 'Every wicket taken (excluding run out)',
                        'code' => 'wicket',
                        'postfix' => null,
                        'note' => null,
                        'point' => 25,
                        'type' => $type,
                    ],
                    [
                        'name' => '2 wickets',
                        'code' => 'two_wicket',
                        'postfix' => null,
                        'note' => null,
                        'point' => 8,
                        'type' => $type,
                    ],
                    [
                        'name' => '3 wickets',
                        'code' => 'three_wicket',
                        'postfix' => null,
                        'note' => null,
                        'point' => 16,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Maiden over',
                        'code' => 'maiden_over',
                        'postfix' => null,
                        'note' => null,
                        'point' => 16,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Bonus (LBW / Bowled)',
                        'code' => 'bowled',
                        'postfix' => null,
                        'note' => null,
                        'point' => 8,
                        'type' => $type,
                    ],
                ];

                $category->fantasy_points()->createMany($points);
            } else if ($category->id === 3) {
                $points = [
                    [
                        'name' => 'Catch taken',
                        'code' => 'catch',
                        'postfix' => null,
                        'note' => null,
                        'point' => 8,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Caught & Bowled',
                        'code' => 'bowled',
                        'postfix' => null,
                        'note' => null,
                        'point' => 33,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Stumping/ Run Out (direct)',
                        'code' => 'stumped',
                        'postfix' => null,
                        'note' => null,
                        'point' => 12,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Run Out (Thrower)',
                        'code' => 'stumped',
                        'postfix' => null,
                        'note' => null,
                        'point' => 6,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Run Out (Catcher)',
                        'code' => 'stumped',
                        'postfix' => null,
                        'note' => null,
                        'point' => 6,
                        'type' => $type,
                    ],
                    [
                        'name' => '3 Catch Bonus',
                        'code' => 'third_catch',
                        'postfix' => null,
                        'note' => null,
                        'point' => 4,
                        'type' => $type,
                    ]
                ];

                $category->fantasy_points()->createMany($points);
            } else if ($category->id === 4) {
                $points = [
                    [
                        'name' => 'Captain',
                        'code' => 'captain',
                        'postfix' => 'x',
                        'note' => null,
                        'point' => 2,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Vice-Captain',
                        'code' => 'vice_captain',
                        'postfix' => 'x',
                        'note' => null,
                        'point' => 1.5,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Being a part of the starting XI',
                        'code' => 'p11',
                        'postfix' => null,
                        'note' => null,
                        'point' => 4,
                        'type' => $type,
                    ],
                ];

                $category->fantasy_points()->createMany($points);
            } else if ($category->id === 5) {
                $points = [
                    [
                        'name' => 'Minimum overs bowled by player to be applicable',
                        'code' => 'lt 6',
                        'postfix' => null,
                        'note' => null,
                        'point' => '1 over',
                        'type' => $type,
                    ],
                    [
                        'name' => 'Below 7 runs per over',
                        'code' => 'lt 7',
                        'postfix' => null,
                        'note' => null,
                        'point' => 6,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Between 7 and 7.99 runs per over',
                        'code' => 'bt 7-7.99',
                        'postfix' => null,
                        'note' => null,
                        'point' => 4,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Between 8 and 9 runs per over',
                        'code' => 'bt 8-9',
                        'postfix' => null,
                        'note' => null,
                        'point' => 2,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Between 14 and 15 runs per over',
                        'code' => 'bt 14-15',
                        'postfix' => null,
                        'note' => null,
                        'point' => -2,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Between 15.01 and 16 runs per over',
                        'code' => 'bt 15.01-16',
                        'postfix' => null,
                        'note' => null,
                        'point' => -4,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Above 16 runs per over',
                        'code' => 'gt 16',
                        'postfix' => null,
                        'note' => null,
                        'point' => -6,
                        'type' => $type,
                    ],
                ];

                $category->fantasy_points()->createMany($points);
            } else if ($category->id === 6) {
                $points = [
                    [
                        'name' => 'Minimum balls faced by a player to be applicable',
                        'code' => 'bt 90-99.99',
                        'postfix' => null,
                        'note' => null,
                        'point' => '5 balls',
                        'type' => $type,
                    ],
                    [
                        'name' => 'Between 70 and 80 runs per 100 balls',
                        'code' => 'bt 70-80',
                        'postfix' => null,
                        'note' => null,
                        'point' => -2,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Between 60 and 69.99 runs per 100 balls',
                        'code' => 'bt 60-69.99',
                        'postfix' => null,
                        'note' => null,
                        'point' => -4,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Below 60 runs per 100 balls',
                        'code' => 'lt 60',
                        'postfix' => null,
                        'note' => null,
                        'point' => -6,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Between 150 and 170 runs per 100 balls',
                        'code' => 'bt 150-170',
                        'postfix' => null,
                        'note' => null,
                        'point' => 2,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Between 170.01 and 190 runs per 100 balls',
                        'code' => 'bt 170.01-190',
                        'postfix' => null,
                        'note' => null,
                        'point' => 4,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Over 190 runs per 100 balls',
                        'code' => 'bt 190',
                        'postfix' => null,
                        'note' => null,
                        'point' => 6,
                        'type' => $type,
                    ],
                ];

                $category->fantasy_points()->createMany($points);
            }
        }
    }

    private function insertTestPoints($categories)
    {
        $type = 'TEST';
        foreach ($categories as $category) {
            if ($category->id === 1) {
                $points = [
                    [
                        'name' => 'Every run scored',
                        'code' => 'run',
                        'postfix' => null,
                        'note' => null,
                        'point' => 1,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Every boundary hit',
                        'code' => 'four',
                        'postfix' => null,
                        'note' => null,
                        'point' => 1,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Every six-hit',
                        'code' => 'six',
                        'postfix' => null,
                        'note' => null,
                        'point' => 2,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Half Century (50 runs scored by a batsman in a single innings)',
                        'code' => 'bt 50-99',
                        'postfix' => null,
                        'note' => null,
                        'point' => 4,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Century (100 runs scored by a batsman in a single innings)',
                        'code' => 'gt 99',
                        'postfix' => null,
                        'note' => null,
                        'point' => 8,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Dismissal for a Duck (only for batsmen, wicket-keepers and all-rounders)',
                        'code' => 'duck',
                        'postfix' => null,
                        'note' => null,
                        'point' => -4,
                        'type' => $type,
                    ],
                ];

                $category->fantasy_points()->createMany($points);
            } else if ($category->id === 2) {
                $points = [
                    [
                        'name' => 'Every wicket taken (excluding run out)',
                        'code' => 'wicket',
                        'postfix' => null,
                        'note' => null,
                        'point' => 16,
                        'type' => $type,
                    ],
                    [
                        'name' => '4 wickets',
                        'code' => 'four_wicket',
                        'postfix' => null,
                        'note' => null,
                        'point' => 4,
                        'type' => $type,
                    ],
                    [
                        'name' => '5 wickets',
                        'code' => 'five_wicket',
                        'postfix' => null,
                        'note' => null,
                        'point' => 8,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Bonus (LBW / Bowled)',
                        'code' => 'bowled',
                        'postfix' => null,
                        'note' => null,
                        'point' => 8,
                        'type' => $type,
                    ],
                ];

                $category->fantasy_points()->createMany($points);
            } else if ($category->id === 3) {
                $points = [
                    [
                        'name' => 'Catch taken',
                        'code' => 'catch',
                        'postfix' => null,
                        'note' => null,
                        'point' => 8,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Caught & Bowled',
                        'code' => 'bowled',
                        'postfix' => null,
                        'note' => null,
                        'point' => 24,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Stumping/ Run Out (direct)',
                        'code' => 'stumped',
                        'postfix' => null,
                        'note' => null,
                        'point' => 12,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Run Out (Thrower)',
                        'code' => 'stumped',
                        'postfix' => null,
                        'note' => null,
                        'point' => 6,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Run Out (Catcher)',
                        'code' => 'stumped',
                        'postfix' => null,
                        'note' => null,
                        'point' => 6,
                        'type' => $type,
                    ],
                ];

                $category->fantasy_points()->createMany($points);
            } else if ($category->id === 4) {
                $points = [
                    [
                        'name' => 'Captain',
                        'code' => 'captain',
                        'postfix' => 'x',
                        'note' => null,
                        'point' => 2,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Vice-Captain',
                        'code' => 'vice_captain',
                        'postfix' => 'x',
                        'note' => null,
                        'point' => 1.5,
                        'type' => $type,
                    ],
                    [
                        'name' => 'Being a part of the starting XI',
                        'code' => 'p11',
                        'postfix' => null,
                        'note' => null,
                        'point' => 4,
                        'type' => $type,
                    ],
                ];

                $category->fantasy_points()->createMany($points);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fantasy_points');
    }
}
