import { useAuthStore } from '@/stores/auth'
import { createRouter, createWebHistory } from 'vue-router'

// Layouts - keep as static imports (needed for initial render)
import Admin from '@/layouts/Admin.vue'
import Auth from '@/layouts/Auth.vue'
import App from '@/layouts/App.vue'

// Lazy load all view components for better initial bundle size
// Admin Views
const Dashboard = () => import('@/views/admin/Dashboard.vue')
const BranchList = () => import('@/views/admin/branch/BranchList.vue')
const BranchForm = () => import('@/views/admin/branch/BranchForm.vue')
const UserList = () => import('@/views/admin/user/UserList.vue')
const UserForm = () => import('@/views/admin/user/UserForm.vue')
const TicketList = () => import('@/views/admin/ticket/TicketList.vue')
const TicketForm = () => import('@/views/admin/ticket/TicketForm.vue')
const TicketDetail = () => import('@/views/admin/ticket/TicketDetail.vue')
const AdminFormPermintaanList = () => import('@/views/admin/formpermintaan/FormPermintaanList.vue')
const TicketCategoryList = () => import('@/views/admin/ticketcategory/TicketCategoryList.vue')
const TicketCategoryForm = () => import('@/views/admin/ticketcategory/TicketCategoryForm.vue')
const AdminProfile = () => import('@/views/admin/Profile.vue')
const WorkOrderList = () => import('@/views/admin/workorder/WorkOrderList.vue')
const WorkOrderForm = () => import('@/views/admin/workorder/WorkOrderForm.vue')
const WorkOrderDetail = () => import('@/views/admin/workorder/WorkOrderDetail.vue')
const WorkReportList = () => import('@/views/admin/workreport/WorkReportList.vue')
const WorkReportForm = () => import('@/views/admin/workreport/WorkReportForm.vue')
const WorkReportDetail = () => import('@/views/admin/workreport/WorkReportDetail.vue')
const DailyRecordList = () => import('@/views/admin/dailyrecord/DailyRecordList.vue')
const DailyRecordForm = () => import('@/views/admin/dailyrecord/DailyRecordForm.vue')
const DailyRecordDetail = () => import('@/views/admin/dailyrecord/DailyRecordDetail.vue')
const DailyUsageReport = () => import('@/views/admin/dailyrecord/DailyUsageReport.vue')
const JobTemplateList = () => import('@/views/admin/jobtemplate/JobTemplateList.vue')
const JobTemplateForm = () => import('@/views/admin/jobtemplate/JobTemplateForm.vue')
const RoleList = () => import('@/views/admin/role/RoleList.vue')
const RoleForm = () => import('@/views/admin/role/RoleForm.vue')
const PermissionMatrix = () => import('@/views/admin/role/PermissionMatrix.vue')
const WhatsAppSettings = () => import('@/views/admin/whatsapp/WhatsAppSettings.vue')
const UserActivityMonitor = () => import('@/views/admin/user/UserActivityMonitor.vue')
const ActivityLogList = () => import('@/views/admin/activitylog/ActivityLogList.vue')

// Auth Views
const Login = () => import('@/views/auth/Login.vue')
const Register = () => import('@/views/auth/Register.vue')

// App Views
const AppDashboard = () => import('@/views/app/Dashboard.vue')
const AppTicketList = () => import('@/views/app/TicketList.vue')
const AppTicketDetail = () => import('@/views/app/TicketDetail.vue')
const AppTicketCreate = () => import('@/views/app/TicketCreate.vue')
const AppProfile = () => import('@/views/app/Profile.vue')
const AppFormPermintaanDetail = () => import('@/views/app/FormPermintaanDetail.vue')
const AppFormPermintaanList = () => import('@/views/app/FormPermintaanList.vue')
const AppFormPermintaanCreate = () => import('@/views/app/FormPermintaanCreate.vue')

// Error Views
const Forbidden = () => import('@/views/errors/Forbidden.vue')
const Unauthorized = () => import('@/views/errors/Unauthorized.vue')
const ServerError = () => import('@/views/errors/ServerError.vue')
const NotFound = () => import('@/views/errors/NotFound.vue')

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
          path: 'tickets',
          name: 'app.tickets',
          component: AppTicketList,
          meta: {
            requiresAuth: true,
            title: 'Tiket Saya',
            permission: 'ticket-list',
          },
        },
        {
          path: 'ticket/:code',
          name: 'app.ticket.detail',
          component: AppTicketDetail,
          meta: {
            requiresAuth: true,
            title: 'Ticket Detail',
            permission: 'ticket-list',
          },
        },
        {
          path: 'ticket/create',
          name: 'app.ticket.create',
          component: AppTicketCreate,
          meta: {
            requiresAuth: true,
            title: 'Buat Tiket',
            permission: 'ticket-create',
          },
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
          component: DailyRecordList,
          meta: { requiresAuth: true, title: 'Laporan Harian Cabang', permission: 'daily-record-list' }
        },
        {
          path: 'daily-record/create',
          name: 'app.daily-record.create',
          component: DailyRecordForm,
          meta: { requiresAuth: true, title: 'Tambah Laporan Harian Cabang', permission: 'daily-record-create' }
        },
        {
          path: 'daily-record/:id/edit',
          name: 'app.daily-record.edit',
          component: DailyRecordForm,
          meta: { requiresAuth: true, title: 'Edit Laporan Harian Cabang', permission: 'daily-record-edit' }
        },
        {
          path: 'daily-record/:id',
          name: 'app.daily-record.detail',
          component: DailyRecordDetail,
          meta: { requiresAuth: true, title: 'Detail Laporan Harian Cabang', permission: 'daily-record-list' }
        },
        {
          path: 'daily-usage-report',
          name: 'app.daily-usage-report',
          component: DailyUsageReport,
          meta: { requiresAuth: true, title: 'Laporan Daily Usage', permission: 'daily-record-list' }
        },
        {
          path: 'form-permintaan',
          name: 'app.form-permintaan',
          component: AppFormPermintaanList,
          meta: { requiresAuth: true, title: 'Form Permintaan', permission: 'form-permintaan-list' }
        },
        {
          path: 'form-permintaan/create',
          name: 'app.form-permintaan.create',
          component: AppFormPermintaanCreate,
          meta: { requiresAuth: true, title: 'Buat Form Permintaan', permission: 'form-permintaan-create' }
        },
        {
          path: 'form-permintaan/:id/edit',
          name: 'app.form-permintaan.edit',
          component: AppFormPermintaanCreate,
          meta: { requiresAuth: true, title: 'Edit Form Permintaan', permission: 'form-permintaan-edit' }
        },
        {
          path: 'form-permintaan/:id',
          name: 'app.form-permintaan.detail',
          component: AppFormPermintaanDetail,
          meta: { requiresAuth: true, title: 'Detail Form Permintaan', permission: 'form-permintaan-list' }
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
            permissions: ['dashboard-view', 'dashboard-view-metrics', 'dashboard-view-charts', 'dashboard-view-staff-rankings', 'dashboard-view-trends'],
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
            permissions: ['ticket-category-list', 'ticket-category-create', 'ticket-category-edit', 'ticket-category-delete'],
          },
        },
        {
          path: 'ticket-category/create',
          name: 'admin.ticket-category.create',
          component: TicketCategoryForm,
          meta: {
            requiresAuth: true,
            title: 'Tambah Kategori Tiket',
            permission: 'ticket-category-create',
          },
        },
        {
          path: 'ticket-category/:id/edit',
          name: 'admin.ticket-category.edit',
          component: TicketCategoryForm,
          meta: {
            requiresAuth: true,
            title: 'Edit Kategori Tiket',
            permission: 'ticket-category-edit',
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
            permissions: ['ticket-list', 'ticket-create', 'ticket-edit', 'ticket-delete', 'ticket-update-status'],
          },
        },
        {
          path: 'ticket/create',
          name: 'admin.ticket.create',
          component: TicketForm,
          meta: {
            requiresAuth: true,
            title: 'Tambah Ticket',
            permission: 'ticket-create',
          },
        },
        {
          path: 'ticket/:id/edit',
          name: 'admin.ticket.edit',
          component: TicketForm,
          meta: {
            requiresAuth: true,
            title: 'Edit Ticket',
            permission: 'ticket-edit',
          },
        },
        {
          path: 'ticket/:id',
          name: 'admin.ticket.detail',
          component: TicketDetail,
          meta: {
            requiresAuth: true,
            title: 'Detail Ticket',
            permission: 'ticket-list',
          },
        },
        {
          path: 'form-permintaan',
          name: 'admin.form-permintaan',
          component: AdminFormPermintaanList,
          meta: {
            requiresAuth: true,
            title: 'Daftar Form Permintaan',
            permission: 'form-permintaan-view-all',
          },
        },
        {
          path: 'form-permintaan/create',
          name: 'admin.form-permintaan.create',
          component: AppFormPermintaanCreate,
          meta: {
            requiresAuth: true,
            title: 'Buat Form Permintaan',
            permission: 'form-permintaan-create',
          },
        },
        {
          path: 'form-permintaan/:id/edit',
          name: 'admin.form-permintaan.edit',
          component: AppFormPermintaanCreate,
          meta: {
            requiresAuth: true,
            title: 'Edit Form Permintaan',
            permission: 'form-permintaan-edit',
          },
        },
        {
          path: 'form-permintaan/:id',
          name: 'admin.form-permintaan.detail',
          component: AppFormPermintaanDetail,
          meta: {
            requiresAuth: true,
            title: 'Detail Form Permintaan',
            permission: 'form-permintaan-view-all',
          },
        },
        {
          path: 'work-orders',
          name: 'admin.workorders',
          component: WorkOrderList,
          meta: { requiresAuth: true, title: 'Data Work Order', permissions: ['work-order-list', 'work-order-create', 'work-order-edit', 'work-order-delete', 'work-order-update-status'] }
        },
        {
          path: 'work-order/create',
          name: 'admin.workorder.create',
          component: WorkOrderForm,
          meta: { requiresAuth: true, title: 'Tambah Work Order', permission: 'work-order-create' }
        },
        {
          path: 'work-order/:id/edit',
          name: 'admin.workorder.edit',
          component: WorkOrderForm,
          meta: { requiresAuth: true, title: 'Edit Work Order', permission: 'work-order-edit' }
        },
        {
          path: 'work-order/:id',
          name: 'admin.workorder.detail',
          component: WorkOrderDetail,
          meta: { requiresAuth: true, title: 'Detail Work Order', permission: 'work-order-list' }
        },
        {
          path: 'work-reports',
          name: 'admin.workreports',
          component: WorkReportList,
          meta: { requiresAuth: true, title: 'Data Laporan Kerja', permissions: ['work-report-list', 'work-report-create', 'work-report-edit', 'work-report-delete'] }
        },
        {
          path: 'work-report/create',
          name: 'admin.workreport.create',
          component: WorkReportForm,
          meta: { requiresAuth: true, title: 'Tambah Laporan Kerja', permission: 'work-report-create' }
        },
        {
          path: 'work-report/:id/edit',
          name: 'admin.workreport.edit',
          component: WorkReportForm,
          meta: { requiresAuth: true, title: 'Edit Laporan Kerja', permission: 'work-report-edit' }
        },
        {
          path: 'work-report/:id',
          name: 'admin.workreport.detail',
          component: WorkReportDetail,
          meta: { requiresAuth: true, title: 'Detail Laporan Kerja', permission: 'work-report-list' }
        },
        {
          path: 'daily-records',
          name: 'admin.daily-records',
          component: DailyRecordList,
          meta: { requiresAuth: true, title: 'Data Laporan Harian Cabang', permissions: ['daily-record-list', 'daily-record-create', 'daily-record-edit', 'daily-record-delete'] }
        },
        {
          path: 'daily-record/create',
          name: 'admin.daily-record.create',
          component: DailyRecordForm,
          meta: { requiresAuth: true, title: 'Tambah Laporan Harian Cabang', permission: 'daily-record-create' }
        },
        {
          path: 'daily-record/:id/edit',
          name: 'admin.daily-record.edit',
          component: DailyRecordForm,
          meta: { requiresAuth: true, title: 'Edit Laporan Harian Cabang', permission: 'daily-record-edit' }
        },
        {
          path: 'daily-record/:id',
          name: 'admin.daily-record.detail',
          component: DailyRecordDetail,
          meta: { requiresAuth: true, title: 'Detail Laporan Harian Cabang', permission: 'daily-record-list' }
        },
        {
          path: 'daily-usage-report',
          name: 'admin.daily-usage-report',
          component: DailyUsageReport,
          meta: { requiresAuth: true, title: 'Laporan Daily Usage', permission: 'daily-record-list' }
        },
        {
          path: 'job-templates',
          name: 'admin.job-templates',
          component: JobTemplateList,
          meta: { requiresAuth: true, title: 'Template Job', permissions: ['job-template-list', 'job-template-create', 'job-template-edit', 'job-template-delete'] }
        },
        {
          path: 'job-template/create',
          name: 'admin.job-template.create',
          component: JobTemplateForm,
          meta: { requiresAuth: true, title: 'Tambah Template Job', permission: 'job-template-create' }
        },
        {
          path: 'job-template/:id/edit',
          name: 'admin.job-template.edit',
          component: JobTemplateForm,
          meta: { requiresAuth: true, title: 'Edit Template Job', permission: 'job-template-edit' }
        },
        {
          path: 'roles',
          name: 'admin.roles',
          component: RoleList,
          meta: { requiresAuth: true, title: 'Data Role', permissions: ['role-list', 'role-create', 'role-edit', 'role-delete'] }
        },
        {
          path: 'roles/matrix',
          name: 'admin.roles.matrix',
          component: PermissionMatrix,
          meta: { requiresAuth: true, title: 'Permission Matrix', permission: 'role-list' }
        },
        {
          path: 'role/create',
          name: 'admin.role.create',
          component: RoleForm,
          meta: { requiresAuth: true, title: 'Tambah Role', permission: 'role-create' }
        },
        {
          path: 'role/:id/edit',
          name: 'admin.role.edit',
          component: RoleForm,
          meta: { requiresAuth: true, title: 'Edit Role', permission: 'role-edit' }
        },
        {
          path: 'whatsapp-settings',
          name: 'admin.whatsapp-settings',
          component: WhatsAppSettings,
          meta: { requiresAuth: true, title: 'Pengaturan WhatsApp', permissions: ['whatsapp-setting-list', 'whatsapp-setting-edit'] }
        },
        {
          path: 'user-activity',
          name: 'admin.user-activity',
          component: UserActivityMonitor,
          meta: { requiresAuth: true, title: 'Monitoring Aktivitas User', permission: 'user-activity-list' }
        },
        {
          path: 'activity-logs',
          name: 'admin.activity-logs',
          component: ActivityLogList,
          meta: { requiresAuth: true, title: 'Activity Log', permission: 'activity-log-list' }
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
          meta: { requiresUnauth: true },
        },
        {
          path: 'register',
          name: 'register',
          component: Register,
          meta: { requiresUnauth: true },
        },
      ],
    },
    // Error Pages
    {
      path: '/error/403',
      name: 'error.forbidden',
      component: Forbidden,
      meta: { title: 'Akses Ditolak' }
    },
    {
      path: '/error/401',
      name: 'error.unauthorized',
      component: Unauthorized,
      meta: { title: 'Tidak Terotentikasi' }
    },
    {
      path: '/error/500',
      name: 'error.server',
      component: ServerError,
      meta: { title: 'Kesalahan Server' }
    },
    {
      path: '/error/404',
      name: 'error.notfound',
      component: NotFound,
      meta: { title: 'Halaman Tidak Ditemukan' }
    },
    // Catch-all route for 404
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: NotFound,
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

        // Permission-based redirect: Check if user has admin panel access
        const userPermissions = authStore.user?.permissions || []
        const hasAdminPanelAccess = userPermissions.includes('system-admin-panel-access')

        // Redirect users based on their layout access permission
        if (hasAdminPanelAccess && to.name?.startsWith('app.')) {
          // User has admin access but trying to access app routes -> redirect to admin routes
          const routeMapping = {
            'app.dashboard': 'admin.dashboard',
            'app.profile': 'admin.profile',
            'app.tickets': 'admin.tickets',
            'app.ticket.detail': 'admin.ticket.detail',
            'app.ticket.create': 'admin.ticket.create',
            'app.form-permintaan': 'admin.form-permintaan',
            'app.form-permintaan.create': 'admin.form-permintaan.create',
            'app.form-permintaan.detail': 'admin.form-permintaan.detail',
            'app.daily-records': 'admin.daily-records',
            'app.daily-record.create': 'admin.daily-record.create',
            'app.daily-record.edit': 'admin.daily-record.edit',
            'app.daily-record.detail': 'admin.daily-record.detail',
            'app.daily-usage-report': 'admin.daily-usage-report',
          }

          if (routeMapping[to.name]) {
            return next({ name: routeMapping[to.name], params: to.params, query: to.query })
          }

          // Any other app.* route that has no admin equivalent -> redirect to admin dashboard
          return next({ name: 'admin.dashboard' })
        } else if (!hasAdminPanelAccess && to.name?.startsWith('admin.')) {
          // User doesn't have admin access but trying to access admin routes -> redirect to app routes or forbidden
          const routeMapping = {
            'admin.dashboard': 'app.dashboard',
            'admin.profile': 'app.profile',
            'admin.tickets': 'app.tickets',
            'admin.ticket.detail': 'app.ticket.detail',
            'admin.ticket.create': 'app.ticket.create',
            'admin.form-permintaan': 'app.form-permintaan',
            'admin.form-permintaan.create': 'app.form-permintaan.create',
            'admin.form-permintaan.detail': 'app.form-permintaan.detail',
            'admin.daily-records': 'app.daily-records',
            'admin.daily-record.create': 'app.daily-record.create',
            'admin.daily-record.edit': 'app.daily-record.edit',
            'admin.daily-record.detail': 'app.daily-record.detail',
            'admin.daily-usage-report': 'app.daily-usage-report',
          }

          if (routeMapping[to.name]) {
            return next({ name: routeMapping[to.name], params: to.params, query: to.query })
          } else {
            // No equivalent app route, show forbidden
            return next({ name: 'error.forbidden' })
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
    // Redirect based on permission when already authenticated
    // Ensure user data is loaded before reading permissions
    if (!authStore.user) {
      try {
        await authStore.checkAuth()
      } catch {
        next()
        return
      }
    }
    const userPermissions = authStore.user?.permissions || []
    const hasAdminPanelAccess = userPermissions.includes('system-admin-panel-access')
    next({ name: hasAdminPanelAccess ? 'admin.dashboard' : 'app.dashboard' })
  } else {
    next()
  }
})


export default router
