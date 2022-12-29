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
        Schema::create('plugs', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number', 80)->unique()->comment("Número de serie único para cada tomada.");
            $table->string('name', 80)->nullable(true);
            $table->integer('power')->default(100)->comment("Potência que deve ser liberada. 0% a 100%");
            $table->float('consumption')->nullable(true)->comment("Consumo de energia.");
            $table->string('token', 100);
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
        Schema::dropIfExists('plugs');
    }
};
