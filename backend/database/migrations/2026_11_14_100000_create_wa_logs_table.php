<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_logs', function (Blueprint $table) {
            $table->id();
            $table->string('recipient_phone');
            $table->string('recipient_name')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type')->default('PENGUMUMAN'); // ALPHA, IZIN, OTP, PENGUMUMAN
            $table->text('message');
            $table->string('status')->default('TERKIRIM'); // TERKIRIM, GAGAL, PENDING
            $table->text('response_payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_logs');
    }
};
