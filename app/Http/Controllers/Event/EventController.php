<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\Sdg;

class EventController extends Controller
{

    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    public function index()
    {
        try {
            $events = Event::with(['sdgs' => function ($query) {
                $query->select('sdgs.id', 'sdgs.name', 'sdgs.code', 'sdgs.sort_order');
            }])->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Get all events success',
                'data' => $events
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $data = $request->only([
            'title',
            'status',
            'category',
            'start_date',
            'end_date',
            'description',
            'event_activity',
            'event_photo',
            'participant',
            'committee',
            'sdgs'
        ]);


        $rule = [
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:open,closed'],
            'category' => ['required', 'in:program,project'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'description' => ['required', 'string'],
            'event_activity' => ['required', 'string'],
            'event_photo' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
            'participant' => ['required', 'integer'],
            'committee' => ['required', 'integer'],
            'sdgs' => ['nullable', 'array'],
            'sdgs.*' => ['uuid', 'exists:sdgs,id'],
        ];

        $validator = Validator::make($data, $rule);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'error' => $validator->messages()
            ], 422);
        }

        try {
            DB::beginTransaction();

            if ($request->hasFile('event_photo')) {
                $image_folder = "image/event";
                $image_file = $request->file('event_photo');
                $image_file_name = Str::slug($data['title']) . '-' . time();
                $image_tags = ['event', $data['category']];

                $image_url = $this->imageService->uploadFile(
                    $image_folder,
                    $image_file,
                    $image_file_name,
                    $image_tags
                );
                $data['event_photo'] = $image_url;
            }

            $event = Event::create($data);

            if (isset($data['sdgs']) && !empty($data['sdgs'])) {
                $sdgIds = $data['sdgs'];
                $uniqueSdgIds = array_unique($sdgIds);
                $existingSdgs = Sdg::whereIn('id', $sdgIds)->get();

                // Validate unique SDG ids
                if (count($uniqueSdgIds) !== count($sdgIds)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Duplicate SDG IDs found in the input',
                    ], 422);
                }

                // Validate all SDGs exist
                if ($existingSdgs->count() !== count($sdgIds)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'One or more SDGs not found with the given ID',
                    ], 422);
                }

                $event->sdgs()->sync($data['sdgs']);
            }

            $event->load('sdgs:id,name,code,sort_order');

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Event created successfully',
                'data' => $event,
            ], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->errors()
            ], 422);
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
        $event =  Event::with(['sdgs' => function ($query) {
            $query->select('sdgs.id', 'sdgs.name', 'sdgs.code', 'sdgs.sort_order');
        }])->find($id);

        if (!$event) {
            return response()->json([
                'status' => 'error',
                'message' => 'Event not found with the given id',
            ], 404);
        }

        try {
            return response()->json([
                'status' => 'success',
                'message' => 'Get event by id success',
                'data' => $event,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, string $id)
    {
        $event = Event::find($id);

        if (!$event) {
            return response()->json([
                'status' => 'error',
                'message' => 'Event not found with the given id',
            ], 404);
        }

        $data = $request->only([
            'title',
            'status',
            'category',
            'start_date',
            'end_date',
            'description',
            'event_activity',
            'event_photo',
            'participant',
            'committee',
            'sdgs'
        ]);

        $rule = [
            'title' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', 'in:open,closed'],
            'category' => ['sometimes', 'in:program,project'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'description' => ['sometimes', 'string'],
            'event_activity' => ['sometimes', 'string'],
            'event_photo' => ['sometimes', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
            'participant' => ['sometimes', 'integer'],
            'committee' => ['sometimes', 'integer'],
            'sdgs' => ['nullable', 'array'],
            'sdgs.*' => ['uuid', 'exists:sdgs,id'],
        ];

        $validator = Validator::make($data, $rule);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'error' => $validator->messages()
            ], 422);
        }

        try {
            DB::beginTransaction();

            if ($request->hasFile('event_photo')) {
                $image_folder = "image/event";
                $image_file = $request->file('event_photo');
                $image_file_name = Str::slug($data['title'] ?? $event->title) . '-' . time();
                $image_tags = ['event', $data['category'] ?? $event->category];

                $image_url = $this->imageService->uploadFile(
                    $image_folder,
                    $image_file,
                    $image_file_name,
                    $image_tags
                );
                $data['event_photo'] = $image_url;
            }

            $event->update($data);

            if (isset($data['sdgs'])) {
                if (empty($data['sdgs'])) {
                    $event->sdgs()->detach();
                } else {
                    $sdgIds = $data['sdgs'];
                    $uniqueSdgIds = array_unique($sdgIds);
                    $existingSdgs = Sdg::whereIn('id', $sdgIds)->get();

                    if (count($uniqueSdgIds) !== count($sdgIds)) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Duplicate SDG IDs found in the input',
                        ], 422);
                    }

                    if ($existingSdgs->count() !== count($sdgIds)) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'One or more SDGs not found with the given ID',
                        ], 422);
                    }

                    $event->sdgs()->sync($data['sdgs']);
                }
            }

            $event->load('sdgs:id,name,code,sort_order');

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Event updated successfully',
                'data' => $event,
            ], 200);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        $event = Event::find($id);

        if (!$event) {
            return response()->json([
                'status' => 'error',
                'message' => 'Event not found with the given id',
            ], 404);
        }

        try {
            DB::beginTransaction();

            $event->sdgs()->detach();
            $event->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Event deleted successfully',
                'data' => $event,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getAvailableEventsForVolunteerRegistration()
    {
        try {
            $events = Event::where('status', 'open')
                ->select('id', 'title', 'start_date', 'end_date', 'description', 'event_photo')
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Get available events for volunteer registration success',
                'data' => $events
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
