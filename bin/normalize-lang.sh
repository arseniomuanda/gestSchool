#!/usr/bin/env bash
#
# Normaliza ficheiros lang/*.json:
#   1. Ordena chaves alfabeticamente
#   2. Remove duplicados (mantém o último valor visto)
#   3. Indenta com 4 espaços (compatível com Laravel Pint)
#   4. Garante newline final
#
# Pode ser corrido manualmente:   bin/normalize-lang.sh
# Ou via hook pre-commit:         ln -sf ../../bin/normalize-lang.sh .git/hooks/pre-commit
#
# Requer: jq (instalado por defeito em macOS via Homebrew, ou apt/yum em Linux).
# Exit non-zero se algum ficheiro não for JSON válido — bloqueia o commit.

set -euo pipefail

if ! command -v jq >/dev/null 2>&1; then
    echo "❌ jq não está instalado. Instala com:" >&2
    echo "   macOS:  brew install jq" >&2
    echo "   Ubuntu: sudo apt install jq" >&2
    exit 1
fi

repo_root="$(cd "$(dirname "$0")/.." && pwd)"
cd "$repo_root"

shopt -s nullglob
files=(lang/*.json)
if [ "${#files[@]}" -eq 0 ]; then
    exit 0
fi

changed=0
for f in "${files[@]}"; do
    # Valida JSON primeiro (falha imediata em ficheiro com marcadores de conflito ou sintaxe errada)
    if ! jq empty < "$f" 2>/dev/null; then
        echo "❌ $f: JSON inválido (provavelmente marcadores de conflito por resolver)" >&2
        echo "   Resolve os conflitos primeiro, depois corre este script." >&2
        exit 1
    fi

    # Normaliza: ordena (--sort-keys), remove duplicados via to_entries|unique_by, reescreve
    tmp="$(mktemp)"
    jq --sort-keys 'to_entries | unique_by(.key) | from_entries' < "$f" > "$tmp"

    # Comparação byte-a-byte (jq output != input em todos os ficheiros não-normalizados)
    if ! cmp -s "$f" "$tmp"; then
        mv "$tmp" "$f"
        echo "→ $f normalizado"
        changed=$((changed + 1))
    else
        rm -f "$tmp"
    fi
done

if [ "$changed" -gt 0 ]; then
    echo ""
    echo "✓ $changed ficheiro(s) normalizado(s). Lembra-te de re-stage com:"
    echo "  git add lang/*.json"
fi
