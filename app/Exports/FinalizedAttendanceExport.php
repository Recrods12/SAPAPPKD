<?php

namespace App\Exports;

use App\Models\AttendanceSummary;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class FinalizedAttendanceExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private readonly array $filters) {}

    public function query(): Builder
    {
        return AttendanceSummary::query()->with(['user.participantProfile', 'trainingSchedule.trainingClass.trainingBatch.trainingProgram'])
            ->when($this->filters['start_date'] ?? null, fn ($query, $date) => $query->whereDate('attendance_date', '>=', $date))
            ->when($this->filters['end_date'] ?? null, fn ($query, $date) => $query->whereDate('attendance_date', '<=', $date))
            ->when($this->filters['program_id'] ?? null, fn ($query, $id) => $query->whereHas('trainingSchedule.trainingClass.trainingBatch', fn ($batch) => $batch->where('training_program_id', $id)))
            ->when($this->filters['batch_id'] ?? null, fn ($query, $id) => $query->whereHas('trainingSchedule.trainingClass', fn ($class) => $class->where('training_batch_id', $id)))
            ->when($this->filters['class_id'] ?? null, fn ($query, $id) => $query->whereHas('trainingSchedule', fn ($schedule) => $schedule->where('training_class_id', $id)))
            ->when($this->filters['participant_id'] ?? null, fn ($query, $id) => $query->where('user_id', $id))
            ->when($this->filters['instructor_id'] ?? null, fn ($query, $id) => $query->whereHas('trainingSchedule.trainingClass.instructors', fn ($instructor) => $instructor->whereKey($id)))
            ->when($this->filters['final_status'] ?? null, fn ($query, $status) => $query->where('overall_status', $status))
            ->orderBy('attendance_date')->orderBy('user_id')->orderBy('id');
    }

    public function headings(): array
    {
        return ['Tanggal', 'Nomor Peserta', 'Nama', 'Program', 'Angkatan', 'Kelas', 'Status Pagi', 'Status Sore', 'Status Akhir'];
    }

    public function map(mixed $row): array
    {
        $class = $row->trainingSchedule->trainingClass;

        return [$row->attendance_date->format('d-m-Y'), $row->user->participantProfile?->participant_number, $row->user->name, $class->trainingBatch->trainingProgram->name, $class->trainingBatch->name, $class->name, $row->morning_status, $row->afternoon_status, $row->overall_status];
    }

    public function title(): string
    {
        return 'Rekap Final';
    }
}
