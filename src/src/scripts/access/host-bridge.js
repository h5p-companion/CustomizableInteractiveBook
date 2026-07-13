import AccessPolicy from './access-policy.js';

const CONTRACT_VERSION = 1;
const LIBRARY_NAME = 'H5P.CustomizableInteractiveBook';
const READY_MESSAGE_TYPE = 'h5p-customizable-interactive-book:ready';
const POLICY_MESSAGE_TYPE = 'h5p-customizable-interactive-book:policy';
const DEFAULT_TIMEOUT_MS = 2500;
const DEFAULT_RETRY_INTERVAL_MS = 500;
const DEFAULT_MAX_ATTEMPTS = 4;

let fallbackRequestCounter = 0;

/**
 * Request an access policy from the immediate parent window.
 */
class HostBridge {
  /**
   * @param {string|number} contentId H5P content id.
   * @param {object} options Runtime overrides, primarily for testing.
   */
  constructor(contentId, options = {}) {
    this.window = options.windowRef || window;
    this.document = options.documentRef || document;
    this.contentId = String(contentId);
    this.timeoutMs = options.timeoutMs || DEFAULT_TIMEOUT_MS;
    this.retryIntervalMs = options.retryIntervalMs || DEFAULT_RETRY_INTERVAL_MS;
    this.maxAttempts = options.maxAttempts || DEFAULT_MAX_ATTEMPTS;

    this.requestId = HostBridge.createRequestId(this.window.crypto);
    this.expectedOrigin = this.getParentOrigin();
    this.manifest = Object.freeze([]);
    this.pendingPromise = null;
    this.resolvePolicy = null;
    this.retryTimer = null;
    this.timeoutTimer = null;
    this.listenerAttached = false;
    this.disposed = false;
    this.attempts = 0;

    this.handleMessage = this.handleMessage.bind(this);
  }

  /**
   * Create a unique correlation id without relying on platform APIs.
   *
   * @param {Crypto} cryptoRef Browser crypto implementation.
   * @return {string} Request id.
   */
  static createRequestId(cryptoRef) {
    if (typeof cryptoRef?.randomUUID === 'function') {
      return cryptoRef.randomUUID();
    }

    if (typeof cryptoRef?.getRandomValues === 'function') {
      const bytes = new Uint8Array(16);
      cryptoRef.getRandomValues(bytes);
      bytes[6] = (bytes[6] & 0x0f) | 0x40;
      bytes[8] = (bytes[8] & 0x3f) | 0x80;
      const hex = Array.from(bytes, byte => byte.toString(16).padStart(2, '0')).join('');
      return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
    }

    fallbackRequestCounter += 1;
    const randomPart = Math.random().toString(36).slice(2);
    return `fallback-${Date.now().toString(36)}-${fallbackRequestCounter.toString(36)}-${randomPart}`;
  }

  /**
   * Resolve the expected parent origin from the iframe referrer.
   *
   * @return {string|null} Parent origin or null when unavailable.
   */
  getParentOrigin() {
    if (!this.window.parent || this.window.parent === this.window) {
      return null;
    }

    if (typeof this.document.referrer !== 'string' || this.document.referrer.length === 0) {
      return null;
    }

    try {
      const origin = new URL(this.document.referrer).origin;
      return origin && origin !== 'null' ? origin : null;
    }
    catch (error) {
      return null;
    }
  }

  /**
   * Request a policy and fall back to allow-all after a short timeout.
   *
   * @param {object[]} manifest Immutable chapter manifest.
   * @return {Promise<AccessPolicy>} Resolved access policy.
   */
  requestPolicy(manifest = []) {
    if (this.pendingPromise) {
      return this.pendingPromise;
    }

    this.manifest = Object.freeze(Array.isArray(manifest) ? manifest.slice() : []);

    if (this.disposed) {
      return Promise.resolve(AccessPolicy.allowAll(this.manifest));
    }

    this.pendingPromise = new Promise(resolve => {
      this.resolvePolicy = resolve;
      this.timeoutTimer = this.window.setTimeout(() => {
        this.finish(AccessPolicy.allowAll(this.manifest));
      }, this.timeoutMs);

      if (!this.expectedOrigin) {
        return;
      }

      this.window.addEventListener('message', this.handleMessage);
      this.listenerAttached = true;
      this.sendReady();
      this.retryTimer = this.window.setInterval(() => {
        if (this.attempts >= this.maxAttempts) {
          this.window.clearInterval(this.retryTimer);
          this.retryTimer = null;
          return;
        }
        this.sendReady();
      }, this.retryIntervalMs);
    });

    return this.pendingPromise;
  }

  /**
   * Send the ready message using only the verified parent origin.
   */
  sendReady() {
    if (!this.expectedOrigin || this.disposed || this.attempts >= this.maxAttempts) {
      return;
    }

    const chapters = this.manifest.map(chapter => ({
      id: chapter.id,
      title: chapter.title,
      position: chapter.position,
      stable: chapter.stable
    }));

    this.attempts += 1;
    try {
      this.window.parent.postMessage({
        type: READY_MESSAGE_TYPE,
        contractVersion: CONTRACT_VERSION,
        requestId: this.requestId,
        contentId: this.contentId,
        library: LIBRARY_NAME,
        chapters
      }, this.expectedOrigin);
    }
    catch (error) {
      // The timeout will safely resolve to allow-all.
    }
  }

  /**
   * Validate a policy response before resolving the request.
   *
   * @param {MessageEvent} event Browser message event.
   */
  handleMessage(event) {
    if (
      event.source !== this.window.parent ||
      event.origin !== this.expectedOrigin ||
      !event.data ||
      typeof event.data !== 'object' ||
      event.data.type !== POLICY_MESSAGE_TYPE ||
      event.data.contractVersion !== CONTRACT_VERSION ||
      event.data.requestId !== this.requestId ||
      event.data.contentId !== this.contentId
    ) {
      return;
    }

    this.finish(new AccessPolicy(event.data, this.manifest));
  }

  /**
   * Resolve once and release all browser resources.
   *
   * @param {AccessPolicy} policy Policy to resolve.
   */
  finish(policy) {
    if (!this.resolvePolicy) {
      return;
    }

    const resolve = this.resolvePolicy;
    this.resolvePolicy = null;
    this.clearResources();
    resolve(policy);
  }

  /**
   * Clear message listeners and timers without changing the result.
   */
  clearResources() {
    if (this.listenerAttached) {
      this.window.removeEventListener('message', this.handleMessage);
      this.listenerAttached = false;
    }
    if (this.retryTimer !== null) {
      this.window.clearInterval(this.retryTimer);
      this.retryTimer = null;
    }
    if (this.timeoutTimer !== null) {
      this.window.clearTimeout(this.timeoutTimer);
      this.timeoutTimer = null;
    }
  }

  /**
   * Stop waiting and release all resources.
   */
  dispose() {
    if (this.disposed) {
      return;
    }

    this.disposed = true;
    if (this.resolvePolicy) {
      this.finish(AccessPolicy.allowAll(this.manifest));
    }
    else {
      this.clearResources();
    }
  }
}

export {
  CONTRACT_VERSION,
  DEFAULT_MAX_ATTEMPTS,
  DEFAULT_RETRY_INTERVAL_MS,
  DEFAULT_TIMEOUT_MS,
  LIBRARY_NAME,
  POLICY_MESSAGE_TYPE,
  READY_MESSAGE_TYPE,
  HostBridge
};
export default HostBridge;
