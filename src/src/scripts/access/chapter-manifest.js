const FALLBACK_TITLE = 'Untitled chapter';

/**
 * Create an immutable manifest for the configured chapters.
 *
 * @param {object[]} chapters Chapter configuration.
 * @return {object[]} Immutable chapter manifest.
 */
const createChapterManifest = (chapters = []) => {
  if (!Array.isArray(chapters)) {
    return Object.freeze([]);
  }

  const ids = new Set();
  const manifest = chapters.map((chapter, position) => {
    const configuredId = typeof chapter?.subContentId === 'string' ?
      chapter.subContentId.trim() : '';
    const stable = configuredId.length > 0;
    const id = stable ? configuredId : `legacy-position-${position}`;

    if (ids.has(id)) {
      throw new Error(`Duplicate chapter id: ${id}`);
    }
    ids.add(id);

    const configuredTitle = typeof chapter?.metadata?.title === 'string' ?
      chapter.metadata.title.trim() : '';

    return Object.freeze({
      id,
      title: configuredTitle || FALLBACK_TITLE,
      position,
      stable
    });
  });

  return Object.freeze(manifest);
};

export { FALLBACK_TITLE, createChapterManifest };
export default createChapterManifest;
