import type {
    Category,
    CreateIssuePayload,
    Issue,
    IssueFilters,
    Paginated,
    PlaybookEntry,
    SingleResource,
    UpdateIssuePayload,
    ValidationError,
} from './types'

const BASE_URL = '/api'

export class ApiError extends Error {
    constructor(
        public status: number,
        message: string,
        public payload?: unknown,
    ) {
        super(message)
        this.name = 'ApiError'
    }
}

async function request<T>(
    path: string,
    options: RequestInit = {},
): Promise<T> {
    const response = await fetch(`${BASE_URL}${path}`, {
        ...options,
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            ...(options.headers ?? {}),
        },
    })

    const text = await response.text()
    const json: unknown = text ? JSON.parse(text) : null

    if (!response.ok) {
        const message =
            (json as { message?: string })?.message ??
            `Request failed with status ${response.status}`
        throw new ApiError(response.status, message, json)
    }

    return json as T
}

export const api = {
    listCategories(): Promise<{ data: Category[] }> {
        return request<{ data: Category[] }>('/categories')
    },

    listIssues(filters: IssueFilters = {}): Promise<Paginated<Issue>> {
        const params = new URLSearchParams()
        if (filters.status) params.set('status', filters.status)
        if (filters.priority) params.set('priority', filters.priority)
        if (filters.category) params.set('category', filters.category)
        if (filters.escalated !== undefined) {
            params.set('escalated', String(filters.escalated))
        }
        const query = params.toString()
        return request<Paginated<Issue>>(`/issues${query ? `?${query}` : ''}`)
    },

    showIssue(id: number): Promise<SingleResource<Issue>> {
        return request<SingleResource<Issue>>(`/issues/${id}`)
    },

    createIssue(payload: CreateIssuePayload): Promise<SingleResource<Issue>> {
        return request<SingleResource<Issue>>('/issues', {
            method: 'POST',
            body: JSON.stringify(payload),
        })
    },

    updateIssue(
        id: number,
        payload: UpdateIssuePayload,
    ): Promise<SingleResource<Issue>> {
        return request<SingleResource<Issue>>(`/issues/${id}`, {
            method: 'PATCH',
            body: JSON.stringify(payload),
        })
    },

    listPlaybook(): Promise<{ data: PlaybookEntry[] }> {
        return request<{ data: PlaybookEntry[] }>('/playbook')
    },

    showPlaybookEntry(slug: string): Promise<SingleResource<PlaybookEntry>> {
        return request<SingleResource<PlaybookEntry>>(`/playbook/${slug}`)
    },
}

export function isValidationError(
    error: unknown,
): error is ApiError & { payload: ValidationError } {
    return (
        error instanceof ApiError &&
        error.status === 422 &&
        typeof error.payload === 'object' &&
        error.payload !== null &&
        'errors' in error.payload
    )
}
