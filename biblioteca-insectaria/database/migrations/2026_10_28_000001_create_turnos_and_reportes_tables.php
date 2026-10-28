<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('turno_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->unsignedTinyInteger('volunteers_per_day')->default(2);
            $table->dateTime('availability_closes_at')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['year', 'month']);
        });

        Schema::create('turno_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turno_schedule_id')->constrained('turno_schedules')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('weekdays');
            $table->json('date_overrides');
            $table->timestamps();
            $table->unique(['turno_schedule_id', 'user_id']);
        });

        Schema::create('turno_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turno_schedule_id')->constrained('turno_schedules')->cascadeOnDelete();
            $table->date('date');
            $table->boolean('is_open')->default(true);
            $table->timestamps();
            $table->unique(['turno_schedule_id', 'date']);
            $table->index(['date', 'is_open']);
        });

        Schema::create('turno_asignaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turno_dia_id')->constrained('turno_days')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['turno_dia_id', 'user_id']);
        });

        Schema::create('turno_actividades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turno_schedule_id')->constrained('turno_schedules')->cascadeOnDelete();
            $table->date('date');
            $table->string('title', 160);
            $table->text('description');
            $table->text('convocatoria')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['turno_schedule_id', 'date']);
        });

        Schema::create('volunteer_reports', function (Blueprint $table) {
            $table->id();
            $table->string('title', 160);
            $table->string('category', 80);
            $table->text('description');
            $table->string('status')->default('open');
            $table->foreignId('reported_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('volunteer_reports');
        Schema::dropIfExists('turno_actividades');
        Schema::dropIfExists('turno_asignaciones');
        Schema::dropIfExists('turno_days');
        Schema::dropIfExists('turno_availabilities');
        Schema::dropIfExists('turno_schedules');
    }
};
