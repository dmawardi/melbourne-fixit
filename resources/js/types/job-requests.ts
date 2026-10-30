export type Option = {
    value: string;
    label: string;
};

export type Service = Option & {
    description: string;
};

export type Faq = {
    question: string;
    answer: string;
};

export type JobRequestOptions = {
    categories: Option[];
    urgencies: Option[];
    budgetRanges: Option[];
    propertyTypes: Option[];
};

export type Permissions = {
    update: boolean;
    delete: boolean;
};

export type JobRequestFile = {
    id: number;
    url: string;
    kind: Option;
    is_image: boolean;
    original_name: string | null;
    mime_type: string | null;
    size: number | null;
    width: number | null;
    height: number | null;
    caption: string | null;
    sort_order: number;
    can: Permissions;
    created_at: string | null;
};

export type JobRequestMessage = {
    id: number;
    body: string;
    created_at: string | null;
};

export type JobRequest = {
    id: string;
    title: string;
    description: string;
    category: Option;
    urgency: Option;
    budget_range: Option | null;
    property_type: Option | null;
    status: Option;
    contact_name: string;
    contact_email: string;
    contact_phone: string | null;
    suburb: string;
    postcode: string;
    address_line: string | null;
    files_count?: number;
    files?: JobRequestFile[];
    messages?: JobRequestMessage[];
    can: Permissions;
    created_at: string | null;
    updated_at: string | null;
};

export type Paginated<T> = {
    data: T[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        from: number | null;
        last_page: number;
        per_page: number;
        to: number | null;
        total: number;
    };
};
