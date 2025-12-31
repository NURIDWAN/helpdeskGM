<?php

namespace Database\Seeders;

use App\Models\WhatsAppSetting;
use App\Models\WhatsAppTemplate;
use Illuminate\Database\Seeder;

class WhatsAppSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Default settings
        $settings = [
            'enabled' => 'true',
            'token' => config('services.whatsapp.token', ''),
            'group_id' => '120363322658703628@g.us',
            'delay' => '2',
        ];

        foreach ($settings as $key => $value) {
            WhatsAppSetting::firstOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        // Default templates
        $templates = [
            [
                'type' => 'new_ticket',
                'name' => 'Tiket Baru',
                'content' => "🚨 *TIKET BARU DITERIMA* 🚨\n\n📋 *Kode Tiket:* {ticket_code}\n📝 *Judul:* {title}\n👤 *Pelapor:* {reporter}\n🏢 *Cabang:* {branch}\n⚡ *Prioritas:* {priority}\n📊 *Status:* {status}\n📅 *Dibuat:* {date}\n\n📄 *Deskripsi:*\n{description}\n\n🔗 Silakan login ke sistem untuk melihat detail lengkap dan menindaklanjuti tiket ini.\n\n_Pesan ini dikirim otomatis oleh sistem GA Maintenance_",
                'is_active' => true,
                'send_to_group' => true,
            ],
            [
                'type' => 'status_update',
                'name' => 'Update Status Tiket',
                'content' => "📢 *UPDATE STATUS TIKET* 📢\n\n📋 *Kode Tiket:* {ticket_code}\n📝 *Judul:* {title}\n👤 *Pelapor:* {reporter}\n🏢 *Cabang:* {branch}\n📊 *Status Lama:* {old_status}\n📊 *Status Baru:* {new_status}\n📅 *Diupdate:* {date}\n\n🔗 Silakan login ke sistem untuk melihat detail lengkap.\n\n_Pesan ini dikirim otomatis oleh sistem GA Maintenance_",
                'is_active' => true,
                'send_to_group' => false,
            ],
            [
                'type' => 'reply',
                'name' => 'Balasan Tiket Baru',
                'content' => "💬 *BALASAN TIKET BARU* 💬\n\n📋 *Kode Tiket:* {ticket_code}\n📝 *Judul:* {title}\n👤 *Pelapor:* {reporter}\n🏢 *Cabang:* {branch}\n⚡ *Prioritas:* {priority}\n📊 *Status:* {status}\n💬 *Balasan dari:* {replier}\n📅 *Waktu:* {date}\n\n📄 *Isi Balasan:*\n{reply_content}\n\n🔗 Silakan login ke sistem untuk melihat detail lengkap dan memberikan tanggapan.\n\n_Pesan ini dikirim otomatis oleh sistem GA Maintenance_",
                'is_active' => true,
                'send_to_group' => false,
            ],
            [
                'type' => 'assignment',
                'name' => 'Penugasan Staff',
                'content' => "👋 Hi {staff_name}, kamu telah ditugaskan untuk menangani tiket berikut:\n\n📋 *Kode Tiket:* {ticket_code}\n📝 *Judul:* {title}\n👤 *Pelapor:* {reporter}\n🏢 *Cabang:* {branch}\n⚡ *Prioritas:* {priority}\n📊 *Status:* {status}\n📅 *Ditugaskan:* {date}\n\n📄 *Deskripsi:*\n{description}\n\n🔗 Silakan login ke sistem untuk melihat detail lengkap dan menindaklanjuti tiket ini.\n\n_Pesan ini dikirim otomatis oleh sistem GA Maintenance_",
                'is_active' => true,
                'send_to_group' => false,
            ],
        ];

        foreach ($templates as $template) {
            WhatsAppTemplate::firstOrCreate(
                ['type' => $template['type']],
                $template
            );
        }
    }
}
