<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBankAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained();
            $table->string('name');
            $table->string('account_number');
            $table->string('branch');
            $table->string('ifsc_code');
            $table->string('photo');
            $table->foreignId('state_id')->nullable()->constrained();
            $table->enum('status', BANK_DETAIL_STATUS)->default(BANK_DETAIL_STATUS[0]);
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
        Schema::dropIfExists('bank_accounts');
    }
}
