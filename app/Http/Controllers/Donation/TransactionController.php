<?php

namespace App\Http\Controllers\Donation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Midtrans\Config;
use Midtrans\Notification;
use Midtrans\Snap;

class TransactionController extends Controller
{

    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.isProduction');
        Config::$isSanitized = config('midtrans.isSanitized');
        Config::$is3ds = config('midtrans.is3ds');
    }

    public function createTransaction(Request $request, string $campaignSlug)
    {
        $user = auth()->user();

        $campaign = Campaign::where('slug', $campaignSlug)
            ->orWhere('id', $campaignSlug)
            ->first();

        if (!$campaign) {
            return response()->json([
                'status' => 'error',
                'message' => 'Campaign not found with the given slug',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'donation_amount' => 'required|numeric|min:10000',
            'donation_message' => 'nullable|string',
            'anonymous' => 'nullable|boolean',
        ]);

        try {
            DB::beginTransaction();

            $campaignPrefix = strtoupper(substr($campaign->id, 0, 3));
            $randomNumber = rand(1000, 9999);
            $invoice = "DON_{$campaignPrefix}{$randomNumber}";

            $donation = Donation::create([
                'campaign_id' => $campaign->id,
                'user_id' => $user ? $user->id : null,
                'name' => $validated['anonymous'] ?? false ? 'Anonymous' : $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'donation_amount' => $validated['donation_amount'],
                'donation_message' => $validated['donation_message'] ?? null,
                'anonymous' => $validated['anonymous'] ?? 0,
                'status' => 'pending',
                'invoice' => $invoice,
            ]);

            $transaction = Transaction::create([
                'donation_id' => $donation->id,
                'user_id' => $user ? $user->id : null,
            ]);

            $params = [
                'transaction_details' => [
                    'order_id' => $transaction->id,
                    'gross_amount' => (int) $validated['donation_amount'],
                ],
                'customer_details' => [
                    'first_name' => $validated['name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'] ?? '',
                ],
                'item_details' => [
                    [
                        'id' => $campaign->id,
                        'price' => (int) $validated['donation_amount'],
                        'quantity' => 1,
                        'name' => 'Donasi: ' . $campaign->title,
                    ],
                ],
                'callbacks' => [
                    'finish' => env('FRONTEND_URL', 'http://localhost:5173') . '/donation/success',
                    'error' => env('FRONTEND_URL', 'http://localhost:5173') . '/donation/error',
                    'pending' => env('FRONTEND_URL', 'http://localhost:5173') . '/donation/pending',
                ],
            ];

            $snapToken = Snap::getSnapToken($params);

            $transaction->update([
                'snap_token' => $snapToken
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Transaction created successfully',
                'data' => [
                    'transaction_id' => $transaction->id,
                    'donation_id' => $donation->id,
                    'invoice' => $invoice,
                    'snap_token' => $snapToken,
                    'donation_amount' => $donation->donation_amount,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function paymentCallback(Request $request)
    {
        try {
            $notification = new Notification();

            $transactionStatus = $notification->transaction_status;
            $fraudStatus = $notification->fraud_status;
            $orderId = $notification->order_id;

            $serverKey = config('midtrans.server_key');
            $hashed = hash('sha512', $orderId . $notification->status_code . $fraudStatus . $serverKey);

            if ($hashed !== $notification->signature_key) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid signature key',
                ], 400);
            }

            $transaction = Transaction::find($orderId);

            if (!$transaction) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Transaction not found',
                ], 404);
            }

            $donation = $transaction->donation;
            $campaign = $donation->campaign;

            $paymentType = $notification->payment_type;
            $transactionTime = $notification->transaction_time;
            $midtransTransactionId = $notification->transaction_id;

            $vaNumber = null;
            $paymentProvider = null;

            if (isset($notification->va_numbers) && !empty($notification->va_numbers)) {
                $vaNumber = $notification->va_numbers[0]->va_number;
                $paymentProvider = $notification->va_numbers[0]->bank;
            } elseif (isset($notification->biller_code) && isset($notification->bill_key)) {
                $vaNumber = $notification->biller_code . '-' . $notification->bill_key;
                $paymentProvider = 'mandiri';
            } elseif (isset($notification->issuer)) {
                $paymentProvider = $notification->issuer;
            }


            if ($paymentType == 'echannel') {
                $paymentType = 'bank_transfer';
                $paymentProvider = 'mandiri';
            }

            DB::beginTransaction();

            if ($transactionStatus == 'capture') {
                if ($fraudStatus == 'accept') {
                    $this->updateSuccessTransaction($transaction, $donation, $campaign, $midtransTransactionId, $paymentType, $paymentProvider, $vaNumber, $transactionTime);
                }
            } elseif ($transactionStatus == 'settlement') {
                $this->updateSuccessTransaction($transaction, $donation, $campaign, $midtransTransactionId, $paymentType, $paymentProvider, $vaNumber, $transactionTime);
            } elseif ($transactionStatus == 'pending') {
                $this->updatePendingTransaction($transaction, $donation, $midtransTransactionId, $paymentType, $paymentProvider, $vaNumber);
            } elseif ($transactionStatus == 'deny' || $transactionStatus == 'cancel') {
                $donation->update(['status' => 'denied']);
                $transaction->update([
                    'midtrans_transaction_id' => $midtransTransactionId,
                    'payment_method' => $paymentType,
                    'payment_provider' => $paymentProvider,
                ]);
            } elseif ($transactionStatus == 'expire') {
                $donation->update(['status' => 'expired']);
                $transaction->update([
                    'midtrans_transaction_id' => $midtransTransactionId,
                    'payment_method' => $paymentType,
                    'payment_provider' => $paymentProvider,
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Payment callback processed successfully',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function updateSuccessTransaction($transaction, $donation, $campaign, $midtransTransactionId, $paymentMethod, $paymentProvider, $vaNumber, $transactionTime)
    {
        $transaction->update([
            'midtrans_transaction_id' => $midtransTransactionId,
            'transaction_success_time' => $transactionTime,
            'payment_method' => $paymentMethod,
            'payment_provider' => $paymentProvider,
            'va_number' => $vaNumber,
        ]);

        $donation->update(['status' => 'paid']);

        $campaign->increment('current_donation', $donation->donation_amount);
    }

    private function updatePendingTransaction($transaction, $donation, $midtransTransactionId, $paymentMethod, $paymentProvider, $vaNumber)
    {
        $transaction->update([
            'midtrans_transaction_id' => $midtransTransactionId,
            'payment_method' => $paymentMethod,
            'payment_provider' => $paymentProvider,
            'va_number' => $vaNumber,
        ]);

        $donation->update(['status' => 'pending']);
    }



    public function invoice(string $id)
    {
        $transaction = Transaction::find($id);

        if (!$transaction) {
            return response()->json([
                'status' => 'error',
                'message' => 'Transaction not found',
            ], 404);
        }

        $donation = $transaction->donation;
        $campaign = $donation->campaign;
        $transactionSuccessTime = $transaction->transaction_success_time
            ? Carbon::parse($transaction->transaction_success_time)
            : null;

        $invoice = [
            'transaction_id' => $transaction->id,
            'invoice' => $donation->invoice,
            'donor_name' => $donation->name,
            'campaign_name' => $campaign->title,
            'donation_amount' => $donation->donation_amount,
            'donation_status' => $donation->status,
            'payment_method' => $transaction->payment_method,
            'payment_provider' => $transaction->payment_provider,
            'va_number' => $transaction->va_number,
            'transaction_date' => $transactionSuccessTime ? $transactionSuccessTime->toDateString() : null,
            'transaction_time' => $transactionSuccessTime ? $transactionSuccessTime->toTimeString() : null,
            'snap_token' => $transaction->snap_token,
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'Success get invoice payment',
            'data' => $invoice
        ], 200);
    }

    public function  checkStatus(string $transactionId)
    {
        try {
            $transaction = Transaction::find($transactionId);

            if (!$transaction) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Transaction not found',
                ], 404);
            }

            $status  = \Midtrans\Transaction::status($transactionId);

            return response()->json([
                'status' => 'success',
                'message' => 'Transaction status retrieved',
                'data' => [
                    'local_status' => $transaction->donation->status,
                    'midtrans_status' => $status,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    //     public function callback(Request $request)
    //     {
    //         // Inisiasi callback dari Tripay
    //         $init = $this->tripay->initCallback();
    //         $result = $init->getJson();

    //         // Memeriksa apakah callback event adalah "payment_status"
    //         if ($request->header("X-Callback-Event") != "payment_status") {
    //             die("Akses Denied");
    //         }

    //         // Cari transaksi donasi berdasarkan invoice (merchant_ref)
    //         $transaction = Donation::where('invoice', $result->merchant_ref)->first();

    //         if ($transaction) {
    //             // Pastikan kondisi pengecekan status sudah sesuai kapitalisasi
    //             if (strtolower($transaction->status) === 'paid') {
    //                 return response()->json([
    //                     'status' => 'success',
    //                     'message' => 'Transaction already processed',
    //                     'transaction' => $transaction
    //                 ], 200);
    //             }

    //             DB::beginTransaction();
    //             try {
    //                 // Mengonversi status dari callback ke huruf kecil untuk konsistensi
    //                 if (strtolower($result->status) == "paid") {
    //                     $transaction->status = "paid";
    //                     $transaction->update();

    //                     // Temukan campaign terkait berdasarkan campaign_id
    //                     $campaign = Campaign::find($transaction->campaign_id);

    //                     if ($campaign) {
    //                         // Tambahkan jumlah donasi ke current_donation
    //                         $campaign->current_donation += $transaction->donation_amount;
    //                         $campaign->save();
    //                     }
    //                 } else {
    //                     // Jika status bukan "paid", cukup update status transaksi
    //                     $transaction->status = strtolower($result->status);
    //                     $transaction->update();
    //                 }

    //                 DB::commit();

    //                 return response()->json([
    //                     'status' => 'success',
    //                     'message' => 'Transaction and campaign updated successfully',
    //                     'transaction' => $transaction
    //                 ], 200);
    //             } catch (\Exception $e) {
    //                 DB::rollBack();
    //                 return response()->json([
    //                     'status' => 'error',
    //                     'message' => $e->getMessage()
    //                 ], 500);
    //             }
    //         }

    //         // Jika transaksi tidak ditemukan
    //         return response()->json(['message' => "Transaksi tidak ada"], 404);
    //     }




    //     public function index()
    //     {
    //         $method = $this->tripay->initChannelPembayaran()->getJson();
    //         dd($method);

    //         return view('donasi');
    //     }

    //     public function proccess(Request $request)
    //     {
    //         $name = $request->name;
    //         $email = $request->email;
    //         $donation_amount = $request->donation_amount;
    //         $donation_message = $request->donation_message;

    //         $transaction = new Donation();
    //         $transaction->name = $name;
    //         $transaction->email = $email;
    //         $transaction->donation_amount = $donation_amount;
    //         $transaction->donation_message = $donation_message;
    //         $transaction->invoice = "donation_" . rand(20, 200);
    //         $transaction->save();

    //         $merchantRef = $transaction->invoice;
    //         $init = $this->tripay->initTransaction($merchantRef);

    //         $init->setAmount($transaction->donation_amount); // for close payment
    //         // $init->setMethod('BNIVA'); // for open payment

    //         $signature = $init->createSignature();

    //         $transactions = $init->closeTransaction(); // define your transaction type, for close transaction use `closeTransaction()`
    //         $transaction->setPayload([
    //             'method'            => 'BNIVA', // IMPORTANT, dont fill by `getMethod()`!, for more code method you can check here https://tripay.co.id/developer
    //             'merchant_ref'      => $merchantRef,
    //             'amount'            => $init->getAmount(),
    //             'customer_name'     => $transaction->name,
    //             'customer_email'    => $transaction->email,
    //             'donation_message'  => $transaction->donation_message,
    //             'order_items'       => [
    //                 [
    //                     'sku'       => 'DONASISOSIAL',
    //                     'name'      => 'Donasi Tes',
    //                     'price'     => $init->getAmount(),
    //                     'quantity'  => 1
    //                 ]
    //             ],
    //             'callback_url'      => 'https://backend.httpsiflmalang.org/api/v1/callback',
    //             'return_url'        => 'https://httpsiflmalang.org/',
    //             'expired_time'      => (time() + (24 * 60 * 60)), // 24 jam
    //             'signature'         => $signature
    //         ]); // set your payload, with more examples https://tripay.co.id/developer

    //         $getPayLoad = $transactions->getPayLoad();
    //         return response()->json($getPayLoad->getData());
    //     }
    // }
}
