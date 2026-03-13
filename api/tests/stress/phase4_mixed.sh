#!/usr/bin/env bash
# phase4_mixed.sh — Mixed concurrent read+write load
# Simulates real-world: some users reading dashboards while others submit tickets/work-reports
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/helpers.sh"

header "PHASE 4 — MIXED LOAD (reads + writes simultaneously)"

info "Authenticating users..."
SA_TOKEN=$(get_token "$SUPERADMIN_EMAIL" "$SUPERADMIN_PASS")
USER_TOKEN=$(get_token "$USER_EMAIL" "$USER_PASS")
STAFF_TOKEN=$(get_token "$STAFF_EMAIL" "$STAFF_PASS")
if [ -z "$SA_TOKEN" ]; then fail "Could not get superadmin token"; exit 1; fi

DB_HOST="127.0.0.1"; DB_PORT="32771"; DB_USER="db"; DB_PASS="db"; DB_NAME="db"
qdb() { mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" 2>/dev/null -e "$1" | tail -1; }

TICKETS_BEFORE=$(qdb "SELECT COUNT(*) FROM tickets;")
WR_BEFORE=$(qdb "SELECT COUNT(*) FROM work_reports;")
info "  Baseline — tickets: $TICKETS_BEFORE  work_reports: $WR_BEFORE"

# ── Background: 50 concurrent dashboard reads (ab, non-blocking) ──────────────
header "Background: 50 concurrent dashboard reads (ab)"
ab -n 50 -c 10 -k \
  -H "Authorization: Bearer $SA_TOKEN" \
  -H "Accept: application/json" \
  "$BASE_URL/dashboard/all" > "$RESULTS_DIR/phase4_dashboard_reads.txt" 2>&1 &
AB_PID=$!

# ── Foreground: 10 concurrent ticket creates + 10 work-report creates ─────────
header "Foreground: 10 concurrent ticket creates"
WRITE_PIDS=()
for i in $(seq 1 10); do
  (
    resp=$(post_json "$BASE_URL/tickets" "$USER_TOKEN" \
      "{\"description\":\"Mixed load ticket #$i\",\"priority\":\"medium\",\"branch_id\":3,\"category_id\":1}")
    echo "$resp" | python3 -c "import sys,json; d=json.load(sys.stdin); print('OK' if d.get('success') else 'FAIL')" 2>/dev/null \
      >> "$RESULTS_DIR/phase4_ticket_creates.txt"
  ) &
  WRITE_PIDS+=($!)
done

header "Foreground: 10 concurrent work-report creates"
for i in $(seq 1 10); do
  (
    resp=$(post_json "$BASE_URL/work-reports" "$STAFF_TOKEN" \
      "{\"description\":\"Mixed load work-report #$i\",\"status\":\"progress\",\"branch_id\":3}")
    echo "$resp" | python3 -c "import sys,json; d=json.load(sys.stdin); print('OK' if d.get('success') else 'FAIL')" 2>/dev/null \
      >> "$RESULTS_DIR/phase4_wr_creates.txt"
  ) &
  WRITE_PIDS+=($!)
done

# ── Also fire concurrent GET /tickets while writes happen ─────────────────────
ab -n 30 -c 5 -k \
  -H "Authorization: Bearer $SA_TOKEN" \
  -H "Accept: application/json" \
  "$BASE_URL/tickets" > "$RESULTS_DIR/phase4_tickets_reads.txt" 2>&1 &
READ_PID=$!

# ── Wait for all writes to complete ───────────────────────────────────────────
for pid in "${WRITE_PIDS[@]}"; do wait "$pid"; done
success "Write requests completed"

# ── Wait for ab reads to finish ───────────────────────────────────────────────
wait "$AB_PID" || true
wait "$READ_PID" || true
success "Read requests completed"

# ── Integrity check ───────────────────────────────────────────────────────────
header "Integrity checks"
TICKETS_AFTER=$(qdb "SELECT COUNT(*) FROM tickets;")
WR_AFTER=$(qdb "SELECT COUNT(*) FROM work_reports;")
T_OK=$(grep -c "^OK" "$RESULTS_DIR/phase4_ticket_creates.txt" 2>/dev/null || echo 0)
WR_OK=$(grep -c "^OK" "$RESULTS_DIR/phase4_wr_creates.txt" 2>/dev/null || echo 0)

T_DIFF=$(( TICKETS_AFTER - TICKETS_BEFORE ))
WR_DIFF=$(( WR_AFTER - WR_BEFORE ))
info "  Ticket OK responses: $T_OK | DB new rows: $T_DIFF"
info "  WorkReport OK responses: $WR_OK | DB new rows: $WR_DIFF"

[ "$T_DIFF" -eq "$T_OK" ] && success "Tickets: no lost writes under mixed load" \
  || fail "Tickets MISMATCH: $T_DIFF DB rows vs $T_OK OK responses"

[ "$WR_DIFF" -eq "$WR_OK" ] && success "WorkReports: no lost writes under mixed load" \
  || fail "WorkReports MISMATCH: $WR_DIFF DB rows vs $WR_OK OK responses"

# ── ab summary ────────────────────────────────────────────────────────────────
header "Dashboard read performance under write pressure"
grep -E "Requests per second|50%|99%|Failed requests" "$RESULTS_DIR/phase4_dashboard_reads.txt" || true

echo ""
success "Phase 4 complete."
