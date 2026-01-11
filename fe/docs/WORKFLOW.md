# Alur Proses & Konfigurasi Sistem

Dokumen ini menjelaskan urutan proses kerja (workflow) sistem Helpdesk GM agar data terintegrasi dengan baik.

---

## Daftar Isi

1. [Overview Alur Sistem](#overview-alur-sistem)
2. [Inisiasi Sistem (Superadmin)](#1-inisiasi-sistem-superadmin)
3. [Persiapan Data Master (Admin)](#2-persiapan-data-master-admin)
4. [Alur Laporan Utilitas Harian](#3-alur-laporan-utilitas-harian)
5. [Alur Penanganan Masalah (End-to-End Ticketing)](#4-alur-penanganan-masalah-end-to-end-ticketing)
6. [Alur Pelaporan & Audit](#5-alur-pelaporan--audit)

---

## Overview Alur Sistem

```mermaid
flowchart TB
    subgraph Phase1["FASE 1: INISIASI"]
        A[Superadmin Login] --> B[Setup Role & User]
        B --> C[Konfigurasi WhatsApp Gateway]
    end

    subgraph Phase2["FASE 2: PERSIAPAN DATA"]
        D[Admin Login] --> E[Setup Data Cabang]
        E --> F[Setup Job Template]
        F --> G[Setup Kategori Tiket]
    end

    subgraph Phase3["FASE 3: OPERASIONAL"]
        H[Laporan Utilitas Harian]
        I[Penanganan Tiket]
        J[Pelaporan & Audit]
    end

    Phase1 --> Phase2
    Phase2 --> Phase3
    
    H --> J
    I --> J
```

---

## 1. Inisiasi Sistem (Superadmin)

Langkah pertama sebelum aplikasi dapat digunakan adalah konfigurasi dasar oleh Superadmin.

### 1.1 Manajemen Role & User

```mermaid
flowchart TD
    Start([Superadmin Login]) --> CheckRole{Role sudah ada?}
    
    CheckRole -->|Tidak| CreateRole[Buat Role Baru]
    CreateRole --> SetPermissions[Atur Permission Role]
    SetPermissions --> SaveRole[Simpan Role]
    SaveRole --> CheckRole
    
    CheckRole -->|Ya| CheckUser{User sudah ada?}
    
    CheckUser -->|Tidak| CreateUser[Buat User Baru]
    CreateUser --> FillUserData[Isi Data User:<br/>- Nama<br/>- Email<br/>- Password<br/>- No. HP]
    FillUserData --> AssignRole[Tetapkan Role]
    AssignRole --> AssignBranch[Tetapkan Cabang]
    AssignBranch --> SaveUser{Simpan User}
    
    SaveUser -->|Gagal - Data tidak valid| FillUserData
    SaveUser -->|Berhasil| CheckUser
    
    CheckUser -->|Ya, Semua siap| SetupWA[Lanjut Konfigurasi WhatsApp]
    
    style Start fill:#4CAF50,color:#fff
    style SetupWA fill:#2196F3,color:#fff
```

### 1.2 Konfigurasi WhatsApp Gateway

```mermaid
flowchart TD
    Start([Buka Menu WhatsApp Settings]) --> InputAPI[Input API Key Fonnte]
    InputAPI --> TestAPI{Test Koneksi API}
    
    TestAPI -->|Gagal| ErrorAPI[Tampilkan Error:<br/>API Key tidak valid]
    ErrorAPI --> InputAPI
    
    TestAPI -->|Berhasil| InputGroup[Input ID Group WhatsApp]
    InputGroup --> TestGroup{Test Kirim ke Group}
    
    TestGroup -->|Gagal| ErrorGroup[Tampilkan Error:<br/>Group ID tidak valid]
    ErrorGroup --> InputGroup
    
    TestGroup -->|Berhasil| SetupTemplate[Setup Template Pesan]
    
    SetupTemplate --> TemplateTicket[Template Tiket Baru]
    TemplateTicket --> TemplateSPK[Template SPK Baru]
    TemplateSPK --> TemplateResolve[Template Tiket Resolve]
    TemplateResolve --> TemplateClose[Template Tiket Close]
    
    TemplateClose --> SaveConfig{Simpan Konfigurasi}
    
    SaveConfig -->|Gagal| ErrorSave[Tampilkan Error]
    ErrorSave --> SetupTemplate
    
    SaveConfig -->|Berhasil| Done([Konfigurasi WhatsApp Selesai])
    
    style Start fill:#25D366,color:#fff
    style Done fill:#4CAF50,color:#fff
```

---

## 2. Persiapan Data Master (Admin)

Setelah user terbentuk, Admin bertugas menyiapkan "wadah" data agar operasional berjalan lancar.

```mermaid
flowchart TD
    Start([Admin Login]) --> CheckBranch{Data Cabang ada?}
    
    %% Branch Setup
    CheckBranch -->|Tidak| CreateBranch[Buat Data Cabang]
    CreateBranch --> FillBranch[Isi Data:<br/>- Nama Cabang<br/>- Alamat<br/>- Kode Cabang]
    FillBranch --> SaveBranch{Simpan Cabang}
    
    SaveBranch -->|Gagal - Kode duplikat| FillBranch
    SaveBranch -->|Berhasil| CheckBranch
    
    CheckBranch -->|Ya| CheckTemplate{Job Template ada?}
    
    %% Job Template Setup
    CheckTemplate -->|Tidak| CreateTemplate[Buat Job Template]
    CreateTemplate --> FillTemplate[Isi Data:<br/>- Nama Template<br/>- Deskripsi<br/>- Estimasi Durasi<br/>- Cabang]
    FillTemplate --> SaveTemplate{Simpan Template}
    
    SaveTemplate -->|Gagal| FillTemplate
    SaveTemplate -->|Berhasil| CheckTemplate
    
    CheckTemplate -->|Ya| CheckCategory{Kategori Tiket ada?}
    
    %% Ticket Category Setup
    CheckCategory -->|Tidak| CreateCategory[Buat Kategori Tiket]
    CreateCategory --> FillCategory[Isi Data:<br/>- Nama Kategori<br/>- Deskripsi<br/>- Prioritas Default]
    FillCategory --> SaveCategory{Simpan Kategori}
    
    SaveCategory -->|Gagal - Nama duplikat| FillCategory
    SaveCategory -->|Berhasil| CheckCategory
    
    CheckCategory -->|Ya| Done([Data Master Siap])
    
    style Start fill:#2196F3,color:#fff
    style Done fill:#4CAF50,color:#fff
```

### Checklist Data Master

```mermaid
flowchart LR
    subgraph Required["DATA WAJIB"]
        A[Cabang] --> B[Kategori Tiket]
    end
    
    subgraph Optional["DATA OPSIONAL"]
        C[Job Template]
        D[Meter Listrik]
    end
    
    Required --> Ready([Siap Operasional])
    Optional -.-> Ready
    
    style Ready fill:#4CAF50,color:#fff
```

---

## 3. Alur Laporan Utilitas Harian

Proses ini dilakukan rutin setiap hari untuk pencatatan energi (Listrik, Air, Gas).

```mermaid
flowchart TD
    Start([Admin Cabang Login]) --> SelectDate[Pilih Tanggal Laporan]
    SelectDate --> CheckExist{Laporan tanggal ini<br/>sudah ada?}
    
    CheckExist -->|Ya| EditOrView{Edit atau Lihat?}
    EditOrView -->|Lihat| ViewReport[Tampilkan Detail Laporan]
    ViewReport --> End1([Selesai])
    
    EditOrView -->|Edit| EditForm[Buka Form Edit]
    
    CheckExist -->|Tidak| CreateForm[Buka Form Baru]
    
    CreateForm --> InputElectricity[Input Pembacaan Listrik]
    EditForm --> InputElectricity
    
    InputElectricity --> HasMultiMeter{Punya Multi Meter?}
    
    HasMultiMeter -->|Ya| InputMultiMeter[Input Setiap Meter:<br/>- ID Meter<br/>- Stand Awal<br/>- Stand Akhir<br/>- kWh Terpakai]
    HasMultiMeter -->|Tidak| InputSingleMeter[Input Stand Meter:<br/>- Stand Awal<br/>- Stand Akhir]
    
    InputMultiMeter --> InputWater
    InputSingleMeter --> InputWater
    
    InputWater[Input Pembacaan Air] --> InputGas[Input Pembacaan Gas]
    InputGas --> InputNotes[Input Catatan/Keterangan]
    
    InputNotes --> Validate{Validasi Data}
    
    Validate -->|Tidak Valid<br/>Nilai negatif/kosong| ShowError[Tampilkan Error]
    ShowError --> InputElectricity
    
    Validate -->|Valid| SaveReport{Simpan Laporan}
    
    SaveReport -->|Gagal| ErrorSave[Tampilkan Error Server]
    ErrorSave --> InputNotes
    
    SaveReport -->|Berhasil| Success[Laporan Tersimpan]
    Success --> NotifyDashboard[Update Dashboard<br/>Grafik Utilitas]
    NotifyDashboard --> End2([Selesai])
    
    style Start fill:#009688,color:#fff
    style End1 fill:#4CAF50,color:#fff
    style End2 fill:#4CAF50,color:#fff
```

### Monitoring Laporan Utilitas

```mermaid
flowchart TD
    Start([User/Manajemen Login]) --> SelectView{Pilih Tampilan}
    
    SelectView -->|Dashboard| ViewDashboard[Lihat Dashboard]
    ViewDashboard --> ChartUtility[Grafik Penggunaan<br/>Listrik/Air/Gas]
    ChartUtility --> CompareData[Bandingkan dengan<br/>Periode Sebelumnya]
    
    SelectView -->|Laporan Detail| ViewList[Lihat Daftar Laporan]
    ViewList --> FilterBranch[Filter per Cabang]
    FilterBranch --> FilterDate[Filter per Tanggal]
    FilterDate --> ViewDetail[Lihat Detail Laporan]
    
    SelectView -->|Export| ExportReport[Export Laporan]
    ExportReport --> SelectFormat{Pilih Format}
    SelectFormat -->|PDF| ExportPDF[Generate PDF]
    SelectFormat -->|Excel| ExportExcel[Generate Excel]
    
    CompareData --> End1([Selesai])
    ViewDetail --> End2([Selesai])
    ExportPDF --> End3([Selesai])
    ExportExcel --> End4([Selesai])
    
    style Start fill:#673AB7,color:#fff
```

---

## 4. Alur Penanganan Masalah (End-to-End Ticketing)

Ini adalah **inti dari sistem Helpdesk**. Berikut adalah urutan kejadian dari awal kerusakan hingga selesai dalam **satu flowchart komprehensif**.

```mermaid
flowchart TD
    %% ==================== FASE 1: USER BUAT TIKET ====================
    subgraph FASE1["FASE 1: PEMBUATAN TIKET (USER)"]
        Start([USER LOGIN]) --> OpenForm[Buka Form Tiket Baru]
        OpenForm --> SelectCategory[Pilih Kategori Masalah]
        
        SelectCategory --> CategoryExist{Kategori<br/>tersedia?}
        CategoryExist -->|Tidak| ContactAdmin[Hubungi Admin untuk<br/>tambah kategori]
        ContactAdmin --> EndNoCategory([GAGAL: Tidak bisa lanjut])
        
        CategoryExist -->|Ya| FillTicket[Isi Data Tiket:<br/>- Judul<br/>- Deskripsi<br/>- Prioritas]
        FillTicket --> AttachFile{Lampirkan<br/>Foto?}
        
        AttachFile -->|Ya| UploadPhoto[Upload Foto Kerusakan]
        UploadPhoto --> ValidateFile{File Valid?<br/>Max 5MB}
        ValidateFile -->|Tidak| ErrorFile[Error: File tidak valid]
        ErrorFile --> AttachFile
        ValidateFile -->|Ya| SubmitTicket
        
        AttachFile -->|Tidak| SubmitTicket[Submit Tiket]
        
        SubmitTicket --> SaveTicket{Simpan ke<br/>Database}
        SaveTicket -->|Gagal| ErrorSave1[Tampilkan Error]
        ErrorSave1 --> FillTicket
        
        SaveTicket -->|Berhasil| TicketCreated[TIKET DIBUAT<br/>Status: OPEN]
        TicketCreated --> NotifTicket[Kirim Notifikasi WA]
        NotifTicket --> NotifToStaff[ke Staff Cabang]
        NotifTicket --> NotifToGroup[ke Grup Maintenance]
    end

    %% ==================== FASE 2: ADMIN TRIAGE ====================
    subgraph FASE2["FASE 2: TRIAGE & ASSIGN (ADMIN)"]
        NotifToStaff --> AdminView[ADMIN: Lihat Tiket Masuk]
        NotifToGroup --> AdminView
        
        AdminView --> ReviewTicket[Review Detail Tiket]
        ReviewTicket --> ValidTicket{Tiket Valid?}
        
        ValidTicket -->|Tidak - Spam/Duplikat| RejectTicket[Tolak Tiket]
        RejectTicket --> AddRejectReason[Tambah Alasan Penolakan]
        AddRejectReason --> TicketRejected[Status: REJECTED]
        TicketRejected --> NotifyReject[Notifikasi ke User:<br/>Tiket Ditolak]
        NotifyReject --> EndRejected([SELESAI: Tiket Ditolak])
        
        ValidTicket -->|Ya| AssignStaff[Assign Staff/Teknisi]
        AssignStaff --> SelectStaff{Pilih Staff}
        
        SelectStaff -->|Staff Lokal| PickLocal[Pilih Staff Cabang]
        SelectStaff -->|Butuh BKO| PickBKO[Pilih Staff dari<br/>Cabang Lain]
        
        PickLocal --> NeedSPK
        PickBKO --> NeedSPK{Perlu SPK?}
        
        NeedSPK -->|Tidak - Minor| DirectAssign[Update Tiket:<br/>IN PROGRESS]
        DirectAssign --> NotifyDirectStaff[Notifikasi ke Staff]
        NotifyDirectStaff --> StaffReceive
        
        NeedSPK -->|Ya| CreateSPK[Buat SPK]
        CreateSPK --> FillSPK[Isi Data SPK:<br/>- Judul Pekerjaan<br/>- Deskripsi<br/>- Deadline<br/>- Staff Ditugaskan]
        FillSPK --> LinkToTicket[Link ke Tiket]
        LinkToTicket --> SaveSPK{Simpan SPK}
        
        SaveSPK -->|Gagal| ErrorSPK[Tampilkan Error]
        ErrorSPK --> FillSPK
        
        SaveSPK -->|Berhasil| SPKCreated[SPK DIBUAT<br/>Status: PENDING]
        SPKCreated --> UpdateTicketAssigned[Tiket: ASSIGNED]
        UpdateTicketAssigned --> NotifySPKCreated[Kirim Notifikasi WA]
        NotifySPKCreated --> NotifySPKStaff[ke Staff: SPK Baru]
        NotifySPKCreated --> NotifySPKGroup[ke Grup: SPK Diterbitkan]
    end

    %% ==================== FASE 3: STAFF EKSEKUSI ====================
    subgraph FASE3["FASE 3: EKSEKUSI PEKERJAAN (STAFF)"]
        NotifySPKStaff --> StaffReceive[STAFF: Terima Notifikasi]
        NotifySPKGroup --> StaffReceive
        
        StaffReceive --> ViewSPK[Lihat Detail SPK & Tiket]
        ViewSPK --> CanHandle{Bisa<br/>Ditangani?}
        
        CanHandle -->|Tidak - Butuh Bantuan| RequestHelp[Request Bantuan ke Admin]
        RequestHelp --> WaitReassign[Tunggu Reassignment]
        WaitReassign --> AdminView
        
        CanHandle -->|Tidak - Alat Tidak Ada| ReportKendala[Laporkan Kendala]
        ReportKendala --> AddKendalaComment[Tambah Komentar di Tiket]
        AddKendalaComment --> WaitReassign
        
        CanHandle -->|Ya| ConfirmStart[Konfirmasi Mulai Kerja]
        ConfirmStart --> TicketInProgress[Tiket: IN PROGRESS]
        TicketInProgress --> SPKInProgress[SPK: IN PROGRESS]
        
        SPKInProgress --> DoWork[Kerjakan Perbaikan<br/>di Lapangan]
        
        DoWork --> WorkComplete{Pekerjaan<br/>Selesai?}
        
        WorkComplete -->|Tidak - Ada Kendala| ReportProblem[Laporkan Masalah]
        ReportProblem --> AddReply[Tambah Balasan di Tiket]
        AddReply --> WaitDecision{Keputusan<br/>Admin?}
        WaitDecision -->|Lanjutkan| DoWork
        WaitDecision -->|Cancel| CancelSPK[Cancel SPK]
        CancelSPK --> SPKCancelled[SPK: CANCELLED]
        SPKCancelled --> EndCancelled([SELESAI: SPK Dibatalkan])
        
        WorkComplete -->|Ya| CompleteSPK[Klik Selesai SPK]
        CompleteSPK --> SPKDone[SPK: DONE]
        SPKDone --> NotifyWorkDone[Notifikasi ke Grup:<br/>Pekerjaan Selesai]
        
        NotifyWorkDone --> ResolveTicket[Update Tiket: RESOLVED]
        ResolveTicket --> NotifyUserResolved[Notifikasi ke User:<br/>Masalah Diperbaiki]
        
        NotifyUserResolved --> CreateWorkReport[Buat Laporan Pekerjaan]
        CreateWorkReport --> FillWorkReport[Isi Detail:<br/>- Deskripsi Pekerjaan<br/>- Material Digunakan<br/>- Waktu Pengerjaan]
        FillWorkReport --> AttachBukti{Upload<br/>Foto Bukti?}
        
        AttachBukti -->|Ya| UploadBukti[Upload Foto Before/After]
        UploadBukti --> SaveWorkReport
        AttachBukti -->|Tidak| SaveWorkReport[Simpan Laporan]
        
        SaveWorkReport --> ReportSaved{Berhasil?}
        ReportSaved -->|Tidak| ErrorReport[Error Simpan]
        ErrorReport --> FillWorkReport
        ReportSaved -->|Ya| WorkReportDone[Laporan Tersimpan]
    end

    %% ==================== FASE 4: USER CLOSE TIKET ====================
    subgraph FASE4["FASE 4: PENUTUPAN TIKET (USER)"]
        WorkReportDone --> UserNotified[USER: Terima Notifikasi<br/>Tiket Resolved]
        
        UserNotified --> UserCheckResult[Periksa Hasil Pekerjaan]
        UserCheckResult --> UserSatisfied{Puas dengan<br/>Hasil?}
        
        UserSatisfied -->|Tidak| UserFeedback[Tambah Komentar/Feedback]
        UserFeedback --> WantReopen{Buka Ulang<br/>Tiket?}
        
        WantReopen -->|Ya| ReopenTicket[Reopen Tiket]
        ReopenTicket --> TicketReopened[Status: REOPENED]
        TicketReopened --> NotifyReopen[Notifikasi ke Admin & Staff]
        NotifyReopen --> AdminView
        
        WantReopen -->|Tidak| SubmitFeedbackOnly[Submit Feedback Saja]
        SubmitFeedbackOnly --> UserCheckResult
        
        UserSatisfied -->|Ya| CloseTicket[Klik Tombol Close/Selesai]
        CloseTicket --> ConfirmClose{Konfirmasi<br/>Penutupan?}
        
        ConfirmClose -->|Batal| UserCheckResult
        ConfirmClose -->|Ya| TicketClosed[TIKET DITUTUP<br/>Status: CLOSED]
        
        TicketClosed --> NotifyClose[Kirim Notifikasi WA]
        NotifyClose --> NotifyCloseStaff[ke Staff: Tiket Ditutup]
        NotifyClose --> NotifyCloseGroup[ke Grup: Tiket Selesai]
        
        NotifyCloseStaff --> EndSuccess([SELESAI: Masalah Tuntas])
        NotifyCloseGroup --> EndSuccess
    end

    %% ==================== STYLING ====================
    %% Start & End nodes
    style Start fill:#FF9800,color:#fff
    style EndNoCategory fill:#9E9E9E,color:#fff
    style EndRejected fill:#f44336,color:#fff
    style EndCancelled fill:#f44336,color:#fff
    style EndSuccess fill:#4CAF50,color:#fff
    
    %% Key status changes
    style TicketCreated fill:#2196F3,color:#fff
    style TicketRejected fill:#f44336,color:#fff
    style SPKCreated fill:#9C27B0,color:#fff
    style UpdateTicketAssigned fill:#3F51B5,color:#fff
    style TicketInProgress fill:#00BCD4,color:#fff
    style SPKInProgress fill:#00BCD4,color:#fff
    style SPKDone fill:#009688,color:#fff
    style SPKCancelled fill:#f44336,color:#fff
    style ResolveTicket fill:#8BC34A,color:#fff
    style TicketReopened fill:#FF5722,color:#fff
    style TicketClosed fill:#4CAF50,color:#fff
```

### Ringkasan Status Tiket

```mermaid
stateDiagram-v2
    [*] --> OPEN: User Buat Tiket
    
    OPEN --> ASSIGNED: Admin Assign Staff
    OPEN --> REJECTED: Admin Tolak (Invalid)
    
    ASSIGNED --> IN_PROGRESS: Staff Konfirmasi
    ASSIGNED --> OPEN: Admin Unassign
    
    IN_PROGRESS --> RESOLVED: Staff Selesai
    IN_PROGRESS --> ASSIGNED: Staff Request Reassign
    
    RESOLVED --> CLOSED: User Close
    RESOLVED --> REOPENED: User Buka Ulang
    
    REOPENED --> ASSIGNED: Admin Re-assign
    REOPENED --> IN_PROGRESS: Staff Lanjutkan
    
    REJECTED --> [*]
    CLOSED --> [*]
```

### Ringkasan Status SPK

```mermaid
stateDiagram-v2
    [*] --> PENDING: Admin Buat SPK
    
    PENDING --> IN_PROGRESS: Staff Konfirmasi
    PENDING --> CANCELLED: Admin Cancel
    
    IN_PROGRESS --> DONE: Staff Selesaikan
    IN_PROGRESS --> PENDING: Staff Return (Butuh Bantuan)
    IN_PROGRESS --> CANCELLED: Admin Cancel
    
    CANCELLED --> [*]
    DONE --> [*]
```

---

## 5. Alur Pelaporan & Audit

Setelah operasional berjalan, Admin/Manajemen melakukan evaluasi.

```mermaid
flowchart TD
    Start([Admin/Manajemen Login]) --> SelectReport{Pilih Jenis Laporan}
    
    %% Work Report Review
    SelectReport -->|Laporan Pekerjaan| ViewWorkReports[Lihat Daftar Laporan Pekerjaan]
    ViewWorkReports --> FilterWR[Filter:<br/>- Cabang<br/>- Staff<br/>- Periode]
    FilterWR --> ReviewWR[Review Detail Laporan]
    ReviewWR --> CheckPhoto[Periksa Foto Bukti]
    CheckPhoto --> ApproveWR{Approve Laporan?}
    ApproveWR -->|Ya| MarkApproved[Tandai Approved]
    ApproveWR -->|Tidak| RequestRevision[Minta Revisi ke Staff]
    
    %% Ticket Report
    SelectReport -->|Laporan Tiket| TicketReport[Generate Laporan Tiket]
    TicketReport --> SetPeriod1[Pilih Periode:<br/>Harian/Mingguan/Bulanan]
    SetPeriod1 --> SelectBranch1[Pilih Cabang]
    SelectBranch1 --> GenerateTicketReport[Generate Laporan]
    GenerateTicketReport --> ShowStats1[Tampilkan Statistik:<br/>- Total Tiket<br/>- Per Status<br/>- Per Kategori<br/>- Avg Response Time]
    
    %% Staff Performance
    SelectReport -->|Kinerja Staff| StaffReport[Generate Laporan Kinerja]
    StaffReport --> SetPeriod2[Pilih Periode]
    SetPeriod2 --> SelectStaff[Pilih Staff/Semua]
    SelectStaff --> GenerateStaffReport[Generate Laporan]
    GenerateStaffReport --> ShowStats2[Tampilkan Statistik:<br/>- Tiket Ditangani<br/>- SPK Diselesaikan<br/>- Avg Completion Time<br/>- Rating]
    
    %% Utility Report
    SelectReport -->|Laporan Utilitas| UtilityReport[Generate Laporan Harian Cabang]
    UtilityReport --> SetPeriod3[Pilih Periode]
    SetPeriod3 --> SelectBranch2[Pilih Cabang]
    SelectBranch2 --> GenerateUtilityReport[Generate Laporan]
    GenerateUtilityReport --> ShowStats3[Tampilkan Statistik:<br/>- Konsumsi Listrik<br/>- Konsumsi Air<br/>- Konsumsi Gas<br/>- Tren Penggunaan]
    
    %% Export Options
    MarkApproved --> ExportOption
    RequestRevision --> End1([Tunggu Revisi])
    ShowStats1 --> ExportOption
    ShowStats2 --> ExportOption
    ShowStats3 --> ExportOption
    
    ExportOption{Export Laporan?}
    ExportOption -->|Tidak| End2([Selesai])
    ExportOption -->|Ya| SelectFormat{Pilih Format}
    
    SelectFormat -->|PDF| GeneratePDF[Generate PDF]
    SelectFormat -->|Excel| GenerateExcel[Generate Excel]
    SelectFormat -->|Print| PrintDirect[Print Langsung]
    
    GeneratePDF --> Download[Download File]
    GenerateExcel --> Download
    PrintDirect --> End3([Selesai Print])
    Download --> End4([Selesai Download])
    
    style Start fill:#673AB7,color:#fff
    style End1 fill:#FF9800,color:#fff
    style End2 fill:#4CAF50,color:#fff
    style End3 fill:#4CAF50,color:#fff
    style End4 fill:#4CAF50,color:#fff
```

### Dashboard Monitoring

```mermaid
flowchart TD
    Start([Buka Dashboard]) --> LoadData[Load Data Dashboard]
    
    LoadData --> Metrics[Tampilkan Metrik Utama]
    Metrics --> M1[Total Tiket Bulan Ini]
    Metrics --> M2[Total SPK Aktif]
    Metrics --> M3[Tiket Pending]
    Metrics --> M4[Avg Response Time]
    
    LoadData --> Charts[Tampilkan Grafik]
    Charts --> C1[Grafik Tiket per Status]
    Charts --> C2[Grafik Tiket per Kategori]
    Charts --> C3[Grafik Tren Mingguan]
    Charts --> C4[Grafik Utilitas Harian]
    
    LoadData --> Rankings[Tampilkan Ranking]
    Rankings --> R1[Top Staff Performer]
    Rankings --> R2[Cabang dengan Tiket Terbanyak]
    
    M1 --> Interactive{Klik untuk Detail?}
    M2 --> Interactive
    M3 --> Interactive
    M4 --> Interactive
    C1 --> Interactive
    C2 --> Interactive
    C3 --> Interactive
    C4 --> Interactive
    R1 --> Interactive
    R2 --> Interactive
    
    Interactive -->|Ya| DrillDown[Drill Down ke Detail]
    DrillDown --> ViewList[Lihat Daftar Data]
    
    Interactive -->|Tidak| End([Selesai])
    ViewList --> End
    
    style Start fill:#3F51B5,color:#fff
```

---

## Ringkasan Notifikasi WhatsApp

| Event | Penerima | Contoh Pesan |
|-------|----------|--------------|
| Tiket Baru | Staff Cabang, Grup WA | "Tiket baru #TKT-001: AC Rusak di Ruang Meeting" |
| SPK Dibuat | Staff yang Ditugaskan | "SPK baru #SPK-001 untuk Anda. Deadline: 2024-01-15" |
| SPK Selesai | Grup WA | "SPK #SPK-001 telah diselesaikan oleh Staff A" |
| Tiket Resolved | User Pelapor | "Tiket #TKT-001 sudah diperbaiki. Silakan cek dan tutup tiket." |
| Tiket Closed | Staff, Grup WA | "Tiket #TKT-001 ditutup oleh User. Terima kasih!" |
| Tiket Reopened | Admin, Staff | "Tiket #TKT-001 dibuka ulang oleh User. Alasan: ..." |

---

## Catatan Penting

1. **Urutan Konfigurasi Wajib:**
   - Superadmin setup Role & User terlebih dahulu
   - Admin setup Data Master sebelum operasional
   - Tanpa Kategori Tiket, User tidak bisa membuat tiket

2. **Dependency Permission:**
   - Untuk melihat menu, user butuh permission `*-menu`
   - Untuk CRUD, user butuh permission `*-list`, `*-create`, `*-edit`, `*-delete`

3. **Notifikasi WhatsApp:**
   - Membutuhkan API Key Fonnte yang valid
   - Group ID harus sudah terdaftar
   - Bot WhatsApp harus sudah di-add ke grup
