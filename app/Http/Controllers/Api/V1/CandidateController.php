<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCandidateRequest;
use App\Http\Resources\Api\V1\CandidateResource;
use App\Models\Candidate;
use App\Models\CandidateCompliance;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class CandidateController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of candidates
     */
    public function index(Request $request): JsonResponse
    {
        $query = Candidate::with(['user', 'jobRole']);

        // Filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('job_role_id')) {
            $query->where('job_role_id', $request->job_role_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            });
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $candidates = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return $this->successResponse([
            'data' => CandidateResource::collection($candidates),
            'pagination' => [
                'current_page' => $candidates->currentPage(),
                'per_page' => $candidates->perPage(),
                'total' => $candidates->total(),
                'last_page' => $candidates->lastPage(),
            ]
        ]);
    }

    /**
     * Store a newly created candidate
     */
    public function store(StoreCandidateRequest $request): JsonResponse
    {
        if (!$request->user()->canManageBookings()) {
            return $this->errorResponse('Unauthorized', 403);
        }

        DB::beginTransaction();
        try {
            // Create user account for candidate
            $user = User::create([
                'name' => $request->first_name . ' ' . $request->last_name,
                'email' => $request->email,
                'password' => Hash::make('password123'), // Default password
                'role' => 'worker',
                'phone' => $request->phone,
                'is_active' => true,
            ]);

            // Create candidate profile
            $candidate = Candidate::create(array_merge(
                $request->validated(),
                ['user_id' => $user->id]
            ));

            // Auto-create compliance documents based on job role
            $complianceDocuments = $candidate->jobRole->complianceDocuments;
            foreach ($complianceDocuments as $doc) {
                CandidateCompliance::create([
                    'candidate_id' => $candidate->id,
                    'compliance_document_id' => $doc->id,
                    'status' => 'Pending',
                ]);
            }

            DB::commit();

            return $this->successResponse(
                new CandidateResource($candidate->load(['user', 'jobRole'])),
                'Candidate created successfully',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to create candidate: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified candidate
     */
    public function show(Candidate $candidate): JsonResponse
    {
        return $this->successResponse(
            new CandidateResource($candidate->load(['user', 'jobRole', 'complianceDocuments']))
        );
    }

    /**
     * Update the specified candidate
     */
    public function update(Request $request, Candidate $candidate): JsonResponse
    {
        if (!$request->user()->canManageBookings()) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $candidate->update($request->all());

        return $this->successResponse(
            new CandidateResource($candidate->load(['user', 'jobRole'])),
            'Candidate updated successfully'
        );
    }

    /**
     * Remove the specified candidate
     */
    public function destroy(Request $request, Candidate $candidate): JsonResponse
    {
        if (!$request->user()->canManageUsers()) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $candidate->delete();
        $candidate->user->delete();

        return $this->successResponse(null, 'Candidate deleted successfully');
    }

    /**
     * Get candidate compliance documents
     */
    public function getCompliance(Candidate $candidate): JsonResponse
    {
        $compliance = $candidate->complianceDocuments()
            ->with('complianceDocument')
            ->get();

        return $this->successResponse($compliance);
    }

    /**
     * Upload compliance document
     */
    public function uploadCompliance(Request $request, Candidate $candidate, CandidateCompliance $compliance): JsonResponse
    {
        if (!$request->user()->canManageCompliance()) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'expiry_date' => 'nullable|date|after:today',
        ]);

        $file = $request->file('file');
        $path = $file->store('compliance/' . $candidate->id, 'public');

        $compliance->update([
            'file_path' => $path,
            'file_url' => Storage::url($path),
            'uploaded_at' => now(),
            'expiry_date' => $request->expiry_date,
            'status' => 'Pending',
        ]);

        return $this->successResponse($compliance, 'Document uploaded successfully');
    }

    /**
     * Update compliance status
     */
    public function updateCompliance(Request $request, Candidate $candidate, CandidateCompliance $compliance): JsonResponse
    {
        if (!$request->user()->canManageCompliance()) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $request->validate([
            'status' => 'required|in:Pending,Approved,Rejected,Expired',
            'expiry_date' => 'nullable|date',
        ]);

        $compliance->update($request->only(['status', 'expiry_date']));

        return $this->successResponse($compliance, 'Compliance status updated successfully');
    }
}
