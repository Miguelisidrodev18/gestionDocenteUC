<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, ClipboardCheck, Folder, LayoutGrid } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { User } from 'lucide-vue-next';
import { LibraryBig } from 'lucide-vue-next';
import { CalendarClock } from 'lucide-vue-next';
import { History } from 'lucide-vue-next';
import { FolderOpenDot } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

const page = usePage<SharedData>();
const unreadUpdates = ref<number>(0);

onMounted(async () => {
    try {
        const res = await fetch('/api/actualizaciones/counter', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (res.ok) {
            const data = await res.json();
            unreadUpdates.value = data.unread ?? 0;
        }
    } catch (e) {
        // ignore
    }
});

const mainNavItems = computed<NavItem[]>(() => {
    const role = page.props.auth?.user?.role ?? null;

    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: '/dashboard',
            icon: LayoutGrid,
        },
        {
            title: 'Docentes',
            href: '/docents',
            icon: User,
        },
        {
            title: 'Cursos',
            href: '/cursos',
            icon: LibraryBig,
        },
    ];

    if (role && (role === 'responsable' || role === 'admin')) {
        items.push({
            title: 'Checklist',
            href: '/cursos/checklist',
            icon: ClipboardCheck,
        });

        items.push({
            title: 'Final',
            href: '/final',
            icon: History,
        });

        items.push({
            title: 'Responsabilidades',
            href: '/responsabilidades',
            icon: User,
        });

        items.push({
            title: 'Catálogos',
            href: '/catalogos',
            icon: Folder,
        });
    }

    items.push(
        {
            title: 'Horarios',
            href: '/horarios',
            icon: CalendarClock,
        },
        {
            title: 'Actualizaciones',
            href: '/actualizaciones',
            icon: History,
        },
        {
            title: 'Asesores y Jurados',
            href: '/asesores-jurados',
            icon: BookOpen,
        },
    );

    return items;
});

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
