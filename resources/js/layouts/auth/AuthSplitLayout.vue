<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const name = page.props.name;
const quote = page.props.quote;

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="min-h-dvh lg:grid lg:grid-cols-2">
        <div class="relative hidden h-full flex-col justify-between overflow-hidden p-10 text-white lg:flex bg-[url('/images/login-left.jpg')] bg-cover bg-center">
            <div class="absolute inset-0 bg-black/60"></div>
            <Link :href="route('home')" class="relative z-10 flex items-center text-lg font-medium">
                <AppLogoIcon class="mr-6 size-20 fill-current text-white" />
                Portafolio Docente – Ingenieria de Sistemas
            </Link>
            <div class="relative z-10 mt-auto">
                <blockquote class="space-y-2">
                    <p class="text-lg">&ldquo;La excelencia docente nace de la mejora continua y el compromiso con el aprendizaje.&rdquo;</p>
                    <footer class="text-sm text-white/80">Equipo Academico</footer>
                </blockquote>
            </div>
        </div>
        <div class="relative flex min-h-dvh items-center justify-center overflow-hidden bg-[#6d28d9] px-6 py-12">
            <div class="absolute -bottom-24 -right-24 h-72 w-72 rounded-full border border-white/35"></div>
            <div class="absolute -bottom-40 -right-40 h-96 w-96 rounded-full border border-white/25"></div>
            <div class="relative w-full max-w-sm rounded-3xl bg-white px-8 py-10 shadow-2xl">
                <div class="mb-6 space-y-1 text-left">
                    <h1 class="text-2xl font-semibold text-slate-900" v-if="title">{{ title }}</h1>
                    <p class="text-sm text-slate-500" v-if="description">{{ description }}</p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
