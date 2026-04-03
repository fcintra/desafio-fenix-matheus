<script setup>
import { ref, computed } from 'vue'
import { api } from '../../api.js'

const props = defineProps(['exam'])
const emit  = defineEmits(['submitted', 'back'])

// { [questionId]: alternativeId }
const answers = ref({})
const loading  = ref(false)
const error    = ref('')

const total        = computed(() => props.exam?.questions?.length ?? 0)
const answeredCount = computed(() => Object.keys(answers.value).length)
const allAnswered   = computed(() => answeredCount.value === total.value && total.value > 0)
const progress      = computed(() => total.value ? (answeredCount.value / total.value) * 100 : 0)

function selectAnswer(questionId, altId) {
    answers.value = { ...answers.value, [questionId]: altId }
}

async function submit() {
    loading.value = true
    error.value   = ''
    try {
        const { data } = await api.post(`/api/exams/${props.exam.id}/submit`, {
            answers: answers.value,
        })
        emit('submitted', data.data)
    } catch (e) {
        error.value =
            e.response?.data?.errors?.exam?.[0] ??
            e.response?.data?.message ??
            'Erro ao enviar as respostas.'
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <div class="max-w-2xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <button
                @click="$emit('back')"
                class="text-blue-600 hover:text-blue-700 text-sm font-semibold mb-3 flex items-center gap-1 transition-colors"
            >← Voltar</button>

            <h1 class="text-2xl font-bold text-gray-900">{{ exam.title }}</h1>
            <p v-if="exam.description" class="text-gray-500 text-sm mt-1">{{ exam.description }}</p>

            <!-- Progress bar -->
            <div class="mt-4 flex items-center gap-3">
                <div class="flex-1 bg-gray-200 rounded-full h-2 overflow-hidden">
                    <div
                        class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                        :style="`width: ${progress}%`"
                    ></div>
                </div>
                <span class="text-sm font-semibold text-gray-500 flex-shrink-0">
                    {{ answeredCount }}/{{ total }}
                </span>
            </div>
        </div>

        <!-- Questions -->
        <div class="space-y-4 mb-6">
            <div
                v-for="(question, qi) in exam.questions"
                :key="question.id"
                class="bg-white rounded-2xl shadow-sm p-6"
            >
                <p class="font-semibold text-gray-900 mb-4 leading-snug">
                    <span class="text-blue-500 font-bold mr-1.5">{{ qi + 1 }}.</span>
                    {{ question.text }}
                </p>

                <div class="space-y-2">
                    <button
                        v-for="alt in question.alternatives"
                        :key="alt.id"
                        type="button"
                        @click="selectAnswer(question.id, alt.id)"
                        class="w-full flex items-start gap-3 px-4 py-3 rounded-xl border-2 text-left transition-all"
                        :class="answers[question.id] === alt.id
                            ? 'border-blue-500 bg-blue-50'
                            : 'border-gray-100 bg-gray-50 hover:border-gray-200 hover:bg-gray-100'"
                    >
                        <!-- Radio visual -->
                        <span
                            class="w-5 h-5 rounded-full border-2 flex-shrink-0 mt-0.5 flex items-center justify-center transition-colors"
                            :class="answers[question.id] === alt.id
                                ? 'border-blue-500 bg-blue-500'
                                : 'border-gray-300'"
                        >
                            <span
                                v-if="answers[question.id] === alt.id"
                                class="w-2 h-2 bg-white rounded-full"
                            ></span>
                        </span>
                        <span class="text-sm text-gray-700 leading-relaxed">{{ alt.text }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Error -->
        <p
            v-if="error"
            class="text-red-600 text-sm bg-red-50 rounded-xl px-4 py-3 text-center mb-4"
        >{{ error }}</p>

        <!-- Submit button -->
        <button
            @click="submit"
            :disabled="!allAnswered || loading"
            class="w-full py-4 rounded-2xl font-bold text-sm transition-colors"
            :class="allAnswered && !loading
                ? 'bg-blue-600 text-white hover:bg-blue-700'
                : 'bg-gray-200 text-gray-400 cursor-not-allowed'"
        >
            <span v-if="loading">Enviando…</span>
            <span v-else-if="allAnswered">Enviar Respostas</span>
            <span v-else>Responda todas as questões ({{ answeredCount }}/{{ total }})</span>
        </button>
    </div>
</template>
