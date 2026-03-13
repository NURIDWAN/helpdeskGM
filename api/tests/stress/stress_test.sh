#!/usr/bin/env bash
# stress_test.sh — Master script: runs all phases in sequence
# Usage:
#   ./stress_test.sh           # run all phases
#   ./stress_test.sh 1 2 6     # run only specified phases
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/helpers.sh"

PHASES_TO_RUN=("${@:-1 2 3 4 5 6}")
if [ $# -gt 0 ]; then
  PHASES_TO_RUN=("$@")
else
  PHASES_TO_RUN=(1 2 3 4 5 6)
fi

START_TIME=$(date +%s)
echo ""
echo "╔══════════════════════════════════════════════════════════╗"
echo "║           HELPDESK API STRESS TEST SUITE                 ║"
echo "║  Target: $BASE_URL"
echo "║  Date:   $(date '+%Y-%m-%d %H:%M:%S')"
echo "╚══════════════════════════════════════════════════════════╝"
echo ""

# ── Preflight: confirm API is accessible ─────────────────────────────────────
info "Preflight: checking API reachability..."
HTTP=$(curl -sk -o /dev/null -w "%{http_code}" "$BASE_URL/auth/me")
if [[ "$HTTP" == "200" || "$HTTP" == "401" ]]; then
  success "API is reachable (HTTP $HTTP)"
else
  fail "API not reachable (HTTP $HTTP). Is ddev running? Run: ddev start"
  exit 1
fi

# ── Preflight: confirm ab is available ────────────────────────────────────────
if ! command -v ab &>/dev/null; then
  warn "Apache Benchmark (ab) not found. Phase 2 & 4 will be skipped."
  SKIP_AB=1
else
  SKIP_AB=0
  success "ab available at $(command -v ab)"
fi

# ── Run requested phases ──────────────────────────────────────────────────────
for phase in "${PHASES_TO_RUN[@]}"; do
  case "$phase" in
    1) bash "$SCRIPT_DIR/phase1_baseline.sh" ;;
    2) [ "$SKIP_AB" -eq 1 ] && warn "Skipping phase 2 (no ab)" || bash "$SCRIPT_DIR/phase2_read_stress.sh" ;;
    3) bash "$SCRIPT_DIR/phase3_write_integrity.sh" ;;
    4) [ "$SKIP_AB" -eq 1 ] && warn "Skipping phase 4 (no ab)" || bash "$SCRIPT_DIR/phase4_mixed.sh" ;;
    5) bash "$SCRIPT_DIR/phase5_ratelimit.sh" ;;
    6) bash "$SCRIPT_DIR/phase6_integrity.sh" ;;
    *) warn "Unknown phase: $phase (valid: 1-6)" ;;
  esac
done

# ── Final summary ─────────────────────────────────────────────────────────────
END_TIME=$(date +%s)
DURATION=$(( END_TIME - START_TIME ))

echo ""
echo "╔══════════════════════════════════════════════════════════╗"
echo "║                  STRESS TEST COMPLETE                    ║"
printf "║  Total time: %ds%$(( 48 - ${#DURATION} ))s║\n" "$DURATION" ""
echo "╚══════════════════════════════════════════════════════════╝"
echo ""
echo "Results directory: $RESULTS_DIR"
ls -lh "$RESULTS_DIR/"
