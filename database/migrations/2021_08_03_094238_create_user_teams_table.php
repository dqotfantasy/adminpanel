<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserTeamsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_teams', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('fixture_id')->constrained();
            $table->foreignUuid('user_id')->constrained();
            $table->string('name');
            $table->json('players');
            $table->foreignId('captain_id');
            $table->foreignId('vice_captain_id');
            $table->decimal('total_points')->default(0);
            $table->timestamps();
            $table->unique(['fixture_id', 'user_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_teams');
    }
}
