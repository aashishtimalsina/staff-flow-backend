<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreBookingRequestRequest;
use App\Http\Resources\Api\V1\BookingRequestResource;
use App\Models\BookingRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingRequestController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = BookingRequest::with(['client', 'jobRole', 'assignments']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->has('job_role_id')) {
            $query->where('job_role_id', $request->job_role_id);
        }

        if ($request->has('start_date')) {
            $query->where('shift_start_time', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('shift_end_time', '<=', $request->end_date);
        }

        $perPage = $request->input('per_page', 15);
        $bookings = $query->orderBy('shift_start_time', 'desc')->paginate($perPage);

        return $this->successResponse([
            'data' => BookingRequestResource::collection($bookings),
            'pagination' => [
                'current_page' => $bookings->currentPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
                'last_page' => $bookings->lastPage(),
            ]
        ]);
    }

    public function store(StoreBookingRequestRequest $request): JsonResponse
    {
        $booking = BookingRequest::create(array_merge(
            $request->validated(),
            ['created_by' => $request->user()->id]
        ));

        return $this->successResponse(
            new BookingRequestResource($booking->load(['client', 'jobRole'])),
            'Booking created successfully',
            201
        );
    }

    public function show(BookingRequest $booking): JsonResponse
    {
        return $this->successResponse(
            new BookingRequestResource($booking->load(['client', 'jobRole', 'assignments.candidate']))
        );
    }

    public function update(Request $request, BookingRequest $booking): JsonResponse
    {
        if (!$request->user()->canManageBookings()) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $booking->update($request->all());

        return $this->successResponse(
            new BookingRequestResource($booking->load(['client', 'jobRole'])),
            'Booking updated successfully'
        );
    }

    public function cancel(Request $request, BookingRequest $booking): JsonResponse
    {
        if (!$request->user()->canManageBookings()) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $booking->update(['status' => 'Cancelled']);

        return $this->successResponse(
            new BookingRequestResource($booking),
            'Booking cancelled successfully'
        );
    }
}
