<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <title>Surat Perintah Kerja</title>
    <style>
        /* ---- Print / A4 Landscape ---- */
        @page {
            size: A4 landscape;
            margin: 5mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
        }

        /* Opsional: latar grid tipis seperti gambar */
        .sheet {
            position: relative;
            padding: 4px;
            min-height: 100vh;
            background:
                repeating-linear-gradient(0deg, #eaeaea 0, #eaeaea 1px, transparent 1px, transparent 16px),
                repeating-linear-gradient(90deg, #eaeaea 0, #eaeaea 1px, transparent 1px, transparent 16px);
        }

        .title {
            font-weight: 700;
            font-size: 16px;
            letter-spacing: .3px;
        }

        .small {
            font-size: 10px;
        }

        .section-title {
            text-align: center;
            font-weight: 700;
            padding: 4px 0;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            margin: 6px 0 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .tb,
        .tb td,
        .tb th {
            border: 1px solid #000;
        }

        .tb td {
            padding: 6px 8px;
            vertical-align: top;
        }

        .no-border td {
            border: 0 !important;
            padding: 0;
        }

        .label {
            width: 120px;
            white-space: nowrap;
        }

        .colon {
            width: 10px;
            text-align: center;
        }

        .w-50 {
            width: 50%;
        }

        .w-33 {
            width: 33.333%;
        }

        .muted {
            color: #111;
        }

        .sig-box {
            height: 70px;
        }

        .dotted {
            border-top: 1px dotted #000;
            width: 85%;
            margin: 0 auto;
            height: 1px;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .bold {
            font-weight: 700;
        }

        /* job description styling */
        .job-description {
            padding: 6px 0;
            line-height: 1.4;
        }

        /* header garis besar */
        .header-wrap {
            border: 1px solid #000;
        }

        .divider-v {
            border-left: 1px solid #000;
        }
    </style>
</head>

<body>
    <div class="sheet">
        <!-- HEADER -->
        <table class="header-wrap" style="width:100%;">
            <tr>
                <td class="w-50" style="padding:8px;">
                    <div class="title">SURAT PERINTAH KERJA</div>
                    <table class="no-border" style="margin-top:6px;">
                        <tr>
                            <td class="label">NOMOR</td>
                            <td class="colon">:</td>
                            <td class="bold">{{ $workOrder->number }}</td>
                        </tr>
                    </table>

                    <table class="tb" style="margin-top:8px;">
                        <tr>
                            <td class="label">TEKNISI</td>
                            <td class="colon">:</td>
                            <td>{{ $workOrder->assignedUser?->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="label">UNIT KERUSAKAN</td>
                            <td class="colon">:</td>
                            <td>{{ $workOrder->damage_unit ?? '-' }}</td>
                        </tr>
                    </table>
                </td>

                <td class="w-50 divider-v" style="padding:8px;">
                    @if ($workOrder->ticket?->branch?->logo)
                        <div style="text-align: center; margin-bottom: 10px;">
                            <img src="{{ public_path('storage/' . $workOrder->ticket?->branch?->logo) }}"
                                alt="Logo Branch" style="max-height: 60px; max-width: 150px; object-fit: contain;">
                        </div>
                    @endif
                    <div class="title center">{{ $workOrder->ticket?->branch?->name ?? 'BRANCH' }}</div>

                    <table class="tb">
                        <tr>
                            <td class="label">Nama Cabang</td>
                            <td class="colon">:</td>
                            <td class="bold">{{ $workOrder->ticket?->branch?->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Contact Person</td>
                            <td class="colon">:</td>
                            <td>{{ $workOrder->contact_person ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Telp / Hp</td>
                            <td class="colon">:</td>
                            <td>{{ $workOrder->contact_phone ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Alamat</td>
                            <td class="colon">:</td>
                            <td>{{ $workOrder->ticket?->branch?->address ?? '-' }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- SECTION -->
        <div class="section-title">JENIS PRODUK</div>

        <!-- PRODUCT / JOB -->
        <table class="tb">
            <tr>
                <!-- Left -->
                <td class="w-50" style="padding:0;">
                    <table class="no-border" style="width:100%;">
                        <tr>
                            <td class="label" style="padding:6px 8px;">Jenis Produk</td>
                            <td class="colon">:</td>
                            <td>{{ $workOrder->product_type ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="label" style="padding:6px 8px;">Merk</td>
                            <td class="colon">:</td>
                            <td>{{ $workOrder->brand ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="label" style="padding:6px 8px;">Tipe</td>
                            <td class="colon">:</td>
                            <td>{{ $workOrder->model ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="label" style="padding:6px 8px;">No. seri</td>
                            <td class="colon">:</td>
                            <td>{{ $workOrder->serial_number ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="label" style="padding:6px 8px;">Tanggal Pembelian</td>
                            <td class="colon">:</td>
                            <td>{{ $workOrder->purchase_date ? \Carbon\Carbon::parse($workOrder->purchase_date)->format('d/m/Y') : '-' }}
                            </td>
                        </tr>
                    </table>
                </td>

                <!-- Right -->
                <td class="w-50" style="padding:0;">
                    <table class="no-border" style="width:100%;">
                        <tr>
                            <td class="label" style="padding:6px 8px;">Deskripsi Pekerjaan</td>
                            <td class="colon">:</td>
                        </tr>
                        <tr>
                            <td>
                                <div class="job-description" style="padding:6px 8px;">
                                    {{ $workOrder->description ?? '-' }}</div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- SIGNATURES -->
        <table class="no-border" style="margin-top:18px;">
            <tr class="center bold">
                <td class="w-33">Manager</td>
                <td class="w-33">Teknisi Lapangan</td>
                <td class="w-33">Cabang</td>
            </tr>
            <tr>
                <td class="sig-box"></td>
                <td class="sig-box"></td>
                <td class="sig-box"></td>
            </tr>
            <tr>
                <td>
                    <div class="dotted"></div>
                </td>
                <td>
                    <div class="dotted"></div>
                </td>
                <td>
                    <div class="dotted"></div>
                </td>
            </tr>
        </table>

        <!-- Footer -->
        <div style="text-align: center; margin-top: 20px; font-size: 8px; font-style: italic;">
            <div>Dokumen ini dibuat secara otomatis oleh sistem GA Maintenance</div>
            <div>Tanggal cetak: {{ \Carbon\Carbon::now()->format('d/m/Y') }}</div>
        </div>
    </div>
</body>

</html>
