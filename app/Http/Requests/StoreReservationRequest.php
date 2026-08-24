<?php

namespace App\Http\Requests;

use App\Rules\NoApprovedReservationOverlap;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('crear reservas') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'vehicle_id' => [
                'required',
                'integer',
                'exists:vehicles,id',
                new NoApprovedReservationOverlap(
                    vehicleId: $this->input('vehicle_id'),
                    startAt: $this->input('start_at'),
                    endAt: $this->input('end_at'),
                ),
            ],
            'start_at' => ['required', 'date', 'after_or_equal:today'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'motive' => ['required', 'string', 'max:255'],
            'destination' => ['nullable', 'string', 'max:255'],
        ];
    }
}
