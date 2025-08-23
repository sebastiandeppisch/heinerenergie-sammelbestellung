<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Button } from '@/shadcn/components/ui/button';
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/shadcn/components/ui/card';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/shadcn/components/ui/table';
import { Badge } from '@/shadcn/components/ui/badge';
import { route } from 'ziggy-js';
import { Plus, Edit, Trash2, ArrowLeft } from 'lucide-vue-next';

const props = defineProps<{
  categories: Array<App.Data.CategoryData>;
}>();

function deleteCategory(categoryId: string) {
  if (confirm('Sind Sie sicher, dass Sie diese Kategorie löschen möchten?')) {
    router.delete(route('categories.destroy', categoryId));
  }
}
</script>

<template>
  <div class="container mx-auto py-8">
    <div class="max-w-6xl mx-auto">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-3xl font-bold">Kategorien</h1>
          <p class="text-gray-600 mt-2">Verwalte Kategorien für Kartenpunkte</p>
        </div>
        
        <div class="flex gap-2">
          <Button
            variant="outline"
            @click="router.visit(route('mappoints.index'))"
          >
            <ArrowLeft class="h-4 w-4 mr-2"/>
            Zurück zu Kartenpunkten
          </Button>
          
          <Button @click="router.visit(route('categories.create'))">
            <Plus class="h-4 w-4 mr-2"/>
            Neue Kategorie
          </Button>
        </div>
      </div>

      <Card>
        <CardContent class="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Bild</TableHead>
                <TableHead>Name</TableHead>
                <TableHead>Kartenpunkte</TableHead>
                <TableHead>Erstellt</TableHead>
                <TableHead class="text-right">Aktionen</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="category in categories" :key="category.id">
                <TableCell>
                  <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden">
                    <img 
                      v-if="category.image_path" 
                      :src="`/storage/${category.image_path}`" 
                      :alt="category.name"
                      class="w-full h-full object-cover"
                    />
                    <span v-else class="text-gray-400 text-xs">Kein Bild</span>
                  </div>
                </TableCell>
                <TableCell class="font-medium">
                  {{ category.name }}
                </TableCell>
                <TableCell>
                  <Badge variant="secondary">
                    {{ category.map_points_count }} Punkte
                  </Badge>
                </TableCell>
                <TableCell class="text-gray-500">
                  {{ category.created_at ? new Date(category.created_at).toLocaleDateString('de-DE') : '-' }}
                </TableCell>
                <TableCell class="text-right">
                  <div class="flex justify-end gap-2">
                    <Button
                      variant="outline"
                      size="sm"
                      @click="router.visit(route('categories.edit', category.id))"
                    >
                      <Edit class="h-4 w-4"/>
                    </Button>
                    <Button
                      variant="destructive"
                      size="sm"
                      @click="deleteCategory(category.id)"
                    >
                      <Trash2 class="h-4 w-4"/>
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
              
              <TableRow v-if="categories.length === 0">
                <TableCell colspan="5" class="text-center py-8 text-gray-500">
                  Noch keine Kategorien erstellt
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
