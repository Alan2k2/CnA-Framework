// CnA Frontend Engine v2.0 Client Bootstrap
export function getCsrfToken(): string | null {
    const meta = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]');
    return meta ? meta.content : null;
}

export async function fetchApi<T = unknown>(url: string, options: RequestInit = {}): Promise<T> {
    const headers = new Headers(options.headers || {});
    headers.set('X-Requested-With', 'XMLHttpRequest');
    headers.set('Accept', 'application/json');

    const token = getCsrfToken();
    if (token && !['GET', 'HEAD'].includes((options.method || 'GET').toUpperCase())) {
        headers.set('X-CSRF-TOKEN', token);
    }

    const response = await fetch(url, { ...options, headers });
    if (!response.ok) {
        throw new Error(`HTTP error ${response.status}: ${response.statusText}`);
    }
    return response.json();
}

