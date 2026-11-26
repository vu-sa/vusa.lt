/** A caller's deadline can expire while Vue still owns the process-wide locale and routes. */
export function serialRenderer<Input, Output>(
  render: (input: Input) => Promise<Output>,
  budgetMs: number,
  onStuck: (elapsed: number) => void,
) {
  const waiting = new Set<() => void>();
  let activeDeadline = 0;

  return (input: Input): Promise<Output> => new Promise((resolve, reject) => {
    const deadline = Date.now() + budgetMs;
    const expire = () => reject(new Error('SSR request deadline exceeded'));
    if (activeDeadline && Date.now() >= activeDeadline) {
      expire();
      return;
    }

    const timeout = setTimeout(() => {
      waiting.delete(start);
      expire();
    }, budgetMs);

    function start() {
      waiting.delete(start);
      const started = Date.now();
      if (started >= deadline) {
        clearTimeout(timeout);
        expire();
        return;
      }
      activeDeadline = deadline;
      const watchdog = setTimeout(() => onStuck(Date.now() - started), 5 * budgetMs);
      void Promise.resolve().then(() => render(input)).then((value) => {
        if (Date.now() >= deadline) expire();
        else resolve(value);
      }, reject).finally(() => {
        clearTimeout(timeout);
        clearTimeout(watchdog);
        activeDeadline = 0;
        for (const next of waiting) {
          next();
          if (activeDeadline) break;
        }
      });
    }

    if (activeDeadline) waiting.add(start);
    else start();
  });
}
