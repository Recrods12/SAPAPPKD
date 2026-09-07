<?php

namespace Tests\Unit;

use App\Services\GeolocationService;
use Tests\TestCase;

class GeolocationServiceTest extends TestCase
{
    public function test_haversine_returns_zero_for_identical_coordinates(): void
    {
        $distance = app(GeolocationService::class)->distanceInMeters(-6.1234567, 106.1234567, -6.1234567, 106.1234567);

        $this->assertSame(0.0, $distance);
    }

    public function test_haversine_uses_controlled_coordinates_and_is_symmetric(): void
    {
        $service = app(GeolocationService::class);
        $forward = $service->distanceInMeters(0, 0, 0, 1);
        $reverse = $service->distanceInMeters(0, 1, 0, 0);

        $this->assertEqualsWithDelta(111194.93, $forward, 0.1);
        $this->assertSame($forward, $reverse);
    }
}
