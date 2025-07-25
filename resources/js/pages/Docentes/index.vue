<script setup lang="ts">            
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage, Link, router } from '@inertiajs/vue3';
import {Docente, type BreadcrumbItem, type SharedData } from '@/types';    
import { Table,TableBody, TableCaption , TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
// Iconos
import { CirclePlus, Pencil, Trash } from 'lucide-vue-next';
import { computed } from 'vue';

interface DocentPageProps extends SharedData{
    docents: Docente[];    
}

const props = usePage<DocentPageProps>();
const docents= computed(()=> props.props.docents);
//breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [{title:"Docentes", href:'/docents'}]
//método para eliminar

const deleteDocente = (id: number) => {
    if (confirm('¿Estás seguro de que deseas eliminar este docente?')) {
        router.delete(`/docents/${id}`, {
            onSuccess: () => {
                // Aquí puedes manejar la respuesta después de eliminar el docente
                console.log('Docente eliminado con éxito');
                router.visit('/docents', {replace: true}); // Redirigir a la lista de docentes después de eliminar
            },
            onError: () => {
                // Aquí puedes manejar el error si la eliminación falla
                console.error('Error al eliminar el docente');
            },
        });
    }
};
</script>

<template>  
  <head title="Docentes"/>
  <AppLayout :breadcrumbs="breadcrumbs"> 
    <!-- Mostrar mensaje de éxito -->
    <div v-if="$page.props.flash && $page.props.flash.success" class="bg-green-100 text-green-800 p-2 rounded mb-4">
      {{ $page.props.flash.success }}
    </div>

    <div class="flex h-full flex-1 flex-col gap-9 rounded-xl p-4">
      <div class="flex">
        <button as-child size="sa" class="bg-indigo-500 text-white hover:bg-indigo-700 px-4 py-2 rounded">  
          <Link href="/docents/create">
            <CirclePlus /> Crear Docente
          </Link>
        </button>
      </div>
    </div> 

    <div class="relative min-h-screen flex-1 overflow-hidden rounded-xl border bg-background p-4 shadow-sm">
      <Table>
        <TableCaption>Lista de Docentes</TableCaption>
        <TableHeader>
          <TableRow>
            <TableHead class="w-[100px]">ID</TableHead>
            <TableHead class="text-center">Nombre</TableHead>
            <TableHead class="text-center">Apellido</TableHead>
            <TableHead class="text-center">DNI</TableHead>
            <TableHead class="text-center">Email</TableHead>
            <TableHead class="text-center">Teléfono</TableHead>
            <TableHead class="text-center">Especialidad</TableHead>
            <TableHead class="text-center">CV Sunedu</TableHead>
            <TableHead class="text-center">CV Personal</TableHead>
            <TableHead class="text-center">LinkedIn</TableHead>
            <TableHead class="text-center">Estado</TableHead>
            <TableHead class="text-center">CIP</TableHead>
            <TableHead class="text-center">Acciones</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-for="(docent, index) in docents" :key="index" class="hover:bg-gray-100 cursor-pointer">
            <TableCell class="font-medium">{{ index + 1 }}</TableCell>
            <TableCell>{{ docent.nombre }}</TableCell>
            <TableCell>{{ docent.apellido }}</TableCell>
            <TableCell>{{ docent.dni }}</TableCell>
            <TableCell>{{ docent.email ?? 'N/A' }}</TableCell>
            <TableCell>{{ docent.telefono }}</TableCell>
            <TableCell>{{ docent.especialidad }}</TableCell>
            <TableCell>
              <template v-if="docent.cv_personal">
                <a :href="`/storage/${docent.cv_personal}`" target="_blank">Ver CV Personal</a>
              </template>
              <template v-else>
                No disponible
              </template>
            </TableCell>
            <TableCell>
              <template v-if="docent.cv_sunedu">
                <a :href="`/storage/${docent.cv_sunedu}`" target="_blank">Ver CV Sunedu</a>
              </template>
              <template v-else>
                No disponible
              </template>
            </TableCell>
            <TableCell>{{ docent.linkedin }}</TableCell>
            <TableCell>{{ docent.activo !== undefined ? (docent.activo ? 'Activo' : 'Inactivo') : 'N/A' }}</TableCell>
            <TableCell>{{ docent.cip }}</TableCell>
            <TableCell class="flex justify-center gap-2">
                <!-- Botón para Editar -->
                <button as-child size="sa" class="bg-blue-500 text-white hover:bg-blue-700">
                    <Link :href="'/docents/' + docent.id + '/edit'">
                        <Pencil />
                    </Link>
                </button>
                <!-- Botón para Eliminar -->
                <button class="bg-red-500 text-white hover:bg-red-700" @click="deleteDocente(docent.id)">
                    <Trash />
                </button>
                
            </TableCell>
          </TableRow>
        </TableBody> 
      </Table>
    </div>
  </AppLayout>
</template>
<!-- No extra code needed here. You can safely remove $SELECTION_PLACEHOLDER$ or leave it empty. -->
