/** Missing completion timestamps also represent incomplete tasks in compact payloads. */
export function isTaskIncomplete(task: { completed_at?: string | null }): boolean {
  return task.completed_at === null || task.completed_at === undefined;
}

export function countIncompleteTasks(tasks: Array<{ completed_at?: string | null }> = []): number {
  return tasks.filter(isTaskIncomplete).length;
}
