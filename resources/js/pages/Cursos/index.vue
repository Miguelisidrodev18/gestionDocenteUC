<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { ref, onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const page = usePage();
const docentes = page.props.docentes ?? [];
const responsables = page.props.responsables ?? [];
const cursos = page.props.cursos ?? [];
const currentUserRole = page.props.currentUserRole ?? page.props.auth?.user?.role;
const currentDocenteId = page.props.currentDocenteId ?? null;

const mostrarFormulario = ref(false);
const nuevoCurso = ref({
  nombre: '',
  codigo: '',
  descripcion: '',
  creditos: '',
  nivel: 'pregrado',
  modalidad: 'presencial',
  docente_id: '',
  drive_url: '',
  responsable_id: '',
});
const periodoSeleccionado = ref(usePage().props.periodo ?? '2025-2');
const filtroDocenteId = ref(page.props.filters?.docente_id ?? '');

onMounted(() => {
  if (currentUserRole === 'docente' && currentDocenteId) {
    nuevoCurso.value.docente_id = String(currentDocenteId);
  }
});

function agregarCurso() {
  const payload = {
    ...nuevoCurso.value,
    periodo: periodoSeleccionado.value,
  };

  if (!payload.responsable_id) {
    delete payload.responsable_id;
  }

  router.post('/cursos', payload, {
    onSuccess: () => {
      alert('Curso creado correctamente.');
      mostrarFormulario.value = false;
      nuevoCurso.value = {
        nombre: '',
        codigo: '',
        descripcion: '',
        creditos: '',
        nivel: 'pregrado',
        modalidad: 'presencial',
        docente_id: '',
        drive_url: '',
        responsable_id: '',
      };
      router.reload();
    },
    onError: (errors) => {
      console.error(errors);
      alert('Hubo un error al guardar el curso.');
    },
  });
}

function abrirGoogleDrive(driveUrl) {
  if (driveUrl) {
    window.open(driveUrl, '_blank');
  } else {
    alert('Este curso no tiene un enlace de Google Drive.');
  }
}

function editarCurso(id) {
  router.get(`/cursos/${id}/edit`);
}

function eliminarCurso(id) {
  if (confirm('¿Estás seguro de que deseas eliminar este curso?')) {
    router.delete(`/cursos/${id}`, {
      onSuccess: () => {
        alert('Curso eliminado correctamente.');
        router.reload();
      }
    });
  }
}

function aplicarFiltros() {
  const params: Record<string, any> = { periodo: periodoSeleccionado.value };
  if (currentUserRole === 'admin' && filtroDocenteId.value) {
    params.docente_id = filtroDocenteId.value;
  }
  router.get('/cursos', params);
}
</script>

<template>
  <AppLayout>
    <div class="p-8 min-h-screen bg-background text-foreground">
      <h1 class="text-3xl font-bold mb-8 text-center">Asignaturas "{{ periodoSeleccionado }}"</h1>
      <!-- Selector de período -->
      <select
        v-model="periodoSeleccionado"
        @change="aplicarFiltros"
        class="border border-border bg-background text-foreground p-2 rounded mb-4 focus:outline-none focus:ring-2 focus:ring-ring"
      >
        <option value="2025-2">2025-2</option>
        <option value="2026-0">2026-0</option>
        <option value="2026-1">2026-1</option>
      </select>
      <select
        v-if="currentUserRole === 'admin'"
        v-model="filtroDocenteId"
        @change="aplicarFiltros"
        class="border border-border bg-background text-foreground p-2 rounded mb-4 ml-2 focus:outline-none focus:ring-2 focus:ring-ring"
      >
        <option value="">Todos los docentes</option>
        <option v-for="docente in docentes" :key="docente.id" :value="docente.id">
          {{ docente.nombre }} {{ docente.apellido }}
        </option>
      </select>
      <!-- Botón para mostrar el formulario -->
      <div class="flex justify-end mb-4">
        <button
          @click="mostrarFormulario = !mostrarFormulario"
          class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition"
        >
          {{ mostrarFormulario ? 'Ocultar formulario' : 'Agregar curso' }}
        </button>
      </div>
      <!-- Formulario -->
      <form
        v-if="mostrarFormulario"
        @submit.prevent="agregarCurso"
        class="bg-card text-foreground p-6 rounded-xl shadow-sm border border-border mb-6 grid grid-cols-1 md:grid-cols-2 gap-4 max-w-4xl mx-auto"
      >
        <label class="flex flex-col gap-1">
          <span class="text-sm text-gray-600">Nombre del curso</span>
          <input v-model="nuevoCurso.nombre" class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring" required />
        </label>
        <label class="flex flex-col gap-1">
          <span class="text-sm text-gray-600">C&oacute;digo</span>
          <input v-model="nuevoCurso.codigo" class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring" required />
        </label>
        <label class="md:col-span-2 flex flex-col gap-1">
          <span class="text-sm text-gray-600">Descripci&oacute;n</span>
          <input v-model="nuevoCurso.descripcion" class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring" />
        </label>
        <label class="flex flex-col gap-1">
          <span class="text-sm text-gray-600">Cr&eacute;ditos</span>
          <input v-model="nuevoCurso.creditos" type="number" class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring" required />
        </label>
        <label class="flex flex-col gap-1">
          <span class="text-sm text-gray-600">Nivel</span>
          <select v-model="nuevoCurso.nivel" class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring" required>
            <option value="pregrado">Pregrado</option>
            <option value="postgrado">Postgrado</option>
          </select>
        </label>
        <label class="flex flex-col gap-1">
          <span class="text-sm text-gray-600">Modalidad</span>
          <input v-model="nuevoCurso.modalidad" class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring" required />
        </label>
        <!-- Selección de docente -->
        <select v-if="currentUserRole !== 'docente'" v-model="nuevoCurso.docente_id" class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring" required>
          <option value="">Seleccione un docente</option>
          <option v-for="docente in docentes" :key="docente.id" :value="docente.id">
            {{ docente.nombre }} {{ docente.apellido }}
          </option>
        </select>
        <input v-else class="border border-border p-2 rounded bg-muted text-foreground" :value="docentes.find(d => d.id === Number(nuevoCurso.docente_id))?.nombre ?? 'Mi usuario'" disabled />
        <label class="flex flex-col gap-1">
          <span class="text-sm text-gray-600">Responsable</span>
          <select v-model="nuevoCurso.responsable_id" class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring">
          <option value="">Seleccione responsable</option>
          <option v-for="responsable in responsables" :key="responsable.id" :value="responsable.id">
            {{ responsable.name }}
          </option>
        </select>
        </label>
        <label class="md:col-span-2 flex flex-col gap-1">
          <span class="text-sm text-gray-600">URL de Google Drive</span>
          <input v-model="nuevoCurso.drive_url" class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring" />
        </label>
        <div class="md:col-span-2 flex justify-end">
          <button type="submit" class="bg-primary text-primary-foreground px-4 py-2 rounded hover:opacity-90 transition">Guardar</button>
        </div>
      </form>
      <!-- Grid de tarjetas -->
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
        <div
          v-for="curso in cursos"
          :key="curso.id"
          class="rounded-xl shadow-lg bg-card text-foreground border border-border relative overflow-hidden group hover:shadow-2xl transition-shadow min-h-[270px] flex flex-col"
        >
          <!-- Banner superior -->
          <div class="h-20 bg-gradient-to-r from-green-600 to-blue-600 flex items-end px-4 pb-2 relative">
            <h2 class="text-white text-lg font-bold truncate w-3/4">{{ curso.nombre }}</h2>
            <span class="absolute right-4 top-4 bg-white rounded-full p-1 shadow">
              <img src="/resources/js/pages/Cursos/snoppy.png" alt="logo" class="w-8 h-8 object-contain" />
            </span>
          </div>
          <div class="p-4 flex-1">
            <div class="text-xs text-muted-foreground mb-1">Docente: {{ curso.docente?.nombre ?? 'Sin asignar' }}</div>
            <div class="text-xs text-muted-foreground mb-1">Responsable: {{ curso.responsable?.name ?? 'Sin asignar' }}</div>
            <div class="text-sm text-foreground mb-2 truncate">{{ curso.descripcion ?? 'Ver tus tareas' }}</div>
          </div>
          <!-- Botones -->
          <div class="flex justify-end gap-2 px-4 pb-4 border-t pt-2">
            <!-- Ícono para Google Drive -->
            <button
              @click="abrirGoogleDrive(curso.drive_url)"
              class="text-blue-600 hover:text-blue-800 transition"
              title="Abrir Google Drive"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v4a1 1 0 001 1h3m10 0h3a1 1 0 001-1V7m-1 4V7a2 2 0 00-2-2H6a2 2 0 00-2 2v4m16 0v10a2 2 0 01-2 2H6a2 2 0 01-2-2V11" />
              </svg>
            </button>
            <!-- Botón para editar -->
            <button
              @click="editarCurso(curso.id)"
              class="text-yellow-600 hover:text-yellow-800 transition"
              title="Editar curso"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5h6m-6 4h6m-6 4h6m-6 4h6M5 5h.01M5 9h.01M5 13h.01M5 17h.01" />
              </svg>
            </button>
            <!-- Botón para eliminar -->
            <button
              @click="eliminarCurso(curso.id)"
              class="text-red-600 hover:text-red-800 transition"
              title="Eliminar curso"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
