<script setup>
import { ref, onMounted } from 'vue'
import { api } from '../../api.js'
import RankingBoard from '../shared/RankingBoard.vue'

const exams   = ref([])
const loading = ref(true)

onMounted(async () => {
    try {
        const { data } = await api.get('/api/exams')
        exams.value = data.data
    } finally {
        loading.value = false
    }
})
</script>

<template>
    <div class="space-y-8">
        <!-- Exams list -->
        <section>
            <h2 class="text-lg font-bold text-gray-900 mb-4">Provas Cadastradas</h2>

            <div v-if="loading" class="bg-white rounded-2xl shadow-sm p-12 text-center text-gray-400 text-sm">
                Carregando…
            </div>

            <div
                v-else-if="exams.length === 0"
                class="bg-white rounded-2xl shadow-sm p-12 text-center"
            >
                <p class="text-gray-400 text-sm">Nenhuma prova cadastrada.</p>
                <p class="text-gray-400 text-sm mt-1">Clique em <strong>+ Criar Prova</strong> para começar.</p>
            </div>

            <div v-else class="grid gap-3 sm:grid-cols-2">
                <div
                    v-for="exam in exams"
                    :key="exam.id"
                    class="bg-white rounded-2xl shadow-sm p-5 flex items-start justify-between gap-4"
                >
                    <div class="min-w-0">
                        <h3 class="font-semibold text-gray-900 truncate">{{ exam.title }}</h3>
                        <p v-if="exam.description" class="text-sm text-gray-500 mt-0.5 truncate">
                            {{ exam.description }}
                        </p>
                        <div class="flex items-center gap-3 mt-2 text-xs text-gray-400">
                            <span>{{ exam.question_count }} questão(ões)</span>
                            <span>·</span>
                            <span>{{ new Date(exam.created_at).toLocaleDateString('pt-BR') }}</span>
                        </div>
                    </div>
                    <span class="text-xs bg-blue-50 text-blue-600 font-bold px-2.5 py-1 rounded-full flex-shrink-0">
                        #{{ exam.id }}
                    </span>
                </div>
            </div>
        </section>

        <!-- Rankings -->
        <RankingBoard />
    </div>
</template>
