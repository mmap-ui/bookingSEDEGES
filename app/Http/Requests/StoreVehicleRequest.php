<?php

namespace App\Http\Requests;

use App\Enums\VehicleStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('crear vehiculos') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return self::baseRules();
    }

    /**
     * Shared rules reused by the update request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public static function baseRules(): array
    {
        return [
            'plate' => ['required', 'string', 'max:15', 'unique:vehicles,plate'],
            'brand' => ['required', 'string', 'max:60'],
            'model' => ['required', 'string', 'max:60'],
            'year' => ['nullable', 'integer', 'between:1990,2099'],
            'capacity' => ['required', 'integer', 'between:1,60'],
            'status' => ['required', Rule::enum(VehicleStatus::class)],
            'driver_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
