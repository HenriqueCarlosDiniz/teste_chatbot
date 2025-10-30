<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessChatMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApiChatController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        // 1. Valida os dados da requisição, incluindo o novo campo de telefone.
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:4000',
            'user_id' => 'required|string|max:255',
            'phone_number' => 'nullable|string|max:20', // NOVO: Validação para o telefone
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();

        // 2. Despacha o job para a fila, passando o telefone como um novo parâmetro.
        ProcessChatMessage::dispatch(
            $validatedData['message'],
            $validatedData['user_id'],
            'web', // O canal é 'web' para este controller.
            $validatedData['phone_number'] ?? null
        );

        // 3. Retorna uma resposta imediata de sucesso.
        return response()->json([
            'status' => 'success',
            'message' => 'Message received and queued for processing.'
        ], 202);
    }
}
