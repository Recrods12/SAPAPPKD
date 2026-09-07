<?php

namespace App\Exports;

use App\Models\FraudFlag;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class FraudFlagReportExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private readonly array $filters) {}

    public function query(): Builder
    {
        return FraudFlag::query()->with(['user.participantProfile', 'attendance.attendanceLocation', 'attendance.trainingSchedule.trainingClass.trainingBatch'])
            ->when($this->filters['start_date'] ?? null, fn ($query, $date) => $query->whereHas('attendance', fn ($attendance) => $attendance->whereDate('attendance_date', '>=', $date)))
            ->when($this->filters['end_date'] ?? null, fn ($query, $date) => $query->whereHas('attendance', fn ($attendance) => $attendance->whereDate('attendance_date', '<=', $date)))
            ->when($this->filters['participant_id'] ?? null, fn ($query, $id) => $query->where('user_id', $id))
            ->when($this->filters['class_id'] ?? null, fn ($query, $id) => $query->whereHas('attendance.trainingSchedule', fn ($schedule) => $schedule->where('training_class_id', $id)))
            ->when($this->filters['batch_id'] ?? null, fn ($query, $id) => $query->whereHas('attendance.trainingSchedule.trainingClass', fn ($class) => $class->where('training_batch_id', $id)))
            ->when($this->filters['program_id'] ?? null, fn ($query, $id) => $query->whereHas('attendance.trainingSchedule.trainingClass.trainingBatch', fn ($batch) => $batch->where('training_program_id', $id)))
            ->orderBy('created_at')->orderBy('id');
    }

    public function headings(): array
    {
        return ['Tanggal', 'Nomor Peserta', 'Nama', 'Kelas', 'Lokasi', 'Jarak (m)', 'Akurasi (m)', 'Alasan', 'Tingkat', 'Status'];
    }

    public function map(mixed $row): array
    {
        return [$row->attendance?->attendance_date, $row->user->participantProfile?->participant_number, $row->user->name, $row->attendance?->trainingSchedule?->trainingClass?->name, $row->attendance?->attendanceLocation?->name, $row->attendance?->distance_meters, $row->attendance?->accuracy_meters, $row->reason, $row->severity, $row->status];
    }

    public function title(): string
    {
        return 'Lokasi dan Fraud';
    }
}
