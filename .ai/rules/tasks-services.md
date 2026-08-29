---
paths:
  - 'app/Tasks/**,app/Services/MeetingCompletionService.php'
---

# Tasks Services

## Agenda completion progress follows the meeting's governance scope
AgendaCompletionTaskHandler must measure progress with MeetingCompletionService::voteIsComplete($vote, $meeting->requiresStudentPerspective()), never by hardcoding student_vote/decision/student_benefit. VU SA's own bodies (governance_scope 'vusa' — Parlamentas, Taryba, padaliniai) never fill student_vote/student_benefit, so a hardcoded check pinned every internal meeting's task at 0% forever. Same rule as Meeting::completion_status and the frontend's getAgendaItemStatus(item, requiresStudentPerspective). Existing tasks keep stale metadata until an agenda item is saved; `tasks:repopulate meeting --include-past --force` resyncs them.
