<?php

namespace App\Http\Controllers\Donation;

use App\Http\Controllers\Controller;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Transaction;

class DonationController extends Controller
{
  private $imageService;

  public function __construct(ImageService $imageService)
  {
    $this->imageService = $imageService;
  }
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

    $campaign = Campaign::where('slug', $slug)->orWhere('id', $slug)->first();

    if (!$campaign) {
      return response()->json([
        'status' => 'error',
        'message' => 'Campaign not found with the given slug',
      ], 404);
    }

    // Generate invoice number
    $campaignId = $campaign->id;
    $campaignPrefix = strtoupper(substr($campaignId, 0, 3));
    $randomNumber = rand(1000, 9999);
    $invoice = "DON_{$campaignPrefix}{$randomNumber}";

    $data = $request->only('name', 'email', 'anonymous', 'phone', 'donation_amount', 'donation_message', 'payment_proof');
    $data['anonymous'] = $request->input('anonymous') ?? 0;
    $data['campaign_id'] = $campaign->id;
    $data['status'] = 'pending'; // Status pending menunggu verifikasi admin
    $data['user_id'] = $user ? $user->id : null;
    $data['invoice'] = $invoice;

    if ($user) {
      $data['email'] = $user->email;
    }

    if ($data['anonymous'] == 1) {
      $data['name'] = 'anonymous';
    }

    $rule = [
      'email' => ['required', 'string', 'email', 'max:255'],
      'name' => ['required', 'string', 'max:255'],
      'anonymous' => ['nullable', 'numeric'],
      'phone' => ['string', 'max:255'],
      'donation_amount' => ['numeric', 'min:1000'],
      'donation_message' => ['nullable', 'string'],
      'payment_proof' => ['required', 'mimes:png,jpg,jpeg,webp,pdf', 'max:5120'], // Max 5MB
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

      // Upload payment proof ke ImageKit
      if ($request->hasFile('payment_proof')) {
        $image_folder = "image/payment_proof";
        $image_file = $request->file('payment_proof');
        $image_file_name = "proof-{$invoice}";
        $image_tags = ['payment_proof', 'donation'];

        $image_url = $this->imageService->uploadFile($image_folder, $image_file, $image_file_name, $image_tags);
        $data['payment_proof'] = $image_url;
      }

      // Buat donasi dengan status pending
      $donation = Donation::create($data);

      DB::commit();

      return response()->json([
        'status' => 'success',
        'message' => 'Donasi berhasil dibuat. Menunggu verifikasi admin.',
        'data' => [
          'donation_id' => $donation->id,
          'invoice' => $donation->invoice,
          'status' => $donation->status,
          'donation_amount' => $donation->donation_amount,
          'payment_proof' => $donation->payment_proof,
        ]
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

  /**
   * Admin approve donation
   */
  public function approve(string $id)
  {
    $donation = Donation::find($id);

    if (!$donation) {
      return response()->json([
        'status' => 'error',
        'message' => 'Donation not found with the given id',
      ], 404);
    }

    if ($donation->status !== 'pending') {
      return response()->json([
        'status' => 'error',
        'message' => 'Only pending donations can be approved',
      ], 400);
    }

    try {
      DB::beginTransaction();

      // Update status donasi menjadi paid
      $donation->update(['status' => 'paid']);

      // Update current_donation di campaign
      $campaign = $donation->campaign;
      $campaign->increment('current_donation', $donation->donation_amount);

      // Buat record transaction
      Transaction::create([
        'donation_id' => $donation->id,
        'user_id' => $donation->user_id,
        'payment_method' => 'Manual Transfer',
        'payment_provider' => 'QRIS',
        'transaction_success_time' => now(),
      ]);

      DB::commit();

      return response()->json([
        'status' => 'success',
        'message' => 'Donation approved successfully',
        'data' => $donation->fresh(),
      ], 200);
    } catch (\Exception $e) {
      DB::rollBack();
      return response()->json([
        'status' => 'error',
        'message' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Admin reject donation
   */
  public function reject(Request $request, string $id)
  {
    $donation = Donation::find($id);

    if (!$donation) {
      return response()->json([
        'status' => 'error',
        'message' => 'Donation not found with the given id',
      ], 404);
    }

    if ($donation->status !== 'pending') {
      return response()->json([
        'status' => 'error',
        'message' => 'Only pending donations can be rejected',
      ], 400);
    }

    $reason = $request->input('reason', 'Bukti pembayaran tidak valid');

    try {
      DB::beginTransaction();

      // Update status donasi menjadi denied
      $donation->update(['status' => 'denied']);

      // TODO: Kirim notifikasi email ke donatur (opsional)
      // Mail::to($donation->email)->send(new DonationRejected($donation, $reason));

      DB::commit();

      return response()->json([
        'status' => 'success',
        'message' => 'Donation rejected successfully',
        'data' => $donation->fresh(),
      ], 200);
    } catch (\Exception $e) {
      DB::rollBack();
      return response()->json([
        'status' => 'error',
        'message' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Get all pending donations (for admin review)
   */
  public function getPending()
  {
    try {
      $pendingDonations = Donation::with(['campaign', 'user'])
        ->where('status', 'pending')
        ->orderBy('created_at', 'desc')
        ->get();

      return response()->json([
        'status' => 'success',
        'message' => 'Get pending donations success',
        'data' => $pendingDonations,
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
      DB::beginTransaction();

      // Hapus payment proof dari ImageKit jika ada
      if ($donation->payment_proof) {
        $image_folder = "image/payment_proof";
        $this->imageService->deleteFile($image_folder, $donation->payment_proof);
      }

      $donation->delete();

      DB::commit();

      return response()->json([
        'status' => 'success',
        'message' => 'Delete donation success',
        'data' => $donation,
      ], 200);
    } catch (\Exception $e) {
      DB::rollBack();
      return response()->json([
        'status' => 'error',
        'message' => $e->getMessage(),
      ], 500);
    }
  }
}
