const DEFAULT_CONTRACT_VERSION = 1;

/**
 * Normalize a platform access policy into an immutable domain object.
 */
class AccessPolicy {
  /**
   * @param {object} policy Raw policy.
   * @param {object[]} manifest Chapter manifest.
   */
  constructor(policy = {}, manifest = []) {
    const source = policy && typeof policy === 'object' ? policy : {};
    const sourceChapters = source.chapters && typeof source.chapters === 'object' && !Array.isArray(source.chapters) ?
      source.chapters : {};

    this.contractVersion = Number.isInteger(source.contractVersion) && source.contractVersion > 0 ?
      source.contractVersion : DEFAULT_CONTRACT_VERSION;
    this.required = source.required === true;
    this.teacherBypass = source.teacherBypass === true;

    const chapters = Object.create(null);
    const safeManifest = Array.isArray(manifest) ? manifest : [];
    safeManifest.forEach(chapter => {
      const configuredChapter = sourceChapters[chapter.id];
      const configuredItem = configuredChapter && typeof configuredChapter === 'object' && !Array.isArray(configuredChapter) ?
        configuredChapter : {};

      chapters[chapter.id] = Object.freeze({
        available: typeof configuredItem.available === 'boolean' ? configuredItem.available : true,
        message: typeof configuredItem.message === 'string' ? configuredItem.message : ''
      });
    });

    this.chapters = Object.freeze(chapters);
    Object.freeze(this);
  }

  /**
   * Create a policy that makes every manifest chapter available.
   *
   * @param {object[]} manifest Chapter manifest.
   * @return {AccessPolicy} Allow-all policy.
   */
  static allowAll(manifest = []) {
    return new AccessPolicy({
      contractVersion: DEFAULT_CONTRACT_VERSION,
      required: false,
      teacherBypass: false,
      chapters: {}
    }, manifest);
  }
}

export { AccessPolicy, DEFAULT_CONTRACT_VERSION };
export default AccessPolicy;
