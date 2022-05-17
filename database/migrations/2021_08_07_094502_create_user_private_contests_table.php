<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserPrivateContestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_private_contests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained();
            $table->foreignUuid('private_contest_id')->constrained();
            $table->foreignUuid('user_team_id')->constrained();
            $table->bigInteger('rank')->nullable();
            $table->decimal('prize')->nullable();
            $table->json('payment_data')->nullable();
            $table->timestamps();
            $table->unique(['private_contest_id', 'user_team_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_private_contests');
    }
}
