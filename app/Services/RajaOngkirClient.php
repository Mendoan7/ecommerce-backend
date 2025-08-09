<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class RajaOngkirClient
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.rajaongkir.base_url'); // https://api.rajaongkir.com/starter
        $this->apiKey  = config('services.rajaongkir.key');
    }

    private function client()
    {
        return Http::withHeaders([
            'key'    => $this->apiKey,
            'Accept' => 'application/json',
        ])->baseUrl($this->baseUrl)->timeout(10)->retry(2, 200);
    }

    /** @return array<int, array{province_id:string, province:string}> */
    public function provinces(?int $id = null): array
    {
        $url = $id ? "/province?id={$id}" : '/province';
        return $this->client()->get($url)->throw()->json('rajaongkir.results', []);
    }

    /** @return array<int, array{city_id:string, city_name:string, province_id:string, province:string, type:string, postal_code:string}> */
    public function cities(?int $provinceExternalId = null, ?int $cityExternalId = null): array
    {
        $params = [];
        if ($provinceExternalId) $params['province'] = $provinceExternalId;
        if ($cityExternalId)     $params['id']       = $cityExternalId;

        return $this->client()->get('/city', $params)->throw()->json('rajaongkir.results', []);
    }
}
