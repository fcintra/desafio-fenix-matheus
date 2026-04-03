<script setup>
import { ref } from 'vue'
import ExamBrowser  from '../components/student/ExamBrowser.vue'
import ExamTaker    from '../components/student/ExamTaker.vue'
import RankingBoard from '../components/shared/RankingBoard.vue'

defineProps(['user'])

// 'browse' | 'taking' | 'result'
const screen      = ref('browse')
const selectedExam = ref(null)
const lastResult   = ref(null)
const tab          = ref('exams') // 'exams' | 'ranking'

function startExam(exam) {
    selectedExam.value = exam
    screen.value       = 'taking'
}

function onSubmitted(result) {
    lastResult.value = result
    screen.value     = 'result'
    tab.value        = 'exams'
}

function backToBrowse() {
    selectedExam.value = null
    screen.value       = 'browse'
}
</script>

<template>
    <div>
        <!-- Top tabs (hidden while taking exam) -->
        <nav v-if="screen !== 'taking'" class="flex gap-1 mb-6 border-b border-gray-200">
            <button
                v-for="t in [
                    { id: 'exams',   label: '📝 Provas' },
                    { id: 'ranking', label: '🏆 Rankings' },
                ]"
                :key="t.id"
                @click="tab = t.id; screen = 'browse'"
                class="px-5 py-2.5 text-sm font-semibold transition-colors border-b-2 -mb-px"
                :class="tab === t.id
                    ? 'border-blue-600 text-blue-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
            >{{ t.label }}</button>
        </nav>

        <!-- Exam browser -->
        <ExamBrowser
            v-if="screen === 'browse' && tab === 'exams'"
            @start="startExam"
        />

        <!-- Ranking board -->
        <RankingBoard v-if="tab === 'ranking' && screen !== 'taking'" />

        <!-- Taking the exam -->
        <ExamTaker
            v-if="screen === 'taking'"
            :exam="selectedExam"
            @submitted="onSubmitted"
            @back="backToBrowse"
        />

        <!-- Result card -->
        <div v-if="screen === 'result' && lastResult" class="max-w-lg mx-auto">
            <div class="bg-white rounded-2xl shadow-sm p-8 text-center">
                <div
                    class="w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-6 text-5xl"
                    :class="lastResult.passed ? 'bg-green-100' : 'bg-orange-100'"
                >
                    {{ lastResult.passed ? '🎉' : '📖' }}
                </div>

                <h2 class="text-2xl font-bold text-gray-900 mb-1">
                    {{ lastResult.passed ? 'Parabéns!' : 'Continue estudando!' }}
                </h2>
                <p class="text-gray-500 mb-6">{{ lastResult.exam.title }}</p>

                <!-- Stats -->
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="bg-gray-50 rounded-xl p-4">
                        <p class="text-xs text-gray-500 font-medium uppercase tracking-wide mb-1">Pontuação</p>
                        <p class="text-3xl font-bold text-gray-900">{{ lastResult.score }}</p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-4">
                        <p class="text-xs text-gray-500 font-medium uppercase tracking-wide mb-1">Aproveitamento</p>
                        <p
                            class="text-3xl font-bold"
                            :class="lastResult.passed ? 'text-green-600' : 'text-red-500'"
                        >{{ lastResult.percentage }}%</p>
                    </div>
                </div>

                <span
                    class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-sm font-semibold mb-6"
                    :class="lastResult.passed
                        ? 'bg-green-100 text-green-700'
                        : 'bg-red-100 text-red-600'"
                >
                    <span>{{ lastResult.passed ? '✓' : '✗' }}</span>
                    {{ lastResult.passed ? 'Aprovado (nota mínima 60%)' : 'Reprovado (abaixo de 60%)' }}
                </span>

                <button
                    @click="backToBrowse"
                    class="w-full py-3 border border-gray-200 rounded-xl text-gray-700
                           hover:bg-gray-50 font-semibold text-sm transition-colors"
                >Ver outras provas</button>
            </div>
        </div>
    </div>
</template>
