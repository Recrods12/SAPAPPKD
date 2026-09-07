<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ParticipantTemplateExport;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\TrainingBatch;
use App\Models\TrainingClass;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ParticipantImportController extends Controller
{
    private const HEADERS = ['nomor peserta', 'nik', 'nama lengkap', 'jenis kelamin', 'tempat lahir', 'tanggal lahir', 'alamat', 'nomor whatsapp', 'email', 'program pelatihan', 'angkatan', 'kelas', 'status'];

    public function index(Request $request): Response
    {
        return Inertia::render('admin/imports/participants', ['preview' => $request->session()->get('participant_import_preview')]);
    }

    public function template(): BinaryFileResponse
    {
        return Excel::download(new ParticipantTemplateExport, 'template-import-peserta.xlsx');
    }

    public function preview(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120']]);
        $sheets = Excel::toArray(new class implements ToArray
        {
            public function array(array $array): void {}
        }, $request->file('file'));
        $sheet = $sheets[0] ?? [];
        $headers = array_map(fn ($header) => Str::lower(trim((string) $header)), array_shift($sheet) ?? []);
        if ($headers !== self::HEADERS) {
            return back()->withErrors(['file' => 'Header file tidak sesuai template resmi. Unduh dan gunakan template terbaru.']);
        }
        $valid = [];
        $errors = [];
        $seen = ['participant_number' => [], 'nik' => [], 'email' => []];
        foreach (array_slice($sheet, 0, 1000) as $index => $values) {
            if (collect($values)->filter(fn ($value) => $value !== null && $value !== '')->isEmpty()) {
                continue;
            }
            $row = array_combine(self::HEADERS, array_pad(array_slice($values, 0, count(self::HEADERS)), count(self::HEADERS), null));
            $batch = TrainingBatch::query()->where('name', trim((string) $row['angkatan']))->whereHas('trainingProgram', fn ($query) => $query->where('name', trim((string) $row['program pelatihan'])))->first();
            $class = $batch ? TrainingClass::query()->where('training_batch_id', $batch->id)->where('name', trim((string) $row['kelas']))->first() : null;
            $data = ['participant_number' => trim((string) $row['nomor peserta']), 'nik' => trim((string) $row['nik']), 'name' => trim((string) $row['nama lengkap']), 'gender' => in_array(Str::lower(trim((string) $row['jenis kelamin'])), ['l', 'laki-laki', 'male'], true) ? 'male' : 'female', 'birth_place' => trim((string) $row['tempat lahir']), 'birth_date' => $row['tanggal lahir'], 'address' => trim((string) $row['alamat']), 'phone' => trim((string) $row['nomor whatsapp']), 'email' => Str::lower(trim((string) $row['email'])), 'account_status' => Str::lower(trim((string) $row['status'])) === 'aktif' ? 'active' : 'inactive', 'training_batch_id' => $batch?->id, 'training_class_id' => $class?->id, 'password' => Str::password(12)];
            $validator = Validator::make($data, ['participant_number' => ['required', 'max:50', 'unique:participant_profiles,participant_number'], 'nik' => ['required', 'digits_between:10,20', 'unique:participant_profiles,nik'], 'name' => ['required', 'max:255'], 'birth_place' => ['required', 'max:100'], 'birth_date' => ['required', 'date', 'before:today'], 'address' => ['required'], 'phone' => ['required', 'regex:/^(?:\+62|62|0)8[0-9]{7,13}$/'], 'email' => ['required', 'email', 'unique:users,email'], 'training_batch_id' => ['required'], 'training_class_id' => ['required']]);
            $duplicateMessages = [];
            foreach (array_keys($seen) as $uniqueField) {
                if (in_array($data[$uniqueField], $seen[$uniqueField], true)) {
                    $duplicateMessages[] = ucfirst(str_replace('_', ' ', $uniqueField)).' duplikat di dalam file.';
                }
            }
            if ($validator->fails() || $duplicateMessages !== []) {
                $errors[] = ['row' => $index + 2, 'name' => $data['name'], 'messages' => [...$validator->errors()->all(), ...$duplicateMessages]];
            } else {
                $valid[] = $data;
                foreach (array_keys($seen) as $uniqueField) {
                    $seen[$uniqueField][] = $data[$uniqueField];
                }
            }
        }
        $request->session()->put('participant_import_preview', ['valid' => $valid, 'errors' => $errors, 'total' => count($valid) + count($errors)]);

        return back()->with('status', 'File berhasil diperiksa. Konfirmasi untuk menyimpan baris yang valid.');
    }

    public function store(Request $request): RedirectResponse
    {
        $preview = $request->session()->pull('participant_import_preview');
        abort_unless(is_array($preview) && count($preview['valid'] ?? []) > 0, 422, 'Tidak ada preview import yang dapat disimpan.');
        DB::transaction(function () use ($request, $preview): void {
            foreach ($preview['valid'] as $row) {
                $user = User::query()->create(['name' => $row['name'], 'username' => $row['participant_number'], 'email' => $row['email'], 'password' => Hash::make($row['password']), 'account_status' => $row['account_status'], 'verified_at' => now(), 'verified_by' => $request->user()->id]);
                $user->assignRole('participant');
                $user->participantProfile()->create(['nik' => $row['nik'], 'participant_number' => $row['participant_number'], 'gender' => $row['gender'], 'birth_place' => $row['birth_place'], 'birth_date' => $row['birth_date'], 'address' => $row['address'], 'phone' => $row['phone'], 'participant_status' => 'active', 'privacy_accepted_at' => now()]);
                $user->enrollments()->create(['training_batch_id' => $row['training_batch_id'], 'training_class_id' => $row['training_class_id'], 'status' => 'active', 'enrolled_at' => today()]);
            }
            ActivityLog::query()->create(['user_id' => $request->user()->id, 'event' => 'participants.imported', 'properties' => ['count' => count($preview['valid'])], 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]);
        });

        return redirect()->route('admin.participants.index')->with('status', count($preview['valid']).' peserta berhasil diimport. Username menggunakan nomor peserta; kata sandi awal tersedia pada halaman preview.');
    }
}
