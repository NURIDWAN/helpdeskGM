<?php

namespace Database\Seeders;

use App\Models\TicketCategory;
use Illuminate\Database\Seeder;

class TicketCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Kelistrikan',
                'description' => 'Masalah terkait listrik, lampu, stop kontak, panel listrik',
                'icon' => 'Zap',
                'color' => '#F59E0B',
                'sort_order' => 1,
            ],
            [
                'name' => 'Plumbing / Air',
                'description' => 'Masalah pipa, kran, toilet, saluran air',
                'icon' => 'Droplet',
                'color' => '#3B82F6',
                'sort_order' => 2,
            ],
            [
                'name' => 'AC / HVAC',
                'description' => 'Masalah pendingin ruangan, AC, ventilasi',
                'icon' => 'Wind',
                'color' => '#06B6D4',
                'sort_order' => 3,
            ],
            [
                'name' => 'IT / Jaringan',
                'description' => 'Masalah komputer, WiFi, jaringan, printer',
                'icon' => 'Wifi',
                'color' => '#8B5CF6',
                'sort_order' => 4,
            ],
            [
                'name' => 'Keamanan',
                'description' => 'Masalah CCTV, akses kontrol, alarm, pintu',
                'icon' => 'Shield',
                'color' => '#EF4444',
                'sort_order' => 5,
            ],
            [
                'name' => 'Kebersihan',
                'description' => 'Masalah cleaning service, sampah, pest control',
                'icon' => 'Sparkles',
                'color' => '#22C55E',
                'sort_order' => 6,
            ],
            [
                'name' => 'Bangunan / Sipil',
                'description' => 'Masalah struktur, atap, dinding, lantai, pintu, jendela',
                'icon' => 'Building',
                'color' => '#78716C',
                'sort_order' => 7,
            ],
            [
                'name' => 'Furnitur',
                'description' => 'Masalah meja, kursi, lemari, furniture kantor',
                'icon' => 'Armchair',
                'color' => '#D97706',
                'sort_order' => 8,
            ],
            [
                'name' => 'Lainnya',
                'description' => 'Kategori lain yang tidak tercakup',
                'icon' => 'MoreHorizontal',
                'color' => '#6B7280',
                'sort_order' => 99,
            ],
        ];

        foreach ($categories as $category) {
            TicketCategory::firstOrCreate(
                ['name' => $category['name']],
                $category
            );
        }

        $this->command->info('✓ Created ' . count($categories) . ' ticket categories');
    }
}
