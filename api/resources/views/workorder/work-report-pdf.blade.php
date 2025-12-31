<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kerja</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }

        .header p {
            margin: 5px 0 0 0;
            font-size: 14px;
            color: #666;
        }

        .info {
            margin-bottom: 20px;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }

        .info h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            font-weight: bold;
        }

        .info p {
            margin: 5px 0;
            font-size: 11px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background-color: #f8f9fa;
            font-weight: bold;
            font-size: 11px;
        }

        td {
            font-size: 10px;
        }

        .status-completed {
            background-color: #d4edda;
            color: #155724;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
        }

        .status-failed {
            background-color: #f8d7da;
            color: #721c24;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
        }

        .status-unknown {
            background-color: #f8f8f8;
            color: #888;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }

        .page-break {
            page-break-before: always;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Laporan Kerja</h1>
        <p>Dicetak pada: {{ date('d/m/Y H:i:s') }}</p>
    </div>

    @if (!empty(array_filter($filters)))
        <div class="info">
            <h3>Filter yang Diterapkan:</h3>
            @if (!empty($filters['search']))
                <p><strong>Pencarian:</strong> {{ $filters['search'] }}</p>
            @endif
            @if (isset($filters['is_done']))
                <p><strong>Status:</strong> {{ $filters['is_done'] ? 'Selesai' : 'Belum Selesai' }}</p>
            @endif
            @if (!empty($filters['start_date']))
                <p><strong>Tanggal Mulai:</strong> {{ date('d/m/Y', strtotime($filters['start_date'])) }}</p>
            @endif
            @if (!empty($filters['end_date']))
                <p><strong>Tanggal Akhir:</strong> {{ date('d/m/Y', strtotime($filters['end_date'])) }}</p>
            @endif
        </div>
    @endif

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 15%;">User</th>
                <th style="width: 12%;">Cabang</th>
                <th style="width: 15%;">Jenis Pekerjaan</th>
                <th style="width: 20%;">Pekerjaan Lainnya</th>
                <th style="width: 10%;">Status</th>
                <th style="width: 13%;">Tanggal Dibuat</th>
                <th style="width: 10%;">Tanggal Diperbarui</th>
            </tr>
        </thead>
        <tbody>
            @forelse($workReports as $index => $report)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $report->user->name ?? '-' }}</td>
                    <td>{{ $report->branch->name ?? '-' }}</td>
                    <td>{{ $report->jobTemplate->name ?? '-' }}</td>
                    <td>{{ $report->custom_job ?? '-' }}</td>
                    <td>
                        @switch($report->status)
                            @case(\App\Enums\WorkReportStatus::COMPLETED)
                                <span class="status-completed">Selesai</span>
                            @break

                            @case(\App\Enums\WorkReportStatus::PROGRESS)
                                <span class="status-pending">Sedang Berlangsung</span>
                            @break

                            @case(\App\Enums\WorkReportStatus::FAILED)
                                <span class="status-failed">Dibatalkan/Gagal</span>
                            @break

                            @default
                                <span class="status-unknown">Tidak Diketahui</span>
                        @endswitch
                    </td>
                    <td>{{ $report->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $report->updated_at->format('d/m/Y H:i') }}</td>
                </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 20px;">
                            Tidak ada data laporan kerja
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="footer">
            <p>Total Data: {{ $workReports->count() }} laporan kerja</p>
            <p>Dokumen ini dibuat secara otomatis oleh sistem GA Maintenance</p>
        </div>
    </body>

    </html>
