#!/usr/bin/env bash

if [ -z "${BASH_VERSION:-}" ]; then
    exec bash "$0" "$@"
fi
set -eo pipefail

fail() {
    printf '%s\n' "$*" >&2
    exit 1
}

base=origin/dev
run_tests=true
while [ "$#" -gt 0 ]; do
    case $1 in
        --base)
            [ "$#" -ge 2 ] && [ -n "$2" ] || fail '--base requires a Git ref.'
            base=$2
            shift
            ;;
        --no-tests) run_tests=false ;;
        --help|-h)
            printf 'Usage: %s [--base <ref>] [--no-tests]\n' "$0"
            exit 0
            ;;
        *) fail "Unknown option: $1" ;;
    esac
    shift
done

cd "$(dirname "$0")/.."
checkout=$(pwd -P)
in_container=false
if [ -e /.dockerenv ] || [ -e /run/.containerenv ] || [ "${LARAVEL_SAIL:-}" = 1 ]; then
    in_container=true
fi

git_args=()
if ! git rev-parse --git-dir >/dev/null 2>&1 && [ "$in_container" = true ] && [ -f .git ]; then
    # Sail mounts the main checkout, but a linked worktree's .git file points to the host.
    git_pointer=$(cat .git)
    case $git_pointer in
        'gitdir: '*/.git/worktrees/*)
            git_args=(--git-dir="/var/www/html/.git/worktrees/${git_pointer##*/.git/worktrees/}" --work-tree="$checkout")
            ;;
    esac
fi

repo_git() {
    git "${git_args[@]}" "$@"
}

git_dir=$(repo_git rev-parse --absolute-git-dir)
common_dir=$(repo_git rev-parse --path-format=absolute --git-common-dir)
base_commit=$(repo_git rev-parse --verify --end-of-options "$base^{commit}") ||
    fail "Cannot resolve $base. Fetch the base branch or pass --base <ref>."
merge_base=$(repo_git merge-base "$base_commit" HEAD) || fail "No merge base found for $base and HEAD."

run_tool() {
    if [ "$in_container" = true ] || [ "${CLAUDE_CODE_REMOTE:-}" = true ]; then
        "$@"
    else
        (cd "$main_checkout" && vendor/bin/sail exec -T -u sail -w "$container_path" laravel.test "$@")
    fi
}

if [ "$in_container" = false ] && [ "${CLAUDE_CODE_REMOTE:-}" != true ]; then
    main_checkout=$(cd "$(dirname "$common_dir")" && pwd -P)
    case $checkout in
        "$main_checkout") container_path=/var/www/html ;;
        "$main_checkout"/*) container_path="/var/www/html/${checkout#"$main_checkout"/}" ;;
        *) fail "The checkout must be inside $main_checkout to use the main Sail mount." ;;
    esac
fi

changed_files=$(mktemp)
trap 'rm -f "$changed_files"' EXIT
repo_git diff --name-only -z --diff-filter=ACMR "$merge_base" HEAD -- > "$changed_files"
repo_git diff --cached --name-only -z --diff-filter=ACMR -- >> "$changed_files"
repo_git diff --name-only -z --diff-filter=ACMR -- >> "$changed_files"
repo_git ls-files --others --exclude-standard -z >> "$changed_files"
LC_ALL=C sort -zu -o "$changed_files" "$changed_files"

php_files=()
js_files=()
pest_files=()
needs_types=false
needs_pest=false
while IFS= read -r -d '' file; do
    [ -f "$file" ] || continue
    case $file in
        vendor/*) continue ;;
        *.php) php_files+=("$file"); needs_pest=true ;;
        composer.json|composer.lock|phpunit.xml|phpunit.xml.dist) needs_pest=true ;;
    esac
    case $file in
        tests/*Test.php) pest_files+=("$file") ;;
        resources/js/*)
            case $file in
                *.js|*.jsx|*.ts|*.tsx|*.vue|*.mjs|*.cjs|*.mts|*.cts) js_files+=("$file") ;;
            esac
            case $file in
                *.ts|*.vue) needs_types=true ;;
            esac
            ;;
    esac
done < "$changed_files"

steps=()
results=()
failed=0
run_step() {
    local label=$1
    shift
    printf '\n== %s ==\n' "$label"
    steps+=("$label")
    if "$@"; then
        results+=(Passed)
    else
        results+=(Failed)
        failed=1
    fi
}

advisory() {
    "$@" || printf '%s\n' 'PHPMD findings above are advisory and do not fail the check.'
}

skip_step() {
    printf '\n== %s (skipped) ==\n' "$1"
    steps+=("$1")
    results+=(Skipped)
}

if [ "${#php_files[@]}" -gt 0 ]; then
    run_step Pint run_tool vendor/bin/pint --format agent -- "${php_files[@]}"
    # Advisory, as in CI: touched files keep their existing findings until someone fixes them.
    run_step 'PHPMD (advisory)' advisory run_tool vendor/bin/phpmd analyze --no-progress -- "${php_files[@]}"
else
    skip_step Pint
    skip_step 'PHPMD (advisory)'
fi

if [ "${#js_files[@]}" -gt 0 ]; then
    run_step ESLint run_tool npx eslint "${js_files[@]}"
else
    skip_step ESLint
fi
if [ "$needs_types" = true ]; then
    run_step 'Typecheck ratchet' run_tool npm run typecheck:ratchet
else
    skip_step 'Typecheck ratchet'
fi
if [ "$run_tests" = true ] && [ "${#js_files[@]}" -gt 0 ]; then
    run_step 'Vitest related' run_tool npx vitest related --run --project unit --project component "${js_files[@]}"
else
    skip_step 'Vitest related'
fi

run_pest_files() {
    local file status=0
    for file in "${pest_files[@]}"; do
        run_tool php artisan test --compact "$file" || status=1
    done
    return "$status"
}

if [ "$run_tests" = false ]; then
    skip_step Pest
elif [ "$git_dir" != "$common_dir" ]; then
    if [ "${#pest_files[@]}" -gt 0 ]; then
        run_step Pest run_pest_files
    else
        skip_step Pest
    fi
    printf '%s\n' 'Worktrees run changed Pest test files individually. Run the full impact check in CI or from the main checkout.'
elif [ "$needs_pest" = true ]; then
    run_step Pest run_tool php artisan test --parallel --compact
else
    skip_step Pest
fi

printf '\n%-22s %s\n' Check Result
for index in "${!steps[@]}"; do
    printf '%-22s %s\n' "${steps[index]}" "${results[index]}"
done
exit "$failed"
