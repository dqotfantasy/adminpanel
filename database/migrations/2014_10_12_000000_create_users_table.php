<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->string('username')->unique()->nullable();
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('photo')->nullable();
            $table->string('gender', 1)->nullable();
            $table->string('phone', 10)->unique()->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->foreignId('state_id')->nullable()->constrained();
            $table->decimal('balance')->default(0);
            $table->decimal('winning_amount')->default(0);
            $table->decimal('deposited_balance')->default(0);
            $table->decimal('cash_bonus')->default(0);
            $table->boolean('phone_verified')->default(0);
            $table->boolean('document_verified')->default(0);
            $table->boolean('email_verified')->default(0);
            $table->boolean('is_locked')->default(0);
            $table->boolean('is_username_update')->default(0);
            $table->boolean('can_played')->default(0);
            $table->string('referral_code');
            $table->decimal('referral_amount')->default(0);
            $table->foreignUuid('referral_id')->nullable();
            $table->boolean('is_deposit')->default(0);
            $table->decimal('referral_pending_amount')->default(0);
            $table->enum('role', ROLES)->default('user');
            $table->string('remember_token')->nullable();
            $table->string('verification_code')->nullable();
            $table->integer('bank_update_count')->default(0);
            $table->integer('level')->default(0);
            $table->string('fcm_token')->nullable();
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
        Schema::dropIfExists('users');
    }
}
