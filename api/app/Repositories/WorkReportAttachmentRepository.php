<?php

namespace App\Repositories;

use App\Interfaces\WorkReportAttachmentRepositoryInterface;
use App\Models\WorkReportAttachment;
use Illuminate\Support\Facades\DB;

class WorkReportAttachmentRepository implements WorkReportAttachmentRepositoryInterface
{
    public function getAllByWorkReportId(string $workReportId)
    {
        return WorkReportAttachment::where('work_report_id', $workReportId)->get();
    }

    public function getById(string $id)
    {
        return WorkReportAttachment::with('workReport')->findOrFail($id);
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $attachment = new WorkReportAttachment();
            $attachment->work_report_id = $data['work_report_id'];
            $attachment->file_path = $data['file_path'];
            $attachment->file_type = $data['file_type'] ?? null;
            $attachment->save();
            return $attachment->load('workReport');
        });
    }

    public function delete(string $id)
    {
        return DB::transaction(function () use ($id) {
            $attachment = $this->getById($id);
            $attachment->delete();
            return $attachment;
        });
    }
}
