import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'results', 'loading'];
    static values = {
        url: String,
        minLength: { type: Number, default: 2 },
        delay: { type: Number, default: 300 }
    };

    connect() {
        this.timeout = null;
        this.abortController = null;
    }

    disconnect() {
        if (this.abortController) {
            this.abortController.abort();
        }
        clearTimeout(this.timeout);
    }

    onInput() {
        clearTimeout(this.timeout);
        
        const query = this.inputTarget.value.trim();
        
        if (query.length < this.minLengthValue) {
            this.hideResults();
            return;
        }
        
        this.timeout = setTimeout(() => {
            this.search(query);
        }, this.delayValue);
    }

    async search(query) {
        if (this.abortController) {
            this.abortController.abort();
        }
        
        this.abortController = new AbortController();
        
        try {
            this.showLoading();
            
            const response = await fetch(`${this.urlValue}?q=${encodeURIComponent(query)}`, {
                signal: this.abortController.signal,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) {
                throw new Error('Search failed');
            }
            
            const results = await response.json();
            this.renderResults(results);
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Search error:', error);
                this.hideResults();
            }
        } finally {
            this.hideLoading();
        }
    }

    renderResults(results) {
        if (results.length === 0) {
            this.resultsTarget.innerHTML = '<div class="p-3 text-sm text-gray-500">No results found</div>';
        } else {
            this.resultsTarget.innerHTML = results.map(item => `
                <a href="${item.url}" class="block p-3 hover:bg-gray-50 transition border-b border-gray-100 last:border-0">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-medium text-gray-900">${this.escapeHtml(item.name || item.renter || 'Unknown')}</div>
                            <div class="text-sm text-gray-500">${this.escapeHtml(item.type || item.item || '')} · ${this.escapeHtml(item.status)}</div>
                        </div>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </a>
            `).join('');
        }
        this.showResults();
    }

    showResults() {
        this.resultsTarget.classList.remove('hidden');
    }

    hideResults() {
        this.resultsTarget.classList.add('hidden');
    }

    showLoading() {
        if (this.hasLoadingTarget) {
            this.loadingTarget.classList.remove('hidden');
        }
    }

    hideLoading() {
        if (this.hasLoadingTarget) {
            this.loadingTarget.classList.add('hidden');
        }
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Close results when clicking outside
    onClickOutside(event) {
        if (!this.element.contains(event.target)) {
            this.hideResults();
        }
    }
}
