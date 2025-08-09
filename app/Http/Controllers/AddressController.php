<?php

namespace App\Http\Controllers;

use App\Models\Address\City;
use App\Models\Address\Province;
use Illuminate\Http\Request;
use App\ResponseFormatter;
use App\Services\RajaOngkirClient;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidationValidator;

class AddressController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $addresses = auth()->user()->addresses;

        return ResponseFormatter::success($addresses->pluck('api_response'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make(request()->all(), $this->getValidation());

        if ($validator->fails()) {
            return ResponseFormatter::error(400, $validator->errors());
        }

        $address = auth()->user()->addresses()->create($this->prepareData());
        $address->refresh();

        return $this->show($address->uuid);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
        $address = auth()->user()->addresses()->where('uuid', $uuid)->firstOrFail();

        return ResponseFormatter::success($address->api_response);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $uuid)
    {
        $validator = Validator::make(request()->all(), $this->getValidation());

        if ($validator->fails()) {
            return ResponseFormatter::error(400, $validator->errors());
        }

        $address = auth()->user()->addresses()->where('uuid', $uuid)->firstOrFail();
        $address->update($this->prepareData());
        $address->refresh();

        return $this->show($address->uuid);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
    {
        $address = auth()->user()->addresses()->where('uuid', $uuid)->firstOrFail();
        $address->delete();

        return ResponseFormatter::success([
            'is_deleted' => true
        ]);
    }

    public function setDefault(string $uuid)
    {
        $address = auth()->user()->addresses()->where('uuid', $uuid)->firstOrFail();
        $address->update([
            'is_default' => true
        ]);
        auth()->user()->addresses()->where('id', '!=', $address->id)->update([
            'is_default' => false
        ]);

        return ResponseFormatter::success([
            'is_success' => true
        ]);
    }

    public function getValidation()
    {
        return [
            'is_default' => 'required|in:1,0',
            'receiver_name' => 'required|min:2|max:30',
            'receiver_phone' => 'required|min:2|max:30',
            'city_uuid' => 'required|exists:cities,uuid',
            'district' => 'required|min:3|max:50',
            'postal_code' => 'required|numeric',
            'detail_address' => 'nullable|max:255',
            'address_note' => 'nullable|max:255',
            'type' => 'required|in:office,home',
        ];
    }

    protected function prepareData()
    {
        $payload = request()->only([
            'is_default',
            'receiver_name',
            'receiver_phone',
            'city_uuid',
            'district',
            'postal_code',
            'detail_address',
            'address_note',
            'type',
        ]);
        $payload['city_id'] = City::where('uuid', $payload['city_uuid'])->firstOrFail()->id;

        if ($payload['is_default'] == 1) {
            auth()->user()->addresses()->update([
                'is_default' => false
            ]);
        }

        return $payload;
    }

    public function getProvince(RajaOngkirClient $rajaOngkirClient)
    {
        $forceRefresh = request()->boolean('refresh');
        $cacheKey = 'rajaongkir_provinces_synced';

        if ($forceRefresh || !cache()->has($cacheKey) || !Province::exists()) {
            $apiProvinces = $rajaOngkirClient->provinces(); // <= pakai method kamu
            foreach ($apiProvinces as $row) {
                Province::updateOrCreate(
                    ['external_id' => (int) $row['province_id']],
                    ['name' => $row['province']]
                );
            }
            cache()->put($cacheKey, true, 3600);
        }

        $provinces = Province::get(['uuid', 'name', 'external_id']);
        return ResponseFormatter::success(
            $provinces->map(fn($p) => [
                'uuid' => $p->uuid,
                'name' => $p->name,
                'external_id' => $p->external_id
            ])
        );
    }

    public function getCity(RajaOngkirClient $rajaOngkirClient)
    {
        $provinceUuid       = request('province_uuid');
        $provinceExternalId = request()->integer('province_external_id');
        $search             = request('search');
        $forceRefresh       = request()->boolean('refresh');

        if (!$provinceExternalId && $provinceUuid) {
            $provinceExternalId = Province::where('uuid', $provinceUuid)->value('external_id');
        }

        $cacheKey = 'rajaongkir_cities_synced_' . ($provinceExternalId ?? 'all');

        if ($forceRefresh || !cache()->has($cacheKey)) {
            if (!Province::exists()) {
                foreach ($rajaOngkirClient->provinces() as $p) {
                    Province::updateOrCreate(
                        ['external_id' => (int) $p['province_id']],
                        ['name' => $p['province']]
                    );
                }
            }

            $apiCities = $rajaOngkirClient->cities($provinceExternalId); // <= pakai method kamu
            $provMap   = Province::pluck('id', 'external_id');

            foreach ($apiCities as $c) {
                $provId = $provMap[(int) $c['province_id']] ?? null;
                if (!$provId) continue;

                City::updateOrCreate(
                    ['external_id' => (int) $c['city_id']],
                    ['province_id' => $provId, 'name' => $c['city_name']]
                );
            }

            cache()->put($cacheKey, true, 3600);
        }

        $cities = City::query()
            ->when(
                $provinceExternalId,
                fn($q) =>
                $q->whereIn('province_id', function ($sq) use ($provinceExternalId) {
                    $sq->from('provinces')->where('external_id', $provinceExternalId)->select('id');
                })
            )
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->get();

        return ResponseFormatter::success($cities->pluck('api_response'));
    }
}
