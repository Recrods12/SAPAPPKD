<?php

namespace Tests\Feature;

use App\Exports\ParticipantTemplateExport;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ParticipantImportTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_download_template_and_preview_rows_without_saving(): void
    {
        Role::findOrCreate('super-admin');
        $admin = User::factory()->create(['account_status' => 'active']);
        $admin->assignRole('super-admin');

        $this->actingAs($admin)->get(route('admin.imports.participants.template'))->assertOk();
        $contents = Excel::raw(new ParticipantTemplateExport, ExcelWriter::XLSX);
        $file = UploadedFile::fake()->createWithContent('peserta.xlsx', $contents);
        $this->actingAs($admin)->post(route('admin.imports.participants.preview'), ['file' => $file])->assertSessionHasNoErrors()->assertSessionHas('participant_import_preview');
        $this->assertDatabaseCount('participant_profiles', 0);
    }
}
