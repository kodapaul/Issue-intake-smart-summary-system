<script setup lang="ts">
import { ref, watch } from 'vue'
import { toast } from 'vue-sonner'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import { Label } from '@/components/ui/label'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import { api, isValidationError } from '@/api'
import type { Issue, Status } from '@/types'

const props = defineProps<{
    open: boolean
    issue: Issue | null
}>()

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void
    (e: 'updated', issue: Issue): void
}>()

const newStatus = ref<Status | ''>('')
const saving = ref(false)
const statuses: Status[] = ['open', 'in_progress', 'resolved', 'closed']

watch(
    () => props.issue,
    issue => {
        newStatus.value = issue?.status ?? ''
    },
    { immediate: true },
)

async function save() {
    if (!props.issue || !newStatus.value || newStatus.value === props.issue.status)
        return
    saving.value = true
    try {
        const res = await api.updateIssue(props.issue.id, {
            status: newStatus.value,
            if_unmodified_since: props.issue.updated_at,
        })
        emit('updated', res.data)
        toast.success(`Status updated to ${res.data.status}`)
        emit('update:open', false)
    } catch (e) {
        if (isValidationError(e)) {
            toast.error('Validation failed', {
                description: Object.values(e.payload.errors).flat().join(', '),
            })
        } else if (e instanceof Error) {
            toast.error('Update failed', { description: e.message })
        }
    } finally {
        saving.value = false
    }
}

function priorityVariant(p: string) {
    return p === 'high'
        ? 'destructive'
        : p === 'medium'
          ? 'default'
          : 'secondary'
}

function formatDate(iso: string | null) {
    if (!iso) return '—'
    return new Date(iso).toLocaleString()
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent v-if="issue" class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <span>Issue #{{ issue.id }}</span>
                    <Badge :variant="priorityVariant(issue.priority)">
                        {{ issue.priority }}
                    </Badge>
                    <Badge v-if="issue.is_escalated" variant="destructive">
                        escalated
                    </Badge>
                </DialogTitle>
                <DialogDescription>
                    Created {{ formatDate(issue.created_at) }} ·
                    Category: {{ issue.category.name }}
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-4 max-h-[60vh] overflow-y-auto pr-1">
                <div>
                    <h3 class="text-sm font-semibold">Title</h3>
                    <p class="text-sm">{{ issue.title }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold">Description</h3>
                    <p class="whitespace-pre-line text-sm text-muted-foreground">
                        {{ issue.description }}
                    </p>
                </div>

                <div v-if="issue.summary">
                    <h3 class="text-sm font-semibold">Auto-generated summary</h3>
                    <p class="text-sm text-muted-foreground">{{ issue.summary }}</p>
                </div>

                <div v-if="issue.suggested_action">
                    <h3 class="text-sm font-semibold">Suggested next action</h3>
                    <p class="text-sm text-muted-foreground">
                        {{ issue.suggested_action }}
                    </p>
                </div>

                <div class="grid gap-2 text-sm sm:grid-cols-2">
                    <div>
                        <span class="text-muted-foreground">Issuer:</span>
                        {{ issue.issuer ?? '—' }}
                    </div>
                    <div>
                        <span class="text-muted-foreground">Email:</span>
                        {{ issue.issuer_email ?? '—' }}
                    </div>
                    <div>
                        <span class="text-muted-foreground">Due date:</span>
                        {{ formatDate(issue.due_date) }}
                    </div>
                    <div>
                        <span class="text-muted-foreground">Escalated at:</span>
                        {{ formatDate(issue.escalated_at) }}
                    </div>
                </div>

                <div class="border-t pt-4">
                    <Label class="text-sm font-semibold">Update status</Label>
                    <p class="mb-2 text-xs text-muted-foreground">
                        Uses optimistic locking — a 409 fires if the issue was modified elsewhere.
                    </p>
                    <Select v-model="newStatus">
                        <SelectTrigger>
                            <SelectValue placeholder="Pick a new status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="s in statuses" :key="s" :value="s">
                                {{ s }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="emit('update:open', false)">
                    Close
                </Button>
                <Button
                    :disabled="
                        saving ||
                        !newStatus ||
                        newStatus === issue.status
                    "
                    @click="save"
                >
                    {{ saving ? 'Saving...' : 'Save status' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
