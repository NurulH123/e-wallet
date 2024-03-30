<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ActivityController extends Controller
{
    public function activities()
    {
        $user = auth()->user();

        $activities = (Activity::orderBy('created_at', 'DESC')
                        ->where(function($query) use($user) {

                            $query->where('user_id', '=', $user->id)
                                    ->orWhere('to', '=', $user->id);
                        })->get())
                        ->groupBy('date_transaction');

        if (!is_null(request('month'))) {
            $activities = (Activity::orderBy('created_at', 'DESC')
                            ->whereMonth('date_traansaction', request('month'))
                            ->whereYear('date_transaction', date('Y'))
                            ->where(function($query) use($user) {

                                $query->where('user_id', '=', $user->id)
                                        ->orWhere('to', '=', $user->id);
                            })->get())
                            ->groupBy('date_transaction');
        }


        return response()->json([
            'status' => true,
            'message' => 'Semua Data Transaksi',
            'data' => $this->allActivity($activities),
        ]);
    }

    public function sendToUser(Request $request)
    {
        $user = auth()->user();
        $data = $request->all();

        $validator = Validator::make($data, [
            'to' => 'required',
            'nominal' => 'required',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'data' => [
                    'status' => false,
                    'message' => $validator->errors()
                ]
            ]);
        }

        $credentials = [
            'email' => $user->email,
            'password' => $request->password
        ];

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'data' => [
                    'status' => false,
                    'message' => 'Password Tidak Sesuai'
                ]
            ]);
        }

        $toUser = User::find($data['to']);

        $saldo = $user->saldo;
        $toSaldo = $toUser->saldo;

        if ($saldo < $request->nominal) {
            return response()->json([
                'data' => [
                    'status' => false,
                    'message' => 'Saldo Anda Tidak Cukup'
                ]
            ]);
        }

        $data['date_transaction'] = now();
        $user->activities()->create($data);

        $currentSaldo = $saldo - $request->nominal; // saldo user pengirim
        $currentToSaldo = $toSaldo + $request->nominal; // saldo user penerima

        $user->update(['saldo' => $currentSaldo]); // Update saldo user yg mengirim uang
        $toUser->update(['saldo' => $currentToSaldo]); // Update saldo user penerima uang

        return response()->json([
            'data' => [
                'status' => true,
                'message' => 'Data Telah Tersimpan'
            ]
        ]);
    }

    public function topUp(Request $request)
    {
        $user = auth()->user();
        $data = $request->all();

        $validator = Validator::make($data, [
            'nominal' => 'required',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'data' => [
                    'status' => false,
                    'message' => $validator->errors()
                ]
            ]);
        }

        $credentials = [
            'email' => $user->email,
            'password' => $request->password
        ];

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'data' => [
                    'status' => false,
                    'message' => 'Password Tidak Sesuai'
                ]
            ]);
        }

        // Proses update saldo
        $saldo = $user->saldo;
        $currSaldo = $saldo + $request->nominal;

        $user->update(['saldo' => $currSaldo]); // update saldo

        // Proses Create  Activity
        $dataAct = $data;
        $dataAct['to'] = $user->id; 
        $dataAct['date_transaction'] = now();

        $user->activities()->create($dataAct); // add activity

        return response()->json([
            'data'  => [
                'status' => true,
                'message' => 'Top Up Berhasil'
            ]
        ]);
    }

    private function allActivity($datas)
    {
        $user = auth()->user();
        $userId = $user->id;

        $newData = [];
        foreach($datas as $month => $activities) {

            $transaction = [];
            foreach($activities as $activity) {

                $isTopUp = $userId == $activity->to ? true : false;

                $trans = [
                    'id' => $activity->id,
                    'label_id' => $activity->id,
                    'label_name' => $isTopUp ? 'Isi Saldo' : 'Saldo Keluar',
                    'status' => true,
                    'status_label' => 'Berhasil',
                    'amount_label' => 'Rp '. number_format($activity->nominal,0,'.','.'),
                    'amount' => $activity->nominal
                ];

                array_push($transaction, $trans);
            }

            array_push($newData, [
                'id' => $activity->id,
                'date' => date('d, F Y', strtotime($activity->date_transaction )),
                'transaction' => $transaction
            ]);
        }

        return $newData;
    }
}
