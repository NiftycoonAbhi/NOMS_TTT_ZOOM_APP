/**
 * AJAX Helper Library for TTT ZOOM System
 * Version: 2.0.0
 */

(function(window) {
    'use strict';

    const AjaxLib = {
        // Default configuration
        config: {
            timeout: 30000,
            retries: 3,
            retryDelay: 1000
        },

        /**
         * Make HTTP request with retry logic
         */
        request: function(options) {
            const defaults = {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                },
                timeout: this.config.timeout,
                retries: this.config.retries
            };

            const config = Object.assign({}, defaults, options);
            
            return this._makeRequest(config, 0);
        },

        /**
         * Internal request method with retry logic
         */
        _makeRequest: function(config, attempt) {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), config.timeout);

            const fetchOptions = {
                method: config.method,
                headers: config.headers,
                signal: controller.signal
            };

            if (config.body) {
                fetchOptions.body = config.body;
            }

            return fetch(config.url, fetchOptions)
                .then(response => {
                    clearTimeout(timeoutId);
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    
                    const contentType = response.headers.get('Content-Type');
                    if (contentType && contentType.includes('application/json')) {
                        return response.json();
                    }
                    return response.text();
                })
                .catch(error => {
                    clearTimeout(timeoutId);
                    
                    if (attempt < config.retries && !controller.signal.aborted) {
                        console.warn(`Request failed, retrying... (${attempt + 1}/${config.retries})`);
                        return new Promise(resolve => {
                            setTimeout(() => {
                                resolve(this._makeRequest(config, attempt + 1));
                            }, this.config.retryDelay * (attempt + 1));
                        });
                    }
                    
                    throw error;
                });
        },

        /**
         * GET request
         */
        get: function(url, options = {}) {
            return this.request(Object.assign({
                method: 'GET',
                url: url
            }, options));
        },

        /**
         * POST request
         */
        post: function(url, data, options = {}) {
            let body = data;
            let headers = options.headers || {};

            if (data instanceof FormData) {
                // Don't set Content-Type for FormData, let browser set it
                delete headers['Content-Type'];
            } else if (typeof data === 'object') {
                body = JSON.stringify(data);
                headers['Content-Type'] = 'application/json';
            }

            return this.request(Object.assign({
                method: 'POST',
                url: url,
                body: body,
                headers: headers
            }, options));
        },

        /**
         * Upload file with progress tracking
         */
        upload: function(url, file, progressCallback = null) {
            return new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                const formData = new FormData();
                
                formData.append('file', file);

                if (progressCallback) {
                    xhr.upload.addEventListener('progress', (e) => {
                        if (e.lengthComputable) {
                            const percentComplete = (e.loaded / e.total) * 100;
                            progressCallback(percentComplete);
                        }
                    });
                }

                xhr.addEventListener('load', () => {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            resolve(response);
                        } catch (e) {
                            resolve(xhr.responseText);
                        }
                    } else {
                        reject(new Error(`Upload failed: ${xhr.status} ${xhr.statusText}`));
                    }
                });

                xhr.addEventListener('error', () => {
                    reject(new Error('Upload failed: Network error'));
                });

                xhr.addEventListener('timeout', () => {
                    reject(new Error('Upload failed: Timeout'));
                });

                xhr.timeout = this.config.timeout;
                xhr.open('POST', url);
                xhr.send(formData);
            });
        }
    };

    // Export to global scope
    window.AjaxLib = AjaxLib;

    // Backward compatibility
    window.ajax = {
        get: AjaxLib.get.bind(AjaxLib),
        post: AjaxLib.post.bind(AjaxLib),
        upload: AjaxLib.upload.bind(AjaxLib)
    };

})(window);
