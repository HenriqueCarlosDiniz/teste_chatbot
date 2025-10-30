<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Cria a tabela para gerir as sessões de chat.
        Schema::create('chat_sessions', function (Blueprint $table) {
            // Usamos um UUID como chave primária, ideal para IDs de sessão.
            $table->uuid('id')->primary();

            // Telefone do participante, pode ser nulo e é indexado para buscas rápidas.
            $table->string('phone_number')->nullable()->index();

            // Coluna JSON para guardar o estado da conversa (ex: app ativa, dados do fluxo).
            $table->json('state')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_sessions');
    }
};
