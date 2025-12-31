<?php

namespace App\Interfaces;

interface TicketReplyRepositoryInterface
{
    public function getAllByTicketId(
        string $ticketId
    );

    public function getAllPaginatedByTicketId(
        string $ticketId,
        int $rowPerPage
    );

    public function getById(
        string $id
    );

    public function create(
        array $data
    );

    public function update(
        string $id,
        array $data
    );

    public function delete(
        string $id
    );
}
