<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page: any = usePage();
const cursos = computed<any[]>(() => page.props.cursos ?? []);
const currentUserRole = computed<string | undefined>(
  () => page.props.currentUserRole ?? page.props.auth?.user?.role,
);

const periodoSeleccionado = ref<string>(page.props.periodo ?? '2025-2');

const mostrarModal = ref(false);
const cursoSeleccionado = ref<any | null>(null);
const docenteSeleccionadoId = ref<number | null>(null);

function abrirModal(curso: any) {
  cursoSeleccionado.value = curso;
  docenteSeleccionadoId.value = curso.docente_id ?? null;
  mostrarModal.value = true;
}

function cerrarModal() {
  mostrarModal.value = false;
  cursoSeleccionado.value = null;
  docenteSeleccionadoId.value = null;
}

function seleccionarDocente(id: number) {
  docenteSeleccionadoId.value = id;
}

function guardarAsignacion() {
  if (!cursoSeleccionado.value || !docenteSeleccionadoId.value) return;
  router.patch(
    `/cursos/${cursoSeleccionado.value.id}/docente-responsable`,
    {
      docente_id: docenteSeleccionadoId.value,
    },
    {
      onSuccess: () => {
        cerrarModal();
        router.reload({ only: ['cursos'] });
      },
    },
  );
}

function estadoEtiqueta(curso: any) {
  return curso.docente_id ? 'Asignado' : 'Sin asignar';
}

function estadoClase(curso: any) {
  return curso.docente_id
    ? 'bg-emerald-600 text-white'
    : 'bg-gray-300 text-gray-800';
}

function aplicarPeriodo() {
  router.get(
    '/cursos/asignaciones',
    {
      periodo: periodoSeleccionado.value,
    },
    {
      preserveState: true,
      preserveScroll: true,
    },
  );
}

function modalidadRelDe(curso: any) {
  return curso.modalidadRel ?? curso.modalidad_rel ?? null;
}

function docentesDe(curso: any) {
  return curso.docentesParticipantes ?? curso.docentes_participantes ?? [];
}
</script>

<template>
  <AppLayout>
    <div class="p-8 min-h-screen bg-background text-foreground">
      <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
          <h1 class="text-2xl font-bold">
            Asignación de docentes responsables
          </h1>
          <p class="text-sm text-muted-foreground">
            Vista para
            {{ currentUserRole === 'admin' ? 'administrador' : 'docente responsable' }}.
          </p>
        </div>
        <div class="flex items-center gap-2">
          <span class="text-sm text-muted-foreground">Periodo:</span>
          <select
            v-model="periodoSeleccionado"
            @change="aplicarPeriodo"
            class="border border-border bg-background text-foreground p-2 rounded"
          >
            <option value="2025-2">2025-2</option>
            <option value="2026-0">2026-0</option>
            <option value="2026-1">2026-1</option>
          </select>
          <button
            type="button"
            class="px-3 py-2 rounded border bg-secondary text-secondary-foreground text-xs md:text-sm hover:opacity-90 transition"
            @click="() => router.post('/cursos/traer', { periodo: periodoSeleccionado }, { onSuccess: () => router.reload() })"
          >
            Traer cursos
          </button>
        </div>
      </div>

      <div v-if="cursos.length === 0" class="text-sm text-muted-foreground">
        No hay cursos para el periodo seleccionado.
      </div>

      <div v-else class="space-y-4">
        <div
          v-for="curso in cursos"
          :key="curso.id"
          class="rounded-xl border border-border bg-card shadow-sm flex flex-col md:flex-row md:items-center md:justify-between px-4 py-3"
        >
          <div class="flex-1">
            <div class="font-semibold text-base md:text-lg">
              {{ curso.nombre }} ({{ curso.codigo }})
            </div>
            <div class="text-xs md:text-sm text-muted-foreground mt-1 space-x-2">
              <span>
                Modalidad:
                {{ modalidadRelDe(curso)?.nombre ?? curso.modalidad }}
              </span>
              <span v-if="modalidadRelDe(curso)?.area">
                |
                Área:
                {{ modalidadRelDe(curso)?.area?.nombre }}
              </span>
              <span>| Periodo: {{ curso.periodo }}</span>
            </div>
            <div class="text-xs md:text-sm text-muted-foreground mt-1">
              Responsable actual:
              <span class="font-medium">
                {{ curso.docente?.nombre ?? 'Sin asignar' }}
                <template v-if="curso.docente?.apellido">
                  {{ ' ' + curso.docente.apellido }}
                </template>
              </span>
            </div>
            <div class="text-xs text-muted-foreground mt-1">
              Docentes asociados: {{ docentesDe(curso).length }}
            </div>
          </div>

          <div
            class="flex flex-row md:flex-col items-center gap-2 mt-3 md:mt-0 md:ml-4"
          >
            <span
              :class="[
                'px-3 py-1 rounded-full text-xs font-semibold',
                estadoClase(curso),
              ]"
            >
              {{ estadoEtiqueta(curso) }}
            </span>
            <button
              type="button"
              class="px-3 py-1 rounded border bg-primary text-primary-foreground text-xs md:text-sm hover:opacity-90 transition"
              @click="abrirModal(curso)"
            >
              Asignar / Editar
            </button>
          </div>
        </div>
      </div>

      <!-- Modal de asignación -->
      <div
        v-if="mostrarModal && cursoSeleccionado"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
      >
        <div
          class="w-full max-w-3xl rounded-xl border border-border bg-card p-6 shadow-xl max-h-[90vh] overflow-y-auto"
        >
          <div class="flex items-start justify-between mb-4">
            <div>
              <h2 class="text-lg font-semibold mb-1">
                Asignar responsable - {{ cursoSeleccionado.nombre }}
              </h2>
              <p class="text-xs text-muted-foreground">
                Seleccione al docente responsable entre los docentes asociados al
                curso.
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

          <div class="grid md:grid-cols-3 gap-4">
            <div class="md:col-span-1 border rounded-lg p-3 bg-muted/40">
              <h3 class="text-sm font-semibold mb-2">Docente seleccionado</h3>
              <div
                v-if="docenteSeleccionadoId && docentesDe(cursoSeleccionado).length"
              >
                <div
                  v-for="d in docentesDe(cursoSeleccionado)"
                  :key="d.id"
                  v-if="d.id === docenteSeleccionadoId"
                  class="border rounded-md p-2 bg-emerald-50 text-sm"
                >
                  <div class="font-medium">
                    {{ d.nombre }} {{ d.apellido }}
                  </div>
                  <div class="text-xs text-muted-foreground mt-1">
                    DNI: {{ d.dni || '—' }}
                  </div>
                  <div class="text-xs text-muted-foreground">
                    {{ d.email || 'Sin correo registrado' }}
                  </div>
                </div>
              </div>
              <div v-else class="text-xs text-muted-foreground">
                No hay docente seleccionado.
              </div>

              <div class="mt-4 text-xs text-muted-foreground">
                Periodo: {{ cursoSeleccionado.periodo }}<br />
                Modalidad:
                {{ modalidadRelDe(cursoSeleccionado)?.nombre ?? cursoSeleccionado.modalidad }}
              </div>
            </div>

            <div class="md:col-span-2">
              <h3 class="text-sm font-semibold mb-2">Docentes del curso</h3>
              <div
                v-if="docentesDe(cursoSeleccionado).length === 0"
                class="text-xs text-muted-foreground"
              >
                Este curso no tiene docentes asociados a través del módulo de
                asignación múltiple.
              </div>
              <div class="space-y-2 max-h-72 overflow-y-auto">
                <button
                  v-for="d in docentesDe(cursoSeleccionado)"
                  :key="d.id"
                  type="button"
                  class="w-full text-left border rounded-md px-3 py-2 text-sm flex items-center justify-between"
                  :class="[
                    docenteSeleccionadoId === d.id
                      ? 'bg-emerald-50 border-emerald-500'
                      : 'bg-background hover:bg-muted',
                  ]"
                  @click="seleccionarDocente(d.id)"
                >
                  <div>
                    <div class="font-medium">
                      {{ d.nombre }} {{ d.apellido }}
                    </div>
                    <div class="text-xs text-muted-foreground">
                      DNI: {{ d.dni || '—' }} ·
                      {{ d.email || 'Sin correo' }}
                    </div>
                  </div>
                  <div
                    v-if="docenteSeleccionadoId === d.id"
                    class="text-emerald-600 text-xs font-semibold"
                  >
                    Seleccionado
                  </div>
                </button>
              </div>
            </div>
          </div>

          <div class="mt-6 flex justify-end gap-2">
            <button
              type="button"
              class="px-4 py-2 rounded border text-sm"
              @click="cerrarModal"
            >
              Cancelar
            </button>
            <button
              type="button"
              class="px-4 py-2 rounded bg-primary text-primary-foreground text-sm hover:opacity-90 transition"
              :disabled="!docenteSeleccionadoId"
              @click="guardarAsignacion"
            >
              Guardar información
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
