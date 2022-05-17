<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrivateContestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('private_contests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained();
            $table->foreignId('fixture_id')->constrained();
            $table->string('invite_code');
            $table->string('contest_name');
            $table->decimal('commission')->default(0);
            $table->bigInteger('total_teams');
            $table->integer('entry_fee');
            $table->integer('max_team');
            $table->bigInteger('prize');
            $table->bigInteger('winner_percentage');
            $table->boolean('is_confirmed');
            $table->json('prize_breakup');
            $table->json('new_prize_breakup')->nullable();
            $table->enum('status', CONTEST_STATUS)->default(CONTEST_STATUS[0]);
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
        Schema::dropIfExists('private_contests');
    }
}
