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
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audio_id')->constrained('audio')->cascadeOnDelete();
            $table->unsignedBigInteger('network_id')->nullable();
            $table->string('mformat')->nullable();
            $table->unsignedBigInteger('channel_id')->nullable();
            $table->integer('priority')->default(0);
            $table->integer('version')->default(1);
            $table->boolean('visible')->default(true);
            $table->timestamp('start_at');
            $table->timestamp('end_at');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['audio_id', 'visible', 'start_at', 'end_at'], 'promotions_active_lookup_idx');
            $table->index(['network_id', 'mformat', 'channel_id'], 'promotions_scope_idx');
            $table->index(['priority', 'version', 'created_at'], 'promotions_conflict_idx');
            $table->index('deleted_at', 'promotions_deleted_at_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
