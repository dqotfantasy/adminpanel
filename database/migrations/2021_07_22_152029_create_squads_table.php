<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSquadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('squads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained();
            $table->foreignId('fixture_id')->constrained();
            $table->string('team')->nullable();
            $table->integer('team_id');
            $table->boolean('substitute')->default(0);
            $table->string('role')->nullable();
            $table->boolean('playing11')->default(0);
            $table->decimal('playing11_point')->default(0);
            $table->decimal('fantasy_player_rating')->default(0);
            $table->boolean('last_played')->default(0);
            $table->boolean('is_active')->default(1);

            $table->integer('runs')->default(0);
            $table->decimal('runs_point')->default(0);

            $table->integer('fours')->default(0);
            $table->decimal('fours_point')->default(0);

            $table->integer('sixes')->default(0);
            $table->decimal('sixes_point')->default(0);

            $table->integer('century_half_century')->default(0);
            $table->decimal('century_half_century_point')->default(0);

            $table->integer('strike_rate')->default(0);
            $table->decimal('strike_rate_point')->default(0);

            $table->boolean('duck')->default(0);
            $table->decimal('duck_point')->default(0);

            $table->integer('wicket')->default(0);
            $table->decimal('wicket_point')->default(0);

            $table->integer('maiden_over')->default(0);
            $table->decimal('maiden_over_point')->default(0);

            $table->integer('economy_rate')->default(0);
            $table->decimal('economy_rate_point')->default(0);

            $table->integer('catch')->default(0);
            $table->decimal('catch_point')->default(0);

            $table->integer('runoutstumping')->default(0);
            $table->decimal('runoutstumping_point')->default(0);

            $table->decimal('bonus_point')->default(0);
            $table->decimal('total_points')->default(0);

            $table->boolean('in_dream_team')->default(0);
            $table->integer('series_point')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('squads');
    }
}
