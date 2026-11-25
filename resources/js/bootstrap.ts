// CnA Frontend Engine v2.1 Client Bootstrap
export function getCsrfToken(): string | null {
    const meta = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]');
    return meta ? meta.content : null;
}

/**
 * Validates that destination URL is strictly same-origin before exposing the CSRF token.
 * Prevents CSRF token leakage to untrusted third-party origins.
 */
export function isSameOrigin(url: string): boolean {
    try {
        const target = new URL(url, window.location.origin);
        return target.origin === window.location.origin;
    } catch {
        return false;
    }
}

export async function fetchApi<T = unknown>(url: string, options: RequestInit = {}): Promise<T> {
    const headers = new Headers(options.headers || {});
    headers.set('X-Requested-With', 'XMLHttpRequest');
    headers.set('Accept', 'application/json');

    const token = getCsrfToken();
    const method = (options.method || 'GET').toUpperCase();
    if (token && !['GET', 'HEAD', 'OPTIONS'].includes(method)) {
        if (isSameOrigin(url)) {
            headers.set('X-CSRF-TOKEN', token);
        } else {
            console.warn(`[CnA Security] Refusing to attach X-CSRF-TOKEN to cross-origin request: ${url}`);
        }
    }

    const response = await fetch(url, { ...options, headers });
    if (!response.ok) {
        throw new Error(`HTTP error ${response.status}: ${response.statusText}`);
    }
    return response.json();
}

