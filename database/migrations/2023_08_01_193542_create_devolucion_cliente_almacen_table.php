<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDevolucionClienteAlmacenTable extends Migration
{
    public function up()
    {
        Schema::create('devolucion_cliente_almacen', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('producto_id');
            $table->integer('cantidad');
            $table->string('precio_venta');
            $table->string('cliente');
            $table->date('fecha');
            $table->string('quien_recibe');
            $table->timestamps();

            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('devolucion_cliente_almacen');
    }
}
