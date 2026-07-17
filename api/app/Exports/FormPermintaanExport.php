<?php

namespace App\Exports;

use App\Models\FormPermintaan;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FormPermintaanExport
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function download(): StreamedResponse
    {
        $records = $this->getRecords();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set title
        $sheet->setTitle('Form Permintaan');

        // Headers
        $headers = [
            'No',
            'No Permintaan',
            'Tanggal',
            'Pemohon',
            'Outlet',
            'Jenis Permintaan',
            'Prioritas',
            'Status',
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
        $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

        // Data rows
        $row = 2;
        $no = 1;
        foreach ($records as $record) {
            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, $record->request_number);
            $sheet->setCellValue('C' . $row, $record->date ? $record->date->format('d/m/Y') : '-');
            $sheet->setCellValue('D' . $row, $record->user->name ?? '-');
            $sheet->setCellValue('E' . $row, $record->branch->name ?? '-');
            $sheet->setCellValue('F' . $row, $this->getRequestTypeLabel($record->request_type));
            $sheet->setCellValue('G' . $row, $this->getPriorityLabel($record->priority));
            $sheet->setCellValue('H' . $row, $this->getStatusLabel($record->status));

            $row++;
            $no++;
        }

        // Data styling
        if ($row > 2) {
            $dataStyle = [
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                ],
            ];
            $sheet->getStyle('A2:H' . ($row - 1))->applyFromArray($dataStyle);
        }

        // Auto-size columns
        foreach (range('A', 'H') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Create response
        $filename = 'form_permintaan_' . now()->format('Y-m-d_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    protected function getRecords()
    {
        $user = Auth::user();

        $query = FormPermintaan::with(['user', 'branch', 'ticket'])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc');

        // Same access control as getAllPaginated
        if (!$user->can('form-permintaan-view-all')) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id);
                if ($user->branch_id) {
                    $q->orWhere('branch_id', $user->branch_id);
                }
            });
        }

        if (!empty($this->filters['search'])) {
            $query->where('request_number', 'like', '%' . $this->filters['search'] . '%');
        }

        if (!empty($this->filters['branch_id'])) {
            $query->where('branch_id', $this->filters['branch_id']);
        }

        if (!empty($this->filters['request_type'])) {
            $query->where('request_type', $this->filters['request_type']);
        }

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['start_date'])) {
            $query->whereDate('date', '>=', $this->filters['start_date']);
        }

        if (!empty($this->filters['end_date'])) {
            $query->whereDate('date', '<=', $this->filters['end_date']);
        }

        return $query->get();
    }

    private function getRequestTypeLabel($type): string
    {
        return match ($type) {
            'pembelian_produk_baru' => 'Pembelian Produk Baru',
            'penggantian_produk_lama' => 'Penggantian Produk Lama',
            'servis' => 'Servis',
            'penggantian_part' => 'Penggantian Part',
            'jasa' => 'Jasa',
            default => $type ?? '-',
        };
    }

    private function getPriorityLabel($priority): string
    {
        return match ($priority) {
            'low' => 'Rendah',
            'medium' => 'Sedang',
            'high' => 'Tinggi',
            'urgent' => 'Urgent',
            default => $priority ?? '-',
        };
    }

    private function getStatusLabel($status): string
    {
        return match ($status) {
            'progress' => 'Progress',
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => $status ?? '-',
        };
    }
}
