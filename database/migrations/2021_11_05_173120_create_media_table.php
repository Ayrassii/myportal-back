<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMediaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('media', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('createdby_id');
            $table->foreign('createdby_id')->on('users')->references('id')->onDelete('cascade');
            $table->unsignedBigInteger('entry_id');
            $table->foreign('entry_id')->on('entries')->references('id')->onDelete('cascade');
            $table->boolean('is_deleted')->default(false);
            $table->boolean('is_main')->default(false);
            $table->string('type');
            $table->string('path');
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
        Schema::dropIfExists('media');
    }
}
