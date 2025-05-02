<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\ResponseFormatter;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = auth()->user()->products()->with(['category', 'images', 'variations']);

        if (request()->search) {
            $query->where('name', 'LIKE', '%' . request()->search . '%');
        }

        if (request()->category) {
            $query->whereHas('category', function($subQuery) {
                $subQuery->where('name', 'LIKE', '%' . request()->category . '%');
            });
        }

        $products = $query->paginate(request()->per_page ?? 10);

        return ResponseFormatter::success($products->through(function($product){
            return $product->api_response_seller;
        }));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
