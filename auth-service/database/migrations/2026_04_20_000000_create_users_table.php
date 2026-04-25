<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crear la tabla users para autenticación con Google OAuth.
     * Incluye campos de perfil académico completados en el primer acceso (RF-01).
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('google_id', 100)->unique();
            $table->string('email', 100)->unique();           // solo @virtual.upt.pe
            $table->string('name', 150)->nullable();          // nombre de Google (temporal)
            $table->string('avatar_url', 500)->nullable();    // foto de perfil (actualizable)

            // Datos del formulario de primer acceso (RF-01)
            $table->string('full_name', 150)->nullable();
            $table->enum('user_type', ['student', 'teacher'])->default('student');
            $table->string('faculty', 150)->nullable();
            $table->string('career', 150)->nullable();
            $table->string('academic_cycle', 20)->nullable(); // ej: "2026-I"
            $table->string('student_code', 20)->nullable();
            $table->text('bio')->nullable();                  // descripción del perfil (RF-06)

            $table->enum('role', ['user', 'admin'])->default('user');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_profile_complete')->default(false); // true al completar primer acceso
            $table->timestamps();
        });
    }

    /**
     * Eliminar la tabla users.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
