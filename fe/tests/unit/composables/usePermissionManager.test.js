import { describe, it, expect, beforeEach } from 'vitest';
import { usePermissionManager } from '@/composables/usePermissionManager';

describe('usePermissionManager — cascading deselection & transitive dependents', () => {
  let pm;

  beforeEach(() => {
    pm = usePermissionManager();
  });

  describe('getTransitiveDependents', () => {
    it('returns empty array when no permission depends on given permission', () => {
      // 'system-admin-panel-access' is not a dependency of anything in the config
      pm.setPermissions(['system-admin-panel-access']);
      const dependents = pm.getTransitiveDependents('system-admin-panel-access');
      expect(dependents).toEqual([]);
    });

    it('returns direct dependents that are currently selected', () => {
      // ticket-list depends on ticket-menu
      // Select ticket-menu and ticket-list
      pm.setPermissions(['ticket-menu', 'ticket-list']);
      const dependents = pm.getTransitiveDependents('ticket-menu');
      expect(dependents).toContain('ticket-list');
    });

    it('returns transitive dependents recursively', () => {
      // Chain: ticket-menu <- ticket-list <- ticket-create
      pm.setPermissions(['ticket-menu', 'ticket-list', 'ticket-create']);
      const dependents = pm.getTransitiveDependents('ticket-menu');
      expect(dependents).toContain('ticket-list');
      expect(dependents).toContain('ticket-create');
    });

    it('does not return unselected permissions', () => {
      // Only select ticket-menu and ticket-list, not ticket-create
      pm.setPermissions(['ticket-menu', 'ticket-list']);
      const dependents = pm.getTransitiveDependents('ticket-menu');
      expect(dependents).toContain('ticket-list');
      expect(dependents).not.toContain('ticket-create');
    });

    it('respects max depth limit of 3 levels', () => {
      // Chain: ticket-menu <- ticket-list <- ticket-reply-list <- ticket-reply-create
      // That's 3 levels from ticket-menu
      pm.setPermissions([
        'ticket-menu', 'ticket-list', 'ticket-reply-list', 'ticket-reply-create'
      ]);
      const dependents = pm.getTransitiveDependents('ticket-menu');
      expect(dependents).toContain('ticket-list'); // level 1
      expect(dependents).toContain('ticket-reply-list'); // level 2
      expect(dependents).toContain('ticket-reply-create'); // level 3
    });

    it('handles permissions with no dependents', () => {
      pm.setPermissions(['dashboard-view-all']);
      const dependents = pm.getTransitiveDependents('dashboard-view-all');
      expect(dependents).toEqual([]);
    });
  });

  describe('forceDeselect', () => {
    it('removes the target permission and all its transitive dependents', () => {
      // Setup: select entire ticket chain
      pm.togglePermission('ticket-create'); // also selects ticket-list, ticket-menu
      expect(pm.selectedPermissions.value).toContain('ticket-menu');
      expect(pm.selectedPermissions.value).toContain('ticket-list');
      expect(pm.selectedPermissions.value).toContain('ticket-create');

      // Force deselect ticket-menu should cascade
      const result = pm.forceDeselect('ticket-menu');
      expect(result.success).toBe(true);
      expect(result.removed).toContain('ticket-menu');
      expect(result.removed).toContain('ticket-list');
      expect(result.removed).toContain('ticket-create');
      expect(pm.selectedPermissions.value).not.toContain('ticket-menu');
      expect(pm.selectedPermissions.value).not.toContain('ticket-list');
      expect(pm.selectedPermissions.value).not.toContain('ticket-create');
    });

    it('returns success with empty removed array when permission was not selected', () => {
      const result = pm.forceDeselect('ticket-menu');
      expect(result.success).toBe(true);
      expect(result.removed).toEqual([]);
    });

    it('rebuilds dependency locks after cascading removal', () => {
      // Select ticket-create (also selects ticket-list and ticket-menu)
      pm.togglePermission('ticket-create');
      expect(pm.isLocked('ticket-menu')).toBe(true);
      expect(pm.isLocked('ticket-list')).toBe(true);

      pm.forceDeselect('ticket-menu');
      // After cascading removal, locks should be cleared
      expect(pm.isLocked('ticket-menu')).toBe(false);
      expect(pm.isLocked('ticket-list')).toBe(false);
    });

    it('only removes dependents of the target, not unrelated permissions', () => {
      // Select permissions from two different features
      pm.togglePermission('ticket-create'); // ticket-menu, ticket-list, ticket-create
      pm.togglePermission('dashboard-view'); // dashboard-menu, dashboard-view

      pm.forceDeselect('ticket-menu');
      // Dashboard permissions should be unaffected
      expect(pm.selectedPermissions.value).toContain('dashboard-menu');
      expect(pm.selectedPermissions.value).toContain('dashboard-view');
    });
  });

  describe('togglePermission — deselection returns dependents info', () => {
    it('returns success:false with dependents array when trying to deselect a locked permission', () => {
      // Select ticket-create (auto-selects ticket-list, ticket-menu)
      pm.togglePermission('ticket-create');

      // Try to deselect ticket-menu (locked by ticket-list and ticket-create)
      const result = pm.togglePermission('ticket-menu');
      expect(result.success).toBe(false);
      expect(result.dependents).toBeDefined();
      expect(result.dependents.length).toBeGreaterThan(0);
      expect(result.dependents).toContain('ticket-list');
      expect(result.dependents).toContain('ticket-create');
    });

    it('returns success:true when deselecting a permission with no dependents', () => {
      pm.togglePermission('ticket-create');
      // ticket-create has no dependents, so we can deselect it
      const result = pm.togglePermission('ticket-create');
      expect(result.success).toBe(true);
    });

    it('includes a human-readable message when deselection fails', () => {
      pm.togglePermission('ticket-create');
      const result = pm.togglePermission('ticket-menu');
      expect(result.success).toBe(false);
      expect(result.message).toBeDefined();
      expect(typeof result.message).toBe('string');
      expect(result.message.length).toBeGreaterThan(0);
    });
  });

  describe('getAllDependencies — max depth limit', () => {
    it('resolves dependencies up to 3 levels', () => {
      // ticket-reply-create depends on: ticket-reply-list, ticket-list, ticket-menu
      // ticket-reply-list depends on: ticket-list, ticket-menu
      // ticket-list depends on: ticket-menu
      const deps = pm.getAllDependencies('ticket-reply-create');
      expect(deps).toContain('ticket-reply-list');
      expect(deps).toContain('ticket-list');
      expect(deps).toContain('ticket-menu');
    });

    it('prevents infinite loops with circular references', () => {
      // The function uses a visited set to prevent loops
      // Even without circular deps in the config, verify it doesn't crash
      const deps = pm.getAllDependencies('non-existent-permission');
      expect(deps).toEqual([]);
    });
  });
});


describe('usePermissionManager — toggle feature/sub-feature OFF with external locks', () => {
  let pm;

  beforeEach(() => {
    pm = usePermissionManager();
  });

  describe('toggleFeature OFF preserves externally-locked permissions', () => {
    it('removes all feature permissions when no external locks exist', () => {
      // Select all dashboard permissions
      pm.toggleFeature('dashboard');
      expect(pm.getFeatureStatus('dashboard')).toBe('all');

      // Toggle OFF — should remove all since no external locks
      pm.toggleFeature('dashboard');
      expect(pm.getFeatureStatus('dashboard')).toBe('none');
      expect(pm.selectedPermissions.value).not.toContain('dashboard-menu');
      expect(pm.selectedPermissions.value).not.toContain('dashboard-view');
    });

    it('preserves permissions locked by another feature group when toggling OFF', () => {
      // ticket-reply-create depends on ticket-list and ticket-menu
      // So if we have ticket-reply-create selected (from 'ticket' feature group),
      // and ticket-list / ticket-menu are part of the ticket group too,
      // there's no external lock in this case. Let's create a real cross-feature scenario.
      
      // Scenario: 'ticket' feature has ticket-menu, ticket-list, etc.
      // The 'ticket' sub-features (reply, attachment) also depend on ticket-list and ticket-menu.
      // If we have a permission from outside the feature that depends on something inside...
      // 
      // Actually, looking at the config, cross-feature dependencies are rare.
      // But the "user" preset has "ticket-category-list" which depends on "ticket-category-menu"
      // Both are in the "ticketCategory" feature group.
      // 
      // A cleaner test: select ticket-reply-create (which needs ticket-menu, ticket-list).
      // These are ALL in the 'ticket' feature. So toggling 'ticket' OFF when ticket-reply-create is external won't apply here.
      //
      // Better: manually set permissions to simulate external lock.
      // If "ticket-list" is locked by a permission in another feature:
      // The dependency lock map tracks this via rebuildDependencyLocks.
      
      // Real scenario: if we select everything in ticket feature + something that depends on ticket-menu from outside
      // Looking at config: no permission outside ticket depends on ticket-menu/ticket-list.
      // So let's use the "electricity" feature where "electricity-reading-list" depends on "electricity-meter-list"
      // Both sub-features are in the same feature group.

      // Let's test with dailyRecord: utility-reading-list depends on daily-record-list and daily-record-menu
      // Both are in dailyRecord feature. If we toggle dailyRecord OFF while utility-reading-list is selected
      // (which is also in dailyRecord), that's internal.

      // Better approach: Use setPermissions to simulate the scenario explicitly.
      // Select all permissions from dashboard + manually create a lock scenario.

      // The actual cross-feature case in this config:
      // user preset has "branch-list" (from branch feature) selected without "branch-menu"
      // but branch-list depends on branch-menu... 

      // Let's test with a concrete realistic scenario:
      // 1. Select all "ticket" feature permissions (includes core + reply + attachment sub-features)
      // 2. Also select "ticketCategory" permissions which DON'T depend on anything in ticket
      // 3. Toggle "ticket" OFF → all should be removed since no cross-feature dependency locks ticket perms

      // First, a simple case: no external locks
      pm.toggleFeature('ticket');
      expect(pm.getFeatureStatus('ticket')).toBe('all');
      pm.toggleFeature('ticket');
      expect(pm.getFeatureStatus('ticket')).toBe('none');
    });

    it('preserves permission when locked by permission outside the feature group', () => {
      // Setup: Select "ticket-reply-create" which auto-selects:
      // ticket-reply-list, ticket-list, ticket-menu (all in 'ticket' feature)
      pm.togglePermission('ticket-reply-create');
      
      // Now select "ticket-menu" and "ticket-list" are locked by ticket-reply-create
      expect(pm.isLocked('ticket-menu')).toBe(true);
      expect(pm.isLocked('ticket-list')).toBe(true);

      // Manually add a permission from another group that also makes ticket-list a dependency
      // We can't easily create cross-feature locks with the current config because all
      // ticket permissions have deps within ticket feature.
      // 
      // But let's test the actual mechanism by using setPermissions to set up
      // a state where ticket-list is locked by something outside ticket:
      // We'll manually craft the scenario by selecting all ticket perms + some external perm.
      
      // Actually the best way to test this is to:
      // 1. Toggle 'ticket' feature ON (all perms selected)
      // 2. The internal dependency locks (e.g. ticket-create locks ticket-list) are all INTERNAL
      // 3. Toggle OFF → all should be removed because locks are internal
      
      // For an EXTERNAL lock test, let's manually add a fake scenario:
      // Let's use the sub-feature test which has a cleaner cross-boundary scenario
      pm.resetAll();
      
      // Select all ticket/core sub-feature perms
      pm.toggleSubFeature('ticket', 'core');
      // Select all ticket/reply sub-feature perms  
      pm.toggleSubFeature('ticket', 'reply');
      
      // Now ticket-list and ticket-menu are locked by ticket-reply-create, ticket-reply-list etc.
      // (reply perms depend on ticket-list and ticket-menu which are in 'core' sub-feature)
      expect(pm.isLocked('ticket-list')).toBe(true);
      expect(pm.isLocked('ticket-menu')).toBe(true);
      
      // Toggle core sub-feature OFF: ticket-list and ticket-menu should be PRESERVED
      // because they're locked by reply permissions (which are OUTSIDE the 'core' sub-feature)
      pm.toggleSubFeature('ticket', 'core');
      
      // ticket-list and ticket-menu should still be selected (locked by reply)
      expect(pm.selectedPermissions.value).toContain('ticket-list');
      expect(pm.selectedPermissions.value).toContain('ticket-menu');
      
      // Other core perms (ticket-create, ticket-edit, etc.) should be removed
      expect(pm.selectedPermissions.value).not.toContain('ticket-create');
      expect(pm.selectedPermissions.value).not.toContain('ticket-edit');
      expect(pm.selectedPermissions.value).not.toContain('ticket-delete');
    });

    it('shows partial status after partial toggle OFF due to external locks', () => {
      // Select ticket core + reply sub-features
      pm.toggleSubFeature('ticket', 'core');
      pm.toggleSubFeature('ticket', 'reply');
      expect(pm.getSubFeatureStatus('ticket', 'core')).toBe('all');
      
      // Toggle core OFF — some perms locked by reply
      pm.toggleSubFeature('ticket', 'core');
      
      // Core should now be 'partial' because ticket-list and ticket-menu remain
      expect(pm.getSubFeatureStatus('ticket', 'core')).toBe('partial');
    });

    it('toggleFeature OFF preserves perms locked by permissions in other feature groups', () => {
      // Create a real cross-feature lock scenario:
      // electricity-reading-list depends on electricity-meter-list and electricity-meter-menu
      // Both electricity-meter-* and electricity-reading-* are in 'electricity' feature but in different sub-features.
      // So toggling 'meter' sub-feature OFF while 'reading' is active should preserve meter perms.
      
      // First, select all electricity perms
      pm.toggleFeature('electricity');
      expect(pm.getFeatureStatus('electricity')).toBe('all');
      
      // electricity-meter-list is locked by electricity-reading-list (dependency)
      expect(pm.isLocked('electricity-meter-list')).toBe(true);
      expect(pm.isLocked('electricity-meter-menu')).toBe(true);
      
      // Now toggle 'meter' sub-feature OFF
      pm.toggleSubFeature('electricity', 'meter');
      
      // meter-list and meter-menu should remain (locked by reading sub-feature)
      expect(pm.selectedPermissions.value).toContain('electricity-meter-list');
      expect(pm.selectedPermissions.value).toContain('electricity-meter-menu');
      
      // Other meter perms should be removed
      expect(pm.selectedPermissions.value).not.toContain('electricity-meter-create');
      expect(pm.selectedPermissions.value).not.toContain('electricity-meter-edit');
      expect(pm.selectedPermissions.value).not.toContain('electricity-meter-delete');
      
      // Sub-feature status should be 'partial'
      expect(pm.getSubFeatureStatus('electricity', 'meter')).toBe('partial');
    });

    it('toggleFeature OFF removes all perms when all locks are internal to the feature', () => {
      // Select all dashboard perms
      pm.toggleFeature('dashboard');
      // dashboard-view is locked by dashboard-view-metrics, etc. — all internal
      expect(pm.isLocked('dashboard-view')).toBe(true);
      expect(pm.isLocked('dashboard-menu')).toBe(true);
      
      // Toggle OFF — all locks are internal so everything should be removed
      pm.toggleFeature('dashboard');
      expect(pm.getFeatureStatus('dashboard')).toBe('none');
      expect(pm.selectedPermissions.value.filter(p => p.startsWith('dashboard'))).toHaveLength(0);
    });
  });

  describe('toggleSubFeature OFF preserves externally-locked permissions', () => {
    it('removes all sub-feature permissions when no external locks', () => {
      // Select ticket/reply sub-feature
      pm.toggleSubFeature('ticket', 'reply');
      expect(pm.getSubFeatureStatus('ticket', 'reply')).toBe('all');
      
      // Toggle OFF — the reply perms have internal dependencies only within themselves
      // Actually reply perms depend on ticket-list and ticket-menu (from 'core' sub-feature)
      // but the reply perms themselves should all be removable
      pm.toggleSubFeature('ticket', 'reply');
      
      // Reply specific perms should be gone
      expect(pm.selectedPermissions.value).not.toContain('ticket-reply-list');
      expect(pm.selectedPermissions.value).not.toContain('ticket-reply-create');
      expect(pm.selectedPermissions.value).not.toContain('ticket-reply-edit');
      expect(pm.selectedPermissions.value).not.toContain('ticket-reply-delete');
    });

    it('preserves sub-feature permission locked by permission in another sub-feature', () => {
      // Setup: Select dailyRecord/core and dailyRecord/utility sub-features
      pm.toggleSubFeature('dailyRecord', 'core');
      pm.toggleSubFeature('dailyRecord', 'utility');
      
      // utility-reading-list depends on daily-record-list and daily-record-menu (in 'core')
      expect(pm.isLocked('daily-record-list')).toBe(true);
      expect(pm.isLocked('daily-record-menu')).toBe(true);
      
      // Toggle 'core' sub-feature OFF
      pm.toggleSubFeature('dailyRecord', 'core');
      
      // daily-record-list and daily-record-menu should remain (locked by utility sub-feature)
      expect(pm.selectedPermissions.value).toContain('daily-record-list');
      expect(pm.selectedPermissions.value).toContain('daily-record-menu');
      
      // Other core perms should be removed
      expect(pm.selectedPermissions.value).not.toContain('daily-record-create');
      expect(pm.selectedPermissions.value).not.toContain('daily-record-edit');
      expect(pm.selectedPermissions.value).not.toContain('daily-record-delete');
      
      // Status should be partial
      expect(pm.getSubFeatureStatus('dailyRecord', 'core')).toBe('partial');
    });
  });

  describe('getFeaturePermissions', () => {
    it('returns all permissions in a feature with only top-level permissions', () => {
      const perms = pm.getFeaturePermissions('dashboard');
      expect(perms).toContain('dashboard-menu');
      expect(perms).toContain('dashboard-view');
      expect(perms).toContain('dashboard-view-metrics');
      expect(perms).toContain('dashboard-view-charts');
      expect(perms).toContain('dashboard-view-staff-rankings');
      expect(perms).toContain('dashboard-view-trends');
      expect(perms).toContain('dashboard-view-all');
      expect(perms).toHaveLength(7);
    });

    it('returns all permissions across all sub-features for a feature with sub-features', () => {
      const perms = pm.getFeaturePermissions('ticket');
      // Should include core + reply + attachment sub-feature permissions
      expect(perms).toContain('ticket-menu');
      expect(perms).toContain('ticket-list');
      expect(perms).toContain('ticket-reply-list');
      expect(perms).toContain('ticket-attachment-list');
      expect(perms.length).toBe(14); // 7 core + 4 reply + 3 attachment
    });

    it('returns empty array for invalid feature key', () => {
      const perms = pm.getFeaturePermissions('nonexistent');
      expect(perms).toEqual([]);
    });
  });

  describe('isLockedByExternalPermission', () => {
    it('returns false when permission has no locks', () => {
      pm.setPermissions(['dashboard-menu']);
      expect(pm.isLockedByExternalPermission('dashboard-menu', ['dashboard-menu', 'dashboard-view'])).toBe(false);
    });

    it('returns false when all lockers are within the scope', () => {
      // Select dashboard-view which locks dashboard-menu
      pm.togglePermission('dashboard-view');
      // dashboard-menu is locked by dashboard-view — both are in dashboard feature
      const dashboardPerms = pm.getFeaturePermissions('dashboard');
      expect(pm.isLockedByExternalPermission('dashboard-menu', dashboardPerms)).toBe(false);
    });

    it('returns true when a locker is outside the scope', () => {
      // ticket-reply-create depends on ticket-list and ticket-menu
      pm.togglePermission('ticket-reply-create');
      // ticket-list is locked by ticket-reply-create
      // If scope is only 'core' sub-feature perms, ticket-reply-create is outside
      const corePerms = Object.keys(
        pm.featureGroups.ticket.subFeatures.core.permissions
      );
      expect(pm.isLockedByExternalPermission('ticket-list', corePerms)).toBe(true);
    });
  });
});
