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
        Schema::create('audio', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->unsignedBigInteger('network_id')->nullable();
            $table->string('mformat')->nullable();
            $table->unsignedBigInteger('channel_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['network_id', 'mformat', 'channel_id'], 'audio_scope_idx');
            $table->index('deleted_at', 'audio_deleted_at_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audio');
    }
};
