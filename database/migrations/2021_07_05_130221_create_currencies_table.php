<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCurrenciesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->autoIncrement();
            $table->string("cbr_id", 10)->unique();
            $table->string("name");
            $table->string("eng_name");
            $table->unsignedInteger('nominal');
            $table->string("parent_code", 10);
            $table->unsignedSmallInteger('iso_num_code')->nullable();
            $table->string("iso_char_code", 10)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('currencies');
    }
}
