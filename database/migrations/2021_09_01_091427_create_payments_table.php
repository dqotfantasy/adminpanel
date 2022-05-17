<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained();
            $table->decimal('amount');
            $table->string('status');
            $table->string('transaction_id');
            $table->string('description');
            $table->enum('type', PAYMENT_TYPES);
            $table->foreignUuid('contest_id')->nullable()->constrained();
            $table->foreignUuid('private_contest_id')->nullable()->constrained();
            $table->string('reference_id')->unique()->nullable();
            $table->foreignId('coupon_id')->nullable();
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
        Schema::dropIfExists('payments');
    }
}
