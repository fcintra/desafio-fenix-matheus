<script setup>
import { ref, reactive } from 'vue'
import { api } from '../../api.js'

const emit = defineEmits(['created'])

const loading = ref(false)
const success = ref(false)
const error   = ref('')

const form = reactive({
    title:       '',
    description: '',
    questions:   [],
})

function addQuestion() {
    form.questions.push({
        text:         '',
        alternatives: [
            { text: '', is_correct: true  },
            { text: '', is_correct: false },
        ],
    })
}

function removeQuestion(qi) {
    form.questions.splice(qi, 1)
}

function addAlternative(qi) {
    form.questions[qi].alternatives.push({ text: '', is_correct: false })
}

function removeAlternative(qi, ai) {
    if (form.questions[qi].alternatives.length <= 2) return
    form.questions[qi].alternatives.splice(ai, 1)
}

function setCorrect(qi, ai) {
    form.questions[qi].alternatives.forEach((a, i) => {
        a.is_correct = i === ai
    })
}

async function submit() {
    loading.value = true
    error.value   = ''
    try {
        await api.post('/api/exams', form)
        success.value = true
        setTimeout(() => {
            success.value = false
            emit('created')
        }, 1200)
    } catch (e) {
        const errs  = e.response?.data?.errors
        error.value = errs
            ? Object.values(errs).flat()[0]
            : (e.response?.data?.message ?? 'Erro ao criar a prova.')
    } finally {
        loading.value = false
    }
}

// Start with one question
addQuestion()
</script>

<template>
    <div class="max-w-3xl mx-auto">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Criar Nova Prova</h1>

        <!-- Success banner -->
        <div
            v-if="success"
            class="flex items-center gap-2 bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-6 text-sm font-medium"
        >
            <span>✓</span> Prova criada com sucesso! Redirecionando…
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <!-- Title & Description -->
            <div class="bg-white rounded-2xl shadow-sm p-6 space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        Título da Prova <span class="text-red-400">*</span>
                    </label>
                    <input
                        v-model="form.title"
                        type="text"
                        required
                        placeholder="Ex.: Prova de PHP Avançado"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none
                               focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                    >
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Descrição</label>
                    <textarea
                        v-model="form.description"
                        rows="2"
                        placeholder="Instruções ou descrição opcional…"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none resize-none
                               focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                    ></textarea>
                </div>
            </div>

            <!-- Questions -->
            <div
                v-for="(question, qi) in form.questions"
                :key="qi"
                class="bg-white rounded-2xl shadow-sm p-6"
            >
                <!-- Question header -->
                <div class="flex items-center justify-between mb-4">
                    <span class="text-sm font-bold text-gray-500 uppercase tracking-wide">
                        Questão {{ qi + 1 }}
                    </span>
                    <button
                        type="button"
                        @click="removeQuestion(qi)"
                        class="text-xs text-red-400 hover:text-red-600 font-semibold transition-colors"
                    >Remover questão</button>
                </div>

                <!-- Question text -->
                <textarea
                    v-model="question.text"
                    rows="2"
                    required
                    :placeholder="`Enunciado da questão ${qi + 1}…`"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none resize-none mb-4
                           focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                ></textarea>

                <!-- Alternatives -->
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-2">
                    Alternativas — selecione a correta
                </p>
                <div class="space-y-2 mb-3">
                    <div
                        v-for="(alt, ai) in question.alternatives"
                        :key="ai"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg border-2 transition-colors"
                        :class="alt.is_correct
                            ? 'border-green-400 bg-green-50'
                            : 'border-gray-100 bg-gray-50 hover:border-gray-200'"
                    >
                        <!-- Correct radio -->
                        <input
                            type="radio"
                            :name="`correct-${qi}`"
                            :checked="alt.is_correct"
                            @change="setCorrect(qi, ai)"
                            class="w-4 h-4 text-green-600 cursor-pointer flex-shrink-0 accent-green-600"
                            title="Marcar como correta"
                        >

                        <!-- Text -->
                        <input
                            v-model="alt.text"
                            type="text"
                            required
                            :placeholder="`Alternativa ${String.fromCharCode(65 + ai)}…`"
                            class="flex-1 bg-transparent text-sm outline-none text-gray-700"
                        >

                        <!-- Remove -->
                        <button
                            v-if="question.alternatives.length > 2"
                            type="button"
                            @click="removeAlternative(qi, ai)"
                            class="text-gray-300 hover:text-red-400 text-xl leading-none flex-shrink-0 transition-colors"
                            title="Remover alternativa"
                        >×</button>
                    </div>
                </div>

                <button
                    type="button"
                    @click="addAlternative(qi)"
                    class="text-blue-600 hover:text-blue-700 text-sm font-semibold transition-colors"
                >+ Alternativa</button>
            </div>

            <!-- Add question button -->
            <button
                type="button"
                @click="addQuestion"
                class="w-full py-4 border-2 border-dashed border-gray-200 rounded-2xl text-gray-400
                       hover:border-blue-400 hover:text-blue-500 font-semibold text-sm transition-colors"
            >+ Adicionar Questão</button>

            <!-- Error -->
            <p
                v-if="error"
                class="text-red-600 text-sm bg-red-50 rounded-xl px-4 py-3 text-center"
            >{{ error }}</p>

            <!-- Submit -->
            <button
                type="submit"
                :disabled="loading || form.questions.length === 0 || success"
                class="w-full py-3.5 bg-blue-600 text-white rounded-xl font-bold text-sm
                       hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >{{ loading ? 'Criando…' : 'Criar Prova' }}</button>
        </form>
    </div>
</template>
