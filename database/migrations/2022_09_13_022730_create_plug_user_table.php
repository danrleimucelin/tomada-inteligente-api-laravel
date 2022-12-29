<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plug_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("plug_id");
            $table->unsignedBigInteger("user_id");
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('plug_id')->references('id')->on('plugs');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('plug_user');
    }
};
