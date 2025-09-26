<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('doorman_user_analytics', function (Blueprint $table) {
            $table->dropUnique(['ip_hash', 'date']);
            $table->dropColumn('ip_hash');
            $table->string('identifier');
            $table->string('identifier_type');

            $table->unique(['identifier', 'identifier_type', 'date']);
        });
    }

    public function down()
    {
        Schema::table('doorman_user_analytics', function (Blueprint $table) {
            $table->string('ip_hash');
            $table->dropUnique(['identifier', 'identifier_type', 'date']);
            $table->dropColumn('identifier');
            $table->dropColumn('identifier_type');

            $table->unique(['ip_hash', 'date']);
        });
    }
};
