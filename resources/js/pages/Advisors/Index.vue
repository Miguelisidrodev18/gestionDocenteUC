<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page: any = usePage();
const periodo = ref(page.props.periodo ?? '2025-2');
const filters = ref(page.props.filters ?? { area: '', estado: '' });
const docentes = computed(() => page.props.docentes ?? []);
const panels = computed(() => page.props.panels ?? []);
const invitaciones = computed(() => page.props.invitaciones ?? []);
const canManage = computed(() => page.props.canManage ?? false);

const area = ref(filters.value.area ?? '');
const estado = ref(filters.value.estado ?? '');

const creandoPanel = ref(false);
const panelCursoId = ref<number | null>(null);
const panelType = ref('asesoria');
const panelMaxMembers = ref(3);

const invitando = ref(false);
const invitacionPanelId = ref<number | null>(null);
const invitacionDocenteId = ref<number | null>(null);
const invitacionRol = ref('asesor');

function aplicarFiltros() {
  router.get(
    '/asesores-jurados',
    {
      periodo: periodo.value,
      area: area.value || undefined,
      estado: estado.value || undefined,
    },
    { preserveState: true, preserveScroll: true },
  );
}

function crearPanel() {
  router.post(
    '/panels',
    {
      curso_id: panelCursoId.value,
      type: panelType.value,
      max_members: panelMaxMembers.value,
      periodo: periodo.value,
    },
    {
      onFinish: () => {
        creandoPanel.value = false;
      },
    },
  );
}

function invitar() {
  router.post(
    '/assignments',
    {
      panel_id: invitacionPanelId.value,
      docente_id: invitacionDocenteId.value,
      role: invitacionRol.value,
    },
    {
      onFinish: () => {
        invitando.value = false;
      },
    },
  );
}

function aceptarInvitacion(id: number) {
  router.put(`/assignments/${id}/aceptar`, {}, { preserveScroll: true });
}

function rechazarInvitacion(id: number) {
  router.put(`/assignments/${id}/rechazar`, {}, { preserveScroll: true });
}
</script>

<template>
  <AppLayout>
    <div class="p-8 min-h-screen bg-background text-foreground">
      <h1 class="text-2xl font-bold mb-6">Asesores y Jurados</h1>

      <!-- Filtros -->
      <div
        class="bg-card/90 backdrop-blur p-4 rounded-2xl border border-white/10 shadow-md shadow-[#190019]/20 mb-6 flex flex-wrap gap-4 items-end"
      >
        <div class="flex flex-col">
          <label class="text-xs text-muted-foreground mb-1">Periodo</label>
          <input
            v-model="periodo"
            class="border border-border bg-background text-foreground p-2 rounded min-w-[120px]"
          />
        </div>
        <div class="flex flex-col">
          <label class="text-xs text-muted-foreground mb-1">Área / especialidad</label>
          <input
            v-model="area"
            placeholder="Ej: Sistemas, Derecho..."
            class="border border-border bg-background text-foreground p-2 rounded min-w-[200px]"
          />
        </div>
        <div class="flex flex-col">
          <label class="text-xs text-muted-foreground mb-1">Estado invitación</label>
          <select
            v-model="estado"
            class="border border-border bg-background text-foreground p-2 rounded min-w-[160px]"
          >
            <option value="">Todos</option>
            <option value="invitado">Invitado</option>
            <option value="aceptado">Aceptado</option>
            <option value="rechazado">Rechazado</option>
          </select>
        </div>
        <button
          type="button"
          class="px-4 py-2 rounded bg-primary text-primary-foreground hover:opacity-90"
          @click="aplicarFiltros"
        >
          Aplicar filtros
        </button>
        <button
          v-if="canManage"
          type="button"
          class="ml-auto px-4 py-2 rounded bg-purple-600 text-white hover:bg-purple-700"
          @click="
            () => {
              creandoPanel = true;
              panelCursoId = null;
            }
          "
        >
          Crear panel
        </button>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Lista de docentes / perfiles -->
        <div
          class="bg-card/90 backdrop-blur rounded-2xl border border-white/10 shadow-md shadow-[#190019]/20 p-4 lg:col-span-2"
        >
          <h2 class="text-sm font-semibold mb-3">Docentes disponibles</h2>
          <div class="max-h-[480px] overflow-auto divide-y">
            <div
              v-for="d in docentes"
              :key="d.id"
              class="py-3 flex items-start justify-between gap-4"
            >
              <div>
                <div class="font-medium">
                  {{ d.nombre }} {{ d.apellido }}
                </div>
                <div class="text-xs text-muted-foreground mb-1">
                  DNI: {{ d.dni }} · Correo: {{ d.email }}
                </div>
                <div class="text-xs text-muted-foreground">
                  Especialidad: {{ d.especialidad || '—' }}
                </div>
                <div v-if="d.advisor_profile" class="text-[11px] text-muted-foreground mt-1">
                  Carga actual: {{ d.advisor_profile.current_load }} / {{ d.advisor_profile.max_load }}
                  <span v-if="d.advisor_profile.main_area"> · Área principal: {{ d.advisor_profile.main_area }}</span>
                </div>
              </div>
              <div class="flex flex-col items-end gap-1">
                <span class="text-[11px] text-muted-foreground">
                  Cursos periodo: {{ (d.cursos || []).length + (d.cursos_colabora || []).length }}
                </span>
                <button
                  v-if="canManage"
                  type="button"
                  class="px-2 py-1 rounded bg-secondary text-xs hover:bg-secondary/80"
                  @click="
                    () => {
                      invitando = true;
                      invitacionDocenteId = d.id;
                    }
                  "
                >
                  Invitar a panel
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Paneles existentes -->
        <div
          class="bg-card/90 backdrop-blur rounded-2xl border border-white/10 shadow-md shadow-[#190019]/20 p-4"
        >
          <h2 class="text-sm font-semibold mb-3">Paneles</h2>
          <div class="max-h-[480px] overflow-auto space-y-3 text-xs">
            <div
              v-for="p in panels"
              :key="p.id"
              class="border border-border/40 rounded-xl p-3 bg-background/40"
            >
              <div class="flex justify-between mb-1">
                <div class="font-medium text-foreground">
                  {{ p.curso?.codigo }} - {{ p.curso?.nombre }}
                </div>
                <span class="uppercase text-[10px] px-2 py-0.5 rounded-full bg-secondary/40">
                  {{ p.type }}
                </span>
              </div>
              <div class="text-[11px] text-muted-foreground mb-1">
                Estado: <span class="text-foreground">{{ p.status }}</span>
                <span v-if="p.scheduled_at">
                  · Fecha: {{ p.scheduled_at }}
                </span>
              </div>
              <div class="text-[11px]">
                Miembros:
                <span v-if="(p.assignments || []).length === 0">Sin asignar</span>
                <span v-else>
                  <span
                    v-for="a in p.assignments"
                    :key="a.id"
                    class="inline-block mr-1"
                  >
                    {{ a.docente?.nombre }} ({{ a.role }} - {{ a.status }})
                  </span>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Bandeja de invitaciones -->
      <div
        v-if="invitaciones.length"
        class="mt-8 bg-card/90 backdrop-blur rounded-2xl border border-white/10 shadow-md shadow-[#190019]/20 p-4"
      >
        <h2 class="text-sm font-semibold mb-3">Mis invitaciones</h2>
        <div class="space-y-2 text-xs">
          <div
            v-for="inv in invitaciones"
            :key="inv.id"
            class="border border-border/40 rounded-xl px-3 py-2 flex items-center justify-between gap-4"
          >
            <div>
              <div class="font-medium">
                {{ inv.panel?.curso?.codigo }} - {{ inv.panel?.curso?.nombre }}
              </div>
              <div class="text-[11px] text-muted-foreground">
                Rol: {{ inv.role }} · Estado: {{ inv.status }}
              </div>
            </div>
            <div class="flex gap-2" v-if="inv.status === 'invitado'">
              <button
                class="px-3 py-1 rounded bg-green-600 text-white text-[11px]"
                @click="aceptarInvitacion(inv.id)"
              >
                Aceptar
              </button>
              <button
                class="px-3 py-1 rounded bg-red-600 text-white text-[11px]"
                @click="rechazarInvitacion(inv.id)"
              >
                Rechazar
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal crear panel -->
      <div
        v-if="creandoPanel"
        class="fixed inset-0 bg-black/40 flex items-center justify-center z-50"
      >
        <div class="bg-card rounded-2xl border border-border p-6 w-full max-w-lg space-y-4">
          <h3 class="text-lg font-semibold mb-2">Crear panel</h3>
          <div class="space-y-2 text-sm">
            <label class="flex flex-col gap-1">
              <span>Curso (ID)</span>
              <input
                v-model.number="panelCursoId"
                type="number"
                class="border border-border bg-background text-foreground p-2 rounded"
                placeholder="ID del curso"
              />
            </label>
            <label class="flex flex-col gap-1">
              <span>Tipo</span>
              <select
                v-model="panelType"
                class="border border-border bg-background text-foreground p-2 rounded"
              >
                <option value="asesoria">Asesoría</option>
                <option value="jurado">Jurado</option>
                <option value="sustentacion">Sustentación</option>
              </select>
            </label>
            <label class="flex flex-col gap-1">
              <span>Máximo de miembros</span>
              <input
                v-model.number="panelMaxMembers"
                type="number"
                min="1"
                max="10"
                class="border border-border bg-background text-foreground p-2 rounded"
              />
            </label>
          </div>
          <div class="flex justify-end gap-2 mt-4">
            <button
              type="button"
              class="px-3 py-1 rounded border border-border text-sm"
              @click="creandoPanel = false"
            >
              Cancelar
            </button>
            <button
              type="button"
              class="px-4 py-2 rounded bg-primary text-primary-foreground text-sm"
              @click="crearPanel"
            >
              Guardar panel
            </button>
          </div>
        </div>
      </div>

      <!-- Modal invitar -->
      <div
        v-if="invitando"
        class="fixed inset-0 bg-black/40 flex items-center justify-center z-50"
      >
        <div class="bg-card rounded-2xl border border-border p-6 w-full max-w-lg space-y-4">
          <h3 class="text-lg font-semibold mb-2">Invitar a panel</h3>
          <div class="space-y-2 text-sm">
            <label class="flex flex-col gap-1">
              <span>ID del panel</span>
              <input
                v-model.number="invitacionPanelId"
                type="number"
                class="border border-border bg-background text-foreground p-2 rounded"
                placeholder="ID panel"
              />
            </label>
            <label class="flex flex-col gap-1">
              <span>ID del docente</span>
              <input
                v-model.number="invitacionDocenteId"
                type="number"
                class="border border-border bg-background text-foreground p-2 rounded"
                placeholder="ID docente"
              />
            </label>
            <label class="flex flex-col gap-1">
              <span>Rol</span>
              <select
                v-model="invitacionRol"
                class="border border-border bg-background text-foreground p-2 rounded"
              >
                <option value="asesor">Asesor</option>
                <option value="jurado">Jurado</option>
                <option value="presidente">Presidente</option>
              </select>
            </label>
          </div>
          <div class="flex justify-end gap-2 mt-4">
            <button
              type="button"
              class="px-3 py-1 rounded border border-border text-sm"
              @click="invitando = false"
            >
              Cancelar
            </button>
            <button
              type="button"
              class="px-4 py-2 rounded bg-primary text-primary-foreground text-sm"
              @click="invitar"
            >
              Enviar invitación
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

