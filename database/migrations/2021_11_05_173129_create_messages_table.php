<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('createdby_id');
            $table->foreign('createdby_id')->on('users')->references('id')->onDelete('cascade');
            $table->unsignedBigInteger('destination_id');
            $table->foreign('destination_id')->on('users')->references('id')->onDelete('cascade');
            $table->unsignedBigInteger('discussion_id');
            $table->foreign('discussion_id')->on('discussions')->references('id')->onDelete('cascade');
            $table->boolean('is_deleted')->default(false);
            $table->longText('body');
            $table->dateTime('read_at')->nullable();
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
        Schema::dropIfExists('messages');
    }
}
