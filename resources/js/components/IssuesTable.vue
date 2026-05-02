<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { toast } from 'vue-sonner'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card'
import { Label } from '@/components/ui/label'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'
import IssueDetailDialog from './IssueDetailDialog.vue'
import { api } from '@/api'
import type { Category, Issue, IssueFilters } from '@/types'

const issues = ref<Issue[]>([])
const total = ref(0)
const loading = ref(true)
const categories = ref<Category[]>([])

const filters = ref<{
    status: string
    priority: string
    category: string
    escalated: string
}>({
    status: 'all',
    priority: 'all',
    category: 'all',
    escalated: 'all',
})

const selectedIssue = ref<Issue | null>(null)
const dialogOpen = ref(false)

const statuses = ['open', 'in_progress', 'resolved', 'closed']
const priorities = ['low', 'medium', 'high']
const escalatedOptions = [
    { value: 'all', label: 'All' },
    { value: 'true', label: 'Escalated only' },
    { value: 'false', label: 'Non-escalated only' },
]

const apiFilters = computed<IssueFilters>(() => {
    const f: IssueFilters = {}
    if (filters.value.status !== 'all')
        f.status = filters.value.status as IssueFilters['status']
    if (filters.value.priority !== 'all')
        f.priority = filters.value.priority as IssueFilters['priority']
    if (filters.value.category !== 'all') f.category = filters.value.category
    if (filters.value.escalated !== 'all')
        f.escalated = filters.value.escalated === 'true'
    return f
})

async function refresh() {
    loading.value = true
    try {
        const res = await api.listIssues(apiFilters.value)
        issues.value = res.data
        total.value = res.meta.total
    } catch (e) {
        toast.error('Failed to load issues')
        console.error(e)
    } finally {
        loading.value = false
    }
}

onMounted(async () => {
    try {
        const res = await api.listCategories()
        categories.value = res.data
    } catch (e) {
        console.error(e)
    }
    await refresh()
})

watch(apiFilters, refresh, { deep: true })

defineExpose({ refresh })

function priorityVariant(p: string) {
    return p === 'high'
        ? 'destructive'
        : p === 'medium'
          ? 'default'
          : 'secondary'
}

function statusVariant(s: string) {
    return s === 'resolved' || s === 'closed' ? 'secondary' : 'outline'
}

function formatDate(iso: string | null) {
    if (!iso) return '—'
    return new Date(iso).toLocaleString()
}

function openDetail(issue: Issue) {
    selectedIssue.value = issue
    dialogOpen.value = true
}

function onUpdated(updated: Issue) {
    const idx = issues.value.findIndex(i => i.id === updated.id)
    if (idx !== -1) issues.value[idx] = updated
}
</script>

<template>
    <Card>
        <CardHeader>
            <div class="flex items-start justify-between gap-4">
                <div>
                    <CardTitle>Issues</CardTitle>
                    <CardDescription>
                        {{ total }} total · click any row to view detail and update status
                    </CardDescription>
                </div>
                <Button variant="outline" size="sm" :disabled="loading" @click="refresh">
                    Refresh
                </Button>
            </div>
        </CardHeader>
        <CardContent>
            <div class="mb-4 grid gap-3 sm:grid-cols-4">
                <div class="grid gap-1.5">
                    <Label class="text-xs text-muted-foreground">Status</Label>
                    <Select v-model="filters.status">
                        <SelectTrigger><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All</SelectItem>
                            <SelectItem v-for="s in statuses" :key="s" :value="s">
                                {{ s }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="grid gap-1.5">
                    <Label class="text-xs text-muted-foreground">Priority</Label>
                    <Select v-model="filters.priority">
                        <SelectTrigger><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All</SelectItem>
                            <SelectItem v-for="p in priorities" :key="p" :value="p">
                                {{ p }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="grid gap-1.5">
                    <Label class="text-xs text-muted-foreground">Category</Label>
                    <Select v-model="filters.category">
                        <SelectTrigger><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All</SelectItem>
                            <SelectItem v-for="c in categories" :key="c.slug" :value="c.slug">
                                {{ c.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="grid gap-1.5">
                    <Label class="text-xs text-muted-foreground">Escalation</Label>
                    <Select v-model="filters.escalated">
                        <SelectTrigger><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="o in escalatedOptions"
                                :key="o.value"
                                :value="o.value"
                            >
                                {{ o.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <div class="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-12">ID</TableHead>
                            <TableHead>Title</TableHead>
                            <TableHead>Category</TableHead>
                            <TableHead>Priority</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Created</TableHead>
                            <TableHead class="w-20">Flag</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="loading">
                            <TableCell colspan="7" class="text-center text-muted-foreground">
                                Loading...
                            </TableCell>
                        </TableRow>
                        <TableRow v-else-if="issues.length === 0">
                            <TableCell colspan="7" class="text-center text-muted-foreground">
                                No issues match these filters.
                            </TableCell>
                        </TableRow>
                        <TableRow
                            v-else
                            v-for="issue in issues"
                            :key="issue.id"
                            class="cursor-pointer"
                            @click="openDetail(issue)"
                        >
                            <TableCell class="font-mono text-xs">#{{ issue.id }}</TableCell>
                            <TableCell class="max-w-md truncate font-medium">
                                {{ issue.title }}
                            </TableCell>
                            <TableCell>
                                <Badge variant="outline">{{ issue.category.name }}</Badge>
                            </TableCell>
                            <TableCell>
                                <Badge :variant="priorityVariant(issue.priority)">
                                    {{ issue.priority }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <Badge :variant="statusVariant(issue.status)">
                                    {{ issue.status }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-xs text-muted-foreground">
                                {{ formatDate(issue.created_at) }}
                            </TableCell>
                            <TableCell>
                                <Badge v-if="issue.is_escalated" variant="destructive">
                                    🚨
                                </Badge>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </CardContent>
    </Card>

    <IssueDetailDialog
        v-model:open="dialogOpen"
        :issue="selectedIssue"
        @updated="onUpdated"
    />
</template>
