<?php

namespace Database\Seeders;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\WorkOrderStatus;
use App\Enums\WorkReportStatus;
use App\Models\Branch;
use App\Models\JobTemplate;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketReply;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkReport;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating dummy data...');

        // Create Branches
        $branches = $this->createBranches();
        $this->command->info('✓ Created ' . count($branches) . ' branches');

        // Create Users
        $users = $this->createUsers($branches);
        $this->command->info('✓ Created users (admins, staff, regular users)');

        // Create Job Templates
        $jobTemplates = $this->createJobTemplates($branches);
        $this->command->info('✓ Created ' . count($jobTemplates) . ' job templates');

        // Create Tickets
        $tickets = $this->createTickets($users, $branches);
        $this->command->info('✓ Created ' . count($tickets) . ' tickets');

        // Create Work Orders
        $workOrders = $this->createWorkOrders($tickets, $users);
        $this->command->info('✓ Created ' . count($workOrders) . ' work orders');

        // Create Work Reports
        $workReports = $this->createWorkReports($users, $branches, $jobTemplates);
        $this->command->info('✓ Created ' . count($workReports) . ' work reports');

        $this->command->info('');
        $this->command->info('🎉 Dummy data created successfully!');
    }

    private function createBranches(): array
    {
        $branchesData = [
            ['name' => 'Cabang Jakarta Pusat', 'address' => 'Jl. Thamrin No. 1, Jakarta Pusat', 'code' => 'JKTP'],
            ['name' => 'Cabang Jakarta Selatan', 'address' => 'Jl. Sudirman No. 25, Jakarta Selatan', 'code' => 'JKS1'],
            ['name' => 'Cabang Bandung', 'address' => 'Jl. Braga No. 10, Bandung', 'code' => 'BDG1'],
            ['name' => 'Cabang Surabaya', 'address' => 'Jl. Pemuda No. 55, Surabaya', 'code' => 'SBY1'],
            ['name' => 'Cabang Yogyakarta', 'address' => 'Jl. Malioboro No. 77, Yogyakarta', 'code' => 'YGY1'],
            ['name' => 'Cabang Semarang', 'address' => 'Jl. Pandanaran No. 33, Semarang', 'code' => 'SMG1'],
            ['name' => 'Cabang Medan', 'address' => 'Jl. Gatot Subroto No. 88, Medan', 'code' => 'MED1'],
            ['name' => 'Cabang Makassar', 'address' => 'Jl. Pettarani No. 45, Makassar', 'code' => 'MKS1'],
        ];

        $branches = [];
        foreach ($branchesData as $data) {
            $branches[] = Branch::firstOrCreate(
                ['name' => $data['name']],
                $data
            );
        }

        return $branches;
    }

    private function createUsers(array $branches): array
    {
        $users = [];

        // Create additional admin users
        $admins = [
            ['name' => 'Super Admin', 'email' => 'superadmin@helpdesk.com', 'position' => 'IT Manager'],
            ['name' => 'Admin Operasional', 'email' => 'admin.ops@helpdesk.com', 'position' => 'Operations Manager'],
        ];

        foreach ($admins as $admin) {
            $user = User::firstOrCreate(
                ['email' => $admin['email']],
                [
                    'name' => $admin['name'],
                    'email' => $admin['email'],
                    'password' => bcrypt('password'),
                    'position' => $admin['position'],
                    'phone_number' => '08' . rand(1000000000, 9999999999),
                    'identity_number' => 'ADM' . rand(1000, 9999),
                    'type' => 'internal',
                ]
            );
            $user->assignRole('admin');
            $users['admins'][] = $user;
        }

        // Create staff/technicians for each branch
        $staffNames = [
            'Budi Santoso',
            'Ahmad Rizki',
            'Dedi Kurniawan',
            'Eko Prasetyo',
            'Fajar Nugroho',
            'Gilang Ramadhan',
            'Hendra Wijaya',
            'Irfan Maulana',
            'Joko Susilo',
            'Krisna Putra',
            'Lukman Hakim',
            'Maman Suryaman',
        ];

        $staffIdx = 0;
        foreach ($branches as $branch) {
            // 1-2 staff per branch
            $staffCount = rand(1, 2);
            for ($i = 0; $i < $staffCount && $staffIdx < count($staffNames); $i++) {
                $user = User::firstOrCreate(
                    ['email' => 'staff' . ($staffIdx + 1) . '@helpdesk.com'],
                    [
                        'name' => $staffNames[$staffIdx],
                        'email' => 'staff' . ($staffIdx + 1) . '@helpdesk.com',
                        'password' => bcrypt('password'),
                        'branch_id' => $branch->id,
                        'position' => 'Teknisi',
                        'phone_number' => '08' . rand(1000000000, 9999999999),
                        'identity_number' => 'STF' . rand(1000, 9999),
                        'type' => 'internal',
                    ]
                );
                $user->assignRole('staff');
                $users['staff'][] = $user;
                $staffIdx++;
            }
        }

        // Create regular users
        $regularUsers = [
            ['name' => 'Siti Rahayu', 'email' => 'siti@company.com', 'position' => 'Office Manager'],
            ['name' => 'Dewi Lestari', 'email' => 'dewi@company.com', 'position' => 'HR Manager'],
            ['name' => 'Rina Anggraini', 'email' => 'rina@company.com', 'position' => 'Finance Staff'],
            ['name' => 'Putri Handayani', 'email' => 'putri@company.com', 'position' => 'Receptionist'],
            ['name' => 'Agus Setiawan', 'email' => 'agus@company.com', 'position' => 'Store Manager'],
            ['name' => 'Bambang Wijaya', 'email' => 'bambang@company.com', 'position' => 'Warehouse Staff'],
            ['name' => 'Cahya Pratama', 'email' => 'cahya@company.com', 'position' => 'Sales Executive'],
            ['name' => 'Diana Kusuma', 'email' => 'diana@company.com', 'position' => 'Marketing Staff'],
        ];

        foreach ($regularUsers as $idx => $userData) {
            $branch = $branches[$idx % count($branches)];
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => bcrypt('password'),
                    'branch_id' => $branch->id,
                    'position' => $userData['position'],
                    'phone_number' => '08' . rand(1000000000, 9999999999),
                    'identity_number' => 'USR' . rand(1000, 9999),
                    'type' => 'external',
                ]
            );
            $user->assignRole('user');
            $users['users'][] = $user;
        }

        return $users;
    }

    private function createJobTemplates(array $branches): array
    {
        $templates = [
            ['name' => 'Perawatan AC', 'description' => 'Pembersihan dan perawatan rutin AC'],
            ['name' => 'Pengecekan Listrik', 'description' => 'Inspeksi panel listrik dan kabel'],
            ['name' => 'Perawatan Genset', 'description' => 'Pengecekan dan perawatan generator set'],
            ['name' => 'Cleaning Service', 'description' => 'Pembersihan area kantor'],
            ['name' => 'Pengecekan Plumbing', 'description' => 'Inspeksi saluran air dan sanitasi'],
            ['name' => 'Perawatan CCTV', 'description' => 'Pengecekan dan pembersihan kamera CCTV'],
            ['name' => 'Pest Control', 'description' => 'Pengendalian hama berkala'],
            ['name' => 'Pengecekan Fire Safety', 'description' => 'Inspeksi alat pemadam dan jalur evakuasi'],
        ];

        $jobTemplates = [];
        foreach ($templates as $template) {
            $jobTemplate = JobTemplate::firstOrCreate(
                ['name' => $template['name']],
                [
                    'name' => $template['name'],
                    'description' => $template['description'],
                    'frequency' => ['daily', 'weekly', 'monthly'][rand(0, 2)],
                ]
            );

            // Assign random branches to template with required pivot data
            $randomBranches = collect($branches)->random(rand(3, count($branches)));
            foreach ($randomBranches as $branch) {
                // Check if not already attached
                if (!$jobTemplate->branches()->where('branch_id', $branch->id)->exists()) {
                    $jobTemplate->branches()->attach($branch->id, [
                        'started_at' => now()->subDays(rand(30, 365)),
                        'is_active' => true,
                    ]);
                }
            }

            $jobTemplates[] = $jobTemplate;
        }

        return $jobTemplates;
    }

    private function createTickets(array $users, array $branches): array
    {
        $ticketTemplates = [
            ['title' => 'AC tidak dingin', 'description' => 'AC di ruang meeting lantai 2 tidak dingin, sudah dicoba restart tapi tetap tidak dingin.'],
            ['title' => 'Lampu mati di lobby', 'description' => 'Beberapa lampu di area lobby depan tidak menyala sejak kemarin.'],
            ['title' => 'Kran air bocor', 'description' => 'Kran wastafel di toilet lantai 1 bocor dan mengeluarkan air terus menerus.'],
            ['title' => 'Pintu otomatis rusak', 'description' => 'Pintu otomatis di pintu masuk utama tidak merespon sensor dengan baik.'],
            ['title' => 'Lift tidak berfungsi', 'description' => 'Lift di gedung A tidak beroperasi, terjadi bunyi aneh saat ditekan tombol.'],
            ['title' => 'WiFi lambat', 'description' => 'Koneksi WiFi di area kerja sangat lambat sejak 2 hari terakhir.'],
            ['title' => 'Komputer error', 'description' => 'Komputer di bagian kasir sering restart sendiri dan menampilkan blue screen.'],
            ['title' => 'Printer tidak bisa print', 'description' => 'Printer di ruang admin tidak bisa mencetak, lampu power menyala tapi tidak merespon.'],
            ['title' => 'Stop kontak tidak berfungsi', 'description' => 'Stop kontak di meja kerja area marketing tidak ada aliran listrik.'],
            ['title' => 'Atap bocor', 'description' => 'Ada kebocoran di atap gudang saat hujan, air menetes ke area penyimpanan barang.'],
            ['title' => 'Kipas angin rusak', 'description' => 'Kipas angin di ruang istirahat karyawan tidak berputar meskipun sudah dinyalakan.'],
            ['title' => 'Mesin kasir error', 'description' => 'Mesin kasir menampilkan error dan tidak bisa memproses transaksi.'],
            ['title' => 'CCTV tidak merekam', 'description' => 'Beberapa CCTV di area parkir tidak merekam dan layar hitam.'],
            ['title' => 'Alarm kebakaran bunyi sendiri', 'description' => 'Alarm kebakaran berbunyi sendiri padahal tidak ada tanda kebakaran.'],
            ['title' => 'Genset tidak menyala', 'description' => 'Saat listrik padam, genset tidak otomatis menyala seperti biasa.'],
            ['title' => 'Kulkas tidak dingin', 'description' => 'Kulkas di pantry karyawan tidak dingin, makanan menjadi basi.'],
            ['title' => 'Telepon kantor mati', 'description' => 'Telepon kantor tidak ada nada sambung, tidak bisa menerima atau melakukan panggilan.'],
            ['title' => 'Kursi kerja rusak', 'description' => 'Hidrolik kursi kerja rusak, kursi tidak bisa dinaikkan atau diturunkan.'],
            ['title' => 'Plafon hampir jatuh', 'description' => 'Ada bagian plafon di koridor lantai 3 yang terlihat akan jatuh, sangat berbahaya.'],
            ['title' => 'Kunci pintu macet', 'description' => 'Kunci pintu ruang arsip macet dan susah dibuka atau dikunci.'],
        ];

        $priorities = [TicketPriority::LOW, TicketPriority::MEDIUM, TicketPriority::HIGH, TicketPriority::URGENT];
        $statuses = [TicketStatus::OPEN, TicketStatus::IN_PROGRESS, TicketStatus::RESOLVED, TicketStatus::CLOSED];

        $tickets = [];
        $regularUsers = $users['users'] ?? [];
        $staffUsers = $users['staff'] ?? [];

        foreach ($ticketTemplates as $idx => $template) {
            $user = $regularUsers[$idx % count($regularUsers)] ?? null;
            if (!$user)
                continue;

            $branch = $branches[$idx % count($branches)];
            $status = $statuses[array_rand($statuses)];
            $priority = $priorities[array_rand($priorities)];

            $ticketCode = "T-NO." . strtoupper(Str::random(3)) . "/SPK/" . ($branch->code ?? 'XXXX') . "/" . $this->getRomanMonth(now()->month) . "/" . now()->year;

            $ticket = Ticket::firstOrCreate(
                ['title' => $template['title'], 'user_id' => $user->id], // Use title and user to find existing, or use code if you want exact match but random code makes it hard
                [
                    'user_id' => $user->id,
                    'branch_id' => $branch->id,
                    'code' => $ticketCode,
                    'title' => $template['title'],
                    'description' => $template['description'],
                    'status' => $status,
                    'priority' => $priority,
                    'completed_at' => $status === TicketStatus::CLOSED ? now()->subDays(rand(1, 10)) : null,
                    'created_at' => now()->subDays(rand(1, 30)),
                ]
            );

            // Assign random staff to some tickets
            if ($status !== TicketStatus::OPEN && !empty($staffUsers)) {
                $assignedStaff = collect($staffUsers)->random(rand(1, 2));
                $ticket->assignedStaff()->attach($assignedStaff->pluck('id'));
            }

            // Add replies to some tickets
            if (rand(0, 1) && !empty($staffUsers)) {
                $repliesCount = rand(1, 3);
                $replyMessages = [
                    'Terima kasih atas laporannya. Tim kami akan segera menindaklanjuti.',
                    'Teknisi sudah dalam perjalanan ke lokasi.',
                    'Masalah sedang dalam proses perbaikan.',
                    'Perbaikan sudah selesai dilakukan. Mohon dicek kembali.',
                    'Kami memerlukan informasi tambahan untuk menyelesaikan masalah ini.',
                ];

                for ($i = 0; $i < $repliesCount; $i++) {
                    $replier = rand(0, 1) ? $staffUsers[array_rand($staffUsers)] : $user;
                    TicketReply::create([
                        'ticket_id' => $ticket->id,
                        'user_id' => $replier->id,
                        'content' => $replyMessages[array_rand($replyMessages)],
                        'created_at' => now()->subDays(rand(0, 10)),
                    ]);
                }
            }

            $tickets[] = $ticket;
        }

        return $tickets;
    }

    private function createWorkOrders(array $tickets, array $users): array
    {
        $workOrders = [];
        $staffUsers = $users['staff'] ?? [];

        if (empty($staffUsers)) {
            return $workOrders;
        }

        $woStatuses = [WorkOrderStatus::PENDING, WorkOrderStatus::IN_PROGRESS, WorkOrderStatus::DONE];

        // Create work orders for tickets that are not OPEN
        foreach ($tickets as $ticket) {
            if ($ticket->status === TicketStatus::OPEN) {
                continue;
            }

            $staff = $staffUsers[array_rand($staffUsers)];
            $woStatus = $woStatuses[array_rand($woStatuses)];

            // If ticket is resolved or closed, work order should be DONE
            if (in_array($ticket->status, [TicketStatus::RESOLVED, TicketStatus::CLOSED])) {
                $woStatus = WorkOrderStatus::DONE;
            }

            $workOrder = WorkOrder::firstOrCreate(
                ['ticket_id' => $ticket->id],
                [
                    'ticket_id' => $ticket->id,
                    'assigned_to' => $staff->id,
                    'number' => 'WO-' . date('Ymd') . '-' . str_pad(count($workOrders) + 1, 4, '0', STR_PAD_LEFT),
                    'description' => 'Work order untuk: ' . $ticket->title,
                    'status' => $woStatus,
                    'damage_unit' => 'Unit terkait: ' . $ticket->title,
                    'contact_person' => $ticket->user->name ?? 'N/A',
                    'contact_phone' => $ticket->user->phone_number ?? '-',
                    'created_at' => $ticket->created_at->addHours(rand(1, 24)),
                ]
            );

            $workOrders[] = $workOrder;
        }

        return $workOrders;
    }

    private function createWorkReports(array $users, array $branches, array $jobTemplates): array
    {
        $workReports = [];
        $staffUsers = $users['staff'] ?? [];

        if (empty($staffUsers) || empty($jobTemplates)) {
            return $workReports;
        }

        $reportStatuses = [WorkReportStatus::PROGRESS, WorkReportStatus::COMPLETED, WorkReportStatus::FAILED];

        $descriptions = [
            'Pekerjaan berjalan lancar, semua item sudah dicek.',
            'Ditemukan beberapa masalah minor yang sudah diperbaiki.',
            'Perawatan rutin selesai sesuai jadwal.',
            'Pengecekan menyeluruh sudah dilakukan, kondisi baik.',
            'Ada komponen yang perlu diganti, sudah diajukan ke purchasing.',
            'Pekerjaan tertunda karena menunggu spare part.',
            'Semua checklist sudah dikerjakan dengan baik.',
            'Kondisi peralatan normal, tidak ada kerusakan.',
        ];

        // Create 30 work reports
        for ($i = 0; $i < 30; $i++) {
            $staff = $staffUsers[array_rand($staffUsers)];
            $branch = $branches[array_rand($branches)];
            $jobTemplate = $jobTemplates[array_rand($jobTemplates)];
            $status = $reportStatuses[array_rand($reportStatuses)];

            $workReport = WorkReport::create([
                'user_id' => $staff->id,
                'branch_id' => $branch->id,
                'job_template_id' => $jobTemplate->id,
                'description' => $descriptions[array_rand($descriptions)],
                'status' => $status,
                'created_at' => now()->subDays(rand(0, 60)),
            ]);

            $workReports[] = $workReport;
        }

        return $workReports;
    }
    private function getRomanMonth($month)
    {
        $map = [
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            5 => 'V',
            6 => 'VI',
            7 => 'VII',
            8 => 'VIII',
            9 => 'IX',
            10 => 'X',
            11 => 'XI',
            12 => 'XII'
        ];
        return $map[$month] ?? $month;
    }
}
