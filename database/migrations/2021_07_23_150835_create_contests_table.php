<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('fixture_id')->constrained();
            $table->foreignUuid('contest_category_id')->constrained();
            $table->string('invite_code');
            $table->decimal('commission')->default(0);
            $table->bigInteger('total_teams');
            $table->integer('entry_fee');
            $table->integer('max_team');
            $table->bigInteger('prize');
            $table->bigInteger('winner_percentage');
            $table->boolean('is_confirmed');
            $table->json('prize_breakup');
            $table->json('new_prize_breakup')->nullable();
            $table->boolean('auto_create_on_full');
            $table->boolean('is_mega_contest')->default(0);
            $table->enum('status', CONTEST_STATUS)->default(CONTEST_STATUS[0]);
            $table->enum('type', CONTEST_TYPE)->default(CONTEST_TYPE[1]);
            $table->integer('discount')->default(0);
            $table->decimal('bonus')->default(0);
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
        Schema::dropIfExists('contests');
    }
}
