<?php

namespace App\Http\Controllers\Donation;

use App\Http\Controllers\Controller;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\Donation;
use Illuminate\Support\Facades\Log;


class CampaignController extends Controller
{
  private $imageService;
  private $apiKey;
  private $privateKey;

  public function __construct(ImageService $imageService)
  {
    $this->imageService = $imageService;
    $this->privateKey = env('TRIPAY_PRIVATE_KEY');
  }

  public function donation($campaign_id_or_slug)
  {
    $campaign = Campaign::where('slug', $campaign_id_or_slug)->orWhere('id', $campaign_id_or_slug)->first();

    if (!$campaign) {
      return response()->json([
        'status' => 'error',
        'message' => 'Campaign not found with the given slug or ID',
      ], 404);
    }

    $apiKey = env('TRIPAY_API_KEY');

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_FRESH_CONNECT  => true,
      CURLOPT_URL            => 'https://tripay.co.id/api/merchant/payment-channel',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HEADER         => false,
      CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $apiKey],
      CURLOPT_FAILONERROR    => false,
      CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4
    ));

    $response = curl_exec($curl);
    $error = curl_error($curl);

    curl_close($curl);

    Log::info('Response dari Tripay:', ['response' => $response]);

    // Handle any error from Tripay API request
    if ($error) {
      return response()->json([
        'status' => 'error',
        'message' => 'Failed to fetch payment methods: ' . $error,
      ], 500);
    }

    // Decode the JSON response from Tripay API
    $method = json_decode($response, true)['data'] ?? [];
    $filteredMethods = array_map(function ($response) {
      return [
        'group' => $response['group'],
        'code' => $response['code'],
        'name' => $response['name'],
        'type' => $response['type'],
        'fee_merchant' => $response['fee_merchant'],
        'fee_customer' => $response['fee_customer'],
        'total_fee' => $response['total_fee'],
        'minimum_fee' => $response['minimum_fee'],
        'maximum_fee' => $response['maximum_fee'],
        'minimum_amount' => $response['minimum_amount'],
        'maximum_amount' => $response['maximum_amount'],
        'icon_url' => $response['icon_url'],
        'active' => $response['active'],
      ];
    }, $method);

    return response()->json($method);

    if (json_last_error() !== JSON_ERROR_NONE) {
      return response()->json([
        'status' => 'error',
        'message' => 'Invalid JSON response from payment channel API',
      ], 500);
    }

    // Retrieve donations related to the campaign
    $latest_donation = Donation::where('campaign_id', $campaign->id)->latest()->value('updated_at');
    $donations = $campaign->donations;
    $donation_data = [];

    foreach ($donations as $donation) {
      $transaction = $donation->transaction;
      $donation_data[] = [
        'donation_id' => $donation->id,
        'name' => $donation->name,
        'email' => $donation->email,
        'message' => $donation->donation_message,
        'donation_amount' => $donation->donation_amount,
        'donation_time' => $transaction->transaction_success_time ?? 'null',
        'payment_method' => $transaction->payment_method ?? 'unpaid',
        'status' => $donation->status,
      ];
    }

    // Final response containing campaign data, payment methods, and donations
    return response()->json([
      'status' => 'success',
      'message' => 'Get campaign donation success',
      'data' => [
        'campaign' => [
          'title' => $campaign->title,
          'current_donation' => $campaign->current_donation,
          'image' => $campaign->image ?? null,
        ],
        'latest_donation' => $latest_donation,
        'donations' => $donation_data,
        'method' => $filteredMethods, // Pastikan struktur respons sesuai
      ],
    ], 200);
  }


  public function getTotalDonation()
  {
    $campaigns = Campaign::all();
    $latest_donation = Campaign::latest()->value('updated_at');

    try {
      $total_donation = 0;

      foreach ($campaigns as $campaign) {
        $total_donation += $campaign->current_donation;
      }

      return response()->json([
        'status' => 'success',
        'message' => 'Get campaign total donation success',
        'data' => [
          "total_donation" => $total_donation,
          'latest_update' => $latest_donation
        ],
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'status' => 'error',
        'message' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Display a listing of the resource.
   */
  public function index()
  {
    $campaigns = Campaign::with(['categories' => function ($query) {
      $query->select('categories.name');
    }])->get();

    $campaigns->each(function ($campaign) {
      $campaign->categories->transform(function ($category) {
        return $category->name;
      });
    });

    $latest_update = Campaign::latest()->value('updated_at');

    try {
      return response()->json([
        'status' => 'success',
        'message' => 'Get all campaign success',
        'data' => [
          'latest_update' => $latest_update,
          'campaigns' => $campaigns
        ],
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'status' => 'error',
        'message' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Show the form for creating a new resource.
   */
  public function create()
  {
    //
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store(Request $request)
  {
    $user = auth()->user();

    $data = $request->only('title', 'slug', 'short_description', 'body', 'view_count', 'status', 'current_donation', 'target_donation', 'publish_date', 'end_date', 'note', 'receiver', 'image', 'categories');
    $data['user_id'] = $user->id;
    $data['slug'] = Str::slug($data['title']);
    $rule = [
      'title' => ['required', 'string', 'unique:campaigns'],
      'slug' => ['nullable', 'string'],
      'short_description' => ['required', 'string'],
      'body' => ['required', 'string'],
      'view_count' => ['nullable', 'integer', 'min:0'],
      'status' => ['required', 'in:active,closed,pending'],
      'current_donation' => ['nullable', 'numeric', 'min:0'],
      'target_donation' => ['required', 'numeric', 'min:0'],
      'publish_date' => ['required', 'date'],
      'end_date' => ['required', 'date', 'after_or_equal:publish_date'],
      'note' => ['nullable', 'string'],
      'receiver' => ['required', 'string'],
      'image' => ['nullable', 'mimes:png,jpg,jpeg,webp', 'max:16384'],
      'categories' => ['required', 'exists:categories,id'],
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

      $campaign = Campaign::create($data);

      if (isset($data['image'])) {
        $image_folder = "image/campaign";

        $image_file = $data['image'];
        $image_file_name = Str::slug($campaign->title);
        $image_tags = ['campaign'];

        $image_url = $this->imageService->uploadFile($image_folder, $image_file, $image_file_name, $image_tags);
        $campaign->image = $image_url;
        $campaign->save();
      }

      if (isset($data['categories'])) {
        $categoryIds = $data['categories'];
        $uniqueCategoryIds = array_unique($categoryIds);
        $existingCategories = Category::whereIn('id', $categoryIds)->get();

        if (count($uniqueCategoryIds) !== count($categoryIds)) {
          return response()->json([
            'status' => 'error',
            'message' => 'Duplicate category IDs found in the input',
          ], 422);
        }

        if ($existingCategories->count() !== count($categoryIds)) {
          return response()->json([
            'status' => 'error',
            'message' => 'One or more categories not found with the given ID',
          ], 422);
        }

        $campaign->categories()->attach($data['categories']);

        $campaign->categories->transform(function ($category) {
          return $category->name;
        });
      }

      DB::commit();

      return response()->json([
        'status' => 'success',
        'message' => 'Create campaign success',
        'data' => $campaign,
      ], 201);
    } catch (ValidationException $e) {
      return response()->json([
        'status' => 'error',
        'message' => $e->errors()
      ], 422);
    } catch (\Exception $e) {
      return response()->json([
        'status' => 'error',
        'message' => $e->getMessage()
      ], 500);
    }
  }
  /**
   * Display the specified resource.
   */
  public function show(string $slug)
  {
    $campaign = Campaign::where('slug', $slug)->first();

    if (!$campaign) {
      return response()->json([
        'status' => 'error',
        'message' => 'Campaign not found with the given slug',
      ], 404);
    }

    $campaign->categories->transform(function ($category) {
      return $category->name;
    });

    $paidDonations = $campaign->donations()
      ->where('status', 'paid')
      ->select('id', 'name', 'anonymous', 'donation_amount', 'donation_message', 'campaign_id', 'updated_at')
      ->get();

    $totalDonation = $paidDonations->sum('donation_amount');

    $donorCount = $paidDonations->count();

    $campaign->total_collected = $totalDonation;

    $donorsData = $paidDonations->map(function ($donation) {
      return [
        'donation_id' => $donation->id,
        'name' => $donation->name,
        'anonymous' => $donation->anonymous,
        'donation_amount' => $donation->donation_amount,
        'donation_message' => $donation->donation_message,
        'donated_at' => $donation->updated_at,
        'email' => $donation->anonymous ? null : $donation->email,
        'phone' => $donation->anonymous ? null : $donation->phone,
      ];
    });

    try {
      return response()->json([
        'status' => 'success',
        'message' => 'Get campaign by slug success',
        'data' => [
          'campaign' => $campaign,
          'donation_summary' => [
            'total_collected' => $totalDonation,
            'donor_count' => $donorCount,
            'target_donation' => $campaign->target_donation,
            'percentage' => $campaign->target_donation
              ? round(($totalDonation / $campaign->target_donation) * 100, 2)
              : 0,
            'remaining' => $campaign->target_donation
              ? max(0, $campaign->target_donation - $totalDonation)
              : 0,
          ],
          'donors' => $donorsData,
        ],
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'status' => 'error',
        'message' => $e->getMessage()
      ], 500);
    }
  }
  /**
   * Show the form for editing the specified resource.
   */
  public function edit(Request $request, $id)
  {
    //
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, string $slug)
  {
    $campaign = Campaign::where('slug', $slug)->first();

    if (!$campaign) {
      return response()->json([
        'status' => 'error',
        'message' => 'Campaign not found with the given id',
      ], 404);
    }

    $data = $request->only('title', 'slug', 'short_description', 'body', 'view_count', 'status', 'current_donation', 'target_donation', 'publish_date', 'end_date', 'note', 'receiver', 'image', 'categories');
    $rule = [
      'title' => ['nullable', 'string', 'unique:campaigns,title,' . $campaign->id],
      'slug' => ['nullable', 'string'],
      'short_description' => ['nullable', 'string'],
      'body' => ['nullable', 'string'],
      'view_count' => ['nullable', 'integer', 'min:0'],
      'status' => ['nullable', 'in:active,closed,pending'],
      'current_donation' => ['nullable', 'numeric', 'min:0'],
      'target_donation' => ['nullable', 'numeric', 'min:0'],
      'publish_date' => ['nullable', 'date'],
      'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
      'note' => ['nullable', 'string'],
      'receiver' => ['nullable', 'string'],
      'image' => ['nullable', 'mimes:png,jpg,jpeg,webp', 'max:16384'],
      'categories.*' => ['nullable', 'exists:categories,id'],
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

      if (isset($data['image'])) {
        $image_folder = "image/campaign";
        if ($campaign->image) {
          $this->imageService->deleteFile($image_folder, $campaign->image);
        }

        $image_file = $data['image'];
        $image_file_name = Str::slug($data['title'] ?? $campaign->title);
        $image_tags = ['campaign'];

        $image_url = $this->imageService->uploadFile($image_folder, $image_file, $image_file_name, $image_tags);
      }

      if (isset($data['categories'])) {
        $categoryIds = $data['categories'];
        $uniqueCategoryIds = array_unique($categoryIds);
        $existingCategories = Category::whereIn('id', $categoryIds)->get();

        if (count($uniqueCategoryIds) !== count($categoryIds)) {
          return response()->json([
            'status' => 'error',
            'message' => 'Duplicate category ids found in the input',
          ], 422);
        }

        if ($existingCategories->count() !== count($categoryIds)) {
          return response()->json([
            'status' => 'error',
            'message' => 'One or more categories not found with the given id',
          ], 422);
        }

        $campaign->categories()->sync($data['categories']);
      }

      $campaign->update([
        'title' => $data['title'] ?? $campaign->title,
        'slug' => $data['title'] ? Str::slug($data['title']) : $campaign->slug,
        'short_description' => $data['short_description'] ?? $campaign->short_description,
        'body' => $data['body'] ?? $campaign->short_description,
        'view_count' => $data['view_count'] ?? $campaign->view_count,
        'status' => $data['status'] ?? $campaign->status,
        'current_donation' => $data['current_donation'] ?? $campaign->current_donation,
        'target_donation' => $data['target_donation'] ?? $campaign->target_donation,
        'start_date' => $data['start_date'] ?? $campaign->start_date,
        'end_date' => $data['end_date'] ?? $campaign->end_date,
        'note' => $data['note'] ?? $campaign->note,
        'receiver' => $data['receiver'] ?? $campaign->receiver,
        'image' => $image_url ?? $campaign->image,
      ]);

      $campaign->categories->transform(function ($category) {
        return $category->name;
      });

      DB::commit();

      return response()->json([
        'status' => 'success',
        'message' => 'Update campaign by id success',
        'data' => $campaign,
      ], 201);
    } catch (ValidationException $e) {
      return response()->json([
        'status' => 'error',
        'message' => $e->errors()
      ], 422);
    } catch (\Exception $e) {
      return response()->json([
        'status' => 'error',
        'message' => $e->getMessage()
      ], 500);
    }
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(string $slug)
  {
    $campaign = Campaign::where('slug', $slug)->first();

    if (!$campaign) {
      return response()->json([
        'status' => 'error',
        'message' => 'Campaign not found with the given id'
      ], 404);
    }

    try {
      DB::beginTransaction();

      $image_folder = "image/campaign";

      if ($campaign->image) {
        $this->imageService->deleteFile($image_folder, $campaign->image);
      }

      $campaign->categories()->detach();
      $campaign->delete();

      DB::commit();

      return response()->json([
        'status' => 'success',
        'message' => 'Delete campaign success',
        'data' => $campaign,
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'status' => 'error',
        'message' => $e->getMessage()
      ], 500);
    }
  }
}
