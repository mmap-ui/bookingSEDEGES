<?php

namespace App\Models;

use App\Enums\VehicleStatus;
use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'plate',
        'brand',
        'model',
        'year',
        'capacity',
        'status',
        'driver_id',
        'maintenance_start_at',
        'maintenance_end_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    // Automatización de campos de auditoría mediante Boot
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });

        static::deleting(function ($model) {
            if (auth()->check() && method_exists($model, 'isForceDeleting') && !$model->isForceDeleting()) {
                $model->deleted_by = auth()->id();
                $model->save();
            }
        });
    }
    // protected static function booted(): void
    // {
    //     static::creating(function ($vehicle) {
    //         if (Auth::check()) {
    //             $vehicle->created_by = Auth::id();
    //             $vehicle->updated_by = Auth::id();
    //         }
    //     });

    //     static::updating(function ($vehicle) {
    //         if (Auth::check()) {
    //             $vehicle->updated_by = Auth::id();
    //         }
    //     });

    //     // static::deleting(function ($vehicle) {
    //     //     // Se ejecuta al hacer $vehicle->delete() (Soft Delete)
    //     //     if (Auth::check() && ! $vehicle->isForceDeleting()) {
    //     //         $vehicle->deleted_by = Auth::id();
    //     //         $vehicle->saveQuietly(); // Guarda el usuario sin disparar nuevamente el evento update
    //     //     }
    //     // });

    //     static::deleting(function ($vehicle) {
    //         if (auth()->check() && method_exists($vehicle, 'isForceDeleting') && !$vehicle->isForceDeleting()) {
    //             $vehicle->deleted_by = auth()->id();
    //             $vehicle->save();
    //         }
    //     });
    // }

    protected function casts(): array
    {
        return [
            'status' => VehicleStatus::class,
            'year' => 'integer',
            'capacity' => 'integer',
            'maintenance_start_at' => 'datetime',
            'maintenance_end_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function scopeAvailable($query): void
    {
        $query->where('status', VehicleStatus::Disponible);
    }

    // Relaciones de auditoría
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
