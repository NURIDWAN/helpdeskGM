<?php

namespace App\Interfaces;

use App\Models\FormPermintaan;
use App\Models\FormPermintaanAttachment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface FormPermintaanRepositoryInterface
{
    public function create(array $data): FormPermintaan;

    public function update(string $id, array $data): FormPermintaan;

    public function delete(string $id): bool;

    public function confirm(string $id): FormPermintaan;

    public function getAllPaginated(
        ?string $search,
        int $rowPerPage,
        ?int $branchId = null,
        ?string $requestType = null,
        ?string $status = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): LengthAwarePaginator;

    public function getById(string $id): FormPermintaan;

    public function addAttachment(string $formPermintaanId, array $fileData): FormPermintaanAttachment;

    public function getAttachment(string $formPermintaanId, string $attachmentId): FormPermintaanAttachment;

    public function deleteAttachment(string $formPermintaanId, string $attachmentId): bool;
}
