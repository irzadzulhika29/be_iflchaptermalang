<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\Role;

class RoleController extends Controller
{
  /**
   * Display a listing of the resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function index()
  {
    $roles = Role::all();

    $latest_update = Role::latest()->value('updated_at');

    try {
      return response()->json([
        'status' => 'success',
        'message' => 'Get all role success',
        'data' => [
          'latest_update' => $latest_update,
          'roles' => $roles
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
      'name' => ['required', 'string', 'unique:roles'],
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
      $role = Role::create($data);

      return response()->json([
        'status' => 'success',
        'message' => 'Create role success',
        'data' => $role,
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
   * @param  \App\Models\Role  $role
   * @return \Illuminate\Http\Response
   */
  public function show(string $id)
  {
    $role = Role::find($id);

    if (!$role) {
      return response()->json([
        'status' => 'error',
        'message' => 'Role not found with the given id',
      ], 404);
    }

    try {
      return response()->json([
        'status' => 'success',
        'message' => 'Get role by id success',
        'data' => $role,
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
   * @param  \App\Models\Role  $role
   * @return \Illuminate\Http\Response
   */
  public function edit(Role $role)
  {
    //
  }

  /**
   * Update the specified resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \App\Models\Role  $role
   * @return \Illuminate\Http\Response
   */
  public function update(Request $request, string $id)
  {
    $role = Role::find($id);

    if (!$role) {
      return response()->json([
        'status' => 'error',
        'message' => 'Role not found with the given id',
      ], 404);
    }

    $data = $request->only('name', 'description');
    $rule = [
      'name' => ['nullable', 'string', 'unique:roles,name,' . $role->id],
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
      $role->update([
        'name' => $data['name'] ?? $role->name,
        'description' => $data['description'] ?? $role->description,
      ]);

      return response()->json([
        'status' => 'success',
        'message' => 'Update role by id success',
        'data' => $role,
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
   * @param  \App\Models\Role  $role
   * @return \Illuminate\Http\Response
   */
  public function destroy(string $id)
  {
    $role = Role::find($id);

    if (!$role) {
      return response()->json([
        'status' => 'error',
        'message' => 'Role not found with the given id',
      ], 404);
    }

    try {
      $role->delete();

      return response()->json([
        'status' => 'success',
        'message' => 'Delete role by id success',
        'data' => $role,
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'status' => 'error',
        'message' => $e->getMessage(),
      ], 500);
    }
  }
}
