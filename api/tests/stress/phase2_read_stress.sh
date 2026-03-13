#!/usr/bin/env bash
# phase2_read_stress.sh — Concurrent reads via Apache Benchmark
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/helpers.sh"

header "PHASE 2 — READ STRESS (concurrent GETs)"

info "Authenticating superadmin..."
TOKEN=$(get_token "$SUPERADMIN_EMAIL" "$SUPERADMIN_PASS")
if [ -z "$TOKEN" ]; then fail "Could not get token"; exit 1; fi

# n=total requests, c=concurrent users
N=100
C=20

header "Dashboard /all (10-query beast)"
run_ab "dashboard_all"    "$BASE_URL/dashboard/all"   "$TOKEN" $N $C

header "Tickets list"
run_ab "tickets_list"     "$BASE_URL/tickets"          "$TOKEN" $N $C

header "Work Orders list"
run_ab "workorders_list"  "$BASE_URL/work-orders"      "$TOKEN" $N $C

header "Work Reports list"
run_ab "workreports_list" "$BASE_URL/work-reports"     "$TOKEN" $N $C

header "Daily Records list"
run_ab "daily_records"    "$BASE_URL/daily-records"    "$TOKEN" $N $C

header "Daily Usage Report"
run_ab "daily_usage"      "$BASE_URL/daily-records/report/daily-usage" "$TOKEN" $N $C

header "Auth /me (Sanctum token lookup per request)"
run_ab "auth_me"          "$BASE_URL/auth/me"          "$TOKEN" $N $C

echo ""
success "Phase 2 complete. ab reports saved to results/ab_*.txt"
success "Summary saved to results/ab_summary.csv"
