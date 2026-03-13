#!/usr/bin/env bash
# phase6_integrity.sh — Post-test DB integrity checks
# Validates no corrupt / orphaned / inconsistent records after all load tests
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/helpers.sh"

header "PHASE 6 — DATA INTEGRITY (SQL checks)"

DB_HOST="127.0.0.1"; DB_PORT="32771"; DB_USER="db"; DB_PASS="db"; DB_NAME="db"
Q() { mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" 2>/dev/null -e "$1" | tail -1; }

check() {
  local label="$1" query="$2" expected="$3"
  local result
  result=$(Q "$query")
  if [ "$result" = "$expected" ]; then
    success "  [PASS] $label → $result"
  else
    fail    "  [FAIL] $label → got '$result', expected '$expected'"
  fi
  echo "$label,$result,$expected" >> "$RESULTS_DIR/phase6_integrity.csv"
}

check_zero() {
  local label="$1" query="$2"
  check "$label" "$query" "0"
}

# ── Ticket integrity ──────────────────────────────────────────────────────────
header "Tickets"
check_zero "No duplicate ticket codes"           "SELECT COUNT(*)-COUNT(DISTINCT code) FROM tickets;"
check_zero "No tickets with NULL user_id"        "SELECT COUNT(*) FROM tickets WHERE user_id IS NULL;"
check_zero "No tickets with invalid status"      "SELECT COUNT(*) FROM tickets WHERE status NOT IN ('open','in_progress','closed');"
check_zero "No tickets with invalid priority"    "SELECT COUNT(*) FROM tickets WHERE priority NOT IN ('low','medium','high');"
check_zero "Orphan tickets (user not found)"     "SELECT COUNT(*) FROM tickets t LEFT JOIN users u ON u.id=t.user_id WHERE u.id IS NULL;"
check_zero "Orphan tickets (category not found)" "SELECT COUNT(*) FROM tickets t LEFT JOIN ticket_categories c ON c.id=t.category_id WHERE t.category_id IS NOT NULL AND c.id IS NULL;"

# ── Work Report integrity ─────────────────────────────────────────────────────
header "Work Reports"
check_zero "No work-reports with invalid status"    "SELECT COUNT(*) FROM work_reports WHERE status NOT IN ('progress','done');"
check_zero "Orphan work-reports (user not found)"   "SELECT COUNT(*) FROM work_reports wr LEFT JOIN users u ON u.id=wr.user_id WHERE u.id IS NULL;"
check_zero "Orphan work-reports (branch not found)" "SELECT COUNT(*) FROM work_reports wr LEFT JOIN branches b ON b.id=wr.branch_id WHERE wr.branch_id IS NOT NULL AND b.id IS NULL;"

# ── Work Order integrity ──────────────────────────────────────────────────────
header "Work Orders"
check_zero "Orphan work-orders (ticket not found)"  "SELECT COUNT(*) FROM work_orders wo LEFT JOIN tickets t ON t.id=wo.ticket_id WHERE wo.ticket_id IS NOT NULL AND t.id IS NULL;"

# ── Sanctum token integrity ───────────────────────────────────────────────────
header "Auth tokens"
check_zero "No tokens for non-existent users" "SELECT COUNT(*) FROM personal_access_tokens pat LEFT JOIN users u ON u.id=pat.tokenable_id WHERE pat.tokenable_type='App\\\\Models\\\\User' AND u.id IS NULL;"

# ── Permission integrity ──────────────────────────────────────────────────────
header "Permissions"
check_zero "No model_has_roles for non-existent users" "SELECT COUNT(*) FROM model_has_roles mhr LEFT JOIN users u ON u.id=mhr.model_id WHERE mhr.model_type='App\\\\Models\\\\User' AND u.id IS NULL;"
check_zero "No orphan role_has_permissions"            "SELECT COUNT(*) FROM role_has_permissions rhp LEFT JOIN roles r ON r.id=rhp.role_id WHERE r.id IS NULL;"

# ── Summary ───────────────────────────────────────────────────────────────────
echo ""
FAILS=$(grep -c ",0\$" "$RESULTS_DIR/phase6_integrity.csv" 2>/dev/null || echo 0)  # miscount — re-check
TOTAL=$(wc -l < "$RESULTS_DIR/phase6_integrity.csv")
PASS=$(grep -v ",0," "$RESULTS_DIR/phase6_integrity.csv" 2>/dev/null | grep -c "^" || echo 0)

# Simpler: count lines where result == expected (col2 == col3)
PASS_COUNT=$(awk -F, 'NR>1 && $2==$3 {c++} END {print c+0}' "$RESULTS_DIR/phase6_integrity.csv")
FAIL_COUNT=$(awk -F, 'NR>1 && $2!=$3 {c++} END {print c+0}' "$RESULTS_DIR/phase6_integrity.csv")

if [ "$FAIL_COUNT" -eq 0 ]; then
  success "All $PASS_COUNT integrity checks PASSED — data is clean"
else
  fail    "$FAIL_COUNT integrity check(s) FAILED out of $((PASS_COUNT+FAIL_COUNT)) total"
fi

success "Phase 6 complete. Results saved to results/phase6_integrity.csv"
