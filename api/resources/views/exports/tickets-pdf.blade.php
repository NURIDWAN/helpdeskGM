<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Laporan Tiket</title>
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

        .filters {
            background: #f5f5f5;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            font-size: 9px;
        }

        .filters span {
            margin-right: 15px;
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

        .status-open {
            color: #2196F3;
            font-weight: bold;
        }

        .status-in_progress {
            color: #FF9800;
            font-weight: bold;
        }

        .status-resolved {
            color: #4CAF50;
            font-weight: bold;
        }

        .status-closed {
            color: #9E9E9E;
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

        .summary {
            margin-top: 15px;
            padding: 10px;
            background: #e3f2fd;
            border-radius: 4px;
        }

        .summary-item {
            display: inline-block;
            margin-right: 20px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>LAPORAN DATA TIKET</h1>
        <p>GA Maintenance System</p>
        <p>Tanggal Cetak: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    @if(!empty($filters))
        <div class="filters">
            <strong>Filter:</strong>
            @if(!empty($filters['status']))
                <span>Status: {{ $filters['status'] }}</span>
            @endif
            @if(!empty($filters['priority']))
                <span>Prioritas: {{ $filters['priority'] }}</span>
            @endif
            @if(!empty($filters['branch']))
                <span>Cabang: {{ $filters['branch'] }}</span>
            @endif
            @if(!empty($filters['date_from']))
                <span>Dari: {{ $filters['date_from'] }}</span>
            @endif
            @if(!empty($filters['date_to']))
                <span>Sampai: {{ $filters['date_to'] }}</span>
            @endif
        </div>
    @endif

    <div class="summary">
        <strong>Ringkasan:</strong>
        <span class="summary-item">Total: {{ count($tickets) }} tiket</span>
        <span class="summary-item">Open: {{ $tickets->where('status', 'open')->count() }}</span>
        <span class="summary-item">In Progress:
            {{ $tickets->filter(fn($t) => ($t->status->value ?? $t->status) === 'in_progress')->count() }}</span>
        <span class="summary-item">Resolved:
            {{ $tickets->filter(fn($t) => ($t->status->value ?? $t->status) === 'resolved')->count() }}</span>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 3%">No</th>
                <th style="width: 12%">Kode</th>
                <th style="width: 10%">Kategori</th>
                <th style="width: 15%">Judul</th>
                <th style="width: 10%">Pelapor</th>
                <th style="width: 10%">Cabang</th>
                <th style="width: 8%">Status</th>
                <th style="width: 7%">Prioritas</th>
                <th style="width: 10%">Staff</th>
                <th style="width: 10%">Dibuat</th>
                <th style="width: 5%">Durasi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tickets as $index => $ticket)
                @php
                    $status = $ticket->status->value ?? $ticket->status;
                    $priority = $ticket->priority->value ?? $ticket->priority;
                    $duration = null;
                    if ($ticket->completed_at && $ticket->created_at) {
                        $duration = round($ticket->created_at->diffInHours($ticket->completed_at), 1) . ' jam';
                    }
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $ticket->code }}</td>
                    <td>{{ $ticket->category->name ?? '-' }}</td>
                    <td>{{ Str::limit($ticket->title, 30) }}</td>
                    <td>{{ $ticket->user->name ?? '-' }}</td>
                    <td>{{ $ticket->branch->name ?? '-' }}</td>
                    <td class="status-{{ $status }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</td>
                    <td class="priority-{{ $priority }}">{{ ucfirst($priority) }}</td>
                    <td>{{ $ticket->assignedStaff->pluck('name')->join(', ') ?: '-' }}</td>
                    <td>{{ $ticket->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $duration ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Dokumen ini digenerate secara otomatis oleh sistem GA Maintenance</p>
    </div>
</body>

</html>