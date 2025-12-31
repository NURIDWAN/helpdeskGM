<?php

namespace App\Providers;

use App\Interfaces\AuthRepositoryInterface;
use App\Repositories\AuthRepository;
use App\Interfaces\BranchRepositoryInterface;
use App\Repositories\BranchRepository;
use App\Interfaces\TicketAttachmentRepositoryInterface;
use App\Repositories\TicketAttachmentRepository;
use App\Interfaces\TicketReplyRepositoryInterface;
use App\Repositories\TicketReplyRepository;
use App\Interfaces\TicketRepositoryInterface;
use App\Repositories\TicketRepository;
use App\Interfaces\UserRepositoryInterface;
use App\Repositories\UserRepository;
use App\Interfaces\WorkOrderRepositoryInterface;
use App\Repositories\WorkOrderRepository;
use App\Interfaces\WorkReportRepositoryInterface;
use App\Repositories\WorkReportRepository;
use App\Interfaces\WorkReportAttachmentRepositoryInterface;
use App\Repositories\WorkReportAttachmentRepository;
use App\Interfaces\DashboardRepositoryInterface;
use App\Repositories\DashboardRepository;
use App\Interfaces\JobTemplateRepositoryInterface;
use App\Repositories\JobTemplateRepository;
use App\Interfaces\DailyRecordRepositoryInterface;
use App\Repositories\DailyRecordRepository;
use App\Interfaces\UtilityReadingRepositoryInterface;
use App\Repositories\UtilityReadingRepository;
use App\Interfaces\ElectricityMeterRepositoryInterface;
use App\Repositories\ElectricityMeterRepository;
use App\Interfaces\ElectricityReadingRepositoryInterface;
use App\Repositories\ElectricityReadingRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(AuthRepositoryInterface::class, AuthRepository::class);
        $this->app->bind(BranchRepositoryInterface::class, BranchRepository::class);
        $this->app->bind(TicketAttachmentRepositoryInterface::class, TicketAttachmentRepository::class);
        $this->app->bind(TicketReplyRepositoryInterface::class, TicketReplyRepository::class);
        $this->app->bind(TicketRepositoryInterface::class, TicketRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(WorkOrderRepositoryInterface::class, WorkOrderRepository::class);
        $this->app->bind(WorkReportRepositoryInterface::class, WorkReportRepository::class);
        $this->app->bind(WorkReportAttachmentRepositoryInterface::class, WorkReportAttachmentRepository::class);
        $this->app->bind(DashboardRepositoryInterface::class, DashboardRepository::class);
        $this->app->bind(JobTemplateRepositoryInterface::class, JobTemplateRepository::class);
        $this->app->bind(DailyRecordRepositoryInterface::class, DailyRecordRepository::class);
        $this->app->bind(UtilityReadingRepositoryInterface::class, UtilityReadingRepository::class);
        $this->app->bind(ElectricityMeterRepositoryInterface::class, ElectricityMeterRepository::class);
        $this->app->bind(ElectricityReadingRepositoryInterface::class, ElectricityReadingRepository::class);
    }


    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
