<?php

namespace App\Repositories;

use App\Interfaces\FormPermintaanRepositoryInterface;
use App\Models\Branch;
use App\Models\FormPermintaan;
use App\Models\FormPermintaanAttachment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FormPermintaanRepository implements FormPermintaanRepositoryInterface
{
    public function create(array $data): FormPermintaan
    {
        return DB::transaction(function () use ($data) {
            $user = Auth::user();

            // Superadmin/admin bisa memilih user via user_id; user biasa pakai user yang login
            $userId = $user->id;
            if (!empty($data['user_id']) && $user->hasAnyRole(['superadmin', 'admin'])) {
                $userId = (int) $data['user_id'];
            }

            // Admin bisa memilih outlet via branch_id; user biasa pakai branch dari akunnya
            $branchId = !empty($data['branch_id']) ? (int) $data['branch_id'] : $user->branch_id;

            // Jika user_id berbeda dari yang login, gunakan branch dari user yang dipilih jika branch_id tidak disertakan
            if ($userId !== $user->id && empty($data['branch_id'])) {
                $selectedUser = \App\Models\User::find($userId);
                $branchId = $selectedUser?->branch_id ?? $branchId;
            }

            $requestNumber = $this->generateRequestNumber($branchId);

            // Default status is always 'progress' (no auto-approve)
            $status = 'progress';

            $formPermintaan = FormPermintaan::create([
                'user_id' => $userId,
                'branch_id' => $branchId,
                'ticket_id' => $data['ticket_id'] ?? null,
                'request_number' => $requestNumber,
                'date' => now()->toDateString(),
                'priority' => $data['priority'],
                'request_type' => $data['request_type'],
                'fa_number' => $data['fa_number'] ?? null,
                'reason' => $data['reason'] ?? null,
                'status' => $status,
                'confirmed_by' => null,
                'confirmed_at' => null,
            ]);

            // Create line items
            foreach ($data['items'] as $item) {
                $formPermintaan->items()->create([
                    'product_description' => $item['product_description'],
                    'quantity' => $item['quantity'],
                    'uom' => $item['uom'] ?? null,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            $formPermintaan = $formPermintaan->load(['user', 'branch', 'ticket', 'items.attachments']);

            try {
                app(\App\Services\NotificationService::class)->sendFormPermintaanNotification($formPermintaan);
            } catch (\Exception $e) {
                Log::error('Failed to send notification for form permintaan', [
                    'form_permintaan_id' => $formPermintaan->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return $formPermintaan;
        }, 5);
    }

    public function update(string $id, array $data): FormPermintaan
    {
        return DB::transaction(function () use ($id, $data) {
            $formPermintaan = $this->getEditableById($id);

            $formPermintaan->update([
                'priority' => $data['priority'],
                'ticket_id' => $data['ticket_id'] ?? null,
                'request_type' => $data['request_type'],
                'fa_number' => $data['fa_number'] ?? null,
                'reason' => $data['reason'] ?? null,
            ]);

            $keptItemIds = [];

            foreach ($data['items'] as $item) {
                $itemData = [
                    'product_description' => $item['product_description'],
                    'quantity' => $item['quantity'],
                    'uom' => $item['uom'] ?? null,
                    'notes' => $item['notes'] ?? null,
                ];

                if (!empty($item['id'])) {
                    $formPermintaanItem = $formPermintaan->items()->where('id', $item['id'])->first();

                    if ($formPermintaanItem) {
                        $formPermintaanItem->update($itemData);
                        $keptItemIds[] = $formPermintaanItem->id;
                        continue;
                    }
                }

                $formPermintaanItem = $formPermintaan->items()->create($itemData);
                $keptItemIds[] = $formPermintaanItem->id;
            }

            $itemsToDelete = $formPermintaan->items()
                ->with('attachments')
                ->whereNotIn('id', $keptItemIds)
                ->get();

            foreach ($itemsToDelete as $itemToDelete) {
                foreach ($itemToDelete->attachments as $attachment) {
                    if ($attachment->file_path) {
                        Storage::disk('public')->delete($attachment->file_path);
                    }
                }
                $itemToDelete->delete();
            }

            return $formPermintaan->fresh(['user', 'branch', 'ticket', 'items.attachments', 'attachments']);
        }, 5);
    }

    public function delete(string $id): bool
    {
        return DB::transaction(function () use ($id) {
            return $this->getEditableById($id)->delete();
        }, 5);
    }

    public function confirm(string $id): FormPermintaan
    {
        return DB::transaction(function () use ($id) {
            $formPermintaan = $this->getAccessibleById($id);

            if ($formPermintaan->status !== 'pending') {
                throw new \Exception('Hanya form permintaan dengan status pending yang dapat dikonfirmasi.');
            }

            $formPermintaan->update([
                'status' => 'approved',
                'confirmed_by' => Auth::id(),
                'confirmed_at' => now(),
            ]);

            return $formPermintaan->fresh(['user', 'branch', 'ticket', 'confirmedBy', 'items.attachments', 'attachments']);
        }, 5);
    }

    public function getAllPaginated(
        ?string $search,
        int $rowPerPage,
        ?int $branchId = null,
        ?string $requestType = null,
        ?string $status = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): LengthAwarePaginator
    {
        $user = Auth::user();

        $query = FormPermintaan::with(['user', 'branch', 'ticket'])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc');

        if (!$user->can('form-permintaan-view-all')) {
            // User can see form permintaan from their own branch (created by anyone)
            // OR form permintaan they created themselves
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id);
                if ($user->branch_id) {
                    $q->orWhere('branch_id', $user->branch_id);
                }
            });
        }

        if ($search) {
            $query->where('request_number', 'like', '%' . $search . '%');
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($requestType) {
            $query->where('request_type', $requestType);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($startDate) {
            $query->whereDate('date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('date', '<=', $endDate);
        }

        return $query->paginate($rowPerPage);
    }

    public function getById(string $id): FormPermintaan
    {
        return $this->getAccessibleById($id);
    }

    private function getAccessibleById(string $id): FormPermintaan
    {
        $user = Auth::user();

        $query = FormPermintaan::with(['user', 'branch', 'ticket', 'confirmedBy', 'items.attachments', 'attachments'])
            ->where('id', $id);

        if (!$user->can('form-permintaan-view-all')) {
            // User can access form permintaan from their own branch or created by themselves
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id);
                if ($user->branch_id) {
                    $q->orWhere('branch_id', $user->branch_id);
                }
            });
        }

        return $query->firstOrFail();
    }

    private function getEditableById(string $id): FormPermintaan
    {
        return $this->getById($id);
    }

    public function addAttachment(string $formPermintaanId, array $fileData): FormPermintaanAttachment
    {
        $formPermintaan = $this->getById($formPermintaanId);
        $itemId = $fileData['form_permintaan_item_id'] ?? null;

        if ($itemId && !$formPermintaan->items->contains('id', (int) $itemId)) {
            throw new \Exception('Item form permintaan tidak valid.');
        }

        return FormPermintaanAttachment::create([
            'form_permintaan_id' => $formPermintaanId,
            'form_permintaan_item_id' => $itemId,
            'file_path' => $fileData['file_path'],
            'file_name' => $fileData['file_name'],
            'file_type' => $fileData['file_type'],
            'file_size' => $fileData['file_size'],
        ]);
    }

    public function deleteAttachment(string $formPermintaanId, string $attachmentId): bool
    {
        $this->getEditableById($formPermintaanId);

        $attachment = $this->getAttachment($formPermintaanId, $attachmentId);

        if ($attachment->file_path) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        return $attachment->delete();
    }

    public function getAttachment(string $formPermintaanId, string $attachmentId): FormPermintaanAttachment
    {
        $this->getById($formPermintaanId);

        return FormPermintaanAttachment::where('form_permintaan_id', $formPermintaanId)
            ->where('id', $attachmentId)
            ->firstOrFail();
    }

    /**
     * Generate request number with format: {BRANCH_CODE}{MM}{YYYY}{NNNN}
     * Example: HGAM0620260001
     *
     * Uses lockForUpdate to prevent race conditions under concurrent inserts.
     *
     * @param int $branchId
     * @return string
     */
    private function generateRequestNumber(int $branchId): string
    {
        $branch = Branch::find($branchId);
        $branchCode = $branch && $branch->code ? $branch->code : 'XXXX';

        $now = now();
        $month = $now->format('m');
        $year = $now->format('Y');

        // Same pattern as TicketRepository: use the table's latest id as a
        // global sequence and serialize concurrent inserts with lockForUpdate.
        $maxId = FormPermintaan::lockForUpdate()->max('id') ?? 0;
        $nextNumber = $maxId + 1;
        $sequence = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        return "{$branchCode}{$month}{$year}{$sequence}";
    }
}
