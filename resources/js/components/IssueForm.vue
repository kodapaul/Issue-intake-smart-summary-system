<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { toast } from 'vue-sonner'
import { Button } from '@/components/ui/button'
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import { Badge } from '@/components/ui/badge'
import { api, isValidationError } from '@/api'
import type { Category, CreateIssuePayload, Issue } from '@/types'

const emit = defineEmits<{
    (e: 'created', issue: Issue): void
}>()

const categories = ref<Category[]>([])
const loadingCategories = ref(true)

const form = ref<CreateIssuePayload>({
    title: '',
    description: '',
    category: '',
    issuer: '',
    issuer_email: '',
})

const submitting = ref(false)
const errors = ref<Record<string, string[]>>({})
const lastCreated = ref<Issue | null>(null)

const formInvalid = computed(
    () =>
        !form.value.title.trim() ||
        !form.value.description.trim() ||
        !form.value.category,
)

onMounted(async () => {
    try {
        const res = await api.listCategories()
        categories.value = res.data
    } catch (e) {
        toast.error('Could not load categories')
        console.error(e)
    } finally {
        loadingCategories.value = false
    }
})

async function submit() {
    submitting.value = true
    errors.value = {}
    try {
        const payload: CreateIssuePayload = {
            title: form.value.title.trim(),
            description: form.value.description.trim(),
            category: form.value.category,
        }
        if (form.value.issuer?.trim()) payload.issuer = form.value.issuer.trim()
        if (form.value.issuer_email?.trim())
            payload.issuer_email = form.value.issuer_email.trim()

        const res = await api.createIssue(payload)
        lastCreated.value = res.data
        emit('created', res.data)
        toast.success('Issue submitted', {
            description: `Auto-classified as ${res.data.category.name}`,
        })
        form.value = {
            title: '',
            description: '',
            category: '',
            issuer: '',
            issuer_email: '',
        }
    } catch (e) {
        if (isValidationError(e)) {
            errors.value = e.payload.errors
            toast.error('Please fix the highlighted fields')
        } else {
            toast.error('Submission failed')
            console.error(e)
        }
    } finally {
        submitting.value = false
    }
}

function priorityVariant(p: string | null | undefined) {
    return p === 'high'
        ? 'destructive'
        : p === 'medium'
          ? 'default'
          : 'secondary'
}
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Submit a new issue</CardTitle>
            <CardDescription>
                Fill in the details. The system will auto-classify, summarize,
                and suggest a next action.
            </CardDescription>
        </CardHeader>
        <CardContent>
            <form class="grid gap-5" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="title">Title <span class="text-destructive">*</span></Label>
                    <Input
                        id="title"
                        v-model="form.title"
                        placeholder="Briefly describe the problem"
                        :class="{ 'border-destructive': errors.title }"
                    />
                    <p v-if="errors.title" class="text-sm text-destructive">
                        {{ errors.title[0] }}
                    </p>
                </div>

                <div class="grid gap-2">
                    <Label for="description">Description <span class="text-destructive">*</span></Label>
                    <Textarea
                        id="description"
                        v-model="form.description"
                        rows="5"
                        placeholder="What happened, what did you expect, what did you try?"
                        :class="{ 'border-destructive': errors.description }"
                    />
                    <p v-if="errors.description" class="text-sm text-destructive">
                        {{ errors.description[0] }}
                    </p>
                </div>

                <div class="grid gap-2">
                    <Label for="category">Category <span class="text-destructive">*</span></Label>
                    <Select v-model="form.category" :disabled="loadingCategories">
                        <SelectTrigger
                            id="category"
                            :class="{ 'border-destructive': errors.category }"
                        >
                            <SelectValue placeholder="Pick the type of issue" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="c in categories"
                                :key="c.slug"
                                :value="c.slug"
                            >
                                {{ c.name }}
                                <span class="ml-2 text-xs text-muted-foreground">
                                    ({{ c.severity_level }} severity)
                                </span>
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p v-if="errors.category" class="text-sm text-destructive">
                        {{ errors.category[0] }}
                    </p>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="issuer">Your name (optional)</Label>
                        <Input
                            id="issuer"
                            v-model="form.issuer"
                            placeholder="Jane Doe"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="issuer_email">Your email (optional)</Label>
                        <Input
                            id="issuer_email"
                            v-model="form.issuer_email"
                            type="email"
                            placeholder="jane@example.com"
                            :class="{ 'border-destructive': errors.issuer_email }"
                        />
                        <p v-if="errors.issuer_email" class="text-sm text-destructive">
                            {{ errors.issuer_email[0] }}
                        </p>
                    </div>
                </div>

                <Button type="submit" :disabled="formInvalid || submitting">
                    {{ submitting ? 'Submitting...' : 'Submit issue' }}
                </Button>
            </form>

            <div v-if="lastCreated" class="mt-8 grid gap-3 rounded-lg border bg-muted/40 p-4">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium">Issue #{{ lastCreated.id }} created</span>
                    <Badge :variant="priorityVariant(lastCreated.priority)">
                        {{ lastCreated.priority }} priority
                    </Badge>
                    <Badge v-if="lastCreated.is_escalated" variant="destructive">
                        escalated
                    </Badge>
                </div>
                <div v-if="lastCreated.summary" class="text-sm">
                    <p class="font-medium text-foreground">Auto-generated summary</p>
                    <p class="text-muted-foreground">{{ lastCreated.summary }}</p>
                </div>
                <div v-if="lastCreated.suggested_action" class="text-sm">
                    <p class="font-medium text-foreground">Suggested next action</p>
                    <p class="text-muted-foreground">{{ lastCreated.suggested_action }}</p>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
