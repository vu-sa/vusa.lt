import { describe, expect, it } from 'vitest';

import { useActiveHotspot } from '../useActiveHotspot';

describe('useActiveHotspot', () => {
  it('openPopover marks that id as the open popover and no other', () => {
    const hotspots = useActiveHotspot();
    hotspots.openPopover('a');
    expect(hotspots.isPopoverOpen('a')).toBe(true);
    expect(hotspots.isPopoverOpen('b')).toBe(false);
    expect(hotspots.isTextFieldLive('a')).toBe(false);
  });

  it('openTextField marks that id as the live text field and no other', () => {
    const hotspots = useActiveHotspot();
    hotspots.openTextField('a');
    expect(hotspots.isTextFieldLive('a')).toBe(true);
    expect(hotspots.isTextFieldLive('b')).toBe(false);
    expect(hotspots.isPopoverOpen('a')).toBe(false);
  });

  it('opening a popover releases a live text field, and vice versa', () => {
    const hotspots = useActiveHotspot();
    hotspots.openTextField('title');
    hotspots.openPopover('button');
    expect(hotspots.isTextFieldLive('title')).toBe(false);
    expect(hotspots.isPopoverOpen('button')).toBe(true);

    hotspots.openTextField('title');
    expect(hotspots.isPopoverOpen('button')).toBe(false);
    expect(hotspots.isTextFieldLive('title')).toBe(true);
  });

  it('opening a second popover closes the first — only one thing is ever active', () => {
    const hotspots = useActiveHotspot();
    hotspots.openPopover('a');
    hotspots.openPopover('b');
    expect(hotspots.isPopoverOpen('a')).toBe(false);
    expect(hotspots.isPopoverOpen('b')).toBe(true);
  });

  it('close(id) only closes when the id matches the currently active one', () => {
    const hotspots = useActiveHotspot();
    hotspots.openPopover('a');
    hotspots.close('b');
    expect(hotspots.isPopoverOpen('a')).toBe(true);

    hotspots.close('a');
    expect(hotspots.isPopoverOpen('a')).toBe(false);
    expect(hotspots.active.value).toBeNull();
  });

  it('close() with no argument always closes whatever is active', () => {
    const hotspots = useActiveHotspot();
    hotspots.openTextField('x');
    hotspots.close();
    expect(hotspots.active.value).toBeNull();
  });
});
