<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;

class SchedulingService
{
    protected string $url_base_api;
    protected string $token_api;
    const CHAVE_CACHE_UNIDADES = 'todas_unidades_ativas';

    public function __construct()
    {
        $this->url_base_api = config('services.scheduling_api.base_url');
        $this->token_api = config('services.scheduling_api.token');
    }

    /**
     * Obtém a lista de unidades do cache.
     * Este é um método de conveniência que chama obterTodasAsUnidadesAtivas.
     *
     * @return array A lista de unidades.
     */
    public function getUnidades(): array
    {
        return $this->obterTodasAsUnidadesAtivas();
    }

    /**
     * Busca todas as unidades ativas da API e as armazena em um cache global.
     * A consulta à API só ocorre se o cache não existir ou estiver expirado.
     *
     * @return array A lista de todas as unidades ativas.
     */
    public function obterTodasAsUnidadesAtivas(): array
    {
        // O cache agora dura 4 horas (240 minutos)
        $duracao_cache = now()->addMinutes(240);

        return Cache::remember(self::CHAVE_CACHE_UNIDADES, $duracao_cache, function () {
            $url = "{$this->url_base_api}/agenda-parceiro/unidades/token/{$this->token_api}";
            Log::info('--- CACHE GLOBAL EXPIRADO. BUSCANDO UNIDADES DA API. ---', ['url' => $url]);
            try {
                $resposta = Http::withoutVerifying()->timeout(60)->get($url);

                if ($resposta->successful()) {
                    $unidades = $resposta->json() ?? [];
                    Log::info('Unidades armazenadas no cache global com sucesso.', ['total_unidades' => count($unidades)]);
                    return $unidades;
                }

                Log::error("Falha ao buscar a lista de unidades da API para o cache global.", ['status' => $resposta->status()]);
                return [];
            } catch (\Exception $e) {
                Log::error("Exceção ao buscar a lista de unidades da API: " . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * NOVO: Busca a unidade mais próxima com base na latitude e longitude.
     *
     * @param float $latitude A latitude para a busca.
     * @param float $longitude A longitude para a busca.
     * @return array|null Os dados da unidade mais próxima ou nulo em caso de falha.
     */
    public function obterUnidadeMaisProxima(float $latitude, float $longitude): ?array
    {
        $url = "{$this->url_base_api}/agenda-parceiro/unidade-mais-proxima/latitude/{$latitude}/longitude/{$longitude}/token/{$this->token_api}";
        Log::info('Buscando unidade mais próxima na API.', ['url' => $url]);

        try {
            $resposta = Http::withoutVerifying()->timeout(30)->get($url);

            if ($resposta->successful() && !empty($resposta->json())) {
                $unidade = $resposta->json();
                Log::info('Unidade mais próxima encontrada.', ['unidade' => $unidade['nomeFranquia']]);
                return $unidade;
            }

            Log::warning('Nenhuma unidade próxima encontrada ou falha na API.', [
                'status' => $resposta->status(),
                'body' => $resposta->body()
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error("Exceção ao buscar unidade mais próxima: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Filtra uma lista de unidades com base nos critérios fornecidos.
     *
     * @param array $unidades_para_filtrar A lista de unidades a ser filtrada.
     * @param string|null $estado O estado para filtrar (ex: "SP").
     * @param string|null $cidade A cidade para filtrar.
     * @param string|null $bairro O bairro para filtrar.
     * @return array A lista de unidades filtrada.
     */
    public function filtrarUnidades(array $unidades_para_filtrar, ?string $estado = null, ?string $cidade = null, ?string $bairro = null): array
    {
        return array_filter($unidades_para_filtrar, function ($unidade) use ($estado, $cidade, $bairro) {
            $match = true;
            if ($estado && !Str::contains(Str::lower($unidade['estadoFranquia']), Str::lower($estado))) {
                $match = false;
            }
            if ($cidade && !Str::contains(Str::lower($unidade['cidadeFranquia']), Str::lower($cidade))) {
                $match = false;
            }
            if ($bairro && !Str::contains(Str::lower($unidade['bairroFranquia']), Str::lower($bairro))) {
                $match = false;
            }
            return $match;
        });
    }

    public function buscarAgendamentoPorTelefone(string $numero_telefone): ?array
    {
        $telefone_limpo = preg_replace('/\D/', '', $numero_telefone);
        $url = "{$this->url_base_api}/agenda-parceiro/buscar-telefone";

        try {
            $resposta = Http::asForm()->withoutVerifying()->post($url, [
                'token' => $this->token_api,
                'telefone' => $telefone_limpo,
            ]);

            if ($resposta->successful()) {
                $dados = $resposta->json();
                if (isset($dados['resposta']['sucesso']) && $dados['resposta']['sucesso'] === true) {
                    return $dados['resposta'];
                }
            }
            return null;
        } catch (\Exception $e) {
            Log::error("ERRO DE EXCEÇÃO ao buscar agendamento por telefone: " . $e->getMessage());
            return null;
        }
    }

    public function obterHorariosDisponiveisPorPeriodo(string $grupo_franquia): array
    {
        $data_inicio = Carbon::now()->format('Y-m-d');
        $data_fim = Carbon::now()->addDays(5)->format('Y-m-d');
        $url = "{$this->url_base_api}/agenda-parceiro/vagas-disponiveis-periodo/local/{$grupo_franquia}/data_inicial/{$data_inicio}/data_final/{$data_fim}/token/{$this->token_api}";

        try {
            $resposta = Http::withoutVerifying()->timeout(30)->get($url);
            return $resposta->successful() ? $resposta->json() ?? [] : [];
        } catch (\Exception $e) {
            Log::error("ERRO DE EXCEÇÃO ao buscar vagas para {$grupo_franquia}: " . $e->getMessage());
            return [];
        }
    }

    public function criarAgendamento(array $dados): array
    {
        $url = "{$this->url_base_api}/agenda-parceiro/agendar/";
        $payload = [
            'nome' => $dados['name'],
            'ddd_cel' => $dados['ddd'],
            'cel' => $dados['phone'],
            'currentData' => $dados['date'],
            'hora' => $dados['time'],
            'unidade' => $dados['unit_franchise_group'],
            'token' => $this->token_api,
        ];

        // Adiciona o token de cancelamento ao payload se ele existir nos dados.
        // Isso é usado especificamente para o fluxo de reagendamento.
        if (!empty($dados['cancellation_token'])) {
            $payload['token_cancelamento'] = $dados['cancellation_token'];
        }

        try {
            $resposta = Http::asForm()->withoutVerifying()->post($url, $payload);
            return $resposta->successful() ? $resposta->json() ?? ['result' => 0] : ['result' => 0];
        } catch (\Exception $e) {
            Log::error("ERRO DE EXCEÇÃO ao criar agendamento: " . $e->getMessage(), ['payload' => $payload]);
            return ['result' => 0, 'success' => 'Ocorreu um erro inesperado.'];
        }
    }

    public function confirmarAgendamento(string $token_confirmacao): array
    {
        $url = "{$this->url_base_api}/agenda-parceiro/confirmar";
        Log::info('Tentando confirmar agendamento via POST.', ['url' => $url]);

        try {
            $resposta = Http::asForm()->withoutVerifying()->post($url, [
                'token' => $this->token_api,
                'token_agendamento' => $token_confirmacao,
            ]);
            return $resposta->json() ?? ['sucesso' => false];
        } catch (\Exception $e) {
            Log::error("ERRO DE EXCEÇÃO ao confirmar agendamento: " . $e->getMessage());
            return ['sucesso' => false, 'message' => 'Ocorreu um erro inesperado.'];
        }
    }

    public function cancelarAgendamento(string $token_cancelamento): array
    {
        $url = "{$this->url_base_api}/agenda-parceiro/cancelar";
        Log::info('Tentando cancelar agendamento via POST.', ['url' => $url]);

        try {
            $resposta = Http::asForm()->withoutVerifying()->post($url, [
                'token' => $this->token_api,
                'token_agendamento' => $token_cancelamento,
            ]);
            return $resposta->json() ?? ['sucesso' => false];
        } catch (\Exception $e) {
            Log::error("ERRO DE EXCEÇÃO ao cancelar agendamento: " . $e->getMessage());
            return ['sucesso' => false, 'message' => 'Ocorreu um erro inesperado.'];
        }
    }
}
