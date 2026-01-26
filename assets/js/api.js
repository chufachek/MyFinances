export async function apiRequest(url, options = {}) {
    const response = await fetch(url, {
        headers: {
            'Content-Type': 'application/json',
            ...(options.headers ?? {}),
        },
        ...options,
    });

    if (!response.ok) {
        const message = await response.text();
        throw new Error(message || 'Network response was not ok');
    }

    return response.json().catch(() => ({}));
}

export async function getJson(url) {
    return apiRequest(url, { method: 'GET' });
}
