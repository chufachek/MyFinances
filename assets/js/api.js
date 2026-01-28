export function apiRequest(url, options = {}) {
    const headers = {
        'Content-Type': 'application/json',
        ...(options.headers ?? {}),
    };

    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        const method = options.method ?? 'GET';
        xhr.open(method, url, true);
        xhr.withCredentials = options.credentials === 'include' || options.credentials === 'same-origin';

        Object.entries(headers).forEach(([key, value]) => {
            if (value !== undefined && value !== null) {
                xhr.setRequestHeader(key, value);
            }
        });

        xhr.onerror = () => {
            reject(new Error('Network response was not ok'));
        };

        xhr.onreadystatechange = () => {
            if (xhr.readyState !== 4) {
                return;
            }

            const responseText = xhr.responseText || '';
            if (xhr.status >= 200 && xhr.status < 300) {
                if (!responseText) {
                    resolve({});
                    return;
                }
                try {
                    resolve(JSON.parse(responseText));
                } catch (error) {
                    reject(new Error('Invalid JSON response'));
                }
                return;
            }

            reject(new Error(responseText || 'Network response was not ok'));
        };

        xhr.send(options.body ?? null);
    });
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
