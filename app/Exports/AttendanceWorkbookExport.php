<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AttendanceWorkbookExport implements Export, WithMultipleSheets
{
    public function __construct(private readonly array $filters) {}

    public function sheets(): array
    {
        return [
            new AttendanceReportExport($this->filters),
            new FinalizedAttendanceExport($this->filters),
            new LeaveRequestReportExport($this->filters),
            new FraudFlagReportExport($this->filters),
            new GroupedAttendanceExport($this->filters, 'participant'),
            new GroupedAttendanceExport($this->filters, 'class'),
            new GroupedAttendanceExport($this->filters, 'batch'),
            new GroupedAttendanceExport($this->filters, 'program'),
        ];
    }
}
