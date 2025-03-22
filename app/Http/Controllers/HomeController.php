<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product\Product;
use App\Models\Slider;
use App\Models\User;
use App\ResponseFormatter;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function getSlider(){
        $sliders = Slider::all();

        return ResponseFormatter::success($sliders->pluck('api_response'));
    }

    public function getCategory(){
        $categories = Category::whereNull('parent_id')->with(['childs'])->get();

        return ResponseFormatter::success($categories->pluck('api_response'));
    }

    public function getProduct(){
        $products = Product::query();

        if (!is_null(request()->category)) {
            $category = Category::where('slug', request()->category)->firstOrFail();
            $products->where('category_id', $category->id);
        }

        if (!is_null(request()->seller)) {
            $seller = User::where('username', request()->seller)->firstOrFail();
            $products->where('seller_id', $seller->id);
        }

        if (!is_null(request()->search)) {
            $products->where('name', 'LIKE', '%' . request()->search . '%');
        }

        if (!is_null(request()->minimum_price)) {
            $products->whereRaw('IF(price_sale  > 0, price_sale, price) >= ?', request()->minimum_price);
        }

        if (!is_null(request()->maximum_price)) {
            $products->whereRaw('IF(price_sale  > 0, price_sale, price) <= ?', request()->maximum_price);
        }

        if (!is_null(request()->sorting_price)) {
            $products->orderByRaw('IF(price_sale  > 0, price_sale, price) ' . request()->sorting_price);
        } else {
            $products->orderBy('id', 'desc');
        }

        if (!is_null(request()->categories) && is_array(request()->categories)) {
            $products->whereHas('category', function($subQuery){
                $subQuery->whereIn('slug', request()->categories);
            });
        }

        $products = $products->paginate(request()->per_page ?? 10);

        return ResponseFormatter::success($products->through(function($product){
            return $product->api_response_excerpt;
        }));
    }

    public function getProductDetail(string $slug){
        $products = Product::where('slug', $slug)->firstOrFail();

        return ResponseFormatter::success($products->api_response);
    }

    public function getProductReview(string $slug){
        $products = Product::where('slug', $slug)->firstOrFail();
        $reviews = $products->reviews();

        if (!is_null(request()->rating)) {
            $reviews = $reviews->where('star_seller', request()->rating);
        }
        if (!is_null(request()->with_attachment)) {
            $reviews = $reviews->whereNotNull('attachments');
        }
        if (!is_null(request()->with_description)) {
            $reviews = $reviews->whereNotNull('with_description');
        }
        
        $reviews = $reviews->paginate(request()->per_page ?? 10);

        return ResponseFormatter::success($reviews->through(function($review){
            return $review->api_response;
        }));
    }

    public function getSellerDetail(string $username){
        $seller = User::where('username', $username)->firstOrFail();

        return ResponseFormatter::success($seller->api_response_as_seller);
    }
    
}
