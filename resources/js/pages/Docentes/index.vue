
<script setup lang="ts">            
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage, Link, router } from '@inertiajs/vue3';
import {Docente, type BreadcrumbItem, type SharedData } from '@/types';    
import { Table,TableBody, TableCaption , TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
// Iconos
import { Pencil, Trash, CirclePlus } from 'lucide-vue-next';
import { computed } from 'vue';

interface DocentPageProps extends SharedData{
    docents: Docente[];    
}

const props = usePage<DocentPageProps>();
const docents= computed(()=> props.props.docents);
//breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [{title:"Docentes", href:'/docents'}]
//método para eliminar

</script>

<template>  
    <head title="Docentes" />
    <AppLayout> 
        <div class="flex h-full flex-1 flex-col  gap-4 rounded-xl p-4">
            <div class="flex">
                <button as-child size="sa" class="bg-indigo-500 text-white hover:bg-indigo-700" >
                    <Link href="/docentes/create" class="flex items-center">
                        <CirclePlus class="w-6 h-6 mr-2" />
                        Nuevo Docente
                    </Link>
                </button>
            </div>
        </div>

    </AppLayout>
</template>