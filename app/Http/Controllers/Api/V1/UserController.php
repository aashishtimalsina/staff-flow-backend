<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreUserRequest;
use App\Http\Requests\Api\V1\UpdateUserRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of users
     */
    public function index(Request $request): JsonResponse
    {
        // Authorization check
        if (!$request->user()->canManageUsers()) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $query = User::query();

        // Filters
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $users = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return $this->successResponse([
            'data' => UserResource::collection($users),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
            ]
        ]);
    }

    /**
     * Store a newly created user
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        return $this->successResponse(
            new UserResource($user),
            'User created successfully',
            201
        );
    }

    /**
     * Display the specified user
     */
    public function show(Request $request, User $user): JsonResponse
    {
        if (!$request->user()->canManageUsers()) {
            return $this->errorResponse('Unauthorized', 403);
        }

        return $this->successResponse(new UserResource($user));
    }

    /**
     * Update the specified user
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return $this->successResponse(
            new UserResource($user),
            'User updated successfully'
        );
    }

    /**
     * Remove the specified user
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        if (!$request->user()->canManageUsers()) {
            return $this->errorResponse('Unauthorized', 403);
        }

        // Prevent deleting own account
        if ($user->id === $request->user()->id) {
            return $this->errorResponse('You cannot delete your own account', 400);
        }

        // Prevent deleting superadmin
        if ($user->role === 'superadmin') {
            return $this->errorResponse('Cannot delete superadmin account', 403);
        }

        $user->delete();

        return $this->successResponse(null, 'User deleted successfully');
    }
}
