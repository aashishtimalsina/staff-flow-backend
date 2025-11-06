<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreClientRequest;
use App\Http\Resources\Api\V1\ClientResource;
use App\Models\Client;
use App\Models\RateCard;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Client::query();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $perPage = $request->input('per_page', 15);
        $clients = $query->orderBy('name')->paginate($perPage);

        return $this->successResponse([
            'data' => ClientResource::collection($clients),
            'pagination' => [
                'current_page' => $clients->currentPage(),
                'per_page' => $clients->perPage(),
                'total' => $clients->total(),
                'last_page' => $clients->lastPage(),
            ]
        ]);
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        $client = Client::create($request->validated());

        return $this->successResponse(
            new ClientResource($client),
            'Client created successfully',
            201
        );
    }

    public function show(Client $client): JsonResponse
    {
        return $this->successResponse(new ClientResource($client));
    }

    public function update(Request $request, Client $client): JsonResponse
    {
        if (!$request->user()->canManageBookings()) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $client->update($request->all());

        return $this->successResponse(
            new ClientResource($client),
            'Client updated successfully'
        );
    }

    public function destroy(Request $request, Client $client): JsonResponse
    {
        if (!$request->user()->canManageUsers()) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $client->delete();

        return $this->successResponse(null, 'Client deleted successfully');
    }

    public function getRateCards(Client $client): JsonResponse
    {
        $rateCards = $client->rateCards()->with('jobRole')->get();

        return $this->successResponse($rateCards);
    }

    public function createRateCard(Request $request, Client $client): JsonResponse
    {
        if (!$request->user()->canManageFinance()) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $request->validate([
            'job_role_id' => 'required|exists:job_roles,id',
            'client_day_rate' => 'required|numeric|min:0',
            'client_night_rate' => 'required|numeric|min:0',
            'client_weekend_rate' => 'required|numeric|min:0',
            'client_bank_holiday_rate' => 'required|numeric|min:0',
            'worker_day_rate' => 'required|numeric|min:0',
            'worker_night_rate' => 'required|numeric|min:0',
            'worker_weekend_rate' => 'required|numeric|min:0',
            'worker_bank_holiday_rate' => 'required|numeric|min:0',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
        ]);

        $rateCard = $client->rateCards()->create($request->all());

        return $this->successResponse($rateCard, 'Rate card created successfully', 201);
    }

    public function getApplicableRate(Request $request, Client $client): JsonResponse
    {
        $request->validate([
            'job_role_id' => 'required|exists:job_roles,id',
            'date' => 'required|date',
        ]);

        $rateCard = $client->getApplicableRateCard(
            $request->job_role_id,
            $request->date
        );

        if (!$rateCard) {
            return $this->errorResponse('No applicable rate card found', 404);
        }

        return $this->successResponse($rateCard);
    }
}
