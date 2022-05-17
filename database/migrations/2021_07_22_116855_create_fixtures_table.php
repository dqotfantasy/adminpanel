<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFixturesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fixtures', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            $table->foreignId('competition_id')->constrained();
            $table->string('competition_name');
            $table->string('season',);

            $table->string('teama');
            $table->string('teama_id');
            $table->string('teama_image')->nullable();
            $table->string('teama_score')->nullable();
            $table->string('teama_short_name')->nullable();

            $table->string('teamb');
            $table->string('teamb_id');
            $table->string('teamb_image')->nullable();
            $table->string('teamb_score')->nullable();
            $table->string('teamb_short_name')->nullable();

            $table->string('format');
            $table->string('format_str');
            $table->dateTime('starting_at');

            $table->boolean('verified');
            $table->boolean('pre_squad');

            $table->boolean('is_active')->default(1);
            $table->boolean('lineup_announced')->default(0);
            $table->enum('status', FIXTURE_STATUS)->default(FIXTURE_STATUS[0]);
            $table->string('status_note')->nullable();
            $table->dateTime('last_squad_update')->nullable();
            $table->bigInteger('mega_value')->default(0);
            $table->timestamps();
            $table->index(['teama', 'teamb', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fixtures');
    }
}
