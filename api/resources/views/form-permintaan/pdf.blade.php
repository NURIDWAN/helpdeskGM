<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <title>Form Permintaan - {{ $formPermintaan->request_number }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm 10mm 15mm 10mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }

        /* Header */
        .header-wrapper {
            position: relative;
            width: 100%;
            margin-bottom: 8px;
            min-height: 65px;
        }

        .logo-wrapper {
            position: absolute;
            left: 0;
            top: 0;
        }

        .logo-img {
            max-height: 65px;
            max-width: 100px;
            object-fit: contain;
        }

        .form-title {
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            padding-top: 15px;
        }

        /* Metadata + Jenis Permintaan side by side */
        .meta-outer {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }

        .meta-outer td {
            vertical-align: top;
            padding: 0;
        }

        .meta-left-col {
            width: 38%;
        }

        .meta-right-col {
            width: 62%;
        }

        /* Left side: Tanggal, User, Outlet, Prioritas */
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 2px 0;
            vertical-align: top;
            font-size: 11px;
        }

        .info-label {
            width: 65px;
        }

        .info-colon {
            width: 10px;
            text-align: center;
        }

        .info-value {
            font-weight: bold;
        }

        /* Right side: Jenis Permintaan */
        .jenis-table {
            width: 100%;
            border-collapse: collapse;
        }

        .jenis-table td {
            padding: 2px 0;
            vertical-align: top;
            font-size: 11px;
        }

        .jenis-label {
            width: 120px;
            white-space: nowrap;
        }

        .jenis-colon {
            width: 10px;
            text-align: center;
        }

        .jenis-value {
            font-weight: bold;
        }

        /* Note */
        .note-text {
            font-size: 9px;
            font-style: italic;
            margin-bottom: 8px;
            text-align: center;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #000;
            padding: 4px 5px;
            vertical-align: top;
        }

        .items-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: center;
            font-size: 10px;
        }

        .general-attachments {
            margin-bottom: 15px;
            clear: both;
        }

        .general-attachments-title {
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .general-attachment-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            table-layout: fixed;
        }

        .general-attachment-table td {
            width: 33.33%;
            padding: 4px;
            vertical-align: top;
        }

        .general-attachment-image {
            max-height: 100px;
            max-width: 140px;
            border: 1px solid #ddd;
            padding: 2px;
        }

        .general-attachment-links {
            display: block;
        }

        .center {
            text-align: center;
        }

        .rich-text-display p {
            margin: 0 0 4px 0;
        }

        .rich-text-display p:last-child {
            margin-bottom: 0;
        }

        .rich-text-display ul,
        .rich-text-display ol {
            margin: 4px 0;
            padding-left: 15px;
        }

        .attachment-links {
            margin-top: 8px;
            font-size: 9px;
        }

        .attachment-links-title {
            font-weight: bold;
            margin-bottom: 3px;
        }

        .attachment-links a {
            color: #0645ad;
            text-decoration: underline;
            word-break: break-all;
        }

        .attachment-image {
            display: inline-block;
            margin-right: 8px;
            margin-bottom: 5px;
        }

        .attachment-image img {
            max-height: 100px;
            max-width: 140px;
            border: 1px solid #ddd;
            padding: 2px;
            object-fit: contain;
        }

        /* Signatures */
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .signature-table td {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            padding: 5px;
        }

        .signature-title {
            font-weight: bold;
            font-size: 10px;
            margin-bottom: 60px;
        }

        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }

        .signature-role {
            font-size: 9px;
            color: #555;
        }

        .footer-note {
            text-align: center;
            margin-top: 25px;
            font-size: 8px;
            font-style: italic;
            color: #666;
            border-top: 1px dotted #ccc;
            padding-top: 5px;
        }
    </style>
</head>
<body>

    <!-- Header: Logo + Title -->
    <div class="header-wrapper">
        <div class="logo-wrapper">
            @if ($formPermintaan->branch && $formPermintaan->branch->logo && file_exists(public_path('storage/' . $formPermintaan->branch->logo)))
                <img src="{{ public_path('storage/' . $formPermintaan->branch->logo) }}" class="logo-img" alt="Logo">
            @endif
        </div>
        <div class="form-title">FORM PERMINTAAN</div>
    </div>

    <!-- Metadata Section: Left (Info) + Right (Jenis Permintaan) -->
    <table class="meta-outer">
        <tr>
            <!-- Left: Tanggal, User, Outlet, Prioritas -->
            <td class="meta-left-col">
                <table class="info-table">
                    <tr>
                        <td class="info-label">Tanggal</td>
                        <td class="info-colon">:</td>
                        <td class="info-value">{{ $formPermintaan->date ? \Carbon\Carbon::parse($formPermintaan->date)->format('d F Y') : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">User</td>
                        <td class="info-colon">:</td>
                        <td class="info-value">{{ strtoupper($formPermintaan->user->name ?? '-') }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Outlet</td>
                        <td class="info-colon">:</td>
                        <td class="info-value">{{ strtoupper($formPermintaan->branch->name ?? '-') }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Prioritas</td>
                        <td class="info-colon">:</td>
                        <td class="info-value" style="color: {{ $formPermintaan->priority === 'urgent' ? 'red' : 'black' }}">
                            {{ strtoupper($formPermintaan->priority ?? '-') }}
                        </td>
                    </tr>
                </table>
            </td>

            <!-- Right: Jenis Permintaan (only active type) -->
            <td class="meta-right-col">
                <table class="jenis-table">
                    <tr>
                        <td class="jenis-label">Jenis Permintaan</td>
                        <td class="jenis-colon">:</td>
                        <td class="jenis-value">
                            @if ($formPermintaan->request_type === 'pembelian_produk_baru')
                                Pembelian produk (unit) baru
                            @elseif ($formPermintaan->request_type === 'penggantian_produk_lama')
                                Penggantian produk (unit) lama
                            @elseif ($formPermintaan->request_type === 'servis')
                                Servis
                            @elseif ($formPermintaan->request_type === 'penggantian_part')
                                Penggantian part
                            @elseif ($formPermintaan->request_type === 'jasa')
                                Jasa
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    @if (in_array($formPermintaan->request_type, ['penggantian_produk_lama', 'servis', 'penggantian_part']))
                    <tr>
                        <td class="jenis-label">No. FA</td>
                        <td class="jenis-colon">:</td>
                        <td class="jenis-value">{{ $formPermintaan->fa_number ?: '-' }}</td>
                    </tr>
                    @endif
                    @if ($formPermintaan->request_type === 'pembelian_produk_baru')
                    <tr>
                        <td class="jenis-label">Alasan</td>
                        <td class="jenis-colon">:</td>
                        <td class="jenis-value">{{ $formPermintaan->reason ?: '-' }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="jenis-label" style="font-weight: bold;">No. Permintaan</td>
                        <td class="jenis-colon" style="font-weight: bold;">:</td>
                        <td class="jenis-value">{{ $formPermintaan->request_number }}</td>
                    </tr>
                    @if ($formPermintaan->ticket)
                    <tr>
                        <td class="jenis-label">Ticket</td>
                        <td class="jenis-colon">:</td>
                        <td class="jenis-value">{{ $formPermintaan->ticket->code ?? '-' }}</td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <div class="note-text">*Ketika ttd, mohon tulis tanggal nama, jabatan</div>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 45%;">Deskripsi Produk (Merk & Tipe)</th>
                <th style="width: 8%;">QTY</th>
                <th style="width: 10%;">UoM</th>
                <th style="width: 32%;">Catatan (bisa diisi untuk keperluan apa)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($formPermintaan->items as $index => $item)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td>
                        <div class="rich-text-display">{!! $item->product_description !!}</div>

                        <!-- Attachment Links -->
                        @if ($item->attachments && $item->attachments->count() > 0)
                            <div class="attachment-links">
                                <div class="attachment-links-title">Lampiran:</div>
                                @foreach ($item->attachments as $attachment)
                                    @if (Str::startsWith($attachment->file_type, 'image/') && file_exists(public_path('storage/' . $attachment->file_path)))
                                        <div class="attachment-image">
                                            <img src="{{ public_path('storage/' . $attachment->file_path) }}" alt="{{ $attachment->file_name }}">
                                        </div>
                                    @else
                                        <div>
                                            - <a href="{{ asset('storage/' . $attachment->file_path) }}">{{ $attachment->file_name }}</a>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </td>
                    <td class="center">{{ $item->quantity }}</td>
                    <td class="center">{{ $item->uom ?? '-' }}</td>
                    <td>{{ $item->notes ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @php
        $generalAttachments = $formPermintaan->attachments->whereNull('form_permintaan_item_id');
        $generalImageAttachments = $generalAttachments
            ->filter(fn ($attachment) => Str::startsWith($attachment->file_type, 'image/') && file_exists(public_path('storage/' . $attachment->file_path)))
            ->values();
        $generalFileAttachments = $generalAttachments
            ->filter(fn ($attachment) => !Str::startsWith($attachment->file_type, 'image/') || !file_exists(public_path('storage/' . $attachment->file_path)))
            ->values();
    @endphp

    @if ($generalAttachments->count() > 0)
        <div class="general-attachments">
            <div class="general-attachments-title">Lampiran Umum:</div>

            @if ($generalImageAttachments->count() > 0)
                <table class="general-attachment-table">
                    <tr>
                        @foreach ($generalImageAttachments as $index => $attachment)
                            @if ($index > 0 && $index % 3 === 0)
                                </tr><tr>
                            @endif
                            <td>
                                <img class="general-attachment-image" src="{{ public_path('storage/' . $attachment->file_path) }}" alt="{{ $attachment->file_name }}">
                            </td>
                        @endforeach
                        @for ($i = $generalImageAttachments->count() % 3; $i > 0 && $i < 3; $i++)
                            <td></td>
                        @endfor
                    </tr>
                </table>
            @endif

            @if ($generalFileAttachments->count() > 0)
                <div class="attachment-links general-attachment-links">
                    @foreach ($generalFileAttachments as $attachment)
                        <div>
                            - <a href="{{ asset('storage/' . $attachment->file_path) }}">{{ $attachment->file_name }}</a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
    <!-- Signature Section -->
    <table class="signature-table">
        <tr>
            <td>
                <div class="signature-title">DIAJUKAN OLEH</div>
                <div class="signature-name">{{ $formPermintaan->user->name ?? '-' }}</div>
                <div class="signature-role">Pemohon</div>
            </td>
            <td>
                <div class="signature-title">DIVERIFIKASI OLEH</div>
                <div class="signature-name">
                    @if ($formPermintaan->confirmedBy)
                        {{ $formPermintaan->confirmedBy->name }}
                    @else
                        ( &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; )
                    @endif
                </div>
                <div class="signature-role">Supervisor / Verifikator</div>
            </td>
            <td>
                <div class="signature-title">DISETUJUI OLEH</div>
                <div class="signature-name">( &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; )</div>
                <div class="signature-role">Manager / Approver</div>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        Dokumen ini digenerate secara otomatis oleh GA Maintenance Helpdesk System pada {{ date('d/m/Y H:i') }}.
    </div>

</body>
</html>
