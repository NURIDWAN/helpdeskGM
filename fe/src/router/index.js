import Admin from '@/layouts/Admin.vue'
import Auth from '@/layouts/Auth.vue'
import { useAuthStore } from '@/stores/auth'
import Dashboard from '@/views/admin/Dashboard.vue'
import BranchList from '@/views/admin/branch/BranchList.vue'
import BranchForm from '@/views/admin/branch/BranchForm.vue'
import UserList from '@/views/admin/user/UserList.vue'
import UserForm from '@/views/admin/user/UserForm.vue'
import TicketList from '@/views/admin/ticket/TicketList.vue'
import TicketForm from '@/views/admin/ticket/TicketForm.vue'
import TicketDetail from '@/views/admin/ticket/TicketDetail.vue'
import AdminProfile from '@/views/admin/Profile.vue'
import WorkOrderList from '@/views/admin/workorder/WorkOrderList.vue'
import WorkOrderForm from '@/views/admin/workorder/WorkOrderForm.vue'
import WorkOrderDetail from '@/views/admin/workorder/WorkOrderDetail.vue'
import WorkReportList from '@/views/admin/workreport/WorkReportList.vue'
import WorkReportForm from '@/views/admin/workreport/WorkReportForm.vue'
import WorkReportDetail from '@/views/admin/workreport/WorkReportDetail.vue'
import DailyRecordList from '@/views/admin/dailyrecord/DailyRecordList.vue'
import DailyRecordForm from '@/views/admin/dailyrecord/DailyRecordForm.vue'
import DailyRecordDetail from '@/views/admin/dailyrecord/DailyRecordDetail.vue'
import DailyUsageReport from '@/views/admin/dailyrecord/DailyUsageReport.vue'
import JobTemplateList from '@/views/admin/jobtemplate/JobTemplateList.vue'
import JobTemplateForm from '@/views/admin/jobtemplate/JobTemplateForm.vue'
import Login from '@/views/auth/Login.vue'
import { createRouter, createWebHistory } from 'vue-router'
import App from '@/layouts/App.vue'
import AppDashboard from '@/views/app/Dashboard.vue'
import AppTicketDetail from '@/views/app/TicketDetail.vue'
import AppTicketCreate from '@/views/app/TicketCreate.vue'
import AppProfile from '@/views/app/Profile.vue'
import Register from '@/views/auth/Register.vue'
import AppDailyRecordList from '@/views/admin/dailyrecord/DailyRecordList.vue'
import AppDailyRecordForm from '@/views/admin/dailyrecord/DailyRecordForm.vue'
import AppDailyRecordDetail from '@/views/admin/dailyrecord/DailyRecordDetail.vue'
import AppDailyUsageReport from '@/views/admin/dailyrecord/DailyUsageReport.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      component: App,
      children: [
        {
          path: '',
          name: 'app.dashboard',
          component: AppDashboard,
          meta: {
            requiresAuth: true,
            title: 'Dashboard',
          },
        },

        {
          path: 'ticket/:code',
          name: 'app.ticket.detail',
          component: AppTicketDetail,
          meta: {
            requiresAuth: true,
            title: 'Ticket Detail',
          },
        },
        {
          path: 'ticket/create',
          name: 'app.ticket.create',
          component: AppTicketCreate,
        },
        {
          path: 'profile',
          name: 'app.profile',
          component: AppProfile,
          meta: { requiresAuth: true, title: 'Profil' }
        },
        {
          path: 'daily-records',
          name: 'app.daily-records',
          component: AppDailyRecordList,
          meta: { requiresAuth: true, title: 'Laporan Harian Cabang' }
        },
        {
          path: 'daily-record/create',
          name: 'app.daily-record.create',
          component: AppDailyRecordForm,
          meta: { requiresAuth: true, title: 'Tambah Laporan Harian Cabang' }
        },
        {
          path: 'daily-record/:id/edit',
          name: 'app.daily-record.edit',
          component: AppDailyRecordForm,
          meta: { requiresAuth: true, title: 'Edit Laporan Harian Cabang' }
        },
        {
          path: 'daily-record/:id',
          name: 'app.daily-record.detail',
          component: AppDailyRecordDetail,
          meta: { requiresAuth: true, title: 'Detail Laporan Harian Cabang' }
        },
        {
          path: 'daily-usage-report',
          name: 'app.daily-usage-report',
          component: AppDailyUsageReport,
          meta: { requiresAuth: true, title: 'Laporan Daily Usage' }
        },
      ],
    },
    {
      path: '/admin',
      component: Admin,
      children: [
        {
          path: 'dashboard',
          name: 'admin.dashboard',
          component: Dashboard,
          meta: {
            requiresAuth: true,
            title: 'Dashboard',
          },
        },
        {
          path: 'profile',
          name: 'admin.profile',
          component: AdminProfile,
          meta: {
            requiresAuth: true,
            title: 'Profil',
          },
        },
        {
          path: 'branches',
          name: 'admin.branches',
          component: BranchList,
          meta: {
            requiresAuth: true,
            title: 'Data Cabang',
          },
        },
        {
          path: 'branch/create',
          name: 'admin.branch.create',
          component: BranchForm,
          meta: {
            requiresAuth: true,
            title: 'Tambah Cabang',
          },
        },
        {
          path: 'branch/:id/edit',
          name: 'admin.branch.edit',
          component: BranchForm,
          meta: {
            requiresAuth: true,
            title: 'Edit Cabang',
          },
        },
        {
          path: 'users',
          name: 'admin.users',
          component: UserList,
          meta: {
            requiresAuth: true,
            title: 'Data User',
          },
        },
        {
          path: 'user/create',
          name: 'admin.user.create',
          component: UserForm,
          meta: {
            requiresAuth: true,
            title: 'Tambah User',
          },
        },
        {
          path: 'user/:id/edit',
          name: 'admin.user.edit',
          component: UserForm,
          meta: {
            requiresAuth: true,
            title: 'Edit User',
          },
        },
        {
          path: 'tickets',
          name: 'admin.tickets',
          component: TicketList,
          meta: {
            requiresAuth: true,
            title: 'Data Ticket',
          },
        },
        {
          path: 'ticket/create',
          name: 'admin.ticket.create',
          component: TicketForm,
          meta: {
            requiresAuth: true,
            title: 'Tambah Ticket',
          },
        },
        {
          path: 'ticket/:id/edit',
          name: 'admin.ticket.edit',
          component: TicketForm,
          meta: {
            requiresAuth: true,
            title: 'Edit Ticket',
          },
        },
        {
          path: 'ticket/:id',
          name: 'admin.ticket.detail',
          component: TicketDetail,
          meta: {
            requiresAuth: true,
            title: 'Detail Ticket',
          },
        },
        {
          path: 'work-orders',
          name: 'admin.workorders',
          component: WorkOrderList,
          meta: { requiresAuth: true, title: 'Data Work Order' }
        },
        {
          path: 'work-order/create',
          name: 'admin.workorder.create',
          component: WorkOrderForm,
          meta: { requiresAuth: true, title: 'Tambah Work Order' }
        },
        {
          path: 'work-order/:id/edit',
          name: 'admin.workorder.edit',
          component: WorkOrderForm,
          meta: { requiresAuth: true, title: 'Edit Work Order' }
        },
        {
          path: 'work-order/:id',
          name: 'admin.workorder.detail',
          component: WorkOrderDetail,
          meta: { requiresAuth: true, title: 'Detail Work Order' }
        },
        {
          path: 'work-reports',
          name: 'admin.workreports',
          component: WorkReportList,
          meta: { requiresAuth: true, title: 'Data Laporan Kerja' }
        },
        {
          path: 'work-report/create',
          name: 'admin.workreport.create',
          component: WorkReportForm,
          meta: { requiresAuth: true, title: 'Tambah Laporan Kerja' }
        },
        {
          path: 'work-report/:id/edit',
          name: 'admin.workreport.edit',
          component: WorkReportForm,
          meta: { requiresAuth: true, title: 'Edit Laporan Kerja' }
        },
        {
          path: 'work-report/:id',
          name: 'admin.workreport.detail',
          component: WorkReportDetail,
          meta: { requiresAuth: true, title: 'Detail Laporan Kerja' }
        },
        {
          path: 'daily-records',
          name: 'admin.daily-records',
          component: DailyRecordList,
          meta: { requiresAuth: true, title: 'Data Laporan Harian Cabang' }
        },
        {
          path: 'daily-record/create',
          name: 'admin.daily-record.create',
          component: DailyRecordForm,
          meta: { requiresAuth: true, title: 'Tambah Laporan Harian Cabang' }
        },
        {
          path: 'daily-record/:id/edit',
          name: 'admin.daily-record.edit',
          component: DailyRecordForm,
          meta: { requiresAuth: true, title: 'Edit Laporan Harian Cabang' }
        },
        {
          path: 'daily-record/:id',
          name: 'admin.daily-record.detail',
          component: DailyRecordDetail,
          meta: { requiresAuth: true, title: 'Detail Laporan Harian Cabang' }
        },
        {
          path: 'daily-usage-report',
          name: 'admin.daily-usage-report',
          component: DailyUsageReport,
          meta: { requiresAuth: true, title: 'Laporan Daily Usage' }
        },
        {
          path: 'job-templates',
          name: 'admin.job-templates',
          component: JobTemplateList,
          meta: { requiresAuth: true, title: 'Template Job' }
        },
        {
          path: 'job-template/create',
          name: 'admin.job-template.create',
          component: JobTemplateForm,
          meta: { requiresAuth: true, title: 'Tambah Template Job' }
        },
        {
          path: 'job-template/:id/edit',
          name: 'admin.job-template.edit',
          component: JobTemplateForm,
          meta: { requiresAuth: true, title: 'Edit Template Job' }
        },
      ],
    },
    {
      path: '/auth',
      component: Auth,
      children: [
        {
          path: 'login',
          name: 'login',
          component: Login,
        },
        {
          path: 'register',
          name: 'register',
          component: Register,
        },
      ],
    },
  ],
})

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()

  if (to.meta.requiresAuth) {
    if (authStore.token) {
      try {
        if (!authStore.user) {
          await authStore.checkAuth()
        }

        next()
      } catch (error) {
        next({ name: 'login' })
      }
    } else {
      next({ name: 'login' })
    }
  } else if (to.meta.requiresUnauth && authStore.token) {
    next({ name: 'dashboard' })
  } else {
    next()
  }
})


export default router
