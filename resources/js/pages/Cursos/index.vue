<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const page = usePage();
const docentes = page.props.docentes ?? [];
const responsables = page.props.responsables ?? [];
const cursos = page.props.cursos ?? [];

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
const periodoSeleccionado = ref(usePage().props.periodo ?? '2025-2'); // Obtén el período desde el backend o usa un valor por defecto

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
      router.reload(); // Recarga la página para reflejar los cambios
    },
    onError: (errors) => {
      console.error(errors); // Muestra los errores en la consola
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
        router.reload(); // Recarga la página para reflejar los cambios
      }
    });
  }
}

function cambiarPeriodo() {
  router.get('/cursos', { periodo: periodoSeleccionado.value });
}
</script>

<template>
  <AppLayout>
    <div class="p-8 bg-gray-100 min-h-screen">
      <h1 class="text-3xl font-bold mb-8 text-center">Asignaturas "{{ periodoSeleccionado }}"</h1>
      <!-- Selector de período -->
      <select
        v-model="periodoSeleccionado"
        @change="cambiarPeriodo"
        class="border p-2 rounded mb-4"
      >
        <option value="2025-2">2025-2</option>
        <option value="2026-0">2026-0</option>
        <option value="2026-1">2026-1</option>
      </select>
      <!-- Botón para mostrar el formulario -->
      <div class="flex justify-end mb-4">
        <button
          @click="mostrarFormulario = !mostrarFormulario"
          class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition"
        >
          + Agregar curso
        </button>
      </div>
      <!-- Formulario para agregar curso -->
      <form
        v-if="mostrarFormulario"
        @submit.prevent="agregarCurso"
        class="bg-white p-4 rounded shadow mb-6 flex flex-col gap-2 max-w-md mx-auto"
      >
        <input v-model="nuevoCurso.nombre" placeholder="Nombre del curso" class="border p-2 rounded" required />
        <input v-model="nuevoCurso.codigo" placeholder="Código" class="border p-2 rounded" required />
        <input v-model="nuevoCurso.descripcion" placeholder="Descripción" class="border p-2 rounded" />
        <input v-model="nuevoCurso.creditos" type="number" placeholder="Créditos" class="border p-2 rounded" required />
        <select v-model="nuevoCurso.nivel" class="border p-2 rounded" required>
          <option value="pregrado">Pregrado</option>
          <option value="postgrado">Postgrado</option>
        </select>
        <input v-model="nuevoCurso.modalidad" placeholder="Modalidad" class="border p-2 rounded" required />
        <!-- Selección de docente -->
        <select v-model="nuevoCurso.docente_id" class="border p-2 rounded" required>
          <option value="">Seleccione un docente</option>
          <option v-for="docente in docentes" :key="docente.id" :value="docente.id">
            {{ docente.nombre }} {{ docente.apellido }}
          </option>
        </select>
        <select v-model="nuevoCurso.responsable_id" class="border p-2 rounded">
          <option value="">Seleccione responsable</option>
          <option v-for="responsable in responsables" :key="responsable.id" :value="responsable.id">
            {{ responsable.name }}
          </option>
        </select>
        <input v-model="nuevoCurso.drive_url" placeholder="URL de Google Drive" class="border p-2 rounded" />
      
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Guardar</button>
      </form>
      <!-- Grid de tarjetas -->
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
        <div
          v-for="curso in cursos"
          :key="curso.id"
          class="rounded-xl shadow-lg bg-white border relative overflow-hidden group hover:shadow-2xl transition-shadow min-h-[270px] flex flex-col"
        >
          <!-- Banner superior -->
          <div class="h-20 bg-gradient-to-r from-green-600 to-blue-600 flex items-end px-4 pb-2 relative">
            <h2 class="text-white text-lg font-bold truncate w-3/4">{{ curso.nombre }}</h2>
            <span class="absolute right-4 top-4 bg-white rounded-full p-1 shadow">
              <img src="/resources/js/pages/Cursos/snoppy.png" alt="logo" class="w-8 h-8 object-contain" />
            </span>
          </div>
          <div class="p-4 flex-1">
            <div class="text-xs text-gray-500 mb-1">Docente: {{ curso.docente?.nombre ?? 'Sin asignar' }}</div>
            <div class="text-xs text-gray-500 mb-1">Responsable: {{ curso.responsable?.name ?? 'Sin asignar' }}</div>
            <div class="text-sm text-gray-700 mb-2 truncate">{{ curso.descripcion ?? 'Ver tus tareas' }}</div>
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
