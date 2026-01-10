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
const WhatsAppSettings = () => import('@/views/admin/whatsapp/WhatsAppSettings.vue')
const UserActivityMonitor = () => import('@/views/admin/user/UserActivityMonitor.vue')

// Auth Views
const Login = () => import('@/views/auth/Login.vue')
const Register = () => import('@/views/auth/Register.vue')

// App Views
const AppDashboard = () => import('@/views/app/Dashboard.vue')
const AppTicketDetail = () => import('@/views/app/TicketDetail.vue')
const AppTicketCreate = () => import('@/views/app/TicketCreate.vue')
const AppProfile = () => import('@/views/app/Profile.vue')

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
          component: DailyRecordList,
          meta: { requiresAuth: true, title: 'Laporan Harian Cabang' }
        },
        {
          path: 'daily-record/create',
          name: 'app.daily-record.create',
          component: DailyRecordForm,
          meta: { requiresAuth: true, title: 'Tambah Laporan Harian Cabang' }
        },
        {
          path: 'daily-record/:id/edit',
          name: 'app.daily-record.edit',
          component: DailyRecordForm,
          meta: { requiresAuth: true, title: 'Edit Laporan Harian Cabang' }
        },
        {
          path: 'daily-record/:id',
          name: 'app.daily-record.detail',
          component: DailyRecordDetail,
          meta: { requiresAuth: true, title: 'Detail Laporan Harian Cabang' }
        },
        {
          path: 'daily-usage-report',
          name: 'app.daily-usage-report',
          component: DailyUsageReport,
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
          component: RoleList,
          meta: { requiresAuth: true, title: 'Data Role', permissions: ['role-list', 'role-create', 'role-edit', 'role-delete'] }
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
          }

          if (routeMapping[to.name]) {
            return next({ name: routeMapping[to.name] })
          }
        } else if (!hasAdminPanelAccess && to.name?.startsWith('admin.')) {
          // User doesn't have admin access but trying to access admin routes -> redirect to app routes or forbidden
          const routeMapping = {
            'admin.dashboard': 'app.dashboard',
            'admin.profile': 'app.profile',
          }

          if (routeMapping[to.name]) {
            return next({ name: routeMapping[to.name] })
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
    const userPermissions = authStore.user?.permissions || []
    const hasAdminPanelAccess = userPermissions.includes('system-admin-panel-access')
    next({ name: hasAdminPanelAccess ? 'admin.dashboard' : 'app.dashboard' })
  } else {
    next()
  }
})


export default router
