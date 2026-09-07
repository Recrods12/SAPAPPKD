<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProgressiveWebAppTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_manifest_contains_installability_metadata_and_valid_png_icons(): void
    {
        $manifestPath = public_path('manifest.webmanifest');
        $manifest = json_decode((string) file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('SAPA PPKD Jakarta Barat', $manifest['name']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('/', $manifest['start_url']);
        $this->assertSame('id-ID', $manifest['lang']);
        $this->assertCount(2, $manifest['icons']);

        foreach ([[192, 'sapa-192.png'], [512, 'sapa-512.png']] as [$expectedSize, $fileName]) {
            $path = public_path('icons/'.$fileName);
            $this->assertFileExists($path);
            $dimensions = getimagesize($path);
            $this->assertIsArray($dimensions);
            $this->assertSame($expectedSize, $dimensions[0]);
            $this->assertSame($expectedSize, $dimensions[1]);
            $this->assertSame('image/png', $dimensions['mime']);
        }
    }

    public function test_service_worker_only_falls_back_for_get_navigations_and_never_queues_attendance(): void
    {
        $serviceWorker = (string) file_get_contents(public_path('sw.js'));

        $this->assertStringContainsString('event.request.method !== "GET"', $serviceWorker);
        $this->assertStringContainsString('event.request.mode !== "navigate"', $serviceWorker);
        $this->assertStringContainsString('caches.match(OFFLINE)', $serviceWorker);
        $this->assertStringContainsString('/pwa/manifest.webmanifest', $serviceWorker);
        $this->assertStringContainsString('/pwa/icon/512', $serviceWorker);
        $this->assertStringNotContainsString('sync', $serviceWorker);
        $this->assertStringNotContainsString('POST', $serviceWorker);
    }

    public function test_offline_page_explicitly_says_attendance_was_not_sent(): void
    {
        $offlinePage = (string) file_get_contents(public_path('offline.html'));

        $this->assertStringContainsString('Absensi tidak dapat dikirim secara offline', $offlinePage);
        $this->assertStringContainsString('Coba lagi', $offlinePage);
    }

    public function test_application_shell_declares_manifest_and_ios_install_metadata(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('rel="manifest" href="http://localhost:8000/pwa/manifest.webmanifest"', false)
            ->assertSee('rel="apple-touch-icon" href="http://localhost:8000/pwa/icon/192"', false)
            ->assertSee('viewport-fit=cover', false);
    }

    public function test_dynamic_manifest_and_icons_follow_admin_branding(): void
    {
        Setting::query()->create(['key' => 'app_name', 'value' => 'Presensi Pelatihan']);
        Setting::query()->create(['key' => 'institution_name', 'value' => 'Unit Pelatihan Barat']);

        $this->get(route('pwa.manifest'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json')
            ->assertJsonPath('name', 'Presensi Pelatihan Unit Pelatihan Barat')
            ->assertJsonPath('icons.1.src', '/pwa/icon/512');

        $this->get(route('pwa.icon', 192))->assertOk()->assertHeader('Content-Type', 'image/png');

        Storage::fake('local');
        $logo = UploadedFile::fake()->image('logo.png', 320, 160);
        $path = $logo->storeAs('branding', 'logo.png', 'local');
        Setting::query()->updateOrCreate(['key' => 'logo_path'], ['value' => $path]);

        $branded = $this->get(route('pwa.icon', 512))->assertOk()->getContent();
        $this->assertSame([512, 512], array_slice(getimagesizefromstring($branded), 0, 2));
        $this->assertNotSame(file_get_contents(public_path('icons/sapa-512.png')), $branded);
    }
}
