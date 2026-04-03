<script setup>
import { ref } from 'vue'
import ExamCreator   from '../components/professor/ExamCreator.vue'
import ExamDashboard from '../components/professor/ExamDashboard.vue'

defineProps(['user'])

const tab        = ref('dashboard')
const refreshKey = ref(0)

function onExamCreated() {
    refreshKey.value++
    tab.value = 'dashboard'
}
</script>

<template>
    <div>
        <!-- Tabs -->
        <nav class="flex gap-1 mb-6 border-b border-gray-200">
            <button
                v-for="t in [
                    { id: 'dashboard', label: '📊 Dashboard' },
                    { id: 'create',    label: '+ Criar Prova' },
                ]"
                :key="t.id"
                @click="tab = t.id"
                class="px-5 py-2.5 text-sm font-semibold transition-colors border-b-2 -mb-px"
                :class="tab === t.id
                    ? 'border-blue-600 text-blue-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
            >{{ t.label }}</button>
        </nav>

        <ExamDashboard v-if="tab === 'dashboard'" :key="refreshKey" />
        <ExamCreator   v-if="tab === 'create'"    @created="onExamCreated" />
    </div>
</template>
