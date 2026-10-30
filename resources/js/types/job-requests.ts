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

export type AdminJobRequestFile = JobRequestFile & {
    visibility: Option;
    uploader?: { id: number; name: string; is_admin: boolean } | null;
};

export type AdminJobRequestMessage = JobRequestMessage & {
    visibility: Option;
    author?: { id: number; name: string } | null;
};

export type JobRequestActivity = {
    id: number;
    event: Option;
    properties: Record<string, unknown>;
    user?: { id: number; name: string } | null;
    created_at: string | null;
};

export type Vendor = {
    id: number;
    name: string;
    type: Option;
    categories: Option[];
    contact_name: string | null;
    email: string | null;
    phone: string | null;
    notes: string | null;
    job_requests_count?: number;
    archived: boolean;
};

export type VendorChoice = {
    id: number;
    name: string;
    handles_category: boolean;
};

export type VendorOptions = {
    types: Option[];
    categories: Option[];
};

export type AdminJobRequest = Omit<JobRequest, 'files' | 'messages'> & {
    admin_notes: string | null;
    owner?: { id: number; name: string; email: string } | null;
    vendor?: (Vendor & { handles_category: boolean }) | null;
    claimed_at: string | null;
    submitted_ip: string | null;
    messages_count?: number;
    files?: AdminJobRequestFile[];
    messages?: AdminJobRequestMessage[];
    activities?: JobRequestActivity[];
    latest_activity?: JobRequestActivity | null;
    last_activity_at: string | null;
    deleted_at: string | null;
};

export type AdminJobRequestOptions = JobRequestOptions & {
    statuses: Option[];
    fileKinds: Option[];
    visibilities: Option[];
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
