<?php

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\BlogCategories;
use Illuminate\Http\Request;

class BlogCategoriesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
      $categories = BlogCategories::all();

      $latest_update = BlogCategories::latest()->value('updated_at');

      try {
        return response()->json([
          'status' => 'success',
          'message' => 'Get all blog categories success',
          'data' => [
            'latest_update' => $latest_update,
            'categories' => $categories
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
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
      $data = $request->only('name', 'description');
      $rule = [
        'name' => ['required', 'string', 'unique:blog_categories'],
        'description' => ['nullable', 'string'],
      ];

      $validator = Validator::make($data, $rule);

      if ($validator->fails()) {
        return response()->json([
          'status' => 'error',
          'error' => $validator->messages(),
        ], 422);
      }

      try {
        $blog_category = BlogCategories::create($data);
  
        return response()->json([
          'status' => 'success',
          'message' => 'Create blog category success',
          'data' => $blog_category,
        ], 201);
      } catch (ValidationException $e) {
        return response()->json([
          'status' => 'error',
          'message' => $e->errors()
        ], 422);
      } catch (\Exception $e) {
        return response()->json([
          'status' => 'error',
          'message' => $e->getMessage(),
        ], 500);
      }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(string $id)
    {
      $category = BlogCategories::find($id);

      if (!$category) {
        return response()->json([
          'status' => 'error',
          'message' => 'Blog category not found with the given id',
        ], 404);
      }

      try {
        return response()->json([
          'status' => 'success',
          'message' => 'Get blog categories by id success',
          'data' => $category,
        ], 200);
      } catch (\Exception $e) {
        return response()->json([
          'status' => 'error',
          'message' => $e->getMessage(),
        ], 500);
      }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, string $id)
    {
      $category = BlogCategories::find($id);

      if (!$category) {
        return response()->json([
          'status' => 'error',
          'message' => 'Blog category not found with the given id',
        ], 404);
      }

      $data = $request->only('name', 'description');
      $rule = [
        'name' => ['nullable', 'string', 'unique:blog_categories,name,' . $category->id],
        'description' => ['nullable', 'string'],
      ];

      $validator = Validator::make($data, $rule);

      if ($validator->fails()) {
        return response()->json([
          'status' => 'error',
          'error' => $validator->messages(),
        ], 422);
      }

      try {
        $category->update([
          'name' => $data['name'] ?? $category->name,
          'description' => $data['description'] ?? $category->description,
        ]);

        return response()->json([
          'status' => 'success',
          'message' => 'Update blog category success',
          'data' => $category,
        ], 200);
      } catch (ValidationException $e) {
        return response()->json([
          'status' => 'error',
          'message' => $e->errors()
        ], 422);
      } catch (\Exception $e) {
        return response()->json([
          'status' => 'error',
          'message' => $e->getMessage(),
        ], 500);
      }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $id)
    {
      $category = BlogCategories::find($id);
      
      if (!$category) {
        return response()->json([
          'status' => 'error',
          'message' => 'Blog category not found with the given id',
        ], 404);
      }
      
      try {
        $category->delete();
  
        return response()->json([
            'status' => 'success',
            'message' => 'Delete blog category success',
            'data' => $category
        ], 200);
      } catch (\Exception $e) {
        return response()->json([
          'status' => 'error',
          'message' => $e->getMessage(),
        ], 500);
      }
    }
}
