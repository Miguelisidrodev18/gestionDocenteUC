<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import type { BreadcrumbItem } from '@/types';
const page: any = usePage();
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }, { title: 'Actualizaciones', href: '/actualizaciones' }];


const updates = computed(() => {
  const raw = page.props.updates ?? [];
  const map = new Map<number, any>();
  raw.forEach((u: any) => {
    if (!map.has(u.id)) {
      map.set(u.id, u);
    }
  });
  return Array.from(map.values());
});
const filters = ref(page.props.filters ?? {});

const estado = ref(filters.value.estado ?? 'activas');
const audiencia = ref(filters.value.audiencia ?? '');
const from = ref(filters.value.from ?? '');
const to = ref(filters.value.to ?? '');

const showModal = ref(false);
const editing = ref<any | null>(null);

function applyFilters() {
  router.get(
    '/actualizaciones',
    {
      estado: estado.value,
      audiencia: audiencia.value || undefined,
      from: from.value || undefined,
      to: to.value || undefined,
    },
    { preserveState: true, preserveScroll: true },
  );
}

function openCreate() {
  editing.value = null;
  showModal.value = true;
}

function openEdit(u: any) {
  editing.value = u;
  showModal.value = true;
}

function submitUpdate(e: Event) {
  const form = e.target as HTMLFormElement;
  const payload = {
    titulo: (form.titulo as any).value,
    cuerpo_md: (form.cuerpo_md as any).value,
    audience: (form.audience as any).value,
    pinned: (form.pinned as any).checked,
    starts_at: (form.starts_at as any).value,
    ends_at: (form.ends_at as any).value || null,
  };

  if (editing.value) {
    router.put(`/actualizaciones/${editing.value.id}`, payload, {
      onSuccess: () => (showModal.value = false),
      preserveScroll: true,
    });
  } else {
    router.post('/actualizaciones', payload, {
      onSuccess: () => (showModal.value = false),
      preserveScroll: true,
    });
  }
}

function markRead(id: number) {
  router.post(`/actualizaciones/${id}/read`, {}, { preserveScroll: true });
}

function pin(id: number) {
  router.post(`/actualizaciones/${id}/pin`, {}, { preserveScroll: true });
}

function unpin(id: number) {
  router.post(`/actualizaciones/${id}/unpin`, {}, { preserveScroll: true });
}

function remove(id: number) {
  if (confirm('¿Eliminar esta actualización?')) {
    router.delete(`/actualizaciones/${id}`, { preserveScroll: true });
  }
}
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
<div class="p-8 min-h-screen bg-background text-foreground">
      <h1 class="text-2xl font-bold mb-6 flex items-center justify-between">
        <span>Actualizaciones</span>
        <button
          type="button"
          class="px-3 py-2 rounded bg-primary text-primary-foreground text-xs hover:opacity-90"
          @click="openCreate"
        >
          + Nueva actualización
        </button>
      </h1>

      <!-- Filtros -->
      <div
        class="bg-card/90 backdrop-blur p-4 rounded-2xl border border-white/10 shadow-md shadow-[#190019]/20 mb-6 flex flex-wrap gap-4 items-end text-xs"
      >
        <div class="flex flex-col">
          <label class="text-[11px] text-muted-foreground mb-1">Estado</label>
          <select
            v-model="estado"
            class="border border-border bg-background text-foreground p-2 rounded min-w-[140px]"
          >
            <option value="activas">Activas</option>
            <option value="proximas">Próximas</option>
            <option value="vencidas">Vencidas</option>
            <option value="todas">Todas</option>
          </select>
        </div>

        <div class="flex flex-col">
          <label class="text-[11px] text-muted-foreground mb-1">Audiencia</label>
          <select
            v-model="audiencia"
            class="border border-border bg-background text-foreground p-2 rounded min-w-[140px]"
          >
            <option value="">Todas</option>
            <option value="TODOS">Todos</option>
            <option value="DOCENTES">Docentes</option>
            <option value="RESPONSABLES">Responsables</option>
            <option value="ADMIN">Admin</option>
          </select>
        </div>

        <div class="flex flex-col">
          <label class="text-[11px] text-muted-foreground mb-1">Desde</label>
          <input
            v-model="from"
            type="date"
            class="border border-border bg-background text-foreground p-2 rounded min-w-[140px]"
          />
        </div>

        <div class="flex flex-col">
          <label class="text-[11px] text-muted-foreground mb-1">Hasta</label>
          <input
            v-model="to"
            type="date"
            class="border border-border bg-background text-foreground p-2 rounded min-w-[140px]"
          />
        </div>

        <button
          type="button"
          class="px-4 py-2 rounded bg-primary text-primary-foreground hover:opacity-90"
          @click="applyFilters"
        >
          Aplicar filtros
        </button>
      </div>

      <!-- Lista -->
      <div class="space-y-3">
        <div
          v-for="u in updates"
          :key="u.id"
          class="bg-card/90 backdrop-blur rounded-2xl border border-white/10 shadow-md shadow-[#190019]/20 p-4 text-xs"
        >
          <div class="flex justify-between items-start mb-2">
            <div>
              <div class="flex items-center gap-2 mb-1">
                <h2 class="font-semibold text-sm">
                  {{ u.titulo }}
                </h2>
                <span
                  v-if="u.pinned"
                  class="px-2 py-0.5 rounded-full bg-yellow-500/20 text-yellow-200 text-[10px]"
                  >PIN</span
                >
                <span
                  class="px-2 py-0.5 rounded-full bg-secondary/40 text-[10px] uppercase"
                >
                  {{ u.audience }}
                </span>
                <span
                  class="px-2 py-0.5 rounded-full text-[10px]"
                  :class="u.is_active ? 'bg-green-500/20 text-green-200' : 'bg-muted text-muted-foreground'"
                >
                  {{ u.is_active ? 'ACTIVA' : 'NO ACTIVA' }}
                </span>
              </div>
              <div class="text-[11px] text-muted-foreground">
                Vigencia:
                {{ u.starts_at ? new Date(u.starts_at).toLocaleString() : '—' }}
                <span v-if="u.ends_at">
                  → {{ new Date(u.ends_at).toLocaleString() }}
                </span>
              </div>
            </div>
            <div class="flex flex-col gap-1 items-end">
              <button
                v-if="!u.leido"
                class="px-2 py-1 rounded bg-primary text-primary-foreground text-[11px]"
                @click="markRead(u.id)"
              >
                Marcar leído
              </button>
              <button
                v-else
                disabled
                class="px-2 py-1 rounded border border-border text-[11px] text-muted-foreground"
              >
                Leído
              </button>
              <div class="flex gap-1 mt-1">
                <button
                  class="px-2 py-1 rounded border border-border text-[11px]"
                  @click="openEdit(u)"
                >
                  Editar
                </button>
                <button
                  class="px-2 py-1 rounded border border-border text-[11px]"
                  @click="remove(u.id)"
                >
                  Eliminar
                </button>
                <button
                  v-if="!u.pinned"
                  class="px-2 py-1 rounded border border-border text-[11px]"
                  @click="pin(u.id)"
                >
                  Pin
                </button>
                <button
                  v-else
                  class="px-2 py-1 rounded border border-border text-[11px]"
                  @click="unpin(u.id)"
                >
                  Unpin
                </button>
              </div>
            </div>
          </div>
          <div class="prose prose-sm max-w-none text-foreground/90">
            <pre class="whitespace-pre-wrap text-xs">{{ u.cuerpo_md }}</pre>
          </div>
        </div>
      </div>

      <!-- Modal Crear/Editar -->
      <div
        v-if="showModal"
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
      >
        <form
          class="bg-card rounded-2xl border border-border p-6 w-[90vw] max-w-2xl space-y-3 text-xs"
          @submit.prevent="submitUpdate"
        >
          <h2 class="text-sm font-semibold mb-2">
            {{ editing ? 'Editar actualización' : 'Nueva actualización' }}
          </h2>
          <label class="flex flex-col gap-1">
            <span>Título</span>
            <input
              name="titulo"
              :default-value="editing?.titulo || ''"
              class="border border-border bg-background text-foreground p-2 rounded"
              required
            />
          </label>
          <label class="flex flex-col gap-1">
            <span>Audiencia</span>
            <select
              name="audience"
              :default-value="editing?.audience || 'TODOS'"
              class="border border-border bg-background text-foreground p-2 rounded"
            >
              <option value="TODOS">Todos</option>
              <option value="DOCENTES">Docentes</option>
              <option value="RESPONSABLES">Responsables</option>
              <option value="ADMIN">Admin</option>
            </select>
          </label>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
            <label class="flex flex-col gap-1">
              <span>Inicio</span>
              <input
                name="starts_at"
                type="datetime-local"
                :default-value="editing?.starts_at ? editing.starts_at.substring(0, 16) : ''"
                class="border border-border bg-background text-foreground p-2 rounded"
                required
              />
            </label>
            <label class="flex flex-col gap-1">
              <span>Fin</span>
              <input
                name="ends_at"
                type="datetime-local"
                :default-value="editing?.ends_at ? editing.ends_at.substring(0, 16) : ''"
                class="border border-border bg-background text-foreground p-2 rounded"
              />
            </label>
            <label class="flex items-center gap-2 mt-5">
              <input
                name="pinned"
                type="checkbox"
                class="border-border"
                :checked="!!editing?.pinned"
              />
              <span>Fijar (pin)</span>
            </label>
          </div>
          <label class="flex flex-col gap-1">
            <span>Cuerpo (Markdown)</span>
            <textarea
              name="cuerpo_md"
              rows="6"
              :default-value="editing?.cuerpo_md || ''"
              class="border border-border bg-background text-foreground p-2 rounded"
              required
            ></textarea>
          </label>
          <div class="flex justify-end gap-2 mt-3">
            <button
              type="button"
              class="px-3 py-1 rounded border border-border"
              @click="showModal = false"
            >
              Cancelar
            </button>
            <button
              type="submit"
              class="px-4 py-2 rounded bg-primary text-primary-foreground"
            >
              Guardar
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

