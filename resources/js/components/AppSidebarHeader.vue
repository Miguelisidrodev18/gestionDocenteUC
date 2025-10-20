<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Link, usePage } from '@inertiajs/vue3';
import { Bell } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import type { BreadcrumbItemType } from '@/types';

defineProps<{
    breadcrumbs?: BreadcrumbItemType[];
}>();

const page = usePage();
const notificationsUnread = computed<any[]>(() => page.props.notifications ?? []);
const notificationsAll = computed<any[]>(() => page.props.notifications_all ?? []);
const notificationsCount = computed<number>(() => (page.props.notifications_count as number) ?? 0);
const showAll = ref(false);
const typeFilter = ref<'all' | 'meeting' | 'checklist'>('all');
const visibleNotifications = computed(() => {
    const list = showAll.value ? notificationsAll.value : notificationsUnread.value;
    if (typeFilter.value === 'all') return list;
    return list.filter((n) => n.type === typeFilter.value);
});
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center justify-between border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-[[data-collapsible=icon]]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
        <div class="flex items-center gap-2">
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="ghost" size="icon" class="relative h-9 w-9">
                        <Bell class="size-5" />
                        <span v-if="notificationsCount > 0" class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] text-white">
                            {{ notificationsCount }}
                        </span>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-80">
                    <div class="p-2">
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <div class="flex gap-1 rounded-md bg-muted p-1 text-xs">
                                <button class="rounded px-2 py-1" :class="{'bg-background shadow': !showAll}" @click="showAll=false">No leídas</button>
                                <button class="rounded px-2 py-1" :class="{'bg-background shadow': showAll}" @click="showAll=true">Todas</button>
                            </div>
                            <div class="flex gap-1 text-xs">
                                <button class="rounded px-2 py-1" :class="{'bg-primary text-primary-foreground': typeFilter==='all'}" @click="typeFilter='all'">Todas</button>
                                <button class="rounded px-2 py-1" :class="{'bg-primary text-primary-foreground': typeFilter==='meeting'}" @click="typeFilter='meeting'">Reuniones</button>
                                <button class="rounded px-2 py-1" :class="{'bg-primary text-primary-foreground': typeFilter==='checklist'}" @click="typeFilter='checklist'">Checklist</button>
                            </div>
                        </div>
                        <div class="max-h-72 overflow-y-auto">
                            <div v-if="visibleNotifications.length === 0" class="p-3 text-sm text-muted-foreground">Sin notificaciones</div>
                            <div v-for="n in visibleNotifications" :key="n.id" class="px-3 py-2 text-sm border-b last:border-b-0">
                                <div class="flex items-start justify-between gap-2">
                                    <Link :href="n.link || '#'" class="hover:underline">
                                        {{ n.message }}
                                    </Link>
                                    <form v-if="!n.read_at" method="post" :action="`/notifications/${n.id}/read`">
                                        <input type="hidden" name="_token" :value="page.props.csrf_token" />
                                        <Button variant="ghost" size="sm">Marcar leído</Button>
                                    </form>
                                </div>
                                <div class="text-[11px] text-muted-foreground">{{ new Date(n.created_at).toLocaleString() }}</div>
                            </div>
                        </div>
                        <form method="post" action="/notifications/read-all" class="pt-2">
                            <input type="hidden" name="_token" :value="page.props.csrf_token" />
                            <Button variant="ghost" size="sm" class="w-full">Marcar todas como leídas</Button>
                        </form>
                    </div>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </header>
</template>
