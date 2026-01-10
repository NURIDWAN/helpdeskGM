<?php

namespace Database\Seeders;

use App\Models\WhatsAppTemplate;
use Illuminate\Database\Seeder;

class WhatsAppTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'type' => 'ticket_created',
                'name' => 'Tiket Baru (Group)',
                'content' => "🚨 *TIKET BARU DITERIMA* 🚨\n\nAda tiket baru masuk! Mohon segera dicek untuk ditentukan penanganannya.\n\n📋 *Kode:* {ticket_code}\n📝 *Judul:* {title}\n👤 *Pelapor:* {reporter_name}\n🏢 *Cabang:* {branch_name}\n⚡ *Prioritas:* {priority}\n📅 *Dibuat:* {created_at}\n\n📄 *Deskripsi:*\n{description}",
                'send_to_group' => true,
                'is_active' => true,
            ],
            [
                'type' => 'ticket_assigned',
                'name' => 'Tiket Ditugaskan (Staff)',
                'content' => "Halo {staff_name},\n\nAnda telah ditugaskan untuk menangani tiket *{ticket_code}*.\nSilakan cek detail kendala dan mulai pengerjaan.\n\n📝 *Judul:* {title}\n👤 *Pelapor:* {reporter_name}\n🏢 *Cabang:* {branch_name}\n⚡ *Prioritas:* {priority}\n\n📄 *Deskripsi:*\n{description}",
                'send_to_group' => false,
                'is_active' => true,
            ],
            [
                'type' => 'ticket_status_progress',
                'name' => 'Status Progress (User)',
                'content' => "Halo {reporter_name},\n\nKabar terbaru! Tiket Anda *{ticket_code}* saat ini sedang dalam proses pengerjaan oleh tim kami.\n\n📝 *Judul:* {title}\n📊 *Status:* {new_status}\n\nMohon ditunggu update selanjutnya.",
                'send_to_group' => false,
                'is_active' => true,
            ],
            [
                'type' => 'ticket_status_resolved',
                'name' => 'Status Resolved (User)',
                'content' => "Halo {reporter_name},\n\nKabar baik! Kendala Anda pada tiket *{ticket_code}* sudah diperbaiki.\n\n📝 *Judul:* {title}\n📊 *Status:* {new_status}\n\nSilakan dicek kembali, jika sudah oke, tiket bisa ditutup.",
                'send_to_group' => false,
                'is_active' => true,
            ],
            [
                'type' => 'ticket_reply',
                'name' => 'Komentar Baru (User & Staff)',
                'content' => "Ada komentar atau pesan baru di tiket *{ticket_code}*.\n\n💬 *Dari:* {replier_name}\n\n📄 *Pesan:*\n{reply_content}\n\nSilakan cek percakapan untuk informasi lebih lanjut.",
                'send_to_group' => false,
                'is_active' => true,
            ],
            [
                'type' => 'ticket_closed',
                'name' => 'Tiket Selesai (Group)',
                'content' => "✅ *LAPORAN SELESAI* ✅\n\nLaporan selesai. Tiket *{ticket_code}* telah resmi ditutup dan dianggap tuntas.\n\n📝 *Judul:* {title}\n👤 *Pelapor:* {reporter_name}\n🏢 *Cabang:* {branch_name}\n👨‍🔧 *Teknisi:* {staff_name}\n⏰ *Waktu Selesai:* {completed_at}\n\nTerima kasih atas kerja kerasnya!",
                'send_to_group' => true,
                'is_active' => true,
            ],
            [
                'type' => 'ticket_unassigned_user_alert',
                'name' => 'Alert Unassigned (User)',
                'content' => "Halo {reporter_name},\n\nTiket Anda *{ticket_code}* belum mendapatkan penugasan teknisi. Mohon hubungi admin jika kendala ini sangat mendesak.\n\n📝 *Judul:* {title}\n🏢 *Cabang:* {branch_name}",
                'send_to_group' => false,
                'is_active' => true,
            ],
            [
                'type' => 'ticket_unassigned_admin_alert',
                'name' => 'Alert Unassigned (Admin)',
                'content' => "⚠️ *ALERT: TIKET BELUM DI-ASSIGN* ⚠️\n\nTiket *{ticket_code}* belum ditugaskan ke teknisi selama lebih dari 1 jam.\nMohon segera assign staff teknisi.\n\n📝 *Judul:* {title}\n👤 *Pelapor:* {reporter_name}\n🏢 *Cabang:* {branch_name}\n⚡ *Prioritas:* {priority}",
                'send_to_group' => true, // Kirim ke group juga biar aware
                'is_active' => true,
            ],
            // --- NEW TEMPLATES ---
            [
                'type' => 'ticket_created_user',
                'name' => 'Tiket Diterima (User)',
                'content' => "Halo {reporter_name}, 👋\n\nKami telah menerima tiket laporan Anda:\n📋 *Kode:* {ticket_code}\n📝 *Judul:* {title}\n\nTim kami akan segera menindaklanjuti laporan ini. Mohon ditunggu updatenya.\n\nTerima kasih.",
                'send_to_group' => false,
                'is_active' => true,
            ],
            [
                'type' => 'work_report_created',
                'name' => 'Laporan Kerja Baru (Group)',
                'content' => "📝 *LAPORAN KERJA BARU* 📝\n\nTeknisi baru saja mengirimkan laporan pekerjaan.\n\n👤 *Teknisi:* {staff_name}\n📋 *Tiket/SPK:* {ticket_code}\n🏢 *Cabang:* {branch_name}\n📊 *Status:* {status}\n\n📄 *Laporan:*\n{description}\n\n🔗 Silakan cek foto & detail di dashboard.",
                'send_to_group' => true,
                'is_active' => true,
            ],
            [
                'type' => 'work_order_completed_user',
                'name' => 'Pekerjaan Selesai (User)',
                'content' => "Halo {reporter_name}, 👋\n\nPekerjaan untuk tiket *{ticket_code}* telah diselesaikan oleh teknisi kami.\n\n📝 *Judul:* {title}\n👨‍🔧 *Teknisi:* {staff_name}\n\nMohon periksa hasilnya. Jika sudah sesuai, tiket akan kami tutup.\n\nTerima kasih.",
                'send_to_group' => false,
                'is_active' => true,
            ],
            [
                'type' => 'sla_warning',
                'name' => 'SLA Warning (Admin)',
                'content' => "⚠️ *SLA WARNING* ⚠️\n\nTiket *{ticket_code}* dengan prioritas *{priority}* belum diselesaikan!\n\n⏰ *Dibuat:* {created_at}\n⏳ *Durasi:* Sudah berjalan > {duration_hours} jam\n📝 *Judul:* {title}\n👨‍🔧 *Teknisi:* {staff_name}\n\nMohon segera di-follow up!",
                'send_to_group' => true,
                'is_active' => true,
            ],
            [
                'type' => 'routine_maintenance_reminder',
                'name' => 'Reminder Maintenance (Staff)',
                'content' => "🔔 *REMINDER JADWAL RUTIN* 🔔\n\nHalo {staff_name},\nJangan lupa hari ini ada jadwal maintenance rutin:\n\n🔧 *Pekerjaan:* {job_name}\n🏢 *Cabang:* {branch_name}\n\nSilakan buat SPK/Laporan sesuai jadwal. Semangat! 💪",
                'send_to_group' => false,
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            WhatsAppTemplate::updateOrCreate(
                ['type' => $template['type']],
                $template
            );
        }
    }
}
