---
paths:
  - resources/js/Composables/useAgendaItemStyling.ts
---

# Composables

## Pass the governance-scope flag into agenda item status helpers
For VU SA's own bodies (governance_scope === 'vusa', i.e. requiresStudentPerspective === false) the vote carries only a decision — student_vote/student_benefit are never filled. Any "is this decided?" judgement must take the scope flag: a decision alone means decided (getAgendaItemStatus(item, false) → decision_positive/decision_negative/neutral_decided). The default parameter is true (external bodies), where a decision without a student vote is deliberately 'no_vote'. Backend counterpart: VoteStatisticsCalculator::decisionOnlyStatistics() and Meeting::requiresStudentPerspective().
