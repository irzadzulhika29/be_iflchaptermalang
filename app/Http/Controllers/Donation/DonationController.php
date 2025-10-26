<?php

namespace App\Http\Controllers\Donation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Transaction;

class DonationController extends Controller
{
    public function index()
    {
      $donations = Donation::with('transaction')->get();
      $latest_update = Donation::latest()->value('updated_at');

      try {
        return response()->json([
            'status' => 'success',
            'message' => 'Get all donation success',
            'data' => [
              'latest_update' => $latest_update,
              'donations' => $donations
            ],
          ], 200);
      } catch (\Exception $e) {
        return response()->json([
          'status' => 'error',
          'message' => $e->getMessage(),
        ], 500);
      }
    }

        public function donate(Request $request, string $slug)
    {
      $user = auth()->user();

      $campaign = Campaign::where('slug', $slug)->orWhere('id',$slug)->first();

      if (!$campaign) {
        return response()->json([
          'status' => 'error',
          'message' => 'Campaign not found with the given slug',
        ], 404);
      }
      
    //   $invoice = "donation_" . rand(20, 200);
    //   $request->merge(['invoice' => $invoice]); // Masukkan invoice ke dalam request
    
        $campaignId = $campaign->id; 
        $campaignPrefix = strtoupper(substr($campaignId, 0, 3)); 
        $randomNumber = rand(1000, 9999); 
    
        $invoice = "Donation_{$campaignPrefix}{$randomNumber}"; 
        $request->merge(['invoice' => $invoice]); // Masukkan invoice ke dalam request

      $data = $request->only('name', 'email', 'anonymous', 'phone', 'donation_amount', 'donation_message', 'status', 'user_id', 'campaign_id', 'invoice');
      $data['anonymous'] = $request->input('anonymous') ?? 0;
      $data['campaign_id'] = $campaign->id;
      $data['status'] = 'unpaid';
      $data['user_id'] = $user ? $user->id : null;
      $data['invoice'] = $invoice;
      
      
      if($user) {
        $data['email'] = $user->email;
      }
      
      if($data['anonymous'] == 1) {
        $data['name'] = 'anonymous';
      } else {

      }

      $rule = [
        'email' => ['required', 'string', 'email', 'max:255'],
        'name' => ['required', 'string', 'max:255'],
        'anonymous' => ['nullable', 'numeric'],
        'phone' => ['required', 'string', 'max:255'],
        'donation_amount' => ['required', 'numeric', 'min:1000'],
        'donation_message' => ['nullable', 'string'],
        'status' => ['required', 'string', 'in:unpaid,pending,paid,denied,expired,canceled'],
        'invoice' => ['required', 'string'],
        'user_id' => ['nullable', 'uuid'],
        'campaign_id' => ['required', 'uuid'],
      ];
  
      $validator = Validator::make($data, $rule);

      if ($validator->fails()) {
        return response()->json([
          'status' => 'error',
          'error' => $validator->messages(),
        ], 422);
      }

      try {
    DB::beginTransaction();

    // Buat donasi
    $donation = Donation::create($data);

    // Buat transaksi berdasarkan donasi
    $transaction = Transaction::create([
        'donation_id' => $donation->id,
        'user_id' => $donation->user_id,
    ]);

    // Rincian transaksi dan kampanye
    $transaction_details = [
        'order_id' => $transaction->id,
        'gross_amount' => $donation->donation_amount,
    ];

    $campaign_details = [
        [
            'id' => $campaign->id,
            'price' => $donation->donation_amount,
            'quantity' => 1,
            'name' => $campaign->title,
        ]
    ];

    $customer_details = [
        'first_name' => $donation->name ?? 'anonymous',
        'email' => $donation->email,
    ];

    
    // Integrasi Tripay untuk membuat transaksi
    
    $merchantRef = $invoice;
    $init = $this->tripay->initTransaction($merchantRef);
    $init->setAmount($donation->donation_amount);
    $signature = $init->createSignature();
    $transactions = $init->closeTransaction();

    // Konfigurasi payload untuk transaksi ke Tripay
    $transactions->setPayload([
        'method'            => $request->method,
        'merchant_ref'      => $merchantRef,
        'amount'            => $init->getAmount(),
        'customer_name'     => $donation->name ?? 'anonymous',
        'customer_email'    => $donation->email,
        'customer_phone'    => $donation->phone,
        'donation_message'  => $donation->donation_message,
        'order_items'       => [
            [
                'sku'       => $campaign->category, 
                'name'      => $campaign->title,   
                'price'     => $init->getAmount(),
                'quantity'  => 1
            ]
        ],
        'callback_url'      => 'https://admin.iflchaptermalang.org/api/v1/callback',
        'return_url'        => 'https://iflchaptermalang.org/',
        'expired_time'      => (time() + (24 * 60 * 60)),
        'signature'         => $signature
    ]);

    // Ambil data transaksi dari Tripay
    $get_data_from_server = $transactions->getJson();
    $paymentUrl = $get_data_from_server->data->checkout_url;

    // Update transaksi dengan informasi pembayaran dari Tripay
    $transaction->update([
        'snap_token' => $get_data_from_server->data->reference,
        'payment_url' => $paymentUrl,
    ]);

    DB::commit();

    return response()->json([
        'status' => 'success',
        'message' => 'create donation success',
        'snap_token' => $get_data_from_server->data->reference,
        'payment_url' => $paymentUrl,
    ], 201);

} catch (\Exception $e) {
    DB::rollBack();
    return response()->json([
        'status' => 'error',
        'message' => $e->getMessage()
    ], 500);
}
}

    public function show(string $id)
    {
      $donation = Donation::find($id);

      if (!$donation) {
        return response()->json([
          'status' => 'error',
          'message' => 'Donation not found with the given id',
        ], 404);
      }
      try {
        return response()->json([
          'status' => 'success',
          'message' => 'Get donation by id success',
          'data' => $donation,
        ], 200);
      } catch (\Exception $e) {
        return response()->json([
          'status' => 'error',
          'message' => $e->getMessage(),
        ], 500);
      }
    }

    public function destroy(string $id)
    {
      $donation = Donation::find($id);
      
      if (!$donation) {
        return response()->json([
          'status' => 'error',
          'message' => 'Donation not found with the given id',
        ], 404);
      }

      try {
        $donation->delete();
  
        return response()->json([
          'status' => 'success',
          'message' => 'Delete donation success',
          'data' => $donation,
        ], 200);
      } catch (\Exception $e) {
        return response()->json([
          'status' => 'error',
          'message' => $e->getMessage(),
        ], 500);
      }
    }
}
