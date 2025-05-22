<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { User } from 'lucide-vue-next';
import { LibraryBig } from 'lucide-vue-next';
import { CalendarClock } from 'lucide-vue-next';
import { History } from 'lucide-vue-next';
import { FolderOpenDot } from 'lucide-vue-next';


const mainNavItems: NavItem[] = [
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
        title: 'Materiales',
        href: '/materiales',
        icon: FolderOpenDot,
    },
  
    {
        title: 'Asesores y Jurados',
        href: '/asesors',
        icon: BookOpen,
    },

];

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
