<?php

namespace App\Interfaces;

interface WorkOrderRepositoryInterface
{
    public function getAll(?string $search, ?int $limit, bool $execute);
    public function getAllPaginated(?string $search, ?int $rowPerPage);
    public function getById($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function getByTicketId($ticketId);
}
