#!/usr/bin/env bash
# phase5_ratelimit.sh — Verify rate limiter behavior
# The api(60/min) limiter IS applied to auth:sanctum route group.
# This phase confirms rate limiting works and tests the login throttle (5/min).
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/helpers.sh"

header "PHASE 5 — RATE LIMITER VERIFICATION"

info "Authenticating superadmin..."
TOKEN=$(get_token "$SUPERADMIN_EMAIL" "$SUPERADMIN_PASS")
if [ -z "$TOKEN" ]; then fail "Could not get token"; exit 1; fi

# ── Test 5.1: api limiter (60/min) — fire 70 requests, expect 429s after 60 ──
header "Test 5.1: Authenticated endpoint — expect 429 after 60 requests (api limiter active)"
PASS429=0
PASS200=0
TOTAL=70
for i in $(seq 1 $TOTAL); do
  code=$(curl -sk -o /dev/null -w "%{http_code}" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json" \
    "$BASE_URL/auth/me")
  if [ "$code" = "429" ]; then
    PASS429=$((PASS429 + 1))
  elif [ "$code" = "200" ]; then
    PASS200=$((PASS200 + 1))
  fi
done
if [ "$PASS429" -gt 0 ]; then
  success "  $TOTAL requests fired, $PASS429 got 429, $PASS200 got 200 — CONFIRMED: 'api' rate limiter IS active (60/min)"
else
  warn "  $TOTAL requests fired, 0 got 429 — rate limiter may not be enforcing (expected some 429s above 60 reqs)"
fi
echo "api_limiter_active,$TOTAL,$PASS200,$PASS429" >> "$RESULTS_DIR/phase5_results.csv"

# ── Test 5.2: login throttle (5/min) — fire 7 requests, expect 429 on 6+ ─────
header "Test 5.2: Login throttle (5/min) — expect 429 after 5 attempts"
# Use a wrong password to avoid actually logging in (but still hit the throttle)
LOGIN_CODES=()
for i in $(seq 1 7); do
  code=$(curl -sk -o /dev/null -w "%{http_code}" \
    "$BASE_URL/auth/login" \
    -X POST -H "Content-Type: application/json" \
    -d '{"email":"nobody@example.com","password":"wrongpass"}')
  LOGIN_CODES+=("$code")
  echo -n "  Attempt $i: HTTP $code"
  [ "$code" = "429" ] && echo " (throttled)" || echo ""
done

THROTTLED=$(printf '%s\n' "${LOGIN_CODES[@]}" | grep -c "429" || echo 0)
if [ "$THROTTLED" -gt 0 ]; then
  success "  Login throttle active — $THROTTLED/7 requests got 429"
else
  fail    "  Login throttle NOT firing — 0/7 requests got 429 (expected throttle after 5)"
fi
echo "login_throttle,7,$THROTTLED" >> "$RESULTS_DIR/phase5_results.csv"

# ── Wait for throttle to reset (61 seconds) ──────────────────────────────────
info "  Waiting 65s for login throttle to reset..."
sleep 65
info "  Reset wait complete"

echo ""
success "Phase 5 complete. Results saved to results/phase5_results.csv"
