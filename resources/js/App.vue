<script setup lang="ts">
import { ref } from 'vue'
import IssueForm from './components/IssueForm.vue'
import IssuesTable from './components/IssuesTable.vue'
import HelpCenter from './components/HelpCenter.vue'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Toaster } from '@/components/ui/sonner'

const activeTab = ref('submit')
const issuesTableRef = ref<InstanceType<typeof IssuesTable> | null>(null)

function onIssueCreated() {
    issuesTableRef.value?.refresh()
}
</script>

<template>
    <div class="min-h-screen bg-muted/30">
        <header class="border-b bg-background">
            <div class="mx-auto max-w-6xl px-4 py-6">
                <h1 class="text-xl font-semibold">Issue Intake System</h1>
                <p class="text-sm text-muted-foreground">
                    Submit issues, browse the queue, and get auto-classified summaries
                    powered by a rules engine + optional LLM.
                </p>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-6">
            <Tabs v-model:model-value="activeTab" class="grid gap-6">
                <TabsList class="grid w-full max-w-md grid-cols-3">
                    <TabsTrigger value="submit">Submit issue</TabsTrigger>
                    <TabsTrigger value="issues">Issues</TabsTrigger>
                    <TabsTrigger value="help">Help center</TabsTrigger>
                </TabsList>

                <TabsContent value="submit">
                    <IssueForm @created="onIssueCreated" />
                </TabsContent>

                <TabsContent value="issues">
                    <IssuesTable ref="issuesTableRef" />
                </TabsContent>

                <TabsContent value="help">
                    <HelpCenter />
                </TabsContent>
            </Tabs>
        </main>

        <Toaster position="top-right" rich-colors />
    </div>
</template>
