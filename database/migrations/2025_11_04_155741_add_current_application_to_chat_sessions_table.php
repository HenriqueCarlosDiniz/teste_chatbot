<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Executa as migrações.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('chat_sessions')) {
            Schema::table('chat_sessions', function (Blueprint $table) {
                $table->string('current_application')->nullable()->after('state');
            });
        }
    }

    /**
     * Reverte as migrações.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('chat_sessions', 'current_application')) {
            Schema::table('chat_sessions', function (Blueprint $table) {
                $table->dropColumn('current_application');
            });
        }
    }
};
