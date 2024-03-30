<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // Buat  Validasi
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'phone' => 'required|unique:users',
            'password' => 'required',
            'password_confirmation' => 'required|confirmed'
        ], [
            'name.required' => 'Nama harus Diisi',
            'email.required' => 'Email Harus Diisi',
            'email.email' => 'Format email tidak sesuai',
            'email.unique' => 'Email Sudah Ada',
            'password.required' => 'Password Harus Diisi',
            'password_confirmation.required' => 'Konfirmasi Password Harus Diisi',
            'password_confirmation.confirmed' => 'Konfirmasi Password Tiidak Sesuai'
        ]);

        if ($validator->fails()) response()->json([
            'status' => false,
            'message' => $validator->errors()
        ]);

        $data = $request->all();
        $data['password'] = Hash::make($request->password);

        // Buat  data user
        $user = User::create($data);

        // Buat Code OTP
        $otp = $user->otp()->create(['code' => random_int(100000, 999999)]);
        // OTP::create([
        //     'user_id' => $user->id,
        //     'code' => random_int(100000, 999999)
        // ]);
        $phone = $user->phone;
        $message = "Kode Otp Anda \n$otp->code";

        $this->waOtp($phone, $message);

        return response()->json([
            'status' => true,
            'message' => 'Data Anda Sudah Tersimpan',
            'data' => $user
        ]);
    }

    public function verifyOtp(Request $request, $phone)
    {
        $data = $request->only('otp');
        $user = User::with('otp')->where('phone', $phone)->first();

        $validator = Validator::make($data, ['otp' => 'required'], ['otp.required' => 'Code OTP Harus Diisi']);
    
        if ($validator->fails()) {
            return response()->json([
                'data' => [
                    'status' => false,
                    'message' => $validator->errors()
                ]
            ]);
        }

        if (!isset($user)) {
            return response()->json([
                'data' => [
                    'status' => false,
                    'message' => 'Nomor Telepon Tidak Sesuai'
                ]
            ]);
        }

        $otp = $user->otp->where('code', $request->code);

        if (!isset($otp)) {
            return response()->json([
                'status' => false,
                'message' => 'Code OTP Tidak Sesuai'
            ]);
        }
    
        $user->otp()->update(['is_verified' => true]);
        $user->update(['is_active' => true]);

        return response()->json([
            'data' => [
                'status' => true,
                'message' => 'Akun Anda Telah Aktif'
            ]
        ]);
    }

    public function login(Request $request) 
    {
        $credentials = $request->only('email', 'password');

        $validator = Validator::make($credentials, [
            'email' => 'required|email',
            'password' => 'required'
        ], [
            'email.required' => 'Email Harus Diisi',
            'email.email' => 'Format Tidak Sesuai',
            'password.required' => 'Password Harus Diisi'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'data' => [
                    'status' => false,
                    'message' => $validator->errors()
                ]
            ]);
        }

        if (!$token = auth()->attempt($credentials)) {
            return response()->json([
                'data' => [
                    'status' => false,
                    'message' => 'Email atau Password Tidak Sesuai'
                ]
            ]);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user->is_active) {
            return response()->json([
                'data' => [
                    'status' => false,
                    'message' => 'Akun Anda Belum Aktif'
                ]
            ]);
        }

        return response()->json([
            'data' => [
                'status' => true,
                'data' => $user,
                'token' => $token
            ]
        ]);
    }
}
