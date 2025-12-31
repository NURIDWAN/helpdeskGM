<?php

namespace App\Interfaces;

interface WorkReportAttachmentRepositoryInterface
{
    public function getAllByWorkReportId(
        string $workReportId
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
