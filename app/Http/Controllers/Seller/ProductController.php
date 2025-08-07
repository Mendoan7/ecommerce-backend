<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\ResponseFormatter;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

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
            $query->whereHas('category', function ($subQuery) {
                $subQuery->where('name', 'LIKE', '%' . request()->category . '%');
            });
        }

        $products = $query->paginate(request()->per_page ?? 10);

        return ResponseFormatter::success($products->through(function ($product) {
            return $product->api_response_seller;
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

        $payload = $this->prepareData($validator->validated());

        $product = DB::transaction(function () use ($payload) {
            $product = auth()->user()->products()->create($payload);

            foreach ($payload['variations'] as $variation) {
                $product->variations()->create($variation);
            }

            foreach ($payload['images'] as $image) {
                $product->images()->create($image);
            }

            return $product;
        });

        $product->refresh();

        return ResponseFormatter::success($product->api_response_seller);
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
        $rules = $this->getValidation();
        $rules['old_images'] = 'array';
        $rules['old_images.*'] = 'url';

        // Ubah rules 'images' agar tidak wajib saat update
        $rules['images'] = 'nullable|array|max:9';
        $rules['images.*'] = 'image|max:1024';

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return ResponseFormatter::error(400, $validator->errors());
        }

        $product = auth()->user()->products()->where('uuid', $uuid)->firstOrFail();
        $payload = $this->prepareData($validator->validated(), $product);

        DB::transaction(function () use ($product, $payload) {
            $product->update($payload);

            $product->variations()->delete();
            foreach ($payload['variations'] as $variation) {
                $product->variations()->create($variation);
            }

            foreach ($product->images as $image) {
                if (!in_array($image->image, $payload['old_images'])) {
                    Storage::disk('public')->delete($image->image);
                    $image->delete();
                }
            }

            foreach ($payload['images'] as $image) {
                $product->images()->create($image);
            }
        });

        $product->refresh();

        return ResponseFormatter::success($product->api_response_seller);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
    {
        $product = auth()->user()->products()->where('uuid', $uuid)->firstOrFail();

        foreach ($product->images as $image) {
            if ($image->image) {
                Storage::disk('public')->delete($image->image);
            }
        }

        $product->delete();

        return ResponseFormatter::success([
            'is_deleted' => true
        ]);
    }

    private function getValidation()
    {
        return [
            'name' => 'required|string|min:5|max:150',
            'price' => 'required|numeric|min:1000',
            'price_sale' => 'nullable|numeric|min:500',
            'stock' => 'required|numeric|min:0',
            'category_slug' => 'required|exists:categories,slug',
            'description' => 'required|min:20|max:1000',
            'weight' => 'required|numeric|min:1',
            'length' => 'required|numeric|min:1',
            'width' => 'required|numeric|min:1',
            'height' => 'required|numeric|min:1',
            'video' => 'nullable|file|mimes:mp4,mov,avi,wmv,flv|max:30720',
            'images' => 'required|array|min:1|max:9',
            'images.*' => 'required|image|max:1024',
            'remove_video' => 'nullable|boolean',
            'variations' => 'array',
            'variations.*.name' => 'required|string|min:2|max:255',
            'variations.*.values' => 'array',
            'variations.*.values.*' => 'string|min:2|max:200',
        ];
    }

    private function prepareData(array $payload, $product = null)
    {
        // Category
        $payload['category_id'] = Category::where('slug', $payload['category_slug'])->firstOrFail()->id;
        unset($payload['category_slug']);

        // Slug
        $payload['slug'] = Str::slug($payload['name']) . '-' . Str::random(5);

        // Upload video
        if (!empty($payload['video'])) {
            $payload['video'] = $payload['video']->store('products/video', 'public');
        }

        // Remove video if requested
        if (($payload['remove_video'] ?? false) && $product && $product->video) {
            Storage::disk('public')->delete($product->video);
            $payload['video'] = null;
        }
        
        // Upload image
        $images = [];
        if (isset($payload['images'])) {
            foreach ($payload['images'] as $image) {
                $images[] = [
                    'image' => $image->store('products/images', 'public')
                ];
            }
        }

        $payload['images'] = $images;

        if (isset($payload['old_images'])) {
            $oldImages = [];
            foreach ($payload['old_images'] as $oldImage) {
                $oldImages[] = str_replace(config('app.url') . '/storage/', '', $oldImage);
            }
            $payload['old_images'] = $oldImages;
        } else {
            $payload['old_images'] = [];
        }

        return $payload;
    }
}
