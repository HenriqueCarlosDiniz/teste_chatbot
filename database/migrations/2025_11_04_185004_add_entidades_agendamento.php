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
        // Só executa se a tabela 'chat_sessions' existir
        if (Schema::hasTable('chat_sessions')) {
            Schema::table('chat_sessions', function (Blueprint $table) {
                $table->string('customer_name')->nullable()->after('current_application');
                $table->string('customer_phone')->nullable()->after('customer_name');
                $table->string('selected_unit_id')->nullable()->after('customer_phone');
                $table->timestamp('selected_datetime')->nullable()->after('selected_unit_id');
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
        if (Schema::hasTable('chat_sessions')) {
            Schema::table('chat_sessions', function (Blueprint $table) {
                $columns = [
                    'customer_name',
                    'customer_phone',
                    'selected_unit_id',
                    'selected_datetime'
                ];

                foreach ($columns as $column) {
                    if (Schema::hasColumn('chat_sessions', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
