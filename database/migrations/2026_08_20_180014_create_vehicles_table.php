<?php

use App\Enums\VehicleStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('plate', 15)->unique();
            $table->string('brand', 60);
            $table->string('model', 60);
            $table->unsignedSmallInteger('year')->nullable();
            $table->unsignedTinyInteger('capacity')->default(4);
            $table->string('status', 20)->default(VehicleStatus::Disponible->value);
            $table->dateTime('maintenance_start_at')->nullable();
            $table->dateTime('maintenance_end_at')->nullable();
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
