<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <title>Laporan Daily Usage - {{ ucfirst($category === 'all' ? 'Semua Kategori' : $category) }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 5mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8px;
            color: #000;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
        }

        .title {
            font-weight: 700;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .subtitle {
            font-size: 10px;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .tb,
        .tb td,
        .tb th {
            border: 1px solid #000;
        }

        .tb th {
            background-color: #f0f0f0;
            padding: 4px 2px;
            text-align: center;
            font-weight: 700;
            font-size: 7px;
        }

        .tb th.category-header {
            background-color: #d0d0d0;
            font-size: 8px;
        }

        .tb td {
            padding: 3px 2px;
            text-align: center;
            vertical-align: middle;
            font-size: 7px;
        }

        .tb td.text-left {
            text-align: left;
        }

        .tb td.text-right {
            text-align: right;
        }

        .photo-cell {
            padding: 2px;
            text-align: center;
        }

        .photo-link {
            color: #0066cc;
            text-decoration: underline;
            font-size: 7px;
        }

        .no-data {
            text-align: center;
            padding: 20px;
            color: #666;
        }

        .meter-row {
            background-color: #fafafa;
        }

        .total-row {
            background-color: #ffffcc;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="title">LAPORAN DAILY USAGE - {{ strtoupper($category === 'gas' ? 'GAS' : ($category === 'water' ? 'AIR' : ($category === 'electricity' ? 'LISTRIK' : 'SEMUA KATEGORI'))) }}</div>
        <div class="subtitle">
            @if ($branch)
                Cabang: {{ $branch->name }}
            @endif
            @if (isset($filters['start_date']) || isset($filters['end_date']))
                | Periode:
                @if (isset($filters['start_date']))
                    {{ date('d/m/Y', strtotime($filters['start_date'])) }}
                @endif
                @if (isset($filters['start_date']) && isset($filters['end_date']))
                    -
                @endif
                @if (isset($filters['end_date']))
                    {{ date('d/m/Y', strtotime($filters['end_date'])) }}
                @endif
            @endif
        </div>
    </div>

    @if (count($reportData) > 0)
        <table class="tb">
            <thead>
                {{-- Row 1: Category Headers --}}
                <tr>
                    <th rowspan="2">No</th>
                    <th rowspan="2">Timestamp</th>
                    <th rowspan="2">Tanggal</th>
                    <th rowspan="2">Nama</th>
                    <th rowspan="2">Outlet</th>
                    <th rowspan="2">Total<br>Customer</th>
                    @if ($category === 'gas' || $category === 'all')
                        <th colspan="7" class="category-header">LAPORAN GAS</th>
                    @endif
                    @if ($category === 'water' || $category === 'all')
                        <th colspan="5" class="category-header">LAPORAN AIR</th>
                    @endif
                    @if ($category === 'electricity' || $category === 'all')
                        <th colspan="12" class="category-header">LAPORAN LISTRIK</th>
                    @endif
                </tr>
                {{-- Row 2: Sub Headers --}}
                <tr>
                    @if ($category === 'gas' || $category === 'all')
                        <th>Jenis<br>Kompor</th>
                        <th>Jenis<br>Gas</th>
                        <th>Opening</th>
                        <th>Closing</th>
                        <th>Total<br>Pemakaian</th>
                        <th>Foto</th>
                        <th>Lokasi</th>
                    @endif
                    @if ($category === 'water' || $category === 'all')
                        <th>Opening</th>
                        <th>Closing</th>
                        <th>Total<br>Pemakaian</th>
                        <th>Foto</th>
                        <th>Lokasi</th>
                    @endif
                    @if ($category === 'electricity' || $category === 'all')
                        <th>Nama</th>
                        <th>Lokasi</th>
                        <th>WBP<br>Opening</th>
                        <th>LWBP<br>Opening</th>
                        <th>WBP<br>Closing</th>
                        <th>LWBP<br>Closing</th>
                        <th>Pemakaian<br>WBP</th>
                        <th>Pemakaian<br>LWBP</th>
                        <th>Total<br>Pemakaian</th>
                        <th>Foto<br>WBP</th>
                        <th>Foto<br>LWBP</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($reportData as $index => $row)
                    @php
                        // Calculate max rows needed for this record (only electricity supports multi-meter)
                        $elecCount = isset($row['electricity']) && is_array($row['electricity']) ? count(array_filter($row['electricity'], fn($e) => isset($e['location']) && $e['location'] !== null)) : 0;
                        $maxRows = max(1, $elecCount);
                        
                        // Get first water and electricity data
                        $firstWater = isset($row['water']) && is_array($row['water']) && count($row['water']) > 0 ? $row['water'][0] : null;
                        $firstElec = $elecCount > 0 ? (array_values(array_filter($row['electricity'], fn($e) => isset($e['location']) && $e['location'] !== null))[0] ?? null) : null;
                        $elecFiltered = $elecCount > 0 ? array_values(array_filter($row['electricity'], fn($e) => isset($e['location']) && $e['location'] !== null)) : [];
                    @endphp
                    
                    {{-- First row of this daily record --}}
                    <tr>
                        <td rowspan="{{ $maxRows }}">{{ $index + 1 }}</td>
                        <td class="text-left" rowspan="{{ $maxRows }}">{{ $row['timestamp'] ?? '-' }}</td>
                        <td class="text-left" rowspan="{{ $maxRows }}">{{ $row['tanggal'] ?? '-' }}</td>
                        <td class="text-left" rowspan="{{ $maxRows }}">{{ $row['nama'] ?? '-' }}</td>
                        <td class="text-left" rowspan="{{ $maxRows }}">{{ $row['outlet'] ?? '-' }}</td>
                        <td rowspan="{{ $maxRows }}">{{ $row['total_customer'] ?? '-' }}</td>
                        
                        {{-- Gas columns (always single row) --}}
                        @if ($category === 'gas' || $category === 'all')
                            <td class="text-left" rowspan="{{ $maxRows }}">{{ $row['gas']['stove_type'] ?? '-' }}</td>
                            <td class="text-left" rowspan="{{ $maxRows }}">{{ $row['gas']['gas_type'] ?? '-' }}</td>
                            <td class="text-right" rowspan="{{ $maxRows }}">{{ isset($row['gas']['opening']) && $row['gas']['opening'] !== null ? number_format($row['gas']['opening'], 2) : '-' }}</td>
                            <td class="text-right" rowspan="{{ $maxRows }}">{{ isset($row['gas']['closing']) && $row['gas']['closing'] !== null ? number_format($row['gas']['closing'], 2) : '-' }}</td>
                            <td class="text-right" rowspan="{{ $maxRows }}">{{ isset($row['gas']['usage']) && $row['gas']['usage'] !== null ? number_format($row['gas']['usage'], 2) : '-' }}</td>
                            <td class="photo-cell" rowspan="{{ $maxRows }}">
                                @if (isset($row['gas']['photo_path']) && $row['gas']['photo_path'])
                                    <a href="{{ asset('storage/' . $row['gas']['photo_path']) }}" class="photo-link" target="_blank">Lihat Foto</a>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-left" rowspan="{{ $maxRows }}">{{ $row['gas']['location'] ?? '-' }}</td>
                        @endif
                        
                        {{-- Water columns (always single row like Gas) --}}
                        @if ($category === 'water' || $category === 'all')
                            <td class="text-right" rowspan="{{ $maxRows }}">{{ $firstWater && isset($firstWater['opening']) ? number_format($firstWater['opening'], 2) : '-' }}</td>
                            <td class="text-right" rowspan="{{ $maxRows }}">{{ $firstWater && isset($firstWater['closing']) ? number_format($firstWater['closing'], 2) : '-' }}</td>
                            <td class="text-right" rowspan="{{ $maxRows }}">{{ $firstWater && isset($firstWater['usage']) ? number_format($firstWater['usage'], 2) : '-' }}</td>
                            <td class="photo-cell" rowspan="{{ $maxRows }}">
                                @if ($firstWater && isset($firstWater['photo_path']) && $firstWater['photo_path'])
                                    <a href="{{ asset('storage/' . $firstWater['photo_path']) }}" class="photo-link" target="_blank">Lihat Foto</a>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-left" rowspan="{{ $maxRows }}">{{ $firstWater['location'] ?? '-' }}</td>
                        @endif
                        
                        {{-- Electricity columns (first meter) --}}
                        @if ($category === 'electricity' || $category === 'all')
                            <td class="text-left">{{ $firstElec['meter_name'] ?? 'Meter 1' }}</td>
                            <td class="text-left">{{ $firstElec['location'] ?? '-' }}</td>
                            <td class="text-right">{{ $firstElec && isset($firstElec['wbp_opening']) ? number_format($firstElec['wbp_opening'], 2) : '-' }}</td>
                            <td class="text-right">{{ $firstElec && isset($firstElec['lwbp_opening']) ? number_format($firstElec['lwbp_opening'], 2) : '-' }}</td>
                            <td class="text-right">{{ $firstElec && isset($firstElec['wbp_closing']) ? number_format($firstElec['wbp_closing'], 2) : '-' }}</td>
                            <td class="text-right">{{ $firstElec && isset($firstElec['lwbp_closing']) ? number_format($firstElec['lwbp_closing'], 2) : '-' }}</td>
                            <td class="text-right">{{ $firstElec && isset($firstElec['wbp_usage']) ? number_format($firstElec['wbp_usage'], 2) : '-' }}</td>
                            <td class="text-right">{{ $firstElec && isset($firstElec['lwbp_usage']) ? number_format($firstElec['lwbp_usage'], 2) : '-' }}</td>
                            <td class="text-right">{{ $firstElec && isset($firstElec['total_usage']) ? number_format($firstElec['total_usage'], 2) : '-' }}</td>
                            <td class="photo-cell">
                                @if ($firstElec && isset($firstElec['photo_wbp_path']) && $firstElec['photo_wbp_path'])
                                    <a href="{{ asset('storage/' . $firstElec['photo_wbp_path']) }}" class="photo-link" target="_blank">Foto WBP</a>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="photo-cell">
                                @if ($firstElec && isset($firstElec['photo_lwbp_path']) && $firstElec['photo_lwbp_path'])
                                    <a href="{{ asset('storage/' . $firstElec['photo_lwbp_path']) }}" class="photo-link" target="_blank">Foto LWBP</a>
                                @else
                                    -
                                @endif
                            </td>
                        @endif
                    </tr>
                    
                    {{-- Additional rows for multi-meter (if any) --}}
                    @for ($i = 1; $i < $maxRows; $i++)
                        <tr class="meter-row">
                            {{-- Common columns already handled with rowspan --}}
                            
                            {{-- Gas columns already handled with rowspan --}}
                            
                            {{-- Water columns already handled with rowspan (like Gas) --}}
                            
                            {{-- Electricity columns (additional meters) --}}
                            @if ($category === 'electricity' || $category === 'all')
                                @php $elec = isset($elecFiltered[$i]) ? $elecFiltered[$i] : null; @endphp
                                <td class="text-left">{{ $elec['meter_name'] ?? ('Meter ' . ($i + 1)) }}</td>
                                <td class="text-left">{{ $elec['location'] ?? '-' }}</td>
                                <td class="text-right">{{ $elec && isset($elec['wbp_opening']) ? number_format($elec['wbp_opening'], 2) : '-' }}</td>
                                <td class="text-right">{{ $elec && isset($elec['lwbp_opening']) ? number_format($elec['lwbp_opening'], 2) : '-' }}</td>
                                <td class="text-right">{{ $elec && isset($elec['wbp_closing']) ? number_format($elec['wbp_closing'], 2) : '-' }}</td>
                                <td class="text-right">{{ $elec && isset($elec['lwbp_closing']) ? number_format($elec['lwbp_closing'], 2) : '-' }}</td>
                                <td class="text-right">{{ $elec && isset($elec['wbp_usage']) ? number_format($elec['wbp_usage'], 2) : '-' }}</td>
                                <td class="text-right">{{ $elec && isset($elec['lwbp_usage']) ? number_format($elec['lwbp_usage'], 2) : '-' }}</td>
                                <td class="text-right">{{ $elec && isset($elec['total_usage']) ? number_format($elec['total_usage'], 2) : '-' }}</td>
                                <td class="photo-cell">
                                    @if ($elec && isset($elec['photo_wbp_path']) && $elec['photo_wbp_path'])
                                        <a href="{{ asset('storage/' . $elec['photo_wbp_path']) }}" class="photo-link" target="_blank">Foto WBP</a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="photo-cell">
                                    @if ($elec && isset($elec['photo_lwbp_path']) && $elec['photo_lwbp_path'])
                                        <a href="{{ asset('storage/' . $elec['photo_lwbp_path']) }}" class="photo-link" target="_blank">Foto LWBP</a>
                                    @else
                                        -
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @endfor
                @endforeach
            </tbody>
        </table>
    @else
        <div class="no-data">Tidak ada data untuk ditampilkan</div>
    @endif
</body>

</html>
