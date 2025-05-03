<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\ResponseFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VoucherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = auth()->user()->vouchers();

        if (request()->search) {
            $query->where('name', 'LIKE', '%' . request()->search . '%');
        }

        if (request()->status) {
            // ongoing, coming_soon, expired
            if (request()->status == 'ongoing') {
                $query->where('start_date', '<=', now())->where('end_date', '>=', now());
            } elseif (request()->status == 'coming_soon') {
                $query->where('start_date', '>', now());
            } elseif (request()->status == 'expired') {
                $query->where('end_date', '<', now());
            }
        }

        $vouchers = $query->paginate(request()->per_page ?? 10);
        
        return ResponseFormatter::success($vouchers->through(function($voucher){
            return $voucher->api_response_seller;
        }));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->getValidation());

        if ($validator->fails()) {
            return ResponseFormatter::error(400, $validator->errors());
        }

        $check = Voucher::where('code', request()->code)->count();
        if ($check > 0) {
            return ResponseFormatter::error(403, null, [
                'Kode voucher sudah digunakan!'
            ]);
        }

        $voucher = auth()->user()->vouchers()->create($validator->validated());
        $voucher->refresh();

        return ResponseFormatter::success($voucher->api_response_seller);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $uuid)
    {
        $validator = Validator::make($request->all(), $this->getValidation());

        if ($validator->fails()) {
            return ResponseFormatter::error(400, $validator->errors());
        }

        $check = Voucher::where('code', request()->code)->where('uuid', '!=', $uuid)->count();
        if ($check > 0) {
            return ResponseFormatter::error(403, null, [
                'Kode voucher sudah digunakan!'
            ]);
        }

        $voucher = auth()->user()->vouchers()->where('uuid', $uuid)->firstOrFail();
        $voucher->update($validator->validated());

        return ResponseFormatter::success($voucher->api_response_seller);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
    {
        $voucher = auth()->user()->vouchers()->where('uuid', $uuid)->firstOrFail();
        $voucher->delete();

        return ResponseFormatter::success([
            'is_deleted' => true
        ]);
    }

    private function getValidation()
    {
        return [
            'code' => 'required|min:5|max:20',
            'name' => 'required|max:50',
            'is_public' => 'required|in:1,0',
            'voucher_type' => 'required|in:discount,cashback',
            'discount_cashback_type' => 'required|in:percentage,fixed',
            'discount_cashback_value' => 'required|numeric',
            'discount_cashback_max' => 'nullable|numeric',
            'start_date' => 'required|date_format:Y-m-d H:i:s',
            'end_date' => 'required|date_format:Y-m-d H:i:s',
        ];
    }
}
