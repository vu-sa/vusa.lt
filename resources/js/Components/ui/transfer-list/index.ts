/**
 * @deprecated For assigning people to a thing (rules/pages.md → Pickers and Forms 1, 15): two panes do
 * not fit a phone, and an association belongs on the record page. Replaced for duties by
 * `Features/Admin/Occupancy/AssignDutyUserSheet` (PR 5.5); `UserForm` and `EditRole` follow in Phase 9.3.
 * Remove in Phase 10.
 */
export { default as TransferList } from './TransferList.vue';
export type { TransferListOption } from './TransferList.vue';
