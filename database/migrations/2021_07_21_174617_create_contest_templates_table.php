<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContestTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contest_templates', function (Blueprint $table) {
            $table->id();
            //$table->integer('rank_category_id');
            $table->foreignUuid('contest_category_id');
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->bigInteger('total_teams');
            $table->integer('entry_fee');
            $table->integer('max_team');
            $table->bigInteger('prize');
            $table->bigInteger('winner_percentage');
            $table->boolean('is_confirmed');
            $table->json('prize_breakup');
            $table->boolean('auto_add');
            $table->boolean('auto_create_on_full');
            $table->integer('commission');
            $table->enum('type', CONTEST_TYPE)->default(CONTEST_TYPE[1]);
            $table->integer('discount')->default(0);
            $table->decimal('bonus')->default(0);
            $table->boolean('is_mega_contest')->default(0);
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
        Schema::dropIfExists('contest_templates');
    }
}
