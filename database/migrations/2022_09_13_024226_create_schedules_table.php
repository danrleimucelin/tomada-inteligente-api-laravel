<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("plug_user_id");
            $table->unsignedInteger("time")->comment("Tempo, em segundos, que ficará ligado");
            $table->boolean("emit_sound")->default(false);
            $table->dateTime("start_date")->nullable(true);
            $table->dateTime("end_date")->nullable(true);
            $table->integer("voltage")->default(100)->comment("Tensão que deve ser utilizada. 0% a 100%");
            $table->float("consumption")->nullable(true)->comment("Consumo de energia");
            $table->boolean("started")->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('plug_user_id')->references('id')->on('plug_user');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('schedules');
    }
};
