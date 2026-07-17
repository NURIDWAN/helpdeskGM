/**
 * Permission Configuration
 * 
 * Konfigurasi permission berbasis fitur untuk halaman Role Management.
 * Mengelompokkan permission berdasarkan fitur yang dipahami user, 
 * bukan berdasarkan modul teknis.
 */

/**
 * Feature Groups - Pengelompokan permission berdasarkan fitur
 * 
 * Struktur:
 * - label: Nama yang ditampilkan ke user
 * - icon: Nama icon dari lucide-vue-next
 * - description: Deskripsi singkat fitur
 * - color: Warna tema untuk visual
 * - permissions: Object permission dengan label dan deskripsi
 * - subFeatures: Sub-fitur yang berelasi (opsional)
 */
export const featureGroups = {
  system: {
    label: "Akses Sistem",
    icon: "Settings",
    description: "Permission dasar untuk mengakses sistem",
    color: "slate",
    permissions: {
      "system-admin-panel-access": {
        label: "Akses Panel Admin",
        description: "Menentukan user bisa mengakses Admin Layout (dashboard admin, tidak hanya app user)"
      }
    }
  },

  dashboard: {
    label: "Dashboard",
    icon: "BarChart3",
    description: "Akses dashboard analitik dan statistik",
    color: "blue",
    permissions: {
      "dashboard-menu": { 
        label: "Akses Menu", 
        description: "Menampilkan menu dashboard di sidebar" 
      },
      "dashboard-view": { 
        label: "Lihat Dashboard", 
        description: "Melihat halaman utama dashboard" 
      },
      "dashboard-view-metrics": { 
        label: "Lihat Metrik", 
        description: "Melihat kartu metrik (total tiket, SPK, dll)" 
      },
      "dashboard-view-charts": { 
        label: "Lihat Grafik", 
        description: "Melihat grafik statistik" 
      },
      "dashboard-view-staff-rankings": { 
        label: "Lihat Ranking Staff", 
        description: "Melihat peringkat kinerja staff" 
      },
      "dashboard-view-trends": { 
        label: "Lihat Tren", 
        description: "Melihat analisis tren data" 
      },
      "dashboard-view-all": { 
        label: "Lihat Semua Data", 
        description: "Akses data dashboard semua cabang" 
      }
    }
  },

  user: {
    label: "Manajemen User",
    icon: "Users",
    description: "Kelola data pengguna sistem",
    color: "indigo",
    permissions: {
      "user-menu": { 
        label: "Akses Menu", 
        description: "Menampilkan menu user di sidebar" 
      },
      "user-list": { 
        label: "Lihat Daftar", 
        description: "Melihat daftar semua user" 
      },
      "user-create": { 
        label: "Buat User", 
        description: "Membuat user baru" 
      },
      "user-edit": { 
        label: "Edit User", 
        description: "Mengedit data user" 
      },
      "user-delete": { 
        label: "Hapus User", 
        description: "Menghapus user dari sistem" 
      }
    }
  },

  role: {
    label: "Manajemen Role",
    icon: "Shield",
    description: "Kelola role dan permission",
    color: "purple",
    permissions: {
      "role-menu": { 
        label: "Akses Menu", 
        description: "Menampilkan menu role di sidebar" 
      },
      "role-list": { 
        label: "Lihat Daftar", 
        description: "Melihat daftar role" 
      },
      "role-create": { 
        label: "Buat Role", 
        description: "Membuat role baru" 
      },
      "role-edit": { 
        label: "Edit Role", 
        description: "Mengedit role dan permission" 
      },
      "role-delete": { 
        label: "Hapus Role", 
        description: "Menghapus role" 
      }
    }
  },

  branch: {
    label: "Manajemen Cabang",
    icon: "Building",
    description: "Kelola data cabang/lokasi",
    color: "emerald",
    permissions: {
      "branch-menu": { 
        label: "Akses Menu", 
        description: "Menampilkan menu cabang di sidebar" 
      },
      "branch-list": { 
        label: "Lihat Daftar", 
        description: "Melihat daftar cabang" 
      },
      "branch-create": { 
        label: "Buat Cabang", 
        description: "Menambah cabang baru" 
      },
      "branch-edit": { 
        label: "Edit Cabang", 
        description: "Mengedit data cabang" 
      },
      "branch-delete": { 
        label: "Hapus Cabang", 
        description: "Menghapus cabang" 
      },
      "branch-view-all": { 
        label: "Lihat Semua", 
        description: "Akses data semua cabang" 
      }
    }
  },

  jobTemplate: {
    label: "Template Job",
    icon: "FileCode",
    description: "Kelola template pekerjaan",
    color: "cyan",
    permissions: {
      "job-template-menu": { 
        label: "Akses Menu", 
        description: "Menampilkan menu template job di sidebar" 
      },
      "job-template-list": { 
        label: "Lihat Daftar", 
        description: "Melihat daftar template" 
      },
      "job-template-create": { 
        label: "Buat Template", 
        description: "Membuat template baru" 
      },
      "job-template-edit": { 
        label: "Edit Template", 
        description: "Mengedit template" 
      },
      "job-template-delete": { 
        label: "Hapus Template", 
        description: "Menghapus template" 
      },
      "job-template-view-all": { 
        label: "Lihat Semua", 
        description: "Akses template semua cabang" 
      }
    }
  },

  ticket: {
    label: "Manajemen Tiket",
    icon: "Tag",
    description: "Kelola tiket, balasan, dan lampiran",
    color: "orange",
    subFeatures: {
      core: {
        label: "Tiket",
        icon: "Tag",
        description: "Operasi dasar tiket",
        permissions: {
          "ticket-menu": { 
            label: "Akses Menu", 
            description: "Menampilkan menu tiket di sidebar" 
          },
          "ticket-list": { 
            label: "Lihat Daftar", 
            description: "Melihat daftar tiket" 
          },
          "ticket-create": { 
            label: "Buat Tiket", 
            description: "Membuat tiket baru" 
          },
          "ticket-edit": { 
            label: "Edit Tiket", 
            description: "Mengedit data tiket" 
          },
          "ticket-delete": { 
            label: "Hapus Tiket", 
            description: "Menghapus tiket" 
          },
          "ticket-update-status": { 
            label: "Ubah Status", 
            description: "Mengubah status tiket" 
          },
          "ticket-view-all": { 
            label: "Lihat Semua", 
            description: "Akses tiket semua cabang" 
          }
        }
      },
      reply: {
        label: "Balasan Tiket",
        icon: "MessageSquare",
        description: "Kelola balasan/komentar tiket",
        dependsOn: ["ticket-list", "ticket-menu"],
        permissions: {
          "ticket-reply-list": { 
            label: "Lihat Balasan", 
            description: "Melihat daftar balasan tiket" 
          },
          "ticket-reply-create": { 
            label: "Balas Tiket", 
            description: "Menambah balasan pada tiket" 
          },
          "ticket-reply-edit": { 
            label: "Edit Balasan", 
            description: "Mengedit balasan" 
          },
          "ticket-reply-delete": { 
            label: "Hapus Balasan", 
            description: "Menghapus balasan" 
          }
        }
      },
      attachment: {
        label: "Lampiran Tiket",
        icon: "Paperclip",
        description: "Kelola file lampiran tiket",
        dependsOn: ["ticket-list", "ticket-menu"],
        permissions: {
          "ticket-attachment-list": { 
            label: "Lihat Lampiran", 
            description: "Melihat daftar lampiran" 
          },
          "ticket-attachment-create": { 
            label: "Upload Lampiran", 
            description: "Mengupload file lampiran" 
          },
          "ticket-attachment-delete": { 
            label: "Hapus Lampiran", 
            description: "Menghapus lampiran" 
          }
        }
      }
    }
  },

  ticketCategory: {
    label: "Kategori Tiket",
    icon: "FolderTree",
    description: "Kelola kategori tiket",
    color: "amber",
    permissions: {
      "ticket-category-menu": { 
        label: "Akses Menu", 
        description: "Menampilkan menu kategori di sidebar" 
      },
      "ticket-category-list": { 
        label: "Lihat Daftar", 
        description: "Melihat daftar kategori" 
      },
      "ticket-category-create": { 
        label: "Buat Kategori", 
        description: "Membuat kategori baru" 
      },
      "ticket-category-edit": { 
        label: "Edit Kategori", 
        description: "Mengedit kategori" 
      },
      "ticket-category-delete": { 
        label: "Hapus Kategori", 
        description: "Menghapus kategori" 
      }
    }
  },

  formPermintaan: {
    label: "Form Permintaan",
    icon: "ScrollText",
    description: "Kelola form permintaan outlet",
    color: "blue",
    permissions: {
      "form-permintaan-menu": {
        label: "Akses Menu",
        description: "Menampilkan menu form permintaan di sidebar"
      },
      "form-permintaan-list": {
        label: "Lihat Daftar",
        description: "Melihat daftar form permintaan"
      },
      "form-permintaan-create": {
        label: "Buat Form",
        description: "Membuat form permintaan baru"
      },
      "form-permintaan-confirm": {
        label: "Konfirmasi Form",
        description: "Mengubah status form permintaan menjadi approved"
      },
      "form-permintaan-view-all": {
        label: "Lihat Semua",
        description: "Akses form permintaan semua user dan outlet"
      },
      "form-permintaan-review": {
        label: "Review Form",
        description: "Mereview form permintaan sebelum disetujui"
      },
      "form-permintaan-reject": {
        label: "Tolak Form",
        description: "Menolak form permintaan"
      },
      "form-permintaan-edit": {
        label: "Edit Form",
        description: "Mengedit form permintaan yang sudah dibuat"
      },
      "form-permintaan-delete": {
        label: "Hapus Form",
        description: "Menghapus form permintaan"
      }
    }
  },

  workOrder: {
    label: "Surat Perintah Kerja",
    icon: "ClipboardList",
    description: "Kelola surat perintah kerja (SPK)",
    color: "violet",
    permissions: {
      "work-order-menu": { 
        label: "Akses Menu", 
        description: "Menampilkan menu SPK di sidebar" 
      },
      "work-order-list": { 
        label: "Lihat Daftar", 
        description: "Melihat daftar SPK" 
      },
      "work-order-create": { 
        label: "Buat SPK", 
        description: "Membuat SPK baru" 
      },
      "work-order-edit": { 
        label: "Edit SPK", 
        description: "Mengedit SPK" 
      },
      "work-order-delete": { 
        label: "Hapus SPK", 
        description: "Menghapus SPK" 
      },
      "work-order-update-status": { 
        label: "Ubah Status", 
        description: "Mengubah status SPK" 
      },
      "work-order-view-all": { 
        label: "Lihat Semua", 
        description: "Akses SPK semua cabang" 
      }
    }
  },

  workReport: {
    label: "Laporan Pekerjaan",
    icon: "FileText",
    description: "Kelola laporan pekerjaan dan lampiran",
    color: "green",
    subFeatures: {
      core: {
        label: "Laporan",
        icon: "FileText",
        description: "Operasi dasar laporan",
        permissions: {
          "work-report-menu": { 
            label: "Akses Menu", 
            description: "Menampilkan menu laporan di sidebar" 
          },
          "work-report-list": { 
            label: "Lihat Daftar", 
            description: "Melihat daftar laporan" 
          },
          "work-report-create": { 
            label: "Buat Laporan", 
            description: "Membuat laporan baru" 
          },
          "work-report-edit": { 
            label: "Edit Laporan", 
            description: "Mengedit laporan" 
          },
          "work-report-delete": { 
            label: "Hapus Laporan", 
            description: "Menghapus laporan" 
          },
          "work-report-view-all": { 
            label: "Lihat Semua", 
            description: "Akses laporan semua cabang" 
          }
        }
      },
      attachment: {
        label: "Lampiran Laporan",
        icon: "Paperclip",
        description: "Kelola file lampiran laporan",
        dependsOn: ["work-report-list", "work-report-menu"],
        permissions: {
          "work-report-attachment-list": { 
            label: "Lihat Lampiran", 
            description: "Melihat daftar lampiran" 
          },
          "work-report-attachment-create": { 
            label: "Upload Lampiran", 
            description: "Mengupload file lampiran" 
          },
          "work-report-attachment-delete": { 
            label: "Hapus Lampiran", 
            description: "Menghapus lampiran" 
          }
        }
      }
    }
  },

  dailyRecord: {
    label: "Laporan Harian",
    icon: "Calendar",
    description: "Kelola laporan harian dan pembacaan utility",
    color: "teal",
    subFeatures: {
      core: {
        label: "Laporan Harian",
        icon: "Calendar",
        description: "Operasi dasar laporan harian",
        permissions: {
          "daily-record-menu": { 
            label: "Akses Menu", 
            description: "Menampilkan menu laporan harian di sidebar" 
          },
          "daily-record-list": { 
            label: "Lihat Daftar", 
            description: "Melihat daftar laporan harian" 
          },
          "daily-record-create": { 
            label: "Buat Laporan", 
            description: "Membuat laporan harian baru" 
          },
          "daily-record-edit": { 
            label: "Edit Laporan", 
            description: "Mengedit laporan harian" 
          },
          "daily-record-delete": { 
            label: "Hapus Laporan", 
            description: "Menghapus laporan harian" 
          },
          "daily-record-view-all": { 
            label: "Lihat Semua", 
            description: "Akses laporan semua cabang" 
          }
        }
      },
      utility: {
        label: "Pembacaan Utility",
        icon: "Gauge",
        description: "Kelola pembacaan meter utility",
        dependsOn: ["daily-record-list", "daily-record-menu"],
        permissions: {
          "utility-reading-list": { 
            label: "Lihat Pembacaan", 
            description: "Melihat daftar pembacaan utility" 
          },
          "utility-reading-create": { 
            label: "Input Pembacaan", 
            description: "Menginput pembacaan utility" 
          },
          "utility-reading-edit": { 
            label: "Edit Pembacaan", 
            description: "Mengedit pembacaan utility" 
          },
          "utility-reading-delete": { 
            label: "Hapus Pembacaan", 
            description: "Menghapus pembacaan utility" 
          },
          "utility-reading-view-all": { 
            label: "Lihat Semua", 
            description: "Akses pembacaan semua cabang" 
          }
        }
      }
    }
  },

  electricity: {
    label: "Manajemen Listrik",
    icon: "Zap",
    description: "Kelola meter dan pembacaan listrik",
    color: "yellow",
    subFeatures: {
      meter: {
        label: "Meter Listrik",
        icon: "Zap",
        description: "Kelola data meter listrik",
        permissions: {
          "electricity-meter-menu": { 
            label: "Akses Menu", 
            description: "Menampilkan menu meter di sidebar" 
          },
          "electricity-meter-list": { 
            label: "Lihat Daftar", 
            description: "Melihat daftar meter" 
          },
          "electricity-meter-create": { 
            label: "Tambah Meter", 
            description: "Menambah meter baru" 
          },
          "electricity-meter-edit": { 
            label: "Edit Meter", 
            description: "Mengedit data meter" 
          },
          "electricity-meter-delete": { 
            label: "Hapus Meter", 
            description: "Menghapus meter" 
          }
        }
      },
      reading: {
        label: "Pembacaan Meter",
        icon: "Activity",
        description: "Kelola pembacaan meter listrik",
        dependsOn: ["electricity-meter-list", "electricity-meter-menu"],
        permissions: {
          "electricity-reading-list": { 
            label: "Lihat Pembacaan", 
            description: "Melihat daftar pembacaan" 
          },
          "electricity-reading-create": { 
            label: "Input Pembacaan", 
            description: "Menginput pembacaan meter" 
          },
          "electricity-reading-edit": { 
            label: "Edit Pembacaan", 
            description: "Mengedit pembacaan" 
          },
          "electricity-reading-delete": { 
            label: "Hapus Pembacaan", 
            description: "Menghapus pembacaan" 
          }
        }
      }
    }
  },

  whatsappSetting: {
    label: "Pengaturan WhatsApp",
    icon: "MessageCircle",
    description: "Kelola integrasi WhatsApp",
    color: "green",
    permissions: {
      "whatsapp-setting-menu": { 
        label: "Akses Menu", 
        description: "Menampilkan menu WhatsApp di sidebar" 
      },
      "whatsapp-setting-list": { 
        label: "Lihat Pengaturan", 
        description: "Melihat pengaturan WhatsApp" 
      },
      "whatsapp-setting-edit": { 
        label: "Edit Pengaturan", 
        description: "Mengubah pengaturan WhatsApp" 
      }
    }
  },

  userActivity: {
    label: "Monitoring Aktivitas",
    icon: "Activity",
    description: "Pantau aktivitas pengguna",
    color: "rose",
    permissions: {
      "user-activity-menu": { 
        label: "Akses Menu", 
        description: "Menampilkan menu aktivitas di sidebar" 
      },
      "user-activity-list": { 
        label: "Lihat Aktivitas", 
        description: "Melihat log aktivitas user" 
      }
    }
  }
};

/**
 * Permission Dependencies
 * 
 * Mendefinisikan permission mana yang dibutuhkan oleh permission lain.
 * Format: "permission-yang-dipilih": ["permission-yang-dibutuhkan", ...]
 * 
 * Saat user memilih permission di key, sistem otomatis memilih permission di value.
 */
export const permissionDependencies = {
  // Dashboard dependencies
  "dashboard-view": ["dashboard-menu"],
  "dashboard-view-metrics": ["dashboard-view", "dashboard-menu"],
  "dashboard-view-charts": ["dashboard-view", "dashboard-menu"],
  "dashboard-view-staff-rankings": ["dashboard-view", "dashboard-menu"],
  "dashboard-view-trends": ["dashboard-view", "dashboard-menu"],
  "dashboard-view-all": ["dashboard-view", "dashboard-menu"],

  // User dependencies
  "user-list": ["user-menu"],
  "user-create": ["user-list", "user-menu"],
  "user-edit": ["user-list", "user-menu"],
  "user-delete": ["user-list", "user-menu"],

  // Role dependencies
  "role-list": ["role-menu"],
  "role-create": ["role-list", "role-menu"],
  "role-edit": ["role-list", "role-menu"],
  "role-delete": ["role-list", "role-menu"],

  // Branch dependencies
  "branch-list": ["branch-menu"],
  "branch-create": ["branch-list", "branch-menu"],
  "branch-edit": ["branch-list", "branch-menu"],
  "branch-delete": ["branch-list", "branch-menu"],
  "branch-view-all": ["branch-list", "branch-menu"],

  // Job Template dependencies
  "job-template-list": ["job-template-menu"],
  "job-template-create": ["job-template-list", "job-template-menu"],
  "job-template-edit": ["job-template-list", "job-template-menu"],
  "job-template-delete": ["job-template-list", "job-template-menu"],
  "job-template-view-all": ["job-template-list", "job-template-menu"],

  // Ticket dependencies
  "ticket-list": ["ticket-menu"],
  "ticket-create": ["ticket-list", "ticket-menu"],
  "ticket-edit": ["ticket-list", "ticket-menu"],
  "ticket-delete": ["ticket-list", "ticket-menu"],
  "ticket-update-status": ["ticket-list", "ticket-menu"],
  "ticket-view-all": ["ticket-list", "ticket-menu"],

  // Ticket Reply dependencies (butuh akses tiket)
  "ticket-reply-list": ["ticket-list", "ticket-menu"],
  "ticket-reply-create": ["ticket-reply-list", "ticket-list", "ticket-menu"],
  "ticket-reply-edit": ["ticket-reply-list", "ticket-list", "ticket-menu"],
  "ticket-reply-delete": ["ticket-reply-list", "ticket-list", "ticket-menu"],

  // Ticket Attachment dependencies (butuh akses tiket)
  "ticket-attachment-list": ["ticket-list", "ticket-menu"],
  "ticket-attachment-create": ["ticket-attachment-list", "ticket-list", "ticket-menu"],
  "ticket-attachment-delete": ["ticket-attachment-list", "ticket-list", "ticket-menu"],

  // Ticket Category dependencies
  "ticket-category-list": ["ticket-category-menu"],
  "ticket-category-create": ["ticket-category-list", "ticket-category-menu"],
  "ticket-category-edit": ["ticket-category-list", "ticket-category-menu"],
  "ticket-category-delete": ["ticket-category-list", "ticket-category-menu"],

  // Form Permintaan dependencies
  "form-permintaan-list": ["form-permintaan-menu"],
  "form-permintaan-create": ["form-permintaan-list", "form-permintaan-menu"],
  "form-permintaan-confirm": ["form-permintaan-list", "form-permintaan-menu"],
  "form-permintaan-view-all": ["form-permintaan-list", "form-permintaan-menu"],
  "form-permintaan-review": ["form-permintaan-list", "form-permintaan-menu"],
  "form-permintaan-reject": ["form-permintaan-list", "form-permintaan-menu"],
  "form-permintaan-edit": ["form-permintaan-list", "form-permintaan-menu"],
  "form-permintaan-delete": ["form-permintaan-list", "form-permintaan-menu"],

  // Work Order dependencies
  "work-order-list": ["work-order-menu"],
  "work-order-create": ["work-order-list", "work-order-menu"],
  "work-order-edit": ["work-order-list", "work-order-menu"],
  "work-order-delete": ["work-order-list", "work-order-menu"],
  "work-order-update-status": ["work-order-list", "work-order-menu"],
  "work-order-view-all": ["work-order-list", "work-order-menu"],

  // Work Report dependencies
  "work-report-list": ["work-report-menu"],
  "work-report-create": ["work-report-list", "work-report-menu"],
  "work-report-edit": ["work-report-list", "work-report-menu"],
  "work-report-delete": ["work-report-list", "work-report-menu"],
  "work-report-view-all": ["work-report-list", "work-report-menu"],

  // Work Report Attachment dependencies (butuh akses laporan)
  "work-report-attachment-list": ["work-report-list", "work-report-menu"],
  "work-report-attachment-create": ["work-report-attachment-list", "work-report-list", "work-report-menu"],
  "work-report-attachment-delete": ["work-report-attachment-list", "work-report-list", "work-report-menu"],

  // Daily Record dependencies
  "daily-record-list": ["daily-record-menu"],
  "daily-record-create": ["daily-record-list", "daily-record-menu"],
  "daily-record-edit": ["daily-record-list", "daily-record-menu"],
  "daily-record-delete": ["daily-record-list", "daily-record-menu"],
  "daily-record-view-all": ["daily-record-list", "daily-record-menu"],

  // Utility Reading dependencies (butuh akses daily record)
  "utility-reading-list": ["daily-record-list", "daily-record-menu"],
  "utility-reading-create": ["utility-reading-list", "daily-record-list", "daily-record-menu"],
  "utility-reading-edit": ["utility-reading-list", "daily-record-list", "daily-record-menu"],
  "utility-reading-delete": ["utility-reading-list", "daily-record-list", "daily-record-menu"],
  "utility-reading-view-all": ["utility-reading-list", "daily-record-list", "daily-record-menu"],

  // Electricity Meter dependencies
  "electricity-meter-list": ["electricity-meter-menu"],
  "electricity-meter-create": ["electricity-meter-list", "electricity-meter-menu"],
  "electricity-meter-edit": ["electricity-meter-list", "electricity-meter-menu"],
  "electricity-meter-delete": ["electricity-meter-list", "electricity-meter-menu"],

  // Electricity Reading dependencies (butuh akses meter)
  "electricity-reading-list": ["electricity-meter-list", "electricity-meter-menu"],
  "electricity-reading-create": ["electricity-reading-list", "electricity-meter-list", "electricity-meter-menu"],
  "electricity-reading-edit": ["electricity-reading-list", "electricity-meter-list", "electricity-meter-menu"],
  "electricity-reading-delete": ["electricity-reading-list", "electricity-meter-list", "electricity-meter-menu"],

  // WhatsApp Setting dependencies
  "whatsapp-setting-list": ["whatsapp-setting-menu"],
  "whatsapp-setting-edit": ["whatsapp-setting-list", "whatsapp-setting-menu"],

  // User Activity dependencies
  "user-activity-list": ["user-activity-menu"]
};

/**
 * Role Presets - Template role yang sudah dikonfigurasi
 * 
 * Mempercepat pembuatan role dengan preset yang umum digunakan.
 * Disesuaikan dengan RoleSeeder.php
 */
export const rolePresets = {
  "staff": {
    label: "Staff",
    description: "Akses operasional tiket, SPK, dan laporan pekerjaan",
    icon: "Wrench",
    permissions: [
      // admin panel access
      "system-admin-panel-access",
      // dashboard (basic view)
      "dashboard-menu",
      "dashboard-view",
      // branches (readonly for dropdowns)
      "branch-list",
      // ticket categories (for ticket forms)
      "ticket-category-list",
      // job templates (for work orders/reports)
      "job-template-list",
      // users (readonly for dropdowns/assign)
      "user-list",
      // tickets
      "ticket-menu",
      "ticket-list",
      "ticket-create",
      "ticket-update-status",
      // ticket replies
      "ticket-reply-list",
      "ticket-reply-create",
      "ticket-reply-edit",
      "ticket-reply-delete",
      // ticket attachments
      "ticket-attachment-list",
      "ticket-attachment-create",
      "ticket-attachment-delete",
      // work orders
      "work-order-menu",
      "work-order-list",
      "work-order-update-status",
      // work reports
      "work-report-menu",
      "work-report-list",
      "work-report-create",
      "work-report-edit",
      "work-report-attachment-list",
      "work-report-attachment-create",
      "work-report-attachment-delete",
      // form permintaan
      "form-permintaan-menu",
      "form-permintaan-list",
      "form-permintaan-confirm",
      "form-permintaan-view-all",
      "form-permintaan-reject"
    ]
  },
  "user": {
    label: "User",
    description: "Akses dasar tiket dan laporan harian",
    icon: "UserCheck",
    permissions: [
      // ticket categories (for dropdown in ticket form)
      "ticket-category-list",
      // tickets
      "ticket-menu",
      "ticket-list",
      "ticket-create",
      "ticket-update-status",
      // ticket replies
      "ticket-reply-list",
      "ticket-reply-create",
      // ticket attachments
      "ticket-attachment-list",
      "ticket-attachment-create",
      "ticket-attachment-delete",
      // resources needed for forms
      "branch-list",
      "user-list",
      "electricity-meter-list",
      // daily reports
      "daily-record-menu",
      "daily-record-list",
      "daily-record-create",
      "daily-record-edit",
      "utility-reading-list",
      "utility-reading-create",
      "utility-reading-edit",
      "electricity-reading-list",
      "electricity-reading-create",
      "electricity-reading-edit"
    ]
  },
  "admin": {
    label: "Admin",
    description: "Akses semua fitur kecuali manajemen role dan pengaturan WhatsApp",
    icon: "Shield",
    permissions: [
      // System
      "system-admin-panel-access",
      // Dashboard
      "dashboard-menu", "dashboard-view", "dashboard-view-metrics", "dashboard-view-charts",
      "dashboard-view-staff-rankings", "dashboard-view-trends", "dashboard-view-all",
      // User management
      "user-menu", "user-list", "user-create", "user-edit", "user-delete",
      // Role (view only)
      "role-menu", "role-list",
      // Branch
      "branch-menu", "branch-list", "branch-create", "branch-edit", "branch-delete", "branch-view-all",
      // Job Template
      "job-template-menu", "job-template-list", "job-template-create", "job-template-edit", "job-template-delete", "job-template-view-all",
      // Ticket Category
      "ticket-category-menu", "ticket-category-list", "ticket-category-create", "ticket-category-edit", "ticket-category-delete",
      // Tickets
      "ticket-menu", "ticket-list", "ticket-create", "ticket-edit", "ticket-delete", "ticket-update-status", "ticket-view-all",
      "ticket-reply-list", "ticket-reply-create", "ticket-reply-edit", "ticket-reply-delete",
      "ticket-attachment-list", "ticket-attachment-create", "ticket-attachment-delete",
      // Form Permintaan
      "form-permintaan-menu", "form-permintaan-list", "form-permintaan-create", "form-permintaan-confirm", "form-permintaan-view-all",
      // Work Order
      "work-order-menu", "work-order-list", "work-order-create", "work-order-edit", "work-order-delete", "work-order-update-status", "work-order-view-all",
      // Work Report
      "work-report-menu", "work-report-list", "work-report-create", "work-report-edit", "work-report-delete", "work-report-view-all",
      "work-report-attachment-list", "work-report-attachment-create", "work-report-attachment-delete",
      // Daily Record
      "daily-record-menu", "daily-record-list", "daily-record-create", "daily-record-edit", "daily-record-delete", "daily-record-view-all",
      "utility-reading-list", "utility-reading-create", "utility-reading-edit", "utility-reading-delete", "utility-reading-view-all",
      // Electricity
      "electricity-meter-menu", "electricity-meter-list", "electricity-meter-create", "electricity-meter-edit", "electricity-meter-delete",
      "electricity-reading-list", "electricity-reading-create", "electricity-reading-edit", "electricity-reading-delete"
    ]
  },
  "approver-permintaan": {
    label: "Approver Permintaan",
    description: "Menyetujui atau menolak form permintaan",
    icon: "CheckCircle",
    permissions: [
      "form-permintaan-menu",
      "form-permintaan-list",
      "form-permintaan-confirm",
      "form-permintaan-view-all",
      "form-permintaan-reject"
    ]
  },
  "reviewer-permintaan": {
    label: "Reviewer Permintaan",
    description: "Mereview form permintaan sebelum approval",
    icon: "Eye",
    permissions: [
      "form-permintaan-menu",
      "form-permintaan-list",
      "form-permintaan-review",
      "form-permintaan-view-all"
    ]
  }
};

/**
 * Color Map untuk badge dan visual
 */
export const colorMap = {
  blue: { bg: "bg-blue-100", text: "text-blue-800", border: "border-blue-200", accent: "bg-blue-500" },
  indigo: { bg: "bg-indigo-100", text: "text-indigo-800", border: "border-indigo-200", accent: "bg-indigo-500" },
  purple: { bg: "bg-purple-100", text: "text-purple-800", border: "border-purple-200", accent: "bg-purple-500" },
  emerald: { bg: "bg-emerald-100", text: "text-emerald-800", border: "border-emerald-200", accent: "bg-emerald-500" },
  cyan: { bg: "bg-cyan-100", text: "text-cyan-800", border: "border-cyan-200", accent: "bg-cyan-500" },
  orange: { bg: "bg-orange-100", text: "text-orange-800", border: "border-orange-200", accent: "bg-orange-500" },
  amber: { bg: "bg-amber-100", text: "text-amber-800", border: "border-amber-200", accent: "bg-amber-500" },
  violet: { bg: "bg-violet-100", text: "text-violet-800", border: "border-violet-200", accent: "bg-violet-500" },
  green: { bg: "bg-green-100", text: "text-green-800", border: "border-green-200", accent: "bg-green-500" },
  teal: { bg: "bg-teal-100", text: "text-teal-800", border: "border-teal-200", accent: "bg-teal-500" },
  yellow: { bg: "bg-yellow-100", text: "text-yellow-800", border: "border-yellow-200", accent: "bg-yellow-500" },
  rose: { bg: "bg-rose-100", text: "text-rose-800", border: "border-rose-200", accent: "bg-rose-500" }
};

/**
 * Helper: Mendapatkan semua permission names dari featureGroups
 */
export function getAllPermissionNames() {
  const permissions = [];
  
  Object.values(featureGroups).forEach(feature => {
    if (feature.permissions) {
      permissions.push(...Object.keys(feature.permissions));
    }
    if (feature.subFeatures) {
      Object.values(feature.subFeatures).forEach(sub => {
        if (sub.permissions) {
          permissions.push(...Object.keys(sub.permissions));
        }
      });
    }
  });
  
  return permissions;
}

/**
 * Helper: Mendapatkan info permission berdasarkan nama
 */
export function getPermissionInfo(permissionName) {
  for (const [featureKey, feature] of Object.entries(featureGroups)) {
    // Check direct permissions
    if (feature.permissions && feature.permissions[permissionName]) {
      return {
        ...feature.permissions[permissionName],
        feature: featureKey,
        featureLabel: feature.label,
        color: feature.color
      };
    }
    
    // Check sub-features
    if (feature.subFeatures) {
      for (const [subKey, sub] of Object.entries(feature.subFeatures)) {
        if (sub.permissions && sub.permissions[permissionName]) {
          return {
            ...sub.permissions[permissionName],
            feature: featureKey,
            featureLabel: feature.label,
            subFeature: subKey,
            subFeatureLabel: sub.label,
            color: feature.color
          };
        }
      }
    }
  }
  
  return null;
}

/**
 * Helper: Menghitung jumlah permission per fitur yang dipilih
 */
export function countSelectedByFeature(selectedPermissions) {
  const counts = {};
  
  Object.entries(featureGroups).forEach(([featureKey, feature]) => {
    let total = 0;
    let selected = 0;
    
    if (feature.permissions) {
      const permKeys = Object.keys(feature.permissions);
      total += permKeys.length;
      selected += permKeys.filter(p => selectedPermissions.includes(p)).length;
    }
    
    if (feature.subFeatures) {
      Object.values(feature.subFeatures).forEach(sub => {
        if (sub.permissions) {
          const permKeys = Object.keys(sub.permissions);
          total += permKeys.length;
          selected += permKeys.filter(p => selectedPermissions.includes(p)).length;
        }
      });
    }
    
    counts[featureKey] = { total, selected };
  });
  
  return counts;
}
