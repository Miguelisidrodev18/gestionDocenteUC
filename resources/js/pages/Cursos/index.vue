<script setup>
import { ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const cursos = ref(page.props.cursos); // <-- Recibe los cursos del backend

const nuevoMaterial = ref({ archivo: null, cursoId: null });

function handleFileChange(e, cursoId) {
  nuevoMaterial.value.archivo = e.target.files[0];
  nuevoMaterial.value.cursoId = cursoId;
}

async function agregarMaterial(cursoId) {
  if (!nuevoMaterial.value.archivo || nuevoMaterial.value.archivo.type !== 'application/pdf') {
    alert('Por favor selecciona un archivo PDF.');
    return;
  }

  const formData = new FormData();
  formData.append('archivo', nuevoMaterial.value.archivo);
  formData.append('curso_id', cursoId);

  // Cambia la URL por la de tu endpoint en Laravel
  const response = await fetch('/api/materiales', {
    method: 'POST',
    body: formData
  });

  if (response.ok) {
    const material = await response.json();
    const curso = cursos.value.find(c => c.id === cursoId);
    curso.materiales.push(material);
    nuevoMaterial.value = { archivo: null, cursoId: null };
  } else {
    alert('Error al subir el archivo');
  }
}
</script>

<template>
  <div class="p-4">
    <h1 class="text-2xl font-bold mb-4">Cursos</h1>
    <div v-for="curso in cursos" :key="curso.id" class="mb-6 border rounded p-4">
      <h2 class="text-xl font-semibold">{{ curso.nombre }} (NRC: {{ curso.nrc }})</h2>
      <ul class="mb-2">
        <li v-for="material in curso.materiales" :key="material.id">
          <a :href="material.url" target="_blank" class="text-blue-600 underline">{{ material.nombre }}</a>
        </li>
      </ul>
      <form @submit.prevent="agregarMaterial(curso.id)" class="flex gap-2 items-center">
        <input type="file" accept="application/pdf" @change="e => handleFileChange(e, curso.id)" />
        <button type="submit" class="bg-blue-500 text-white px-3 py-1 rounded">Agregar PDF</button>
      </form>
    </div>
  </div>
</template>