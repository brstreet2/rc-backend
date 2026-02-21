<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_populates_audios_and_promotions(): void
    {
        $this->seed();

        $audioCount = DB::table('audio')->count();
        $promotionCount = DB::table('promotions')->count();

        $this->assertGreaterThanOrEqual(20, $audioCount);
        $this->assertGreaterThanOrEqual(60, $promotionCount);
    }
}
