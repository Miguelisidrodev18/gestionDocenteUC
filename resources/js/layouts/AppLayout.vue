<script setup lang="ts">
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { usePage } from '@inertiajs/vue3';

interface Props {
    breadcrumbs?: BreadcrumbItem[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

// Add a type for auth to avoid 'unknown' type error
interface AuthUser {
    user: {
        role: string;
        // add other properties as needed
    };
    canChecklist?: boolean;
    // add other properties as needed
}

const pageProps = usePage().props as any;
const auth = pageProps.auth as AuthUser;
const canChecklist = Boolean(pageProps.canChecklist);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <slot />
    </AppLayout>
    <nav>
    <ul>
        <li v-if="auth.user.role === 'admin'">
            <a href="/dashboard">Dashboard</a>
            <a href="/docents">Docentes</a>
            <a v-if="canChecklist" href="/cursos/checklist">Checklist</a>
            <a href="/final">Final</a>
        </li>
        <li v-if="auth.user.role === 'responsable' || auth.user.role === 'docente'">
            <a v-if="canChecklist" href="/cursos/checklist">Checklist</a>
            <a href="/cursos">Cursos</a>
            <a v-if="auth.user.role === 'responsable'" href="/final">Final</a>
        </li>
        <li v-if="auth.user.role === 'docente'">
            <a href="/cursos">Cursos</a>
        </li>
    </ul>
    </nav>
</template>
