<?php

namespace App\Providers;

use App\Adapters\Ai\AiAdapterInterface;
use App\Adapters\Ai\ChatGPTAdapter;
use App\Adapters\Ai\PrismAdapter;
use App\Adapters\Messaging\MessagingAdapterInterface;
use App\Adapters\Messaging\WebAdapter;
use App\Adapters\Messaging\WhatsAppAdapter;
use App\Chat\Applications\BookingApplication;
use App\Chat\Applications\ExistingAppointmentApplication;
use App\Chat\Applications\GreetingApplication;
use App\Chat\SentimentAnalyzerService;
use App\Chat\ChatApplicationManager;
use App\Chat\Flows\BookingFlow\StateHandlerFactory;
use App\Chat\PromptManager;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class ChatbotServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // --- REGISTO DOS ADAPTADORES ---
        $this->app->singleton(AiAdapterInterface::class, function ($app) {
            return new PrismAdapter();
        });

        $this->app->singleton(MessagingAdapterInterface::class, function ($app) {
            $adapter = config('services.messaging.adapter');
            switch ($adapter) {
                case 'whatsapp':
                    return new WhatsAppAdapter();
                case 'web':
                    return new WebAdapter();
                default:
                    throw new InvalidArgumentException("Adaptador de Mensagens inválido: {$adapter}");
            }
        });

        // --- REGISTO DOS MANAGERS E FACTORIES ---
        $this->app->singleton(ChatApplicationManager::class);
        $this->app->singleton(StateHandlerFactory::class);
        $this->app->singleton(PromptManager::class);
        $this->app->singleton(SentimentAnalyzerService::class);

        // --- REGISTO E ETIQUETAGEM DAS APLICAÇÕES DE CHAT ---
        $this->app->singleton(GreetingApplication::class);
        $this->app->tag(GreetingApplication::class, 'chatbot.application');

        $this->app->singleton(BookingApplication::class);
        $this->app->tag(BookingApplication::class, 'chatbot.application');

        $this->app->singleton(ExistingAppointmentApplication::class);
        $this->app->tag(ExistingAppointmentApplication::class, 'chatbot.application');
    }

    public function boot(): void
    {
        //
    }
}
