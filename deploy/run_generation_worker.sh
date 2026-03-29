#!/bin/bash
set -euo pipefail

APP_ROOT="${APP_ROOT:-/www/wwwroot/ai-creator}"
WORKER_NAME="${1:-}"

if [[ -z "${WORKER_NAME}" ]]; then
  echo "用法: $0 submit|poll|sync" >&2
  exit 1
fi

find_php_bin() {
  local candidates=(
    "/www/server/php/81/bin/php"
    "/www/server/php/74/bin/php"
    "/usr/bin/php"
    "$(command -v php 2>/dev/null || true)"
  )
  local candidate
  for candidate in "${candidates[@]}"; do
    [[ -n "${candidate}" && -x "${candidate}" ]] && echo "${candidate}" && return 0
  done
  echo "未找到可执行的 php" >&2
  exit 1
}

PHP_BIN="${PHP_BIN:-$(find_php_bin)}"

case "${WORKER_NAME}" in
  submit)
    exec "${PHP_BIN}" "${APP_ROOT}/api/workers/submit_worker.php" --name=submitWorker
    ;;
  poll)
    exec "${PHP_BIN}" "${APP_ROOT}/api/workers/poll_worker.php" --name=pollWorker
    ;;
  sync)
    exec "${PHP_BIN}" "${APP_ROOT}/api/workers/oss_worker.php" --name=ossWorker
    ;;
  *)
    echo "未知 worker: ${WORKER_NAME}" >&2
    exit 1
    ;;
esac
