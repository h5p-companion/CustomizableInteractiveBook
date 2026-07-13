import AccessPolicy from './access-policy.js';

/**
 * Query chapter access without depending on a platform integration.
 */
class AccessController {
  /**
   * @param {object[]} manifest Chapter manifest.
   * @param {AccessPolicy} policy Normalized access policy.
   */
  constructor(manifest = [], policy = AccessPolicy.allowAll(manifest)) {
    this.manifest = Object.freeze(Array.isArray(manifest) ? manifest.slice() : []);
    this.policy = policy instanceof AccessPolicy ? policy : new AccessPolicy(policy, this.manifest);
    this.manifestById = Object.freeze(this.manifest.reduce((chapters, chapter) => {
      chapters[chapter.id] = chapter;
      return chapters;
    }, Object.create(null)));
  }

  isAvailable(chapterId) {
    if (!this.manifestById[chapterId]) {
      return false;
    }

    return this.policy.teacherBypass || this.policy.chapters[chapterId]?.available !== false;
  }

  isLocked(chapterId) {
    return !this.isAvailable(chapterId);
  }

  getMessage(chapterId) {
    return this.policy.chapters[chapterId]?.message || '';
  }

  getAvailableIds() {
    return this.manifest
      .filter(chapter => this.isAvailable(chapter.id))
      .map(chapter => chapter.id);
  }

  getAvailableCount() {
    return this.getAvailableIds().length;
  }

  getVisiblePosition(chapterId) {
    const position = this.getAvailableIds().indexOf(chapterId);
    return position === -1 ? 0 : position + 1;
  }

  getNextAvailableId(chapterId) {
    return this.getAdjacentAvailableId(chapterId, 1);
  }

  getPreviousAvailableId(chapterId) {
    return this.getAdjacentAvailableId(chapterId, -1);
  }

  hasAvailableChapters() {
    return this.getAvailableCount() > 0;
  }

  getPolicy() {
    return this.policy;
  }

  getAdjacentAvailableId(chapterId, direction) {
    const position = this.manifest.findIndex(chapter => chapter.id === chapterId);
    if (position === -1) {
      return null;
    }

    for (let index = position + direction; index >= 0 && index < this.manifest.length; index += direction) {
      const candidateId = this.manifest[index].id;
      if (this.isAvailable(candidateId)) {
        return candidateId;
      }
    }

    return null;
  }
}

export { AccessController };
export default AccessController;
