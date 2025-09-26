<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('user_analytics', function (Blueprint $table) {
            $table->string('ref')->nullable();
            $table->string('country')->nullable();
        });
    }

    public function down()
    {
        Schema::table('user_analytics', function (Blueprint $table) {
            $table->dropColumn(['ref', 'country']);
        });
    }
};
