<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Adapters\Ai\AiAdapterInterface;

class WebChatController extends Controller
{
    /**
     * Exibe a interface do chat.
     */
    public function show()
    {
        $history = session('chat_history', []);
        return view('webchat', ['history' => $history]);
    }

    /**
     * Processa a mensagem enviada pelo usuário.
     */
    public function store(Request $request, AiAdapterInterface $aiAdapter)
    {
        $request->validate(['message' => 'required|string|max:2000']);

        $user_message = $request->input('message');
        $sessionId = session()->getId();

        // Adiciona a mensagem do usuário à sessão
        app('App\Adapters\Messaging\WebAdapter')->processIncoming($request);

        // Envia para a IA utilizando o método getChat
        $aiResponse = $aiAdapter->getChat($user_message, $sessionId);

        // Salva a resposta da IA na sessão
        app('App\Adapters\Messaging\WebAdapter')->sendResponse($sessionId, $aiResponse);

        return redirect()->route('chat.show');
    }

    /**
     * Limpa o histórico do chat da sessão.
     */
    public function clear()
    {
        session()->forget('chat_history');
        return redirect()->route('chat.show');
    }
}
