<?php

namespace App\Http\Controllers;

use App\Models\Address\City;
use App\Models\Address\Province;
use Illuminate\Http\Request;
use App\ResponseFormatter;
use App\Services\RajaOngkirClient;
use Illuminate\Support\Facades\Http;
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

        $response = Http::withHeader([
            'key' => config('services.rajaongkir.key')
        ])->get(config('services.rajaongkir.base_url') . '/destination/domestic-destination', [
            'search' => request()->postal_code,
        ]);

        if ($response->object()->meta->code == '404' || !isset($response->object()->data[0])) {
            return ResponseFormatter::error(404, 'Kodepos tidak ditemukan');
        }

        $payload = $this->prepareData();
        $payload['rajaongkir_subdistrict_id'] = $response->object()->data[0]->id;

        $address = auth()->user()->addresses()->create($payload);
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

        $response = Http::withHeader([
            'key' => config('services.rajaongkir.key')
        ])->get(config('services.rajaongkir.base_url') . '/destination/domestic-destination', [
            'search' => request()->postal_code,
        ]);

        if ($response->object()->meta->code == '404' || !isset($response->object()->data[0])) {
            return ResponseFormatter::error(404, 'Kodepos tidak ditemukan');
        }

        $payload = $this->prepareData();
        $payload['rajaongkir_subdistrict_id'] = $response->object()->data[0]->id;

        $address->update($payload);
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

    public function getProvince()
    {
        $provinces = cache()->remember('provinces', 3600, function(){
            return \App\Models\Address\Province::get(['uuid', 'name']);
        });

        return ResponseFormatter::success($provinces);
    }

    public function getCity()
    {
        $query = \App\Models\Address\City::query();
        if (request()->province_uuid) {
            $query = $query->whereIn('province_id', function($subQuery){
                $subQuery->from('provinces')->where('uuid', request()->province_uuid)->select('id');
            });
        }

        if (request()->search) {
            $query = $query->where('name', 'LIKE', '%' . request()->search . '%');
        }

        $cities = cache()->remember('cities_' . request()->province_uuid . '_' . request()->search, 3600, function() use($query) {
            return $query->get();
        });

        return ResponseFormatter::success($cities->pluck('api_response'));
    }
}
