<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\ResponseFormatter;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = auth()->user()->transactions()->orderBy('id', 'desc');
        if (!is_null(request()->search)) {
            $query->where('meta', 'LIKE', '%' . request()->search . '%');
        }

        $transactions = $query->paginate(request()->per_page ?? 10);

        return ResponseFormatter::success($transactions->through(function($transaction){
            return [
                'uuid' => $transaction->uuid,
                'type' => $transaction->type,
                'amount' => (float) $transaction->amount,
                'desription' => isset($transaction->meta['description']) ? $transaction->meta['description'] : null,
                'meta' => $transaction->meta,
                'time' => $transaction->created_at->format('Y-m-d H:i:s'),
            ];
        }));
    }

    public function getListBank()
    {
        $banks = config('bank.list');

        return ResponseFormatter::success($banks);
    }

    public function createWithdraw()
    {
        $validator = Validator::make(request()->all(), [
            'amount' => 'required|numeric|min:1000',
            'description' => 'nullable|min:1|max:100',
            'bank_code' => 'required',
            'bank_account_number' => 'required|string|min:5|max:20',
            'bank_account_holder' => 'required|string|min:5|max:30',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error(400, $validator->errors());
        }

        if (request()->amount > auth()->user()->balance) {
            return ResponseFormatter::error(400, null, [
                'Saldo tidak cukup!'
            ]);
        }

        $bank = collect(config('bank.list'))->where('code', request()->bank_code)->first();
        if (is_null($bank)) {
            return ResponseFormatter::error(400, null, [
                'Bank tidak ditemukan!'
            ]);
        }

        auth()->user()->withdraw(request()->amount, [
            'description' => 'Penarikan ke Bank ' . $bank['name'] . ' ' . request()->bank_account_number . ' ' . request()->description,
            'bank_code' => $bank['code'],
            'bank_name' => $bank['name'],
            'bank_account_number' => request()->bank_account_number,
            'bank_account_holder' => request()->bank_account_holder,
        ]);

        return $this->index();
    }
}
