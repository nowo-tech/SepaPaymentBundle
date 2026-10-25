#!/usr/bin/env bash
# REQ-TWIG-005 — fail when bundle views or demo templates submit forms with raw <form> / <input> / <textarea> /
# <select> markup instead of Symfony Form (form_start / form_widget). No exceptions: the bundle ships no views and the demo
# forms are Symfony Form Types.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

dirs=()
[[ -d src/Resources/views ]] && dirs+=(src/Resources/views)
for d in demo/*/templates; do [[ -d "$d" ]] && dirs+=("$d"); done
if [[ ${#dirs[@]} -eq 0 ]]; then
  echo "check-no-raw-html-form: no Twig views — OK"
  exit 0
fi

matches="$(grep -RInE '<(form|input|textarea|select)([[:space:]>]|$)' "${dirs[@]}" --include='*.twig' || true)"

if [[ -n "$matches" ]]; then
  echo "ERROR: raw form markup in Twig (REQ-TWIG-005):" >&2
  echo "$matches" >&2
  exit 1
fi

echo "check-no-raw-html-form: OK (${dirs[*]})"
