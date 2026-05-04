type PaginationState = {
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    per_page: number;
};

type AsyncTableConfig = {
    endpoint: string;
    search?: string;
    sort?: string;
    direction?: 'asc' | 'desc';
    perPage?: number | string;
    filters?: Record<string, string>;
    pagination: PaginationState;
    labels: {
        loading: string;
        error: string;
    };
};

type AsyncTableResponse = {
    html: string;
    pagination: PaginationState;
};

export default function asyncTable(config: AsyncTableConfig) {
    return {
        endpoint: config.endpoint,
        search: config.search ?? '',
        sort: config.sort ?? '',
        direction: config.direction ?? 'asc',
        perPage: String(config.perPage ?? config.pagination.per_page),
        filters: { ...(config.filters ?? {}) },
        pagination: config.pagination,
        labels: config.labels,
        loading: false,
        error: '',
        abortController: null as AbortController | null,

        setSearch(value: string): void {
            this.search = value;
            this.fetchPage(1);
        },

        setFilter(name: string, value: string): void {
            this.filters[name] = value;
            this.fetchPage(1);
        },

        setPerPage(value: string): void {
            this.perPage = value;
            this.fetchPage(1);
        },

        sortBy(column: string): void {
            if (this.sort === column) {
                this.direction = this.direction === 'asc' ? 'desc' : 'asc';
            } else {
                this.sort = column;
                this.direction = 'asc';
            }

            this.fetchPage(1);
        },

        isSorted(column: string): boolean {
            return this.sort === column;
        },

        sortIndicator(column: string): string {
            if (!this.isSorted(column)) {
                return '-';
            }

            return this.direction === 'asc' ? '^' : 'v';
        },

        async fetchPage(page: number): Promise<void> {
            if (page < 1 || page > Math.max(1, this.pagination.last_page)) {
                return;
            }

            this.abortController?.abort();
            this.abortController = new AbortController();
            this.loading = true;
            this.error = '';

            const url = new URL(this.endpoint, window.location.origin);
            url.searchParams.set('page', String(page));
            url.searchParams.set('per_page', this.perPage);

            if (this.search.trim() !== '') {
                url.searchParams.set('search', this.search.trim());
            }

            if (this.sort !== '') {
                url.searchParams.set('sort', this.sort);
                url.searchParams.set('direction', this.direction);
            }

            Object.entries(this.filters).forEach(([name, value]) => {
                if (value !== '') {
                    url.searchParams.set(name, value);
                }
            });

            try {
                const response = await fetch(url.toString(), {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    signal: this.abortController.signal,
                });

                if (!response.ok) {
                    throw new Error(`Async table request failed with status ${response.status}`);
                }

                const data = (await response.json()) as AsyncTableResponse;
                (this as any).$refs.body.innerHTML = data.html;
                this.pagination = data.pagination;
            } catch (error) {
                if (error instanceof DOMException && error.name === 'AbortError') {
                    return;
                }

                this.error = this.labels.error;
            } finally {
                this.loading = false;
            }
        },

        pages(): Array<number | string> {
            const current = this.pagination.current_page;
            const last = this.pagination.last_page;

            if (last <= 7) {
                return Array.from({ length: last }, (_, index) => index + 1);
            }

            const pages = new Set<number>([1, last]);
            for (let page = current - 1; page <= current + 1; page += 1) {
                if (page > 1 && page < last) {
                    pages.add(page);
                }
            }

            const sortedPages = Array.from(pages).sort((a, b) => a - b);
            const result: Array<number | string> = [];

            sortedPages.forEach((page, index) => {
                const previous = sortedPages[index - 1];
                if (previous && page - previous > 1) {
                    result.push('...');
                }

                result.push(page);
            });

            return result;
        },
    };
}
