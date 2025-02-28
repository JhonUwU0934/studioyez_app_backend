<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMontosTable extends Migration
{
    public function up()
    {
        Schema::create('montos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('creador_id');
            $table->decimal('monto', 10, 2);
            $table->timestamps();

            $table->foreign('creador_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('montos');
    }
}
