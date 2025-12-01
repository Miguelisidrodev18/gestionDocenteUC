<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage<any>();
const cursos = computed(() => page.props.cursos ?? []);
const responsables = computed(() => page.props.responsables ?? []);
const filters = computed(() => page.props.filters ?? {});
const currentUserId = computed<number | null>(() => page.props.auth?.user?.id ?? null);

const periodo = ref<string>(filters.value.periodo ?? '2025-2');

const modalOpen = ref(false);
const cursoSeleccionado = ref<any | null>(null);
const responsableSeleccionado = ref<string>('');
const campusId = ref<string>('');
const modalidadDocente = ref<string>('');
const motivo = ref<string>('');

function abrirModal(curso: any) {
  cursoSeleccionado.value = curso;
  responsableSeleccionado.value = String(curso.assignment?.responsable_user_id ?? curso.responsable?.id ?? '');
  campusId.value = String(curso.assignment?.campus_id ?? '');
  modalidadDocente.value = String(curso.assignment?.modalidad_docente ?? '');
  motivo.value = '';
  modalOpen.value = true;
}

function cerrarModal() {
  modalOpen.value = false;
  cursoSeleccionado.value = null;
}

function guardar() {
  if (!cursoSeleccionado.value || !responsableSeleccionado.value) return;
  router.patch(
    `/responsabilidades/${cursoSeleccionado.value.id}`,
    {
      responsable_user_id: responsableSeleccionado.value,
      campus_id: campusId.value || undefined,
      modalidad_docente: modalidadDocente.value || undefined,
      reason: motivo.value || undefined,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        cerrarModal();
      },
    },
  );
}

function aceptarAsignacion(curso: any) {
  if (!curso.assignment) return;
  router.post(
    `/responsabilidades/${curso.assignment.id}/aceptar`,
    {},
    { preserveScroll: true },
  );
}

function rechazarAsignacion(curso: any) {
  if (!curso.assignment) return;
  router.post(
    `/responsabilidades/${curso.assignment.id}/rechazar`,
    {},
    { preserveScroll: true },
  );
}

const estadoLabel = (curso: any) => {
  if (!curso.assignment) return 'Pendiente';
  const status = curso.assignment.status ?? 'aceptado';
  if (status === 'invitado') return 'Pendiente de aceptación';
  if (status === 'rechazado') return 'Rechazado';
  return 'Aceptado';
};

const estadoClase = (curso: any) => {
  if (!curso.assignment) return 'bg-gray-300 text-gray-800';
  const status = curso.assignment.status ?? 'aceptado';
  if (status === 'invitado') return 'bg-amber-500 text-white';
  if (status === 'rechazado') return 'bg-red-500 text-white';
  return 'bg-emerald-600 text-white';
};
</script>

<template>
  <AppLayout>
    <Head title="Responsabilidades de cursos" />
    <div class="p-6 space-y-4">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold">Responsables de cursos</h1>
        <div class="flex items-center gap-2">
          <label class="text-sm flex items-center gap-2">
            <span class="text-muted-foreground">Periodo:</span>
            <select
              v-model="periodo"
              class="border rounded px-2 py-1 text-sm bg-background text-foreground"
              @change="router.get('/responsabilidades', { periodo }, { preserveScroll: true })"
            >
              <option value="2025-2">2025-2</option>
              <option value="2026-0">2026-0</option>
              <option value="2026-1">2026-1</option>
            </select>
          </label>
        </div>
      </div>

      <div v-if="cursos.length === 0" class="text-sm text-muted-foreground">
        No hay cursos para el periodo seleccionado.
      </div>

      <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="curso in cursos"
          :key="curso.id"
          class="border rounded-lg bg-card p-4 flex flex-col justify-between"
        >
          <div class="space-y-1">
            <div class="font-semibold text-sm md:text-base">
              {{ curso.codigo }} - {{ curso.nombre }}
            </div>
            <div class="text-xs text-muted-foreground">
              Modalidad:
              <span class="font-medium text-foreground">
                {{ curso.modalidadRel?.nombre ?? curso.modalidad }}
              </span>
            </div>
            <div class="text-xs text-muted-foreground">
              Docente principal:
              <span class="font-medium text-foreground">
                {{ curso.docente?.nombre ?? 'Sin asignar' }}
              </span>
            </div>
            <div class="text-xs text-muted-foreground">
              Responsable actual:
              <span class="font-medium text-foreground">
                {{ curso.assignment?.responsable?.name ?? curso.responsable?.name ?? 'Sin asignar' }}
              </span>
            </div>
          </div>
          <div class="mt-3 flex items-center justify-between">
            <span
              class="px-3 py-1 rounded-full text-xs font-semibold"
              :class="estadoClase(curso)"
            >
              {{ estadoLabel(curso) }}
            </span>
            <div class="flex items-center gap-2">
              <button
                v-if="curso.assignment && curso.assignment.status === 'invitado' && currentUserId && curso.assignment.responsable_user_id === currentUserId"
                type="button"
                class="px-3 py-1 text-xs rounded border bg-emerald-600 text-white hover:bg-emerald-700"
                @click="aceptarAsignacion(curso)"
              >
                Aceptar
              </button>
              <button
                v-if="curso.assignment && curso.assignment.status === 'invitado' && currentUserId && curso.assignment.responsable_user_id === currentUserId"
                type="button"
                class="px-3 py-1 text-xs rounded border bg-red-600 text-white hover:bg-red-700"
                @click="rechazarAsignacion(curso)"
              >
                Rechazar
              </button>
              <button
                type="button"
                class="px-3 py-1 text-xs rounded border bg-primary text-primary-foreground hover:opacity-90"
                @click="abrirModal(curso)"
              >
                Editar
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal edición -->
      <div
        v-if="modalOpen && cursoSeleccionado"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
      >
        <div class="bg-card border rounded-xl shadow-xl max-w-2xl w-full p-4 space-y-4">
          <div class="flex justify-between items-start gap-2">
            <div>
              <h2 class="text-lg font-semibold">Editar responsable</h2>
              <p class="text-xs text-muted-foreground">
                {{ cursoSeleccionado.codigo }} - {{ cursoSeleccionado.nombre }}
              </p>
            </div>
            <button
              type="button"
              class="text-muted-foreground hover:text-foreground"
              @click="cerrarModal"
            >
              ✕
            </button>
          </div>

          <div class="space-y-2">
            <label class="block text-sm">
              <span class="text-muted-foreground">Responsable</span>
              <select
                v-model="responsableSeleccionado"
                class="mt-1 w-full border rounded px-2 py-1 text-sm bg-background text-foreground"
              >
                <option value="">Seleccione responsable</option>
                <option
                  v-for="resp in responsables"
                  :key="resp.id"
                  :value="resp.id"
                >
                  {{ resp.name }}
                </option>
              </select>
            </label>

            <label class="block text-sm">
              <span class="text-muted-foreground">Campus (ID opcional)</span>
              <input
                v-model="campusId"
                class="mt-1 w-full border rounded px-2 py-1 text-sm bg-background text-foreground"
              />
            </label>

            <label class="block text-sm">
              <span class="text-muted-foreground">Modalidad docente (opcional)</span>
              <input
                v-model="modalidadDocente"
                class="mt-1 w-full border rounded px-2 py-1 text-sm bg-background text-foreground"
                placeholder="Presencial, Virtual, etc."
              />
            </label>

            <label class="block text-sm">
              <span class="text-muted-foreground">Motivo / comentario</span>
              <textarea
                v-model="motivo"
                rows="3"
                class="mt-1 w-full border rounded px-2 py-1 text-sm bg-background text-foreground"
              />
            </label>
          </div>

          <div class="flex justify-end gap-2">
            <button
              type="button"
              class="px-3 py-1 text-sm rounded border"
              @click="cerrarModal"
            >
              Cancelar
            </button>
            <button
              type="button"
              class="px-3 py-1 text-sm rounded bg-primary text-primary-foreground hover:opacity-90"
              :disabled="!responsableSeleccionado"
              @click="guardar"
            >
              Guardar
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
