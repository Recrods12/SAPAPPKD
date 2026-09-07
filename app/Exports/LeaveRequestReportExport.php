<?php

namespace App\Exports;

use App\Models\LeaveRequest;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class LeaveRequestReportExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private readonly array $filters) {}

    public function query(): Builder
    {
        return LeaveRequest::query()->with(['user.participantProfile'])
            ->when($this->filters['start_date'] ?? null, fn ($query, $date) => $query->whereDate('end_date', '>=', $date))
            ->when($this->filters['end_date'] ?? null, fn ($query, $date) => $query->whereDate('start_date', '<=', $date))
            ->when($this->filters['participant_id'] ?? null, fn ($query, $id) => $query->where('user_id', $id))
            ->when($this->filters['class_id'] ?? null, fn ($query, $id) => $query->whereHas('user.enrollments', fn ($enrollment) => $enrollment->where('training_class_id', $id)))
            ->when($this->filters['batch_id'] ?? null, fn ($query, $id) => $query->whereHas('user.enrollments', fn ($enrollment) => $enrollment->where('training_batch_id', $id)))
            ->when($this->filters['program_id'] ?? null, fn ($query, $id) => $query->whereHas('user.enrollments.trainingBatch', fn ($batch) => $batch->where('training_program_id', $id)))
            ->orderBy('start_date')->orderBy('id');
    }

    public function headings(): array
    {
        return ['Nomor Peserta', 'Nama', 'Jenis', 'Tanggal Awal', 'Tanggal Akhir', 'Status', 'Alasan', 'Catatan Admin'];
    }

    public function map(mixed $row): array
    {
        return [$row->user->participantProfile?->participant_number, $row->user->name, $row->type, $row->start_date->format('d-m-Y'), $row->end_date->format('d-m-Y'), $row->status, $row->reason, $row->admin_notes];
    }

    public function title(): string
    {
        return 'Izin dan Sakit';
    }
}
