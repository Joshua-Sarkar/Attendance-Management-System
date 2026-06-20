<?php

namespace Tests\Feature;

use Tests\TestCase;
use Carbon\Carbon;

class TimezoneTest extends TestCase
{
    /**
     * Test that application timezone is correctly set to Asia/Kolkata.
     */
    public function test_application_timezone_is_kolkata(): void
    {
        $this->assertEquals('Asia/Kolkata', config('app.timezone'));
        $this->assertEquals('Asia/Kolkata', now()->timezoneName);
        $this->assertEquals('Asia/Kolkata', Carbon::now()->timezoneName);
    }
}
