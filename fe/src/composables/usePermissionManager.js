import { ref, computed } from 'vue';
import { 
  featureGroups, 
  permissionDependencies, 
  rolePresets,
  colorMap,
  getPermissionInfo,
  countSelectedByFeature
} from '@/config/permissionConfig';

/**
 * Composable untuk mengelola permission dalam form Role
 * 
 * Fitur:
 * - Toggle permission dengan auto-select dependency
 * - Toggle seluruh fitur/sub-fitur
 * - Apply preset role template
 * - Track permission yang di-lock karena dependency
 * 
 * @param {Array} initialPermissions - Permission awal (untuk mode edit)
 * @returns {Object} - Methods dan state untuk permission management
 */
export function usePermissionManager(initialPermissions = []) {
  // State
  const selectedPermissions = ref([...initialPermissions]);
  const dependencyLocks = ref({}); // { permissionName: [lockedByPermission1, lockedByPermission2] }

  /**
   * Menghitung dependency untuk sebuah permission
   * Recursively mendapatkan semua dependency termasuk nested.
   * Dibatasi maksimal 3 level kedalaman.
   * 
   * @param {string} permissionName - Permission yang dicari dependency-nya
   * @param {Set} visited - Set untuk mencegah circular reference
   * @param {number} depth - Kedalaman saat ini (dimulai dari 0)
   * @param {number} maxDepth - Kedalaman maksimal (default: 3)
   */
  const getAllDependencies = (permissionName, visited = new Set(), depth = 0, maxDepth = 3) => {
    if (visited.has(permissionName) || depth >= maxDepth) return [];
    visited.add(permissionName);
    
    const directDeps = permissionDependencies[permissionName] || [];
    const allDeps = [...directDeps];
    
    // Recursively get dependencies of dependencies (up to maxDepth)
    directDeps.forEach(dep => {
      const nestedDeps = getAllDependencies(dep, visited, depth + 1, maxDepth);
      nestedDeps.forEach(nd => {
        if (!allDeps.includes(nd)) {
          allDeps.push(nd);
        }
      });
    });
    
    return allDeps;
  };

  /**
   * Mendapatkan permission mana yang langsung bergantung pada permission tertentu
   * (hanya direct dependents yang saat ini aktif/selected)
   */
  const getDependents = (permissionName) => {
    const dependents = [];
    for (const [perm, deps] of Object.entries(permissionDependencies)) {
      if (deps.includes(permissionName) && selectedPermissions.value.includes(perm)) {
        dependents.push(perm);
      }
    }
    return dependents;
  };

  /**
   * Mendapatkan semua permission yang bergantung secara transitif pada permission tertentu.
   * Traversal dibatasi maksimal 3 level kedalaman.
   * 
   * @param {string} permissionName - Permission prasyarat yang dicari dependents-nya
   * @param {number} maxDepth - Kedalaman maksimal resolusi (default: 3)
   * @returns {string[]} - Array permission names yang bergantung secara transitif
   */
  const getTransitiveDependents = (permissionName, maxDepth = 3) => {
    const result = new Set();
    
    const traverse = (currentPerm, depth) => {
      if (depth > maxDepth) return;
      
      for (const [perm, deps] of Object.entries(permissionDependencies)) {
        if (deps.includes(currentPerm) && selectedPermissions.value.includes(perm) && !result.has(perm)) {
          result.add(perm);
          traverse(perm, depth + 1);
        }
      }
    };
    
    traverse(permissionName, 1);
    return [...result];
  };

  /**
   * Force deselect permission beserta semua dependents secara rekursif (cascading).
   * Dipanggil setelah user mengkonfirmasi dialog.
   * 
   * @param {string} permissionName - Permission prasyarat yang akan di-deselect
   * @returns {{ success: boolean, removed: string[] }}
   */
  const forceDeselect = (permissionName) => {
    const transitiveDependents = getTransitiveDependents(permissionName);
    const toRemove = [permissionName, ...transitiveDependents];
    const removed = [];
    
    toRemove.forEach(perm => {
      const idx = selectedPermissions.value.indexOf(perm);
      if (idx !== -1) {
        selectedPermissions.value.splice(idx, 1);
        removed.push(perm);
      }
    });
    
    rebuildDependencyLocks();
    return { success: true, removed };
  };

  /**
   * Rebuild dependency locks berdasarkan selected permissions
   */
  const rebuildDependencyLocks = () => {
    const newLocks = {};
    
    selectedPermissions.value.forEach(perm => {
      const deps = getAllDependencies(perm);
      deps.forEach(dep => {
        if (!newLocks[dep]) {
          newLocks[dep] = [];
        }
        if (!newLocks[dep].includes(perm)) {
          newLocks[dep].push(perm);
        }
      });
    });
    
    dependencyLocks.value = newLocks;
  };

  /**
   * Toggle permission tunggal
   * Menambah/menghapus permission dengan mempertimbangkan dependency
   * 
   * Saat deselection gagal karena ada dependents aktif, mengembalikan:
   * { success: false, dependents: [...], message: "..." }
   * Agar UI bisa menampilkan dialog konfirmasi cascading deselection.
   */
  const togglePermission = (permissionName) => {
    const index = selectedPermissions.value.indexOf(permissionName);
    
    if (index === -1) {
      // SELECTING - tambahkan permission dan semua dependency-nya
      selectedPermissions.value.push(permissionName);
      
      const deps = getAllDependencies(permissionName);
      deps.forEach(dep => {
        if (!selectedPermissions.value.includes(dep)) {
          selectedPermissions.value.push(dep);
        }
      });
      
      rebuildDependencyLocks();
      return { success: true };
    } else {
      // DESELECTING - cek apakah permission ini dibutuhkan yang lain
      // Gunakan getTransitiveDependents untuk mendapatkan semua dependent secara rekursif
      const dependents = getTransitiveDependents(permissionName);
      
      if (dependents.length > 0) {
        // Tidak bisa deselect, kembalikan info lengkap agar UI bisa tampilkan dialog
        const info = dependents.map(d => {
          const permInfo = getPermissionInfo(d);
          return permInfo ? permInfo.label : d;
        });
        return { 
          success: false, 
          message: `Permission ini dibutuhkan oleh: ${info.join(', ')}`,
          dependents
        };
      }
      
      // Hapus permission
      selectedPermissions.value.splice(index, 1);
      rebuildDependencyLocks();
      return { success: true };
    }
  };

  /**
   * Mendapatkan semua permission names yang termasuk dalam sebuah Feature_Group
   */
  const getFeaturePermissions = (featureKey) => {
    const feature = featureGroups[featureKey];
    if (!feature) return [];

    const featurePermissions = [];
    
    if (feature.permissions) {
      featurePermissions.push(...Object.keys(feature.permissions));
    }
    
    if (feature.subFeatures) {
      Object.values(feature.subFeatures).forEach(sub => {
        if (sub.permissions) {
          featurePermissions.push(...Object.keys(sub.permissions));
        }
      });
    }

    return featurePermissions;
  };

  /**
   * Cek apakah sebuah permission di-lock oleh permission di luar scope tertentu
   * @param {string} perm - permission yang dicek
   * @param {string[]} scopePermissions - kumpulan permission dalam scope saat ini (feature/sub-feature)
   * @returns {boolean} - true jika permission di-lock oleh permission di luar scope
   */
  const isLockedByExternalPermission = (perm, scopePermissions) => {
    const lockers = dependencyLocks.value[perm] || [];
    // Cek apakah ada locker yang berada di LUAR scope
    return lockers.some(locker => 
      !scopePermissions.includes(locker) && selectedPermissions.value.includes(locker)
    );
  };

  /**
   * Toggle semua permission dalam satu fitur
   */
  const toggleFeature = (featureKey) => {
    const feature = featureGroups[featureKey];
    if (!feature) return;

    // Kumpulkan semua permission dalam fitur ini
    const featurePermissions = getFeaturePermissions(featureKey);

    // Cek apakah semua sudah dipilih
    const allSelected = featurePermissions.every(p => selectedPermissions.value.includes(p));

    if (allSelected) {
      // Deselect semua yang TIDAK di-lock oleh permission di luar Feature_Group ini
      featurePermissions.forEach(perm => {
        if (!isLockedByExternalPermission(perm, featurePermissions)) {
          const idx = selectedPermissions.value.indexOf(perm);
          if (idx !== -1) {
            selectedPermissions.value.splice(idx, 1);
          }
        }
      });
    } else {
      // Select semua permission dalam group
      featurePermissions.forEach(perm => {
        if (!selectedPermissions.value.includes(perm)) {
          selectedPermissions.value.push(perm);
        }
      });

      // Resolve cross-group dependencies untuk setiap permission yang ditambahkan
      featurePermissions.forEach(perm => {
        const deps = getAllDependencies(perm);
        deps.forEach(dep => {
          if (!selectedPermissions.value.includes(dep)) {
            selectedPermissions.value.push(dep);
          }
        });
      });

      // Resolve dependsOn config dari sub-features dalam group ini
      if (feature.subFeatures) {
        Object.values(feature.subFeatures).forEach(sub => {
          if (sub.dependsOn && Array.isArray(sub.dependsOn)) {
            sub.dependsOn.forEach(dep => {
              if (!selectedPermissions.value.includes(dep)) {
                selectedPermissions.value.push(dep);
              }
              // Also resolve transitive dependencies of dependsOn items
              const transitiveDeps = getAllDependencies(dep);
              transitiveDeps.forEach(td => {
                if (!selectedPermissions.value.includes(td)) {
                  selectedPermissions.value.push(td);
                }
              });
            });
          }
        });
      }
    }

    rebuildDependencyLocks();
  };

  /**
   * Toggle semua permission dalam sub-fitur
   */
  const toggleSubFeature = (featureKey, subFeatureKey) => {
    const feature = featureGroups[featureKey];
    if (!feature || !feature.subFeatures || !feature.subFeatures[subFeatureKey]) return;

    const subFeature = feature.subFeatures[subFeatureKey];
    const subPermissions = Object.keys(subFeature.permissions || {});

    const allSelected = subPermissions.every(p => selectedPermissions.value.includes(p));

    if (allSelected) {
      // Deselect semua dari sub-feature ini yang TIDAK di-lock oleh permission di luar sub-feature
      subPermissions.forEach(perm => {
        if (!isLockedByExternalPermission(perm, subPermissions)) {
          const idx = selectedPermissions.value.indexOf(perm);
          if (idx !== -1) {
            selectedPermissions.value.splice(idx, 1);
          }
        }
      });
    } else {
      // Select semua permission dalam sub-feature
      subPermissions.forEach(perm => {
        if (!selectedPermissions.value.includes(perm)) {
          selectedPermissions.value.push(perm);
        }
      });

      // Resolve dependencies untuk setiap permission yang ditambahkan (termasuk cross-group)
      subPermissions.forEach(perm => {
        const deps = getAllDependencies(perm);
        deps.forEach(dep => {
          if (!selectedPermissions.value.includes(dep)) {
            selectedPermissions.value.push(dep);
          }
        });
      });

      // Resolve dependsOn config dari sub-feature (cross-group dependency)
      if (subFeature.dependsOn && Array.isArray(subFeature.dependsOn)) {
        subFeature.dependsOn.forEach(dep => {
          if (!selectedPermissions.value.includes(dep)) {
            selectedPermissions.value.push(dep);
          }
          // Also resolve transitive dependencies of dependsOn items
          const transitiveDeps = getAllDependencies(dep);
          transitiveDeps.forEach(td => {
            if (!selectedPermissions.value.includes(td)) {
              selectedPermissions.value.push(td);
            }
          });
        });
      }
    }

    rebuildDependencyLocks();
  };

  /**
   * Apply preset role template
   */
  const applyPreset = (presetKey) => {
    const preset = rolePresets[presetKey];
    if (!preset) return false;

    selectedPermissions.value = [...preset.permissions];
    rebuildDependencyLocks();
    return true;
  };

  /**
   * Select semua permission
   */
  const selectAll = () => {
    const allPermissions = [];
    Object.values(featureGroups).forEach(feature => {
      if (feature.permissions) {
        allPermissions.push(...Object.keys(feature.permissions));
      }
      if (feature.subFeatures) {
        Object.values(feature.subFeatures).forEach(sub => {
          if (sub.permissions) {
            allPermissions.push(...Object.keys(sub.permissions));
          }
        });
      }
    });
    
    selectedPermissions.value = [...new Set(allPermissions)];
    rebuildDependencyLocks();
  };

  /**
   * Reset semua permission
   */
  const resetAll = () => {
    selectedPermissions.value = [];
    dependencyLocks.value = {};
  };

  /**
   * Set permissions dari data yang sudah ada (untuk mode edit)
   */
  const setPermissions = (permissions) => {
    selectedPermissions.value = [...permissions];
    rebuildDependencyLocks();
  };

  // Computed properties
  const isSelected = (permissionName) => {
    return selectedPermissions.value.includes(permissionName);
  };

  const isLocked = (permissionName) => {
    return !!dependencyLocks.value[permissionName]?.length;
  };

  const getLockedBy = (permissionName) => {
    return dependencyLocks.value[permissionName] || [];
  };

  const totalSelected = computed(() => selectedPermissions.value.length);

  const featureCounts = computed(() => countSelectedByFeature(selectedPermissions.value));

  const getFeatureStatus = (featureKey) => {
    const counts = featureCounts.value[featureKey];
    if (!counts) return 'none';
    if (counts.selected === 0) return 'none';
    if (counts.selected === counts.total) return 'all';
    return 'partial';
  };

  const getSubFeatureStatus = (featureKey, subFeatureKey) => {
    const feature = featureGroups[featureKey];
    if (!feature?.subFeatures?.[subFeatureKey]) return 'none';
    
    const subPerms = Object.keys(feature.subFeatures[subFeatureKey].permissions || {});
    const selectedCount = subPerms.filter(p => selectedPermissions.value.includes(p)).length;
    
    if (selectedCount === 0) return 'none';
    if (selectedCount === subPerms.length) return 'all';
    return 'partial';
  };

  const getSubFeatureCounts = (featureKey, subFeatureKey) => {
    const feature = featureGroups[featureKey];
    if (!feature?.subFeatures?.[subFeatureKey]) return { total: 0, selected: 0 };
    
    const subPerms = Object.keys(feature.subFeatures[subFeatureKey].permissions || {});
    return {
      total: subPerms.length,
      selected: subPerms.filter(p => selectedPermissions.value.includes(p)).length
    };
  };

  // Initialize dependency locks if there are initial permissions
  if (initialPermissions.length > 0) {
    rebuildDependencyLocks();
  }

  return {
    // State
    selectedPermissions,
    dependencyLocks,
    
    // Methods
    togglePermission,
    toggleFeature,
    toggleSubFeature,
    applyPreset,
    selectAll,
    resetAll,
    setPermissions,
    getAllDependencies,
    getDependents,
    getTransitiveDependents,
    forceDeselect,
    getFeaturePermissions,
    isLockedByExternalPermission,
    
    // Checkers
    isSelected,
    isLocked,
    getLockedBy,
    getFeatureStatus,
    getSubFeatureStatus,
    getSubFeatureCounts,
    
    // Computed
    totalSelected,
    featureCounts,
    
    // Config references
    featureGroups,
    rolePresets,
    colorMap
  };
}
