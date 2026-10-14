export interface User {
    id: number;
    name: string;
    email: string;
    role: 'admin' | 'buyer';
    email_verified_at?: string;
}

export interface Domain {
    id: number;
    name: string;
    tld: string;
    registered_year: number;
    age: number;
    backlinks: number;
    referring_domains: number;
    authority_score: number;
    niche: string;
    price_cents: number;
    price: number;
    status: 'draft' | 'pending' | 'live' | 'sold';
    notes: string | null;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginated<T> {
    data: T[];
    links: { first: string | null; last: string | null; prev: string | null; next: string | null };
    meta: {
        current_page: number;
        from: number | null;
        to: number | null;
        last_page: number;
        per_page: number;
        total: number;
        links: PaginationLink[];
    };
}

export interface Filters {
    q?: string;
    tld?: string;
    niche?: string;
    min_age?: number | string;
    max_price?: number | string;
    min_authority?: number | string;
    sort?: string;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
    flash?: { success?: string; error?: string };
};
