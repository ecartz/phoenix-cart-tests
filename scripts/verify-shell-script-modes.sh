#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

failed=0
while read -r mode path _rest; do
  if [[ "$mode" != "100755" ]]; then
    echo "Shell script not marked executable in git (expected 100755, got $mode): $path" >&2
    failed=1
  fi
done < <(git ls-files -s | awk '$4 ~ /\.sh$/ { print $1, $4 }')

if [[ "$failed" -ne 0 ]]; then
  echo "Fix with: git update-index --chmod=+x <path>" >&2
  exit 1
fi

echo "All tracked .sh files are mode 100755."
