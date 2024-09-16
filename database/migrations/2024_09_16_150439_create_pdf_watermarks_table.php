<?php

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
        Schema::create('pdf_watermarks', static function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->text('pdf_url');
            $table->text('watermark_url');
            $table->string('watermark_position')->nullable();
            $table->float('watermark_x')->nullable();
            $table->float('watermark_y')->nullable();
            $table->boolean('watermark_background')->nullable();
            $table->text('pdf_final')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedTinyInteger('status')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->foreignIdFor(\App\Models\User::class);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdf_watermarks');
    }
};
