/**
 * Return chapters that are allowed to participate in runtime behavior.
 *
 * @param {object[]} chapters Internal chapter descriptors.
 * @return {object[]} Available non-summary chapters with valid instances.
 */
const getAvailableRuntimeChapters = (chapters = []) => {
  const safeChapters = Array.isArray(chapters) ? chapters : [];
  return safeChapters.filter(chapter =>
    chapter &&
    !chapter.isSummary &&
    chapter.available === true &&
    chapter.locked !== true &&
    chapter.instance
  );
};

/**
 * Choose the initial chapter while honoring an explicit chapter selection.
 *
 * @param {object[]} chapters Internal chapter descriptors.
 * @param {number} requestedIndex Index selected by a URL or saved state.
 * @param {boolean} allowLockedSelection Whether an explicit URL may select a locked chapter.
 * @return {number} Initial chapter index.
 */
const getInitialChapterIndex = (chapters, requestedIndex = -1, allowLockedSelection = true) => {
  if (requestedIndex >= 0 && requestedIndex < chapters.length &&
    (allowLockedSelection || (chapters[requestedIndex].available === true && chapters[requestedIndex].locked !== true))) {
    return requestedIndex;
  }

  const firstAvailable = chapters.findIndex(chapter =>
    chapter && !chapter.isSummary && chapter.available === true && chapter.locked !== true
  );
  return firstAvailable === -1 ? 0 : firstAvailable;
};

/**
 * Sum one H5P scoring method across available chapter runtimes.
 *
 * @param {object[]} chapters Internal chapter descriptors.
 * @param {string} method H5P scoring method.
 * @return {number} Aggregated score.
 */
const sumAvailableChapterMetric = (chapters, method) => {
  return getAvailableRuntimeChapters(chapters).reduce((total, chapter) => {
    const value = typeof chapter.instance[method] === 'function' ?
      Number(chapter.instance[method]()) : 0;
    return total + (Number.isFinite(value) ? value : 0);
  }, 0);
};

/**
 * Run a chapter factory only when its descriptor is available.
 *
 * @param {object} chapter Internal chapter descriptor.
 * @param {Function} factory H5P runnable factory.
 * @return {object|null} Created instance or null for a locked chapter.
 */
const createAvailableChapterInstance = (chapter, factory) => {
  if (!chapter || chapter.available !== true || chapter.locked === true || typeof factory !== 'function') {
    return null;
  }

  return factory();
};

/**
 * Resolve previous state by stable chapter ID before considering legacy position.
 *
 * A present chaptersById map is authoritative. This prevents positional state
 * from being applied to another chapter after reordering.
 *
 * @param {object|null} previousState Persisted book state.
 * @param {object} chapter Current chapter descriptor.
 * @return {object|null} Matching state.
 */
const getPreviousChapterState = (previousState, chapter) => {
  if (!previousState || typeof previousState !== 'object' || !chapter) {
    return null;
  }

  const chaptersById = previousState.chaptersById;
  if (chaptersById && typeof chaptersById === 'object' && !Array.isArray(chaptersById)) {
    return Object.prototype.hasOwnProperty.call(chaptersById, chapter.id) ?
      chaptersById[chapter.id] : null;
  }

  const legacyState = Array.isArray(previousState.chapters) ?
    previousState.chapters[chapter.position] : null;
  if (!legacyState || typeof legacyState !== 'object') {
    return null;
  }

  const persistedId = legacyState.id || legacyState.subContentId;
  if (persistedId && persistedId !== chapter.id) {
    return null;
  }

  return legacyState;
};

export {
  createAvailableChapterInstance,
  getAvailableRuntimeChapters,
  getInitialChapterIndex,
  getPreviousChapterState,
  sumAvailableChapterMetric
};
