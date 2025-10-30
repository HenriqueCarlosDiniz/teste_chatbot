<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat de Teste Local</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">
    <div class="container mx-auto max-w-2xl mt-10 p-4">
        <div class="bg-white rounded-lg shadow-lg">
            <!-- Cabeçalho -->
            <div class="bg-blue-600 text-white p-4 rounded-t-lg flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold">Chatbot Local</h1>
                    <p class="text-sm">Ambiente de Desenvolvimento</p>
                </div>
                <form action="{{ route('chat.clear') }}" method="POST">
                    @csrf
                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-lg text-sm transition duration-300">
                        Limpar Conversa
                    </button>
                </form>
            </div>

            <!-- Área de Mensagens -->
            <div id="chat-box" class="p-6 h-96 overflow-y-auto">
                @forelse ($history as $chat)
                    @if ($chat['sender'] === 'user')
                        <div class="flex justify-end mb-4">
                            <div class="bg-blue-500 text-white rounded-lg py-2 px-4 max-w-xs break-words">
                                {{ $chat['message'] }}
                            </div>
                        </div>
                    @else
                        <div class="flex justify-start mb-4">
                            <div class="bg-gray-200 text-gray-800 rounded-lg py-2 px-4 max-w-xs break-words">
                                {!! \Illuminate\Support\Str::markdown($chat['message']) !!}
                            </div>
                        </div>
                    @endif
                @empty
                    <div class="text-center text-gray-500">
                        Nenhuma mensagem ainda. Envie uma para começar!
                    </div>
                @endforelse
            </div>

            <!-- Formulário de Envio -->
            <div class="p-4 border-t border-gray-200">
                <form action="{{ route('chat.store') }}" method="POST">
                    @csrf
                    <div class="flex">
                        <input
                            type="text"
                            name="message"
                            class="flex-grow rounded-l-lg p-3 border-t mr-0 border-b border-l text-gray-800 border-gray-200 bg-white focus:outline-none"
                            placeholder="Digite sua mensagem..."
                            autofocus
                        />
                        <button type="submit" class="px-6 rounded-r-lg bg-blue-600 text-white font-bold p-3 uppercase border-blue-600 border-t border-b border-r hover:bg-blue-700 transition duration-300">
                            Enviar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        // Rola a caixa de chat para a última mensagem
        const chatBox = document.getElementById('chat-box');
        chatBox.scrollTop = chatBox.scrollHeight;
    </script>
</body>
</html>
