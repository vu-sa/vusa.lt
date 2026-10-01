import { afterEach, beforeEach, expect, it, vi } from 'vitest';
import { Editor } from '@tiptap/core';
import { createFullExtensions } from '../extensions/presets';
import { useTiptapFileUpload } from '../composables/useTiptapFileUpload';

const uploadFiles = vi.hoisted(() => vi.fn());
const errorToast = vi.hoisted(() => vi.fn());
vi.mock('@/Composables/useFileUpload', () => ({ uploadFiles }));
vi.mock('@/Composables/useToasts', () => ({ useToasts: () => ({ error: errorToast }) }));
const editors: Editor[] = [];
const uploads: ReturnType<typeof useTiptapFileUpload>[] = [];
function session() {
  const editor = new Editor({ extensions: createFullExtensions(), content: '<p>left right</p>' });
  const upload = useTiptapFileUpload();
  editors.push(editor);
  uploads.push(upload);
  return { editor, upload };
}
function result(name: string) { return { uploaded: [{ name, url: `/uploads/${name}` }], failed: [] }; }
beforeEach(() => vi.clearAllMocks());
afterEach(() => { uploads.splice(0).forEach(upload => upload.clearPendingUploads()); editors.splice(0).forEach(editor => editor.destroy()); });

it('keeps adjacent text and file order while typing during a multi-file upload', async () => {
  let finish!: (value: unknown) => void;
  uploadFiles.mockImplementationOnce(() => new Promise(resolve => { finish = resolve; }))
    .mockResolvedValueOnce(result('second.pdf'));
  const { editor, upload } = session();
  const pending = upload.handleFileDrop(editor, [new File(['a'], 'first.pdf', { type: 'application/pdf' }), new File(['b'], 'second.pdf', { type: 'application/pdf' })], 5);
  expect(editor.getText()).toBe('left right');
  editor.commands.insertContentAt(1, 'New ');
  finish(result('first.pdf'));
  await pending;
  expect(editor.getText()).toBe('New leftfirst.pdfsecond.pdf right');
  expect(editor.getHTML()).toContain('href="/uploads/first.pdf"');
  expect(upload.uploadingFiles.value.size).toBe(0);
});

it('inserts uploaded video URLs rather than base64 data', async () => {
  uploadFiles.mockResolvedValue(result('movie.mp4'));
  const { editor, upload } = session();
  await upload.handleFileDrop(editor, [new File(['movie'], 'movie.mp4', { type: 'video/mp4' })], 5);
  const video = editor.getJSON().content?.find(node => node.type === 'video');
  expect(video?.attrs?.src).toBe('/uploads/movie.mp4');
  expect(editor.getText().replace(/\s/g, '')).toBe('leftright');
  expect(editor.getHTML()).not.toContain('base64');
});

it('does not insert a completed upload after its editor has been closed', async () => {
  let finish!: (value: unknown) => void;
  uploadFiles.mockImplementationOnce(() => new Promise(resolve => { finish = resolve; }));
  const { editor, upload } = session();
  const pending = upload.handleFileDrop(editor, [new File(['a'], 'first.pdf', { type: 'application/pdf' })], 5);
  upload.clearPendingUploads();
  finish(result('first.pdf'));
  await pending;
  expect(editor.getText()).toBe('left right');
  expect(upload.uploadingFiles.value.size).toBe(0);
});

it('keeps authored text when an upload fails', async () => {
  uploadFiles.mockRejectedValueOnce(new Error('Upload unavailable'));
  const { editor, upload } = session();
  await upload.handleFileDrop(editor, [new File(['a'], 'first.pdf', { type: 'application/pdf' })], 5);
  expect(editor.getText()).toBe('left right');
  expect(errorToast).toHaveBeenCalled();
  expect(upload.uploadingFiles.value.size).toBe(0);
});
