#!/usr/bin/env bash
# helpers.sh — shared utilities for stress test scripts

BASE_URL="https://api.ddev.site/api/v1"
RESULTS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/results"
mkdir -p "$RESULTS_DIR"

SUPERADMIN_EMAIL="superadmin@gmail.com"
SUPERADMIN_PASS="password"
STAFF_EMAIL="staff@gmail.com"
STAFF_PASS="password"
USER_EMAIL="user@gmail.com"
USER_PASS="password"

# ── Color helpers ─────────────────────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
CYAN='\033[0;36m'; BOLD='\033[1m'; RESET='\033[0m'

info()    { echo -e "${CYAN}[INFO]${RESET} $*"; }
success() { echo -e "${GREEN}[OK]${RESET}   $*"; }
warn()    { echo -e "${YELLOW}[WARN]${RESET} $*"; }
fail()    { echo -e "${RED}[FAIL]${RESET} $*"; }
header()  { echo -e "\n${BOLD}${CYAN}══ $* ══${RESET}"; }

# ── Token management ──────────────────────────────────────────────────────────
# Retries up to 3 times with a 5s gap (in case of throttle or transient error)
get_token() {
  local email="$1" pass="$2"
  local resp token
  for attempt in 1 2 3; do
    resp=$(curl -sk --max-time 15 "$BASE_URL/auth/login" \
      -X POST -H "Content-Type: application/json" \
      -d "{\"email\":\"$email\",\"password\":\"$pass\"}")
    token=$(echo "$resp" | python3 -c "import sys,json; d=json.load(sys.stdin); print(d['data']['token'])" 2>/dev/null)
    if [ -n "$token" ]; then
      echo "$token"
      return 0
    fi
    warn "    Login attempt $attempt failed for $email — waiting 5s..."
    sleep 5
  done
  fail "Could not get token for $email after 3 attempts"
  return 1
}

# ── Single timed request ──────────────────────────────────────────────────────
timed_get() {
  local label="$1" url="$2" token="$3"
  local result
  result=$(curl -sk -o /dev/null -w "%{http_code} %{time_total}" \
    -H "Authorization: Bearer $token" \
    -H "Accept: application/json" \
    "$url")
  local code time_s
  code=$(echo "$result" | awk '{print $1}')
  time_s=$(echo "$result" | awk '{print $2}')
  local time_ms
  time_ms=$(echo "$time_s * 1000" | bc | xargs printf "%.0f")
  if [ "$code" = "200" ]; then
    success "  $label → ${time_ms}ms [HTTP $code]"
  else
    fail    "  $label → ${time_ms}ms [HTTP $code]"
  fi
  echo "$label,$code,$time_ms" >> "$RESULTS_DIR/timings.csv"
}

# ── ab wrapper (Apache Benchmark) ─────────────────────────────────────────────
# run_ab <label> <url> <token> <requests> <concurrency>
run_ab() {
  local label="$1" url="$2" token="$3" n="$4" c="$5"
  local outfile="$RESULTS_DIR/ab_${label// /_}.txt"
  info "  ab: n=$n c=$c → $url"
  ab -n "$n" -c "$c" -k \
    -H "Authorization: Bearer $token" \
    -H "Accept: application/json" \
    "$url" > "$outfile" 2>&1
  # Parse key metrics
  local rps p50 p99 failed
  rps=$(grep "Requests per second" "$outfile" | awk '{print $4}')
  p50=$(grep "50%" "$outfile" | awk '{print $2}')
  p99=$(grep "99%" "$outfile" | awk '{print $2}')
  failed=$(grep "Failed requests" "$outfile" | awk '{print $3}')
  echo -e "    RPS: ${BOLD}${rps}${RESET}  p50: ${p50}ms  p99: ${p99}ms  failed: ${failed}"
  echo "$label,$n,$c,$rps,$p50,$p99,$failed" >> "$RESULTS_DIR/ab_summary.csv"
}

# ── POST helper ───────────────────────────────────────────────────────────────
post_json() {
  local url="$1" token="$2" data="$3"
  curl -sk -X POST "$url" \
    -H "Authorization: Bearer $token" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d "$data"
}

# ── DB row count via ddev mysql ────────────────────────────────────────────────
db_count() {
  local table="$1" where="${2:-1=1}"
  ddev mysql -e "SELECT COUNT(*) FROM \`$table\` WHERE $where;" 2>/dev/null \
    | tail -1 | tr -d ' '
}

# Ensure CSV headers
[ -f "$RESULTS_DIR/timings.csv" ] || echo "label,http_code,time_ms" > "$RESULTS_DIR/timings.csv"
[ -f "$RESULTS_DIR/ab_summary.csv" ] || echo "label,requests,concurrency,rps,p50_ms,p99_ms,failed" >> "$RESULTS_DIR/ab_summary.csv"
