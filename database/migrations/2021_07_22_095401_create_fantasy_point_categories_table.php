<?php

use App\Models\FantasyPointCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFantasyPointCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fantasy_point_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('note')->nullable();
            $table->longText('description')->nullable();
            $table->string('image');
            $table->timestamps();
        });

        $data = [
            [
                'name' => 'Batting Points',
                'image' => 'positions/batting.png'
            ],
            [
                'name' => 'Bowling Points',
                'image' => 'positions/bowling.png'
            ],
            [
                'name' => 'Fielding Points',
                'image' => 'positions/fielding.png'
            ],
            [
                'name' => 'Other Points',
                'image' => 'positions/other.png'
            ],
            [
                'name' => 'Economy Rate Points',
                'image' => 'positions/other.png'
            ],
            [
                'name' => 'Strike Rate (Except Bowler) Points',
                'image' => 'positions/other.png'
            ]
        ];

        FantasyPointCategory::insert($data);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fantasy_point_categories');
    }
}
