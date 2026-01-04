<?php

namespace App\Exports;

use App\Models\Ticket;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketExport
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function download(): StreamedResponse
    {
        $tickets = $this->getTickets();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set title
        $sheet->setTitle('Data Tiket');

        // Headers
        $headers = [
            'No',
            'Kode Tiket',
            'Kategori',
            'Deskripsi',
            'Pelapor',
            'Cabang',
            'Status',
            'Prioritas',
            'Staff Ditugaskan',
            'Tanggal Dibuat',
            'Tanggal Selesai',
            'Durasi (Jam)',
        ];

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        // Header styling
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ];
        $sheet->getStyle('A1:L1')->applyFromArray($headerStyle);

        // Data rows
        $row = 2;
        $no = 1;
        foreach ($tickets as $ticket) {
            $status = $ticket->status->value ?? $ticket->status;
            $priority = $ticket->priority->value ?? $ticket->priority;
            $staffNames = $ticket->assignedStaff->pluck('name')->join(', ') ?: '-';

            $duration = null;
            if ($ticket->completed_at && $ticket->created_at) {
                $duration = round($ticket->created_at->diffInHours($ticket->completed_at), 1);
            }

            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, $ticket->code);
            $sheet->setCellValue('C' . $row, $ticket->category->name ?? '-');
            $sheet->setCellValue('D' . $row, $ticket->description);
            $sheet->setCellValue('E' . $row, $ticket->user->name ?? '-');
            $sheet->setCellValue('F' . $row, $ticket->branch->name ?? '-');
            $sheet->setCellValue('G' . $row, $this->getStatusLabel($status));
            $sheet->setCellValue('H' . $row, $this->getPriorityLabel($priority));
            $sheet->setCellValue('I' . $row, $staffNames);
            $sheet->setCellValue('J' . $row, $ticket->created_at->format('d/m/Y H:i'));
            $sheet->setCellValue('K' . $row, $ticket->completed_at ? $ticket->completed_at->format('d/m/Y H:i') : '-');
            $sheet->setCellValue('L' . $row, $duration ?? '-');

            $row++;
            $no++;
        }

        // Data styling
        $dataStyle = [
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ];
        $sheet->getStyle('A2:L' . ($row - 1))->applyFromArray($dataStyle);

        // Auto-size columns
        foreach (range('A', 'L') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Create response
        $filename = 'tickets_' . now()->format('Y-m-d_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    protected function getTickets()
    {
        $query = Ticket::with(['user', 'branch', 'assignedStaff', 'category']);

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }
        if (!empty($this->filters['priority'])) {
            $query->where('priority', $this->filters['priority']);
        }
        if (!empty($this->filters['branch_id'])) {
            $query->where('branch_id', $this->filters['branch_id']);
        }
        if (!empty($this->filters['date_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['date_from']);
        }
        if (!empty($this->filters['date_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['date_to']);
        }
        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }
        if (!empty($this->filters['duration'])) {
            $duration = $this->filters['duration'];
            if ($duration === 'today') {
                $query->whereDate('created_at', now());
            } elseif ($duration === 'week') {
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
            } elseif ($duration === 'month') {
                $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
            }
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    private function getStatusLabel($status): string
    {
        return match ($status) {
            'open' => 'Open',
            'in_progress' => 'In Progress',
            'resolved' => 'Resolved',
            'closed' => 'Closed',
            'rejected' => 'Rejected',
            default => $status,
        };
    }

    private function getPriorityLabel($priority): string
    {
        return match ($priority) {
            'low' => 'Rendah',
            'medium' => 'Sedang',
            'high' => 'Tinggi',
            'urgent' => 'Urgent',
            default => $priority,
        };
    }
}
