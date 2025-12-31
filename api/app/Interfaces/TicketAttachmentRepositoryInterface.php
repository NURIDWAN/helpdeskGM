<?php

namespace App\Interfaces;

interface TicketAttachmentRepositoryInterface
{
    public function getAllByTicketId(
        string $ticketId
    );

    public function getById(
        string $id
    );

    public function create(
        array $data
    );

    public function delete(
        string $id
    );
}
