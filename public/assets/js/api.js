export async function apiRequest(url, options = {}) {
    const response = await fetch(url, {
        headers: {
            'Content-Type': 'application/json',
            ...(options.headers ?? {}),
        },
        credentials: 'same-origin',
        ...options,
    });

    if (!response.ok) {
        const message = await response.text();
        let errorMessage = message || 'Network response was not ok';
        try {
            const data = JSON.parse(message);
            errorMessage = data.error || data.message || errorMessage;
        } catch (error) {
            // keep original message
        }
        throw new Error(errorMessage);
    }

    const text = await response.text();
    return text ? JSON.parse(text) : {};
}

export async function getJson(url) {
    return apiRequest(url, { method: 'GET' });
}

export async function postJson(url, body) {
    return apiRequest(url, { method: 'POST', body: JSON.stringify(body) });
}

export async function putJson(url, body) {
    return apiRequest(url, { method: 'PUT', body: JSON.stringify(body) });
}

export async function deleteJson(url) {
    return apiRequest(url, { method: 'DELETE' });
}
