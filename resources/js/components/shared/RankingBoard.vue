<script setup>
import { ref, watch, onMounted } from 'vue'
import { api } from '../../api.js'

const globalRanking = ref([])
const examRanking   = ref([])
const exams         = ref([])
const selectedExam  = ref('')
const loadingGlobal = ref(true)
const loadingExam   = ref(false)

onMounted(async () => {
    try {
        const [globalRes, examsRes] = await Promise.all([
            api.get('/api/ranking'),
            api.get('/api/exams'),
        ])
        globalRanking.value = globalRes.data.data.leaderboard
        exams.value         = examsRes.data.data
    } finally {
        loadingGlobal.value = false
    }
})

watch(selectedExam, async (examId) => {
    if (!examId) { examRanking.value = []; return }
    loadingExam.value = true
    try {
        const { data } = await api.get(`/api/exams/${examId}/ranking`)
        examRanking.value = data.data.leaderboard
    } finally {
        loadingExam.value = false
    }
})

const medalColors = [
    'bg-yellow-400 text-white',   // 1st
    'bg-gray-400  text-white',    // 2nd
    'bg-orange-400 text-white',   // 3rd
]
function medalClass(i) {
    return medalColors[i] ?? 'bg-gray-100 text-gray-600'
}
</script>

<template>
    <div class="space-y-6">
        <!-- Global ranking -->
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h3 class="text-base font-bold text-gray-900 mb-4">🏆 Ranking Global (Top 10)</h3>

            <div v-if="loadingGlobal" class="text-gray-400 text-sm text-center py-6">Carregando…</div>

            <div v-else-if="globalRanking.length === 0" class="text-gray-400 text-sm text-center py-6">
                Nenhuma pontuação registrada ainda.
            </div>

            <ol v-else class="space-y-2">
                <li
                    v-for="(entry, i) in globalRanking"
                    :key="entry.user_id"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-colors"
                    :class="i === 0 ? 'bg-yellow-50' : i === 1 ? 'bg-gray-50' : i === 2 ? 'bg-orange-50' : 'hover:bg-gray-50'"
                >
                    <span
                        class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
                        :class="medalClass(i)"
                    >{{ i + 1 }}</span>
                    <span class="flex-1 text-sm font-medium text-gray-700">Usuário #{{ entry.user_id }}</span>
                    <span class="text-sm font-bold text-gray-900">{{ entry.total_score }} pts</span>
                </li>
            </ol>
        </div>

        <!-- Per-exam ranking -->
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h3 class="text-base font-bold text-gray-900 mb-4">📊 Ranking por Prova</h3>

            <select
                v-model="selectedExam"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm outline-none mb-4
                       focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
            >
                <option value="">Selecione uma prova…</option>
                <option
                    v-for="exam in exams"
                    :key="exam.id"
                    :value="exam.id"
                >{{ exam.title }}</option>
            </select>

            <div v-if="loadingExam" class="text-gray-400 text-sm text-center py-4">Carregando…</div>

            <div
                v-else-if="selectedExam && examRanking.length === 0"
                class="text-gray-400 text-sm text-center py-4"
            >Nenhuma tentativa para esta prova.</div>

            <ol v-else-if="examRanking.length" class="space-y-2">
                <li
                    v-for="(entry, i) in examRanking"
                    :key="entry.user_id"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl"
                    :class="i === 0 ? 'bg-yellow-50' : 'hover:bg-gray-50'"
                >
                    <span
                        class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
                        :class="medalClass(i)"
                    >{{ i + 1 }}</span>
                    <span class="flex-1 text-sm font-medium text-gray-700">Usuário #{{ entry.user_id }}</span>
                    <span class="text-sm font-bold text-blue-600">{{ entry.percentage }}%</span>
                </li>
            </ol>
        </div>
    </div>
</template>
