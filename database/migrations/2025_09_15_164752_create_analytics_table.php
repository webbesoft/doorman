<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('doorman_user_analytics', function (Blueprint $table) {
            $table->id();
            $table->string('ip_hash');
            $table->date('date');
            $table->timestamps();

            $table->unique(['ip_hash', 'date']);
            $table->index('date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('doorman_user_analytics');
    }
};
