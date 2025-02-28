<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIngresoDeMercanciaTable extends Migration
{
    public function up()
    {
        Schema::create('ingreso_de_mercancia', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('producto_id');
            $table->date('fecha');
            $table->integer('cantidad_de_ingreso');
            $table->timestamps();

            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ingreso_de_mercancia');
    }
}
