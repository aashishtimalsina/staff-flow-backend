<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\JobRoleResource;
use App\Models\JobRole;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobRoleController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $jobRoles = JobRole::with('complianceDocuments')->where('is_active', true)->get();

        return $this->successResponse(JobRoleResource::collection($jobRoles));
    }

    public function store(Request $request): JsonResponse
    {
        if (!$request->user()->canManageCompliance()) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $jobRole = JobRole::create($request->all());

        return $this->successResponse(
            new JobRoleResource($jobRole),
            'Job role created successfully',
            201
        );
    }

    public function show(JobRole $jobRole): JsonResponse
    {
        return $this->successResponse(
            new JobRoleResource($jobRole->load('complianceDocuments'))
        );
    }

    public function update(Request $request, JobRole $jobRole): JsonResponse
    {
        if (!$request->user()->canManageCompliance()) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $jobRole->update($request->all());

        return $this->successResponse(
            new JobRoleResource($jobRole),
            'Job role updated successfully'
        );
    }

    public function destroy(Request $request, JobRole $jobRole): JsonResponse
    {
        if (!$request->user()->canManageUsers()) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $jobRole->delete();

        return $this->successResponse(null, 'Job role deleted successfully');
    }
}
