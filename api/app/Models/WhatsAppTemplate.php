<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppTemplate extends Model
{
    protected $table = 'whatsapp_templates';

    protected $fillable = [
        'type',
        'name',
        'content',
        'is_active',
        'send_to_group',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'send_to_group' => 'boolean',
    ];

    /**
     * Template types
     */
    const TYPE_NEW_TICKET = 'new_ticket';
    const TYPE_STATUS_UPDATE = 'status_update';
    const TYPE_REPLY = 'reply';
    const TYPE_ASSIGNMENT = 'assignment';

    /**
     * Get template by type
     */
    public static function getByType(string $type): ?self
    {
        return self::where('type', $type)->first();
    }

    /**
     * Get active template by type
     */
    public static function getActiveByType(string $type): ?self
    {
        return self::where('type', $type)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Replace placeholders in template content
     */
    public function renderContent(array $data): string
    {
        $content = $this->content;

        foreach ($data as $key => $value) {
            $content = str_replace('{' . $key . '}', $value ?? '', $content);
        }

        return $content;
    }

    /**
     * Available placeholders for each template type
     */
    public static function getPlaceholders(string $type): array
    {
        $common = [
            '{ticket_code}' => 'Kode tiket',
            '{title}' => 'Judul tiket',
            '{reporter}' => 'Nama pelapor',
            '{branch}' => 'Nama cabang',
            '{priority}' => 'Prioritas',
            '{status}' => 'Status',
            '{description}' => 'Deskripsi tiket',
            '{date}' => 'Tanggal/waktu',
        ];

        $specific = match ($type) {
            self::TYPE_NEW_TICKET => [],
            self::TYPE_STATUS_UPDATE => [
                '{old_status}' => 'Status lama',
                '{new_status}' => 'Status baru',
            ],
            self::TYPE_REPLY => [
                '{replier}' => 'Nama yang membalas',
                '{reply_content}' => 'Isi balasan',
            ],
            self::TYPE_ASSIGNMENT => [
                '{staff_name}' => 'Nama staff yang ditugaskan',
            ],
            default => [],
        };

        return array_merge($common, $specific);
    }
}
