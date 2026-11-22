#!/bin/sh
set -eu

usage() {
    cat <<'EOF'
Usage:
  dev/worktree.sh add <name> [base] [--branch <branch>] [--no-build]
  dev/worktree.sh rm <name> [--force-unpushed]
  dev/worktree.sh list

Run from the main checkout root. The default base is origin/dev and the
default branch is feat/<name>.
EOF
}

fail() {
    printf '%s\n' "$*" >&2
    exit 1
}

case ${1:-} in
    --help|-h) usage; exit 0 ;;
    add|rm|list) action=$1; shift ;;
    *) usage >&2; exit 2 ;;
esac

root=$(git rev-parse --show-toplevel) || fail 'Run from the main checkout root.'
git_dir=$(git rev-parse --absolute-git-dir)
common_dir=$(git rev-parse --path-format=absolute --git-common-dir)
if [ "$(pwd -P)" != "$root" ] || [ "$git_dir" != "$common_dir" ]; then
    fail 'Run from the main checkout root.'
fi

if [ "$action" = list ]; then
    [ "$#" -eq 0 ] || fail 'Usage: dev/worktree.sh list'
    exec git worktree list
fi

name=${1:-}
case $name in
    ''|[!a-zA-Z0-9]*|*[!a-zA-Z0-9._-]*) fail 'Use a worktree name starting with a letter or digit, with only letters, digits, dots, underscores and hyphens.' ;;
esac
shift
path=".worktrees/$name"
container_path="/var/www/html/$path"

if [ -L .worktrees ] || [ -L "$path" ]; then
    fail "Refusing to use a symlink for $path or its parent."
fi

if [ "$action" = add ]; then
    branch="feat/$name"
    base=
    build=true
    while [ "$#" -gt 0 ]; do
        case $1 in
            --branch)
                [ "$#" -ge 2 ] && [ -n "$2" ] || fail '--branch requires a branch name.'
                branch=$2
                shift
                ;;
            --no-build) build=false ;;
            -*) fail "Unknown add option: $1" ;;
            *)
                [ -z "$base" ] || fail "Unexpected argument: $1"
                base=$1
                ;;
        esac
        shift
    done
    [ -f .env ] || fail 'Set up the main checkout with dev/setup.sh first.'

    git fetch origin
    git worktree add -b "$branch" -- "$path" "${base:-origin/dev}"

    exclude=$(git rev-parse --git-path info/exclude)
    if ! grep -Fxq '/.worktrees/' "$exclude" 2>/dev/null; then
        mkdir -p "$(dirname "$exclude")"
        printf '\n/.worktrees/\n' >> "$exclude"
    fi
    cp .env "$path/.env"
    ln -s ../../node_modules "$path/node_modules"

    printf '%s\n' 'In a worktree, pass explicit file paths to Pint and run Pest one file at a time. Pint --dirty and parallel/TIA Pest are unavailable there.'
    vendor/bin/sail exec -T -u sail -w "$container_path" laravel.test composer install --no-interaction
    if [ "$build" = true ]; then
        vendor/bin/sail exec -T -u sail -w "$container_path" laravel.test npm run build
    fi
    exit 0
fi

force_unpushed=false
if [ "${1:-}" = --force-unpushed ]; then
    force_unpushed=true
    shift
fi
[ "$#" -eq 0 ] || fail 'Usage: dev/worktree.sh rm <name> [--force-unpushed]'

registrations=$(git worktree list --porcelain)
if ! printf '%s\n' "$registrations" | grep -Fxq "worktree $root/$path"; then
    fail "No worktree is registered at $path."
fi
status=$(git -C "$path" status --porcelain --untracked-files=all --ignore-submodules=none)
if [ -n "$status" ]; then
    printf '%s\n' "$status" >&2
    fail "Commit or move the changes in $path before removing it."
fi
unpushed=$(git -C "$path" rev-list --count HEAD --not --remotes)
if [ "$unpushed" -gt 0 ]; then
    printf '%s\n' "Warning: $path has $unpushed commit(s) missing from the local remote-tracking refs." >&2
    [ "$force_unpushed" = true ] || fail 'Push the commits or run rm with --force-unpushed.'
fi

vendor/bin/sail exec -T -u root laravel.test chown -h -R -P -- "$(id -u):$(id -g)" "$container_path"
remove_status=0
git worktree remove -- "$path" || remove_status=$?
registrations=$(git worktree list --porcelain)
if printf '%s\n' "$registrations" | grep -Fxq "worktree $root/$path"; then
    printf '%s\n' "$path is still registered. Resolve the error and retry dev/worktree.sh rm $name." >&2
    exit 1
fi
if [ -e "$path" ] || [ -L "$path" ]; then
    ls -la "$path" >&2 || :
    fail "$path is no longer registered. Inspect and preserve any remaining files, then remove the folder manually."
fi
exit "$remove_status"
