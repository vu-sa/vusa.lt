<?php

return [
    'field_decision_label' => 'Decision',
    'field_decision_tooltip' => "The final decision of the entire body on this issue. Select: approved (positive vote), rejected (negative vote), or abstained (no decision was made / didn't vote).",
    'field_student_vote_label' => 'How students voted',
    'field_student_vote_tooltip' => 'How the student representative(s) voted on this issue.',
    'field_student_benefit_label' => 'Is the decision favorable to students',
    'field_student_benefit_tooltip' => 'Assess and reflect, whether the decision is beneficial to students. For example: favorable – improves study quality, provides more flexibility; unfavorable – reduces retake opportunities, tightens requirements.',
    'field_description_tooltip' => 'Briefly describe what was discussed or decided. Include key points that may be relevant to other representatives (and students, if the meeting is made public).',
    'help_title' => 'How to fill in voting data',
    'help_description' => 'Voting data helps track student representation activities and ensures transparency.',
    'help_why_important' => 'Why is this important?',
    'help_why_important_text' => 'Voting data allows students to see how their representatives vote and whether decisions align with student interests. This promotes accountability and helps evaluate representation effectiveness.',
    'help_tips_title' => 'Tips for filling in',
    'help_tip_1' => 'Fill in immediately after the meeting while information is fresh',
    'help_tip_2' => "If unsure about student benefit – select 'neutral' and explain in the description",
    'help_tip_3' => 'Mention important arguments or discussions in the description',
    'help_contact' => 'If you have questions – contact your student representative coordinator',

    // Agenda item status explanations
    'help_agenda_status_description' => 'Each agenda item is publicly displayed based on its type and voting result. Here is what each status means:',
    'status_aligned_admin_help' => 'The final decision matches how student representatives voted. Mark when the body agreed with what students voted for.',
    'status_misaligned_admin_help' => 'The final decision differs from how student representatives voted. Mark when the body decided differently than students.',
    'status_neutral_admin_help' => 'Students abstained or the decision had no clear benefit or harm to students.',
    'status_no_vote_admin_help' => 'A voting item, but voting data has not been entered yet. Do not forget to fill it in!',
    'status_deferred_admin_help' => 'The discussion of this item was postponed to another meeting. Select type "Deferred".',
    'status_informational_admin_help' => 'An informational item with no voting involved. Select type "Informational".',
    'status_unset_admin_help' => 'The item type has not been specified yet. Make sure to select the appropriate type!',

    // Agenda item type tooltips
    'type_voting_tooltip' => 'An item that was voted on. You need to fill in the voting results (decision, student vote, and student benefit).',
    'type_deferred_tooltip' => 'The discussion of this item was postponed to another meeting. No voting data required.',
    'type_informational_tooltip' => 'An informational item with no voting. No voting data required.',
    'main_vote_required_tooltip' => 'The main vote is required for voting type items and cannot be removed.',
];
