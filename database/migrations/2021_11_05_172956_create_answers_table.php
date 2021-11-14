<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAnswersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('answers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('createdby_id');
            $table->foreign('createdby_id')->on('users')->references('id')->onDelete('cascade');
            $table->unsignedBigInteger('question_id');
            $table->foreign('question_id')->on('questions')->references('id')->onDelete('cascade');
            $table->boolean('is_deleted')->default(false);
            $table->text('text');
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
        Schema::dropIfExists('answers');
    }
}
