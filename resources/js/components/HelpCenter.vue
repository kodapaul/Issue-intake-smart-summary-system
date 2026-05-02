<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { toast } from 'vue-sonner'
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion'
import { Badge } from '@/components/ui/badge'
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { api } from '@/api'
import type { PlaybookEntry } from '@/types'

const entries = ref<PlaybookEntry[]>([])
const loading = ref(true)
const search = ref('')
const selectedSlug = ref<string | null>(null)

onMounted(async () => {
    try {
        const res = await api.listPlaybook()
        entries.value = res.data
        if (res.data.length > 0) selectedSlug.value = res.data[0].slug
    } catch (e) {
        toast.error('Could not load knowledge base')
        console.error(e)
    } finally {
        loading.value = false
    }
})

const filteredEntries = computed(() => {
    const q = search.value.trim().toLowerCase()
    if (!q) return entries.value
    return entries.value.filter(
        e =>
            e.name.toLowerCase().includes(q) ||
            e.description.toLowerCase().includes(q),
    )
})

const selected = computed(() =>
    entries.value.find(e => e.slug === selectedSlug.value),
)

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
            <CardTitle>How can we help you?</CardTitle>
            <CardDescription>
                Browse common topics, troubleshooting steps, and FAQs from our
                support knowledge base.
            </CardDescription>
        </CardHeader>
        <CardContent>
            <div class="mb-6 grid gap-2 sm:max-w-md">
                <Label for="kb-search">Search topics</Label>
                <Input
                    id="kb-search"
                    v-model="search"
                    placeholder="login, payment, delivery..."
                />
            </div>

            <div v-if="loading" class="text-muted-foreground">Loading...</div>

            <div v-else class="grid gap-6 md:grid-cols-[260px_1fr]">
                <nav class="border-r-0 md:border-r md:pr-4">
                    <ul class="grid gap-1 text-sm">
                        <li v-for="entry in filteredEntries" :key="entry.slug">
                            <button
                                type="button"
                                class="w-full rounded-md px-3 py-2 text-left transition hover:bg-accent"
                                :class="{
                                    'bg-accent font-medium text-accent-foreground':
                                        entry.slug === selectedSlug,
                                }"
                                @click="selectedSlug = entry.slug"
                            >
                                {{ entry.name }}
                            </button>
                        </li>
                        <li
                            v-if="filteredEntries.length === 0"
                            class="px-3 py-2 text-muted-foreground"
                        >
                            No matches.
                        </li>
                    </ul>
                </nav>

                <article v-if="selected" class="grid gap-6">
                    <header class="grid gap-2">
                        <h2 class="text-xl font-semibold">{{ selected.name }}</h2>
                        <p class="text-sm text-muted-foreground">
                            {{ selected.description }}
                        </p>
                        <div class="flex flex-wrap gap-2 text-xs">
                            <Badge v-if="selected.priority_hint" :variant="priorityVariant(selected.priority_hint)">
                                {{ selected.priority_hint }} severity
                            </Badge>
                            <Badge v-if="selected.category_hint" variant="outline">
                                {{ selected.category_hint }}
                            </Badge>
                        </div>
                    </header>

                    <section class="grid gap-2">
                        <h3 class="text-sm font-semibold">Standard summary</h3>
                        <p class="text-sm text-muted-foreground">
                            {{ selected.summary_template }}
                        </p>
                    </section>

                    <section class="grid gap-2">
                        <h3 class="text-sm font-semibold">Suggested action</h3>
                        <p class="text-sm text-muted-foreground">
                            {{ selected.suggested_action }}
                        </p>
                    </section>

                    <section v-if="selected.troubleshooting_steps.length" class="grid gap-2">
                        <h3 class="text-sm font-semibold">Troubleshooting steps</h3>
                        <ol class="list-decimal space-y-1 pl-5 text-sm text-muted-foreground">
                            <li v-for="(step, i) in selected.troubleshooting_steps" :key="i">
                                {{ step }}
                            </li>
                        </ol>
                    </section>

                    <section v-if="selected.faqs.length" class="grid gap-2">
                        <h3 class="text-sm font-semibold">Frequently asked questions</h3>
                        <Accordion type="single" collapsible>
                            <AccordionItem
                                v-for="(faq, i) in selected.faqs"
                                :key="i"
                                :value="`faq-${i}`"
                            >
                                <AccordionTrigger>{{ faq.q }}</AccordionTrigger>
                                <AccordionContent>{{ faq.a }}</AccordionContent>
                            </AccordionItem>
                        </Accordion>
                    </section>
                </article>
            </div>
        </CardContent>
    </Card>
</template>
