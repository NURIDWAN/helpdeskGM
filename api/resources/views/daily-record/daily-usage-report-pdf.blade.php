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
        
        .align-top {
            vertical-align: top;
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
                        <th colspan="6" class="category-header">LAPORAN LISTRIK</th>
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
                        <th>Opening</th>
                        <th>Closing</th>
                        <th>Pemakaian</th>
                        <th>Foto</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($reportData as $index => $row)
                    @php
                        // Filter valid electricity data
                        $elecFiltered = isset($row['electricity']) && is_array($row['electricity']) 
                            ? array_values(array_filter($row['electricity'], fn($e) => $e !== null)) 
                            : [];
                        $elecCount = count($elecFiltered);
                        
                        // Calculate rowspan: meters + 1 (for TOTAL row) if > 1 meter, else 1
                        $rowspan = $elecCount > 1 ? ($elecCount + 1) : 1;
                        
                        // Get first items
                        $firstWater = isset($row['water']) && is_array($row['water']) && count($row['water']) > 0 ? $row['water'][0] : null;
                        $firstElec = $elecCount > 0 ? $elecFiltered[0] : null;
                        
                        // Calculate electricity total usage
                        $elecTotalUsage = 0;
                        foreach ($elecFiltered as $e) {
                            $elecTotalUsage += floatval($e['usage'] ?? 0);
                        }
                    @endphp
                    
                    {{-- First row of this daily record --}}
                    <tr>
                        <td class="align-top" rowspan="{{ $rowspan }}">{{ $index + 1 }}</td>
                        <td class="text-left align-top" rowspan="{{ $rowspan }}">{{ $row['timestamp'] ?? '-' }}</td>
                        <td class="text-left align-top" rowspan="{{ $rowspan }}">{{ $row['tanggal'] ?? '-' }}</td>
                        <td class="text-left align-top" rowspan="{{ $rowspan }}">{{ $row['nama'] ?? '-' }}</td>
                        <td class="text-left align-top" rowspan="{{ $rowspan }}">{{ $row['outlet'] ?? '-' }}</td>
                        <td class="align-top" rowspan="{{ $rowspan }}">{{ $row['total_customer'] ?? '-' }}</td>
                        
                        {{-- Gas columns (rowspan to match electricity) --}}
                        @if ($category === 'gas' || $category === 'all')
                            <td class="text-left align-top" rowspan="{{ $rowspan }}">{{ $row['gas']['stove_type'] ?? '-' }}</td>
                            <td class="text-left align-top" rowspan="{{ $rowspan }}">{{ $row['gas']['gas_type'] ?? '-' }}</td>
                            <td class="text-right align-top" rowspan="{{ $rowspan }}">{{ isset($row['gas']['opening']) && $row['gas']['opening'] !== null ? number_format($row['gas']['opening'], 2) : '-' }}</td>
                            <td class="text-right align-top" rowspan="{{ $rowspan }}">{{ isset($row['gas']['closing']) && $row['gas']['closing'] !== null ? number_format($row['gas']['closing'], 2) : '-' }}</td>
                            <td class="text-right align-top" rowspan="{{ $rowspan }}">{{ isset($row['gas']['usage']) && $row['gas']['usage'] !== null ? number_format($row['gas']['usage'], 2) : '-' }}</td>
                            <td class="photo-cell align-top" rowspan="{{ $rowspan }}">
                                @if (isset($row['gas']['photo_path']) && $row['gas']['photo_path'])
                                    <a href="{{ asset('storage/' . $row['gas']['photo_path']) }}" class="photo-link" target="_blank">Lihat Foto</a>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-left align-top" rowspan="{{ $rowspan }}">{{ $row['gas']['location'] ?? '-' }}</td>
                        @endif
                        
                        {{-- Water columns (rowspan to match electricity) --}}
                        @if ($category === 'water' || $category === 'all')
                            <td class="text-right align-top" rowspan="{{ $rowspan }}">{{ $firstWater && isset($firstWater['opening']) ? number_format($firstWater['opening'], 2) : '-' }}</td>
                            <td class="text-right align-top" rowspan="{{ $rowspan }}">{{ $firstWater && isset($firstWater['closing']) ? number_format($firstWater['closing'], 2) : '-' }}</td>
                            <td class="text-right align-top" rowspan="{{ $rowspan }}">{{ $firstWater && isset($firstWater['usage']) ? number_format($firstWater['usage'], 2) : '-' }}</td>
                            <td class="photo-cell align-top" rowspan="{{ $rowspan }}">
                                @if ($firstWater && isset($firstWater['photo_path']) && $firstWater['photo_path'])
                                    <a href="{{ asset('storage/' . $firstWater['photo_path']) }}" class="photo-link" target="_blank">Lihat Foto</a>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-left align-top" rowspan="{{ $rowspan }}">{{ $firstWater['location'] ?? '-' }}</td>
                        @endif
                        
                        {{-- Electricity columns (first meter) --}}
                        @if ($category === 'electricity' || $category === 'all')
                            <td class="text-left">{{ $firstElec['meter_name'] ?? 'Meter 1' }}</td>
                            <td class="text-left">{{ $firstElec['location'] ?? '-' }}</td>
                            <td class="text-right">{{ $firstElec && isset($firstElec['opening']) ? number_format($firstElec['opening'], 2) : '-' }}</td>
                            <td class="text-right">{{ $firstElec && isset($firstElec['closing']) ? number_format($firstElec['closing'], 2) : '-' }}</td>
                            <td class="text-right">{{ $firstElec && isset($firstElec['usage']) ? number_format($firstElec['usage'], 2) : '-' }}</td>
                            <td class="photo-cell">
                                @if ($firstElec && isset($firstElec['photo_path']) && $firstElec['photo_path'])
                                    <a href="{{ asset('storage/' . $firstElec['photo_path']) }}" class="photo-link" target="_blank">Lihat Foto</a>
                                @else
                                    -
                                @endif
                            </td>
                        @endif
                    </tr>
                    
                    {{-- Additional rows for multi-meter electricity --}}
                    @if ($elecCount > 1)
                        @for ($i = 1; $i < $elecCount; $i++)
                            @php $elec = $elecFiltered[$i]; @endphp
                            <tr class="meter-row">
                                {{-- Only electricity columns (no common/gas/water - they use rowspan) --}}
                                @if ($category === 'electricity' || $category === 'all')
                                    <td class="text-left">{{ $elec['meter_name'] ?? ('Meter ' . ($i + 1)) }}</td>
                                    <td class="text-left">{{ $elec['location'] ?? '-' }}</td>
                                    <td class="text-right">{{ isset($elec['opening']) ? number_format($elec['opening'], 2) : '-' }}</td>
                                    <td class="text-right">{{ isset($elec['closing']) ? number_format($elec['closing'], 2) : '-' }}</td>
                                    <td class="text-right">{{ isset($elec['usage']) ? number_format($elec['usage'], 2) : '-' }}</td>
                                    <td class="photo-cell">
                                        @if (isset($elec['photo_path']) && $elec['photo_path'])
                                            <a href="{{ asset('storage/' . $elec['photo_path']) }}" class="photo-link" target="_blank">Lihat Foto</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @endfor
                        
                        {{-- TOTAL row for electricity --}}
                        <tr class="total-row">
                            @if ($category === 'electricity' || $category === 'all')
                                <td class="text-left" colspan="2"><strong>TOTAL</strong></td>
                                <td class="text-center">-</td>
                                <td class="text-center">-</td>
                                <td class="text-right" style="color: green;"><strong>{{ number_format($elecTotalUsage, 2) }}</strong></td>
                                <td class="text-center">-</td>
                            @endif
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    @else
        <div class="no-data">Tidak ada data untuk ditampilkan</div>
    @endif
</body>

</html>
