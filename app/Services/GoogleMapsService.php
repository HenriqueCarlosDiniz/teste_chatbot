<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleMapsService
{
    protected string $api_key;
    protected string $base_url = 'https://maps.googleapis.com/maps/api/geocode/json';

    public function __construct()
    {
        $this->api_key = config('services.google_maps.api_key');
    }

    /**
     * Obtém as coordenadas (latitude e longitude) a partir de um CEP.
     *
     * @param string $cep O CEP a ser pesquisado.
     * @return array|null Um array com 'latitude' e 'longitude' ou nulo em caso de falha.
     */
    public function getCoordinatesFromCep(string $cep): ?array
    {
        if (empty($this->api_key)) {
            Log::error('A chave da API do Google Maps não está configurada.');
            return null;
        }

        $cep_limpo = preg_replace('/\D/', '', $cep);

        try {
            $response = Http::get($this->base_url, [
                'address' => $cep_limpo,
                'key' => $this->api_key,
                'region' => 'BR'
            ]);

            if ($response->successful() && $response->json('status') === 'OK') {
                $location = $response->json('results.0.geometry.location');
                if ($location) {
                    return [
                        'latitude' => $location['lat'],
                        'longitude' => $location['lng'],
                    ];
                }
            }

            Log::warning('Não foi possível obter coordenadas para o CEP.', [
                'cep' => $cep,
                'response_status' => $response->json('status'),
                'response_body' => $response->body()
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('Exceção ao chamar a API do Google Maps Geocoding.', [
                'cep' => $cep,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
