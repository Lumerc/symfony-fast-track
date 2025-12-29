// src/api/api.js
function fetchCollection(path) {
    const url = ENV_API_ENDPOINT + path;
    console.log('Fetching from:', url);
    
    return fetch(url)
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(json => {
            console.log('API Response:', json);
            // Ищем данные в разных возможных полях
            return json['member'] || json['hydra:member'] || json['items'] || [];
        })
        .catch(error => {
            console.error('Fetch error:', error);
            return [];
        });
}

export function findConferences() {
    return fetchCollection('api/conferences');
}

export function findComments(conference) {
    return fetchCollection(`api/comments?conference=${conference.id}`);
}