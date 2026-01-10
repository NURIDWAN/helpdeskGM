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
   * Recursively mendapatkan semua dependency termasuk nested
   */
  const getAllDependencies = (permissionName, visited = new Set()) => {
    if (visited.has(permissionName)) return [];
    visited.add(permissionName);
    
    const directDeps = permissionDependencies[permissionName] || [];
    const allDeps = [...directDeps];
    
    // Recursively get dependencies of dependencies
    directDeps.forEach(dep => {
      const nestedDeps = getAllDependencies(dep, visited);
      nestedDeps.forEach(nd => {
        if (!allDeps.includes(nd)) {
          allDeps.push(nd);
        }
      });
    });
    
    return allDeps;
  };

  /**
   * Mendapatkan permission mana yang bergantung pada permission tertentu
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
      const dependents = getDependents(permissionName);
      
      if (dependents.length > 0) {
        // Tidak bisa deselect, kembalikan info
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
   * Toggle semua permission dalam satu fitur
   */
  const toggleFeature = (featureKey) => {
    const feature = featureGroups[featureKey];
    if (!feature) return;

    // Kumpulkan semua permission dalam fitur ini
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

    // Cek apakah semua sudah dipilih
    const allSelected = featurePermissions.every(p => selectedPermissions.value.includes(p));

    if (allSelected) {
      // Deselect semua (dari akhir agar dependency terpenuhi)
      // Urutkan berdasarkan jumlah dependents (yang paling sedikit dihapus dulu)
      const sortedPerms = [...featurePermissions].sort((a, b) => {
        return getDependents(b).length - getDependents(a).length;
      });
      
      sortedPerms.forEach(perm => {
        const dependents = getDependents(perm);
        // Hanya hapus jika tidak ada dependent dari luar fitur ini
        const externalDependents = dependents.filter(d => !featurePermissions.includes(d));
        if (externalDependents.length === 0) {
          const idx = selectedPermissions.value.indexOf(perm);
          if (idx !== -1) {
            selectedPermissions.value.splice(idx, 1);
          }
        }
      });
    } else {
      // Select semua
      featurePermissions.forEach(perm => {
        if (!selectedPermissions.value.includes(perm)) {
          selectedPermissions.value.push(perm);
        }
      });
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
      // Deselect semua dari sub-feature ini
      const sortedPerms = [...subPermissions].sort((a, b) => {
        return getDependents(b).length - getDependents(a).length;
      });
      
      sortedPerms.forEach(perm => {
        const dependents = getDependents(perm);
        const externalDependents = dependents.filter(d => !subPermissions.includes(d));
        if (externalDependents.length === 0) {
          const idx = selectedPermissions.value.indexOf(perm);
          if (idx !== -1) {
            selectedPermissions.value.splice(idx, 1);
          }
        }
      });
    } else {
      // Select semua (dengan dependency)
      subPermissions.forEach(perm => {
        if (!selectedPermissions.value.includes(perm)) {
          // Tambah permission dan dependency-nya
          selectedPermissions.value.push(perm);
          const deps = getAllDependencies(perm);
          deps.forEach(dep => {
            if (!selectedPermissions.value.includes(dep)) {
              selectedPermissions.value.push(dep);
            }
          });
        }
      });
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
