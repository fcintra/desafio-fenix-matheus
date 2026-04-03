<script setup>
import { ref, onMounted } from 'vue'
import { api } from '../../api.js'

const emit = defineEmits(['start'])

const exams    = ref([])
const loading  = ref(true)
const starting = ref(null) // exam id being loaded

onMounted(async () => {
    try {
        const { data } = await api.get('/api/exams')
        exams.value = data.data
    } finally {
        loading.value = false
    }
})

async function startExam(exam) {
    starting.value = exam.id
    try {
        const { data } = await api.get(`/api/exams/${exam.id}`)
        emit('start', data.data)
    } catch {
        starting.value = null
    }
}
</script>

<template>
    <div>
        <h2 class="text-lg font-bold text-gray-900 mb-4">Provas Disponíveis</h2>

        <!-- Loading -->
        <div v-if="loading" class="bg-white rounded-2xl shadow-sm p-12 text-center text-gray-400 text-sm">
            Carregando…
        </div>

        <!-- Empty -->
        <div
            v-else-if="exams.length === 0"
            class="bg-white rounded-2xl shadow-sm p-12 text-center"
        >
            <p class="text-gray-400 text-sm">Nenhuma prova disponível no momento.</p>
        </div>

        <!-- Grid -->
        <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="exam in exams"
                :key="exam.id"
                class="bg-white rounded-2xl shadow-sm p-6 flex flex-col"
            >
                <!-- Info -->
                <div class="flex-1">
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <h3 class="font-bold text-gray-900 leading-snug">{{ exam.title }}</h3>
                        <span
                            v-if="exam.has_attempted"
                            class="text-xs bg-green-50 text-green-600 font-bold px-2 py-0.5 rounded-full flex-shrink-0"
                        >Feita</span>
                    </div>
                    <p
                        v-if="exam.description"
                        class="text-sm text-gray-500 line-clamp-2 mb-3"
                    >{{ exam.description }}</p>
                    <p class="text-xs text-gray-400">
                        {{ exam.question_count }} questão(ões)
                    </p>
                </div>

                <!-- Action -->
                <div class="mt-4">
                    <div
                        v-if="exam.has_attempted"
                        class="w-full text-center py-2.5 text-sm text-gray-400 bg-gray-50 rounded-xl font-semibold"
                    >✓ Já realizada</div>
                    <button
                        v-else
                        @click="startExam(exam)"
                        :disabled="starting === exam.id"
                        class="w-full py-2.5 bg-blue-600 text-white rounded-xl text-sm font-bold
                               hover:bg-blue-700 disabled:opacity-60 disabled:cursor-wait transition-colors"
                    >{{ starting === exam.id ? 'Carregando…' : 'Iniciar Prova' }}</button>
                </div>
            </div>
        </div>
    </div>
</template>
