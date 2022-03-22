<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @return void
     */
    public function up()
    {
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->integer('creator_id');
            $table->float('latitude')->nullable();
            $table->float('longitude')->nullable();
            $table->string('photo');
        });
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('requests');
    }
};
