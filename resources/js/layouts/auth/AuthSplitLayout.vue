<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const page = usePage();
const name = page.props.name;
const quote = page.props.quote;

// Simple ripple click effect on the left panel
type Ripple = { id: number; x: number; y: number };
const ripples = ref<Ripple[]>([]);
function addRipple(e: MouseEvent) {
    const el = e.currentTarget as HTMLElement;
    const rect = el.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;
    const id = Date.now() + Math.random();
    ripples.value.push({ id, x, y });
    setTimeout(() => {
        ripples.value = ripples.value.filter((r) => r.id !== id);
    }, 600);
}

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
        <div class="relative hidden h-full flex-col p-10 text-white dark:border-r lg:flex bg-[url('/images/login-left.jpg')] bg-cover bg-center" @click="addRipple">
            <div class="absolute inset-0 bg-black/60" />
            <!-- Ripple elements -->
            <span v-for="r in ripples" :key="r.id" class="pointer-events-none absolute -translate-x-1/2 -translate-y-1/2 animate-ripple rounded-full bg-white/30" :style="{ left: r.x + 'px', top: r.y + 'px' }" />
            <Link :href="route('home')" class="relative z-20 flex items-center text-lg font-medium">
                <AppLogoIcon class="mr-6 size-20 fill-current text-white" />
                {{ name }}
            </Link>
            <div v-if="quote" class="relative z-20 mt-auto">
                <blockquote class="space-y-2">
                    <p class="text-lg">&ldquo;{{ quote.message }}&rdquo;</p>
                    <footer class="text-sm text-neutral-300">{{ quote.author }}</footer>
                </blockquote>
            </div>
        </div>
        <div class="lg:p-8">
            <div class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]">
                <div class="flex flex-col space-y-2 text-center">
                    <h1 class="text-xl font-medium tracking-tight" v-if="title">{{ title }}</h1>
                    <p class="text-sm text-muted-foreground" v-if="description">{{ description }}</p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes ripple {
  0% { transform: translate(-50%, -50%) scale(0); opacity: .6; }
  100% { transform: translate(-50%, -50%) scale(12); opacity: 0; }
}
.animate-ripple {
  width: 12px;
  height: 12px;
  animation: ripple .6s ease-out forwards;
}
</style>
