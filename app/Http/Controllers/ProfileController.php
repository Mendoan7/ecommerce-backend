<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ResponseFormatter;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function getProfile()
    {
        $user = auth()->user();

        return ResponseFormatter::success($user->api_response);
    }

    public function updateProfile()
    {
        // Validasi
        $validator = Validator::make(request()->all(), [
            'name' => 'required|min:5|max:100',
            'email' => 'required|email',
            'photo_url' => 'nullable|image|max:1024',
            'username' => 'nullable|min:5|max:20',
            'phone' => 'nullable|numeric',
            'store_name' => 'nullable|min:5|max:100',
            'gender' => 'required|in:Laki-Laki,Perempuan,Lainnya',
            'birth_date' => 'nullable|date_format:Y-m-d',
        ]);
        
        // Jika terjadi error
        if ($validator->fails()) {
            return ResponseFormatter::error(400, $validator->errors());
        }

        $payload = $validator->validate();
        if (!is_null(request()->photo)) {
            $payload['photo'] = request()->file('photo')->store(
                'user-photo', 'public'
            );
        }

        // Melakukan update 
        auth()->user()->update($payload);

        // Menampilkan data profile
        return $this->getProfile();
    }
}
