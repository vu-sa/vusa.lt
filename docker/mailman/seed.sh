#!/usr/bin/env bash
# Seeds the local Mailman (docker-compose profile "mailman") with a few konf.vusa.lt-like lists,
# members and archived posts. Safe to re-run: existing lists and members are left alone.
#
#   COMPOSE_PROFILES=mailman ./vendor/bin/sail up -d
#   docker/mailman/seed.sh
#   ./vendor/bin/sail artisan mailing-lists:import
#   ./vendor/bin/sail artisan mailing-lists:fetch-archives
set -euo pipefail

cd "$(dirname "$0")/../.."
export WWWUSER="${WWWUSER:-$(id -u)}" WWWGROUP="${WWWGROUP:-$(id -g)}"

core() {
    docker compose exec -T "$@"
}

echo "Waiting for the Mailman REST API…"
for _ in $(seq 1 30); do
    if core mailman-core curl -sf -o /dev/null -u "${MAILMAN_USERNAME:-vusa-app}:${MAILMAN_PASSWORD:-vusa-local}" http://mailman-core:8001/3.1/system/versions; then
        break
    fi
    sleep 2
done

core -e MAILMAN_USERNAME="${MAILMAN_USERNAME:-vusa-app}" -e MAILMAN_PASSWORD="${MAILMAN_PASSWORD:-vusa-local}" mailman-core python3 - <<'PY'
import base64, json, os, urllib.error, urllib.request

BASE = "http://mailman-core:8001/3.1"
AUTH = "Basic " + base64.b64encode(f"{os.environ['MAILMAN_USERNAME']}:{os.environ['MAILMAN_PASSWORD']}".encode()).decode()

def call(method, path, data=None):
    headers = {"Authorization": AUTH}
    body = None
    if data is not None:
        body = json.dumps(data).encode()
        headers["Content-Type"] = "application/json"
    request = urllib.request.Request(BASE + path, data=body, method=method, headers=headers)
    try:
        with urllib.request.urlopen(request) as response:
            return response.status
    except urllib.error.HTTPError as error:
        if error.code in (400, 409):  # already exists / already subscribed
            return error.code
        raise

# Settings mirror the konf.vusa.lt lists (see MailingListController::NEW_LIST_CONFIG).
CONFIG = {
    "archive_policy": "private",
    "dmarc_mitigate_action": "munge_from",
    "default_member_action": "defer",
    "default_nonmember_action": "hold",
    "send_welcome_message": False,
    "advertised": True,
}

LISTS = {
    "test": ("Test", ["tester@vusa.lt"]),
    "centriniai": ("Centriniai", ["senatorius@cr.vu.lt", "atstove@fsf.stud.vu.lt", "atstovai@vusa.lt"]),
    "akad.etika": ("Akad.etika", ["aek.atstovas@mif.stud.vu.lt", "akadem@vusa.lt", "buves.atstovas@vm.stud.vu.lt"]),
}

call("POST", "/domains", {"mail_host": "konf.vusa.lt"})

for name, (display_name, members) in LISTS.items():
    list_id = f"{name}.konf.vusa.lt"
    call("POST", "/lists", {"fqdn_listname": f"{name}@konf.vusa.lt", "style_name": "private-default"})
    call("PATCH", f"/lists/{list_id}/config", {**CONFIG, "display_name": display_name, "subject_prefix": f"[{display_name}] "})
    call("POST", "/members", {"list_id": list_id, "subscriber": "it@vusa.lt", "role": "owner",
                              "pre_verified": True, "pre_confirmed": True, "pre_approved": True})
    for email in members:
        call("POST", "/members", {"list_id": list_id, "subscriber": email, "role": "member",
                                  "pre_verified": True, "pre_confirmed": True, "pre_approved": True})
    print(f"  {name}@konf.vusa.lt: {len(members)} members")
PY

post() {
    local list="$1" from="$2" subject="$3" body="$4" id
    id="$(echo "$list $subject" | md5sum | cut -c1-16)@seed.vusa.test"
    # Re-running must not archive the same post twice; HyperKitty keys posts by Message-ID.
    if core mailman-web sh -c "cd /opt/mailman-web && python3 manage.py shell -c \"from hyperkitty.models import Email; import sys; sys.exit(0 if Email.objects.filter(message_id='$id').exists() else 1)\"" >/dev/null 2>&1; then
        return
    fi
    # Headers must be ASCII; Lithuanian subjects go in RFC 2047 encoded-word form.
    printf 'From: %s\nTo: %s\nSubject: =?utf-8?b?%s?=\nMessage-ID: <%s>\nDate: %s\nMIME-Version: 1.0\nContent-Type: text/plain; charset=utf-8\nContent-Transfer-Encoding: 8bit\n\n%s\n' \
        "$from" "$list" "$(printf '%s' "$subject" | base64 -w0)" "$id" "$(date -R)" "$body" \
        | core -u mailman mailman-core mailman inject "$list"
}

echo "Marking the web admin's email verified, so it can log in to Postorius/HyperKitty…"
core mailman-web sh -c "cd /opt/mailman-web && python3 manage.py shell -c \"
from django.contrib.auth.models import User
from allauth.account.models import EmailAddress
user = User.objects.get(username='${MAILMAN_ARCHIVES_USERNAME:-vusa-app-archive}')
EmailAddress.objects.update_or_create(user=user, email=user.email, defaults={'verified': True, 'primary': True})
\"" >/dev/null

echo "Posting sample messages (delivered to Mailpit, archived in HyperKitty)…"
post akad.etika@konf.vusa.lt akadem@vusa.lt "Mokymų registracija" "Sveiki, registracija į AEK atstovų mokymus jau atidaryta."
post akad.etika@konf.vusa.lt akadem@vusa.lt "AEK mokymų medžiaga" "Prisegu mokymų medžiagą ir susitikimo įrašą."
post centriniai@konf.vusa.lt atstovai@vusa.lt "Centrinių atstovų susitikimas" "Kviečiame į pirmąjį kadencijos susitikimą kitą savaitę."

echo "Done. Postorius: http://localhost:${FORWARD_MAILMAN_WEB_PORT:-8095}/postorius/ (log in as ${MAILMAN_ARCHIVES_USERNAME:-vusa-app-archive} / ${MAILMAN_ARCHIVES_PASSWORD:-vusa-local}) · Mailpit: http://localhost:8025"
