#!/usr/bin/env bash
# phase1_baseline.sh — Sequential baseline: 1 user, measure each endpoint once
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/helpers.sh"

header "PHASE 1 — BASELINE (sequential, single user)"

info "Authenticating superadmin..."
TOKEN=$(get_token "$SUPERADMIN_EMAIL" "$SUPERADMIN_PASS")
if [ -z "$TOKEN" ]; then fail "Could not get token"; exit 1; fi
success "Token obtained"

info "Authenticating staff..."
STAFF_TOKEN=$(get_token "$STAFF_EMAIL" "$STAFF_PASS")

# ── Auth ──────────────────────────────────────────────────────────────────────
header "Auth endpoints"
timed_get "GET /auth/me"            "$BASE_URL/auth/me"                         "$TOKEN"

# ── Dashboard ─────────────────────────────────────────────────────────────────
header "Dashboard (heaviest endpoint)"
timed_get "GET /dashboard/all"      "$BASE_URL/dashboard/all"                   "$TOKEN"

# ── Branches ──────────────────────────────────────────────────────────────────
header "Branches"
timed_get "GET /branches"           "$BASE_URL/branches"                        "$TOKEN"

# ── Tickets ───────────────────────────────────────────────────────────────────
header "Tickets"
timed_get "GET /tickets"            "$BASE_URL/tickets"                         "$TOKEN"
timed_get "GET /tickets?page=1"     "$BASE_URL/tickets?page=1"                  "$TOKEN"

# ── Work Orders ───────────────────────────────────────────────────────────────
header "Work Orders"
timed_get "GET /work-orders"        "$BASE_URL/work-orders"                     "$TOKEN"

# ── Work Reports ──────────────────────────────────────────────────────────────
header "Work Reports"
timed_get "GET /work-reports"       "$BASE_URL/work-reports"                    "$TOKEN"

# ── Daily Records ─────────────────────────────────────────────────────────────
header "Daily Records"
timed_get "GET /daily-records"      "$BASE_URL/daily-records"                   "$TOKEN"
timed_get "GET /daily-usage-report" "$BASE_URL/daily-records/report/daily-usage" "$TOKEN"

# ── Job Templates ─────────────────────────────────────────────────────────────
header "Job Templates"
timed_get "GET /job-templates"      "$BASE_URL/job-templates"                   "$TOKEN"

# ── Users ─────────────────────────────────────────────────────────────────────
header "Users"
timed_get "GET /users"              "$BASE_URL/users"                           "$TOKEN"

# ── Roles ─────────────────────────────────────────────────────────────────────
header "Roles"
timed_get "GET /roles"              "$BASE_URL/roles"                           "$TOKEN"

echo ""
success "Phase 1 complete. Timings saved to results/timings.csv"
