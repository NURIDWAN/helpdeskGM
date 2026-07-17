<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Laporan Form Permintaan</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }

        .header h1 {
            margin: 0;
            font-size: 18px;
            color: #333;
        }

        .header p {
            margin: 5px 0 0;
            color: #666;
            font-size: 11px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            background: #4472C4;
            color: white;
            padding: 8px 5px;
            text-align: left;
            font-size: 9px;
        }

        td {
            padding: 6px 5px;
            border-bottom: 1px solid #ddd;
            font-size: 9px;
        }

        tr:nth-child(even) {
            background: #f9f9f9;
        }

        .status-progress {
            color: #FF9800;
            font-weight: bold;
        }

        .status-pending {
            color: #9E9E9E;
            font-weight: bold;
        }

        .status-approved {
            color: #4CAF50;
            font-weight: bold;
        }

        .status-rejected {
            color: #F44336;
            font-weight: bold;
        }

        .priority-low {
            color: #4CAF50;
        }

        .priority-medium {
            color: #2196F3;
        }

        .priority-high {
            color: #FF9800;
        }

        .priority-urgent {
            color: #F44336;
            font-weight: bold;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 9px;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>LAPORAN DATA FORM PERMINTAAN</h1>
        <p>GA Maintenance System</p>
        <p>Tanggal Cetak: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 4%">No</th>
                <th style="width: 14%">No Permintaan</th>
                <th style="width: 12%">Tanggal</th>
                <th style="width: 15%">Pemohon</th>
                <th style="width: 15%">Outlet</th>
                <th style="width: 18%">Jenis Permintaan</th>
                <th style="width: 10%">Prioritas</th>
                <th style="width: 12%">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $index => $record)
                @php
                    $requestTypeLabels = [
                        'pembelian_produk_baru' => 'Pembelian Produk Baru',
                        'penggantian_produk_lama' => 'Penggantian Produk Lama',
                        'servis' => 'Servis',
                        'penggantian_part' => 'Penggantian Part',
                        'jasa' => 'Jasa',
                    ];
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $record->request_number }}</td>
                    <td>{{ $record->date ? \Carbon\Carbon::parse($record->date)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $record->user->name ?? '-' }}</td>
                    <td>{{ $record->branch->name ?? '-' }}</td>
                    <td>{{ $requestTypeLabels[$record->request_type] ?? '-' }}</td>
                    <td class="priority-{{ $record->priority }}">{{ ucfirst($record->priority ?? '-') }}</td>
                    <td class="status-{{ $record->status }}">{{ ucfirst($record->status ?? '-') }}</td>
                </tr>
            @empty
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Dokumen ini digenerate secara otomatis oleh sistem GA Maintenance</p>
    </div>
</body>

</html>
