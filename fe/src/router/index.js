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
import TicketCategoryList from '@/views/admin/ticketcategory/TicketCategoryList.vue'
import TicketCategoryForm from '@/views/admin/ticketcategory/TicketCategoryForm.vue'
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
            permissions: ['branch-list', 'branch-create', 'branch-edit', 'branch-delete'],
          },
        },
        {
          path: 'branch/create',
          name: 'admin.branch.create',
          component: BranchForm,
          meta: {
            requiresAuth: true,
            title: 'Tambah Cabang',
            permission: 'branch-create',
          },
        },
        {
          path: 'branch/:id/edit',
          name: 'admin.branch.edit',
          component: BranchForm,
          meta: {
            requiresAuth: true,
            title: 'Edit Cabang',
            permission: 'branch-edit',
          },
        },
        // Ticket Categories
        {
          path: 'ticket-categories',
          name: 'admin.ticket-categories',
          component: TicketCategoryList,
          meta: {
            requiresAuth: true,
            title: 'Kategori Tiket',
          },
        },
        {
          path: 'ticket-category/create',
          name: 'admin.ticket-category.create',
          component: TicketCategoryForm,
          meta: {
            requiresAuth: true,
            title: 'Tambah Kategori Tiket',
          },
        },
        {
          path: 'ticket-category/:id/edit',
          name: 'admin.ticket-category.edit',
          component: TicketCategoryForm,
          meta: {
            requiresAuth: true,
            title: 'Edit Kategori Tiket',
          },
        },
        {
          path: 'users',
          name: 'admin.users',
          component: UserList,
          meta: {
            requiresAuth: true,
            title: 'Data User',
            permissions: ['user-list', 'user-create', 'user-edit', 'user-delete'],
          },
        },
        {
          path: 'user/create',
          name: 'admin.user.create',
          component: UserForm,
          meta: {
            requiresAuth: true,
            title: 'Tambah User',
            permission: 'user-create',
          },
        },
        {
          path: 'user/:id/edit',
          name: 'admin.user.edit',
          component: UserForm,
          meta: {
            requiresAuth: true,
            title: 'Edit User',
            permission: 'user-edit',
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
        {
          path: 'roles',
          name: 'admin.roles',
          component: () => import('@/views/admin/role/RoleList.vue'),
          meta: { requiresAuth: true, title: 'Data Role', permissions: ['role-list', 'role-create', 'role-edit', 'role-delete'] }
        },
        {
          path: 'role/create',
          name: 'admin.role.create',
          component: () => import('@/views/admin/role/RoleForm.vue'),
          meta: { requiresAuth: true, title: 'Tambah Role', permission: 'role-create' }
        },
        {
          path: 'role/:id/edit',
          name: 'admin.role.edit',
          component: () => import('@/views/admin/role/RoleForm.vue'),
          meta: { requiresAuth: true, title: 'Edit Role', permission: 'role-edit' }
        },
        {
          path: 'whatsapp-settings',
          name: 'admin.whatsapp-settings',
          component: () => import('@/views/admin/whatsapp/WhatsAppSettings.vue'),
          meta: { requiresAuth: true, title: 'Pengaturan WhatsApp', permissions: ['whatsapp-setting-list', 'whatsapp-setting-edit'] }
        },
        {
          path: 'user-activity',
          name: 'admin.user-activity',
          component: () => import('@/views/admin/user/UserActivityMonitor.vue'),
          meta: { requiresAuth: true, title: 'Monitoring Aktivitas User', permission: 'user-activity-list' }
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
    // Error Pages
    {
      path: '/error/403',
      name: 'error.forbidden',
      component: () => import('@/views/errors/Forbidden.vue'),
      meta: { title: 'Akses Ditolak' }
    },
    {
      path: '/error/401',
      name: 'error.unauthorized',
      component: () => import('@/views/errors/Unauthorized.vue'),
      meta: { title: 'Tidak Terotentikasi' }
    },
    {
      path: '/error/500',
      name: 'error.server',
      component: () => import('@/views/errors/ServerError.vue'),
      meta: { title: 'Kesalahan Server' }
    },
    {
      path: '/error/404',
      name: 'error.notfound',
      component: () => import('@/views/errors/NotFound.vue'),
      meta: { title: 'Halaman Tidak Ditemukan' }
    },
    // Catch-all route for 404
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('@/views/errors/NotFound.vue'),
      meta: { title: 'Halaman Tidak Ditemukan' }
    },
  ],
})

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()

  // Set document title
  document.title = to.meta.title ? `${to.meta.title} - Helpdesk` : 'Helpdesk'

  if (to.meta.requiresAuth) {
    if (authStore.token) {
      try {
        if (!authStore.user) {
          await authStore.checkAuth()
        }

        // Role-based redirect: Admin/Staff should go to admin dashboard, not app dashboard
        const userRoles = authStore.user?.roles || []
        const isAdminOrStaff = userRoles.includes('superadmin') || userRoles.includes('admin') || userRoles.includes('staff')

        // Redirect admin/staff from app routes to admin routes
        if (isAdminOrStaff && to.name?.startsWith('app.')) {
          // Map app routes to admin routes
          const routeMapping = {
            'app.dashboard': 'admin.dashboard',
            'app.profile': 'admin.profile',
          }

          if (routeMapping[to.name]) {
            return next({ name: routeMapping[to.name] })
          }
        }

        // Check for permission requirements
        const requiredPermission = to.meta.permission
        const requiredPermissions = to.meta.permissions

        if (requiredPermission) {
          // Single permission required
          const userPermissions = authStore.user?.permissions || []
          if (!userPermissions.includes(requiredPermission)) {
            return next({ name: 'error.forbidden' })
          }
        }

        if (requiredPermissions && Array.isArray(requiredPermissions)) {
          // Multiple permissions (user must have at least one)
          const userPermissions = authStore.user?.permissions || []
          const hasPermission = requiredPermissions.some(p => userPermissions.includes(p))
          if (!hasPermission) {
            return next({ name: 'error.forbidden' })
          }
        }

        next()
      } catch (error) {
        next({ name: 'login' })
      }
    } else {
      next({ name: 'login' })
    }
  } else if (to.meta.requiresUnauth && authStore.token) {
    next({ name: 'admin.dashboard' })
  } else {
    next()
  }
})


export default router
