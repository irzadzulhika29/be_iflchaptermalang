<?php

namespace App\Http\Controllers\Donation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{

    public function invoice(string $id)
    {
        $transaction = Transaction::find($id);
        $donation = Donation::find($transaction->donation_id);
        $transaction_success_time = Carbon::parse($transaction->transaction_success_time);

        $invoice = [
            'id' => $transaction->id,
            'name' => $donation->name,
            'donation_amount' => $donation->donation_amount,
            'donation_status' => $donation->status,
            'date' => $transaction_success_time->toDateString(),
            'time' => $transaction_success_time->toTimeString(),
            'payment_method' => $transaction->payment_provider,
            'payment_url' => $transaction->payment_url,
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'Success get invoice payment',
            'data' => $invoice
        ], 200);
    }

    public function callback(Request $request)
    {
        // Inisiasi callback dari Tripay
        $init = $this->tripay->initCallback();
        $result = $init->getJson();

        // Memeriksa apakah callback event adalah "payment_status"
        if ($request->header("X-Callback-Event") != "payment_status") {
            die("Akses Denied");
        }

        // Cari transaksi donasi berdasarkan invoice (merchant_ref)
        $transaction = Donation::where('invoice', $result->merchant_ref)->first();

        if ($transaction) {
            // Pastikan kondisi pengecekan status sudah sesuai kapitalisasi
            if (strtolower($transaction->status) === 'paid') {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Transaction already processed',
                    'transaction' => $transaction
                ], 200);
            }

            DB::beginTransaction();
            try {
                // Mengonversi status dari callback ke huruf kecil untuk konsistensi
                if (strtolower($result->status) == "paid") {
                    $transaction->status = "paid";
                    $transaction->update();

                    // Temukan campaign terkait berdasarkan campaign_id
                    $campaign = Campaign::find($transaction->campaign_id);

                    if ($campaign) {
                        // Tambahkan jumlah donasi ke current_donation
                        $campaign->current_donation += $transaction->donation_amount;
                        $campaign->save();
                    }
                } else {
                    // Jika status bukan "paid", cukup update status transaksi
                    $transaction->status = strtolower($result->status);
                    $transaction->update();
                }

                DB::commit();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Transaction and campaign updated successfully',
                    'transaction' => $transaction
                ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], 500);
            }
        }

        // Jika transaksi tidak ditemukan
        return response()->json(['message' => "Transaksi tidak ada"], 404);
    }




    public function index()
    {
        $method = $this->tripay->initChannelPembayaran()->getJson();
        dd($method);

        return view('donasi');
    }

    public function proccess(Request $request)
    {
        $name = $request->name;
        $email = $request->email;
        $donation_amount = $request->donation_amount;
        $donation_message = $request->donation_message;

        $transaction = new Donation();
        $transaction->name = $name;
        $transaction->email = $email;
        $transaction->donation_amount = $donation_amount;
        $transaction->donation_message = $donation_message;
        $transaction->invoice = "donation_" . rand(20, 200);
        $transaction->save();

        $merchantRef = $transaction->invoice;
        $init = $this->tripay->initTransaction($merchantRef);

        $init->setAmount($transaction->donation_amount); // for close payment
        // $init->setMethod('BNIVA'); // for open payment

        $signature = $init->createSignature();

        $transactions = $init->closeTransaction(); // define your transaction type, for close transaction use `closeTransaction()`
        $transaction->setPayload([
            'method'            => 'BNIVA', // IMPORTANT, dont fill by `getMethod()`!, for more code method you can check here https://tripay.co.id/developer
            'merchant_ref'      => $merchantRef,
            'amount'            => $init->getAmount(),
            'customer_name'     => $transaction->name,
            'customer_email'    => $transaction->email,
            'donation_message'  => $transaction->donation_message,
            'order_items'       => [
                [
                    'sku'       => 'DONASISOSIAL',
                    'name'      => 'Donasi Tes',
                    'price'     => $init->getAmount(),
                    'quantity'  => 1
                ]
            ],
            'callback_url'      => 'https://backend.httpsiflmalang.org/api/v1/callback',
            'return_url'        => 'https://httpsiflmalang.org/',
            'expired_time'      => (time() + (24 * 60 * 60)), // 24 jam
            'signature'         => $signature
        ]); // set your payload, with more examples https://tripay.co.id/developer

        $getPayLoad = $transactions->getPayLoad();
        return response()->json($getPayLoad->getData());
    }
}
