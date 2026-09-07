<?php

namespace App\Exports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class GroupedAttendanceExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    private const DIMENSIONS = [
        'participant' => ['summaries.user_id', 'users.name', 'profiles.participant_number', 'Per Peserta'],
        'class' => ['classes.id', 'classes.name', "''", 'Per Kelas'],
        'batch' => ['batches.id', 'batches.name', "''", 'Per Angkatan'],
        'program' => ['programs.id', 'programs.name', 'programs.code', 'Per Program'],
    ];

    public function __construct(private readonly array $filters, private readonly string $dimension)
    {
        if (! array_key_exists($dimension, self::DIMENSIONS)) {
            throw new InvalidArgumentException('Dimensi laporan tidak didukung.');
        }
    }

    public function query(): Builder
    {
        [$groupId, $groupName, $groupCode] = self::DIMENSIONS[$this->dimension];

        return DB::table('attendance_summaries as summaries')->join('users', 'users.id', '=', 'summaries.user_id')->leftJoin('participant_profiles as profiles', 'profiles.user_id', '=', 'users.id')->join('training_schedules as schedules', 'schedules.id', '=', 'summaries.training_schedule_id')->join('training_classes as classes', 'classes.id', '=', 'schedules.training_class_id')->join('training_batches as batches', 'batches.id', '=', 'classes.training_batch_id')->join('training_programs as programs', 'programs.id', '=', 'batches.training_program_id')
            ->selectRaw("{$groupId} as group_id, {$groupName} as group_name, {$groupCode} as group_code")
            ->selectRaw("SUM(CASE WHEN summaries.overall_status IN ('present', 'late') THEN 1 ELSE 0 END) as present_count")
            ->selectRaw("SUM(CASE WHEN summaries.overall_status = 'late' THEN 1 ELSE 0 END) as late_count")
            ->selectRaw("SUM(CASE WHEN summaries.overall_status = 'leave' THEN 1 ELSE 0 END) as leave_count")
            ->selectRaw("SUM(CASE WHEN summaries.overall_status = 'sick' THEN 1 ELSE 0 END) as sick_count")
            ->selectRaw("SUM(CASE WHEN summaries.overall_status = 'absent' THEN 1 ELSE 0 END) as absent_count")
            ->selectRaw("SUM(CASE WHEN summaries.overall_status = 'incomplete' THEN 1 ELSE 0 END) as incomplete_count")
            ->selectRaw("SUM(CASE WHEN summaries.overall_status != 'holiday' THEN 1 ELSE 0 END) as effective_count")
            ->when($this->filters['start_date'] ?? null, fn ($query, $date) => $query->whereDate('summaries.attendance_date', '>=', $date))->when($this->filters['end_date'] ?? null, fn ($query, $date) => $query->whereDate('summaries.attendance_date', '<=', $date))->when($this->filters['program_id'] ?? null, fn ($query, $id) => $query->where('programs.id', $id))->when($this->filters['batch_id'] ?? null, fn ($query, $id) => $query->where('batches.id', $id))->when($this->filters['class_id'] ?? null, fn ($query, $id) => $query->where('classes.id', $id))->when($this->filters['participant_id'] ?? null, fn ($query, $id) => $query->where('summaries.user_id', $id))->when($this->filters['instructor_id'] ?? null, fn ($query, $id) => $query->whereExists(fn ($subquery) => $subquery->selectRaw('1')->from('class_instructor')->whereColumn('class_instructor.training_class_id', 'classes.id')->where('class_instructor.user_id', $id)))->when($this->filters['final_status'] ?? null, fn ($query, $status) => $query->where('summaries.overall_status', $status))
            ->groupBy($groupId, $groupName, DB::raw($groupCode))->orderBy('group_name')->orderBy('group_id');
    }

    public function headings(): array
    {
        return ['Kode/Nomor', 'Nama', 'Hadir', 'Terlambat', 'Izin', 'Sakit', 'Alpa', 'Tidak Lengkap', 'Persentase Kehadiran'];
    }

    public function map(mixed $row): array
    {
        $rate = (int) $row->effective_count > 0 ? round(((int) $row->present_count / (int) $row->effective_count) * 100, 1) : 0;

        return [$row->group_code ?: '-', $row->group_name, $row->present_count, $row->late_count, $row->leave_count, $row->sick_count, $row->absent_count, $row->incomplete_count, $rate.'%'];
    }

    public function title(): string
    {
        return self::DIMENSIONS[$this->dimension][3];
    }
}
