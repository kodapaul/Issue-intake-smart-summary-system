export type Priority = 'low' | 'medium' | 'high'
export type Status = 'open' | 'in_progress' | 'resolved' | 'closed'

export interface Category {
    slug: string
    name: string
    severity_level: Priority
    description?: string | null
}

export interface IssueCategoryEmbed {
    slug: string
    name: string
    severity_level: Priority
}

export interface Issue {
    id: number
    title: string
    description: string
    priority: Priority
    category: IssueCategoryEmbed
    status: Status
    summary: string | null
    suggested_action: string | null
    due_date: string | null
    escalated_at: string | null
    is_escalated: boolean
    issuer: string | null
    issuer_email: string | null
    created_at: string
    updated_at: string
}

export interface Faq {
    q: string
    a: string
}

export interface PlaybookEntry {
    slug: string
    name: string
    description: string
    summary_template: string
    suggested_action: string
    troubleshooting_steps: string[]
    faqs: Faq[]
    category_hint: string | null
    priority_hint: Priority | null
}

export interface Paginated<T> {
    data: T[]
    meta: {
        current_page: number
        last_page: number
        per_page: number
        total: number
    }
    links: {
        first: string | null
        last: string | null
        prev: string | null
        next: string | null
    }
}

export interface SingleResource<T> {
    data: T
}

export interface ValidationError {
    message: string
    errors: Record<string, string[]>
}

export interface IssueFilters {
    status?: Status
    priority?: Priority
    category?: string
    escalated?: boolean
}

export interface CreateIssuePayload {
    title: string
    description: string
    category: string
    issuer?: string
    issuer_email?: string
    due_date?: string
}

export interface UpdateIssuePayload {
    status?: Status
    title?: string
    description?: string
    category?: string
    if_unmodified_since?: string
}
