#!/usr/bin/env bash
# phase3_write_integrity.sh — Concurrent writes + data integrity verification
# Tests that concurrent POSTs don't cause lost writes, duplicate codes, or corruption
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/helpers.sh"

header "PHASE 3 — WRITE INTEGRITY (concurrent POSTs)"

info "Authenticating users..."
SA_TOKEN=$(get_token "$SUPERADMIN_EMAIL" "$SUPERADMIN_PASS")
USER_TOKEN=$(get_token "$USER_EMAIL" "$USER_PASS")
STAFF_TOKEN=$(get_token "$STAFF_EMAIL" "$STAFF_PASS")
if [ -z "$SA_TOKEN" ]; then fail "Could not get superadmin token"; exit 1; fi

DB_HOST="127.0.0.1"
DB_PORT="32771"
DB_USER="db"
DB_PASS="db"
DB_NAME="db"
qdb() { mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" 2>/dev/null -e "$1" | tail -1; }

# ── Baseline counts ───────────────────────────────────────────────────────────
header "Baseline DB counts"
TICKETS_BEFORE=$(qdb "SELECT COUNT(*) FROM tickets;")
WORKREPORTS_BEFORE=$(qdb "SELECT COUNT(*) FROM work_reports;")
info "  Tickets before: $TICKETS_BEFORE"
info "  WorkReports before: $WORKREPORTS_BEFORE"

# ── Test 1: 20 concurrent ticket creates (user role) ─────────────────────────
header "Test 3.1: 20 concurrent ticket creates"
CONCURRENT=20
PIDS=()
for i in $(seq 1 $CONCURRENT); do
  (
    resp=$(post_json "$BASE_URL/tickets" "$USER_TOKEN" \
      "{\"description\":\"Stress test ticket #$i $(date +%s%N)\",\"priority\":\"low\",\"branch_id\":3,\"category_id\":1}")
    code=$(echo "$resp" | python3 -c "import sys,json; d=json.load(sys.stdin); print(d.get('success','false'))" 2>/dev/null)
    if [ "$code" = "True" ]; then
      echo "OK $i" >> "$RESULTS_DIR/phase3_ticket_creates.txt"
    else
      echo "FAIL $i: $resp" >> "$RESULTS_DIR/phase3_ticket_creates.txt"
    fi
  ) &
  PIDS+=($!)
done
# Wait for all
for pid in "${PIDS[@]}"; do wait "$pid"; done
success "All concurrent ticket create requests finished"

OK_COUNT=$(grep -c "^OK" "$RESULTS_DIR/phase3_ticket_creates.txt" 2>/dev/null || echo 0)
FAIL_COUNT=$(grep -c "^FAIL" "$RESULTS_DIR/phase3_ticket_creates.txt" 2>/dev/null || echo 0)
info "  OK: $OK_COUNT  FAIL: $FAIL_COUNT"

# ── DB integrity check: ticket count ─────────────────────────────────────────
TICKETS_AFTER=$(qdb "SELECT COUNT(*) FROM tickets;")
DIFF=$(( TICKETS_AFTER - TICKETS_BEFORE ))
info "  DB tickets created: $DIFF (expected ~$OK_COUNT)"
if [ "$DIFF" -eq "$OK_COUNT" ]; then
  success "  Ticket count matches — no lost writes"
else
  fail    "  MISMATCH: DB shows $DIFF new rows vs $OK_COUNT OK responses"
fi

# ── DB integrity check: duplicate codes ──────────────────────────────────────
DUP_CODES=$(qdb "SELECT COUNT(*)-COUNT(DISTINCT code) as dup FROM tickets;")
if [ "$DUP_CODES" -eq "0" ]; then
  success "  No duplicate ticket codes"
else
  fail    "  DUPLICATE CODES DETECTED: $DUP_CODES duplicates"
fi

# ── Test 2: 20 concurrent work report creates (staff role) ───────────────────
header "Test 3.2: 20 concurrent work-report creates"
PIDS=()
for i in $(seq 1 $CONCURRENT); do
  (
    resp=$(post_json "$BASE_URL/work-reports" "$STAFF_TOKEN" \
      "{\"description\":\"Stress test work report #$i $(date +%s%N)\",\"status\":\"progress\",\"branch_id\":3}")
    code=$(echo "$resp" | python3 -c "import sys,json; d=json.load(sys.stdin); print(d.get('success','false'))" 2>/dev/null)
    if [ "$code" = "True" ]; then
      echo "OK $i" >> "$RESULTS_DIR/phase3_workreport_creates.txt"
    else
      echo "FAIL $i: $resp" >> "$RESULTS_DIR/phase3_workreport_creates.txt"
    fi
  ) &
  PIDS+=($!)
done
for pid in "${PIDS[@]}"; do wait "$pid"; done
success "All concurrent work-report create requests finished"

WR_OK=$(grep -c "^OK" "$RESULTS_DIR/phase3_workreport_creates.txt" 2>/dev/null || echo 0)
WR_FAIL=$(grep -c "^FAIL" "$RESULTS_DIR/phase3_workreport_creates.txt" 2>/dev/null || echo 0)
info "  OK: $WR_OK  FAIL: $WR_FAIL"

WORKREPORTS_AFTER=$(qdb "SELECT COUNT(*) FROM work_reports;")
WR_DIFF=$(( WORKREPORTS_AFTER - WORKREPORTS_BEFORE ))
info "  DB work_reports created: $WR_DIFF (expected ~$WR_OK)"
if [ "$WR_DIFF" -eq "$WR_OK" ]; then
  success "  Work report count matches — no lost writes"
else
  fail    "  MISMATCH: DB shows $WR_DIFF new rows vs $WR_OK OK responses"
fi

echo ""
success "Phase 3 complete. Logs saved to results/phase3_*.txt"
