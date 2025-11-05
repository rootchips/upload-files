<template>
      <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                  <h2 class="text-lg font-semibold text-gray-800">Product List</h2>
                  <div class="flex gap-3">
                        <input v-model="store.search" @keyup.enter="store.fetch(1)" type="text"
                              placeholder="Search product..."
                              class="border border-gray-200 rounded px-3 py-1.5 text-sm focus:ring focus:ring-blue-200 focus:outline-none" />
                        <button @click="store.fetch(1)"
                              class="bg-blue-600 text-white text-sm px-3 py-1.5 rounded hover:bg-blue-700">
                              Search
                        </button>
                  </div>
            </div>

            <div class="overflow-x-auto">
                  <table class="min-w-full border-collapse border border-gray-200 text-sm">
                        <thead class="bg-gray-100">
                              <tr>
                                    <th class="border border-gray-200 p-3 text-left text-gray-700 text-sm font-semibold">#</th>
                                    <th class="border border-gray-200 p-3 text-left text-gray-700 text-sm font-semibold">Unique Key</th>
                                    <th class="border border-gray-200 p-3 text-left text-gray-700 text-sm font-semibold">Title</th>
                                    <th class="border border-gray-200 p-3 text-left text-gray-700 text-sm font-semibold">Color</th>
                                    <th class="border border-gray-200 p-3 text-left text-gray-700 text-sm font-semibold">Size</th>
                                    <th class="border border-gray-200 p-3 text-left text-gray-700 text-sm font-semibold">Price</th>
                              </tr>
                        </thead>
                        <tbody>
                              <tr v-if="store.loading">
                                    <td colspan="6" class="text-center p-4 text-gray-400">Loading...</td>
                              </tr>

                              <tr v-for="(item, i) in store.list" :key="item.id" class="hover:bg-gray-50">
                                    <td class="border border-gray-200 p-3 text-sm text-gray-600">{{ i + 1 + (store.pagination.current_page - 1) *
                                          store.pagination.per_page }}</td>
                                    <td class="border border-gray-200 p-3 text-sm text-gray-600">{{ item.unique_key }}</td>
                                    <td class="border border-gray-200 p-3 text-sm text-gray-600">{{ decodeHtml(item.product_title) }}</td>
                                    <td class="border border-gray-200 p-3 text-sm text-gray-600">{{ item.color_name || '-' }}</td>
                                    <td class="border border-gray-200 p-3 text-sm text-gray-600">{{ item.size || '-' }}</td>
                                    <td class="border border-gray-200 p-3 text-sm text-gray-600 text-right">{{ formatPrice(item.piece_price) }}</td>
                              </tr>

                              <tr v-if="!store.loading && store.list.length === 0">
                                    <td colspan="6" class="text-center p-4 text-gray-400">No data found</td>
                              </tr>
                        </tbody>
                  </table>
            </div>

            <div class="flex items-center justify-between mt-4 text-sm text-gray-600">
                  <div>Showing {{ store.pagination.current_page }} / {{ store.pagination.last_page }} pages</div>
                  <div class="flex gap-1">
                        <button @click="store.fetch(store.pagination.current_page - 1)"
                              :disabled="store.pagination.current_page <= 1"
                              class="px-3 py-1 border border-gray-200 rounded disabled:opacity-40 hover:bg-gray-100">
                              Prev
                        </button>
                        <button @click="store.fetch(store.pagination.current_page + 1)"
                              :disabled="store.pagination.current_page >= store.pagination.last_page"
                              class="px-3 py-1 border border-gray-200 rounded disabled:opacity-40 hover:bg-gray-100">
                              Next
                        </button>
                  </div>
            </div>
      </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useProductStore } from '@/stores/product';

const store = useProductStore();

onMounted(() => store.fetch());

const formatPrice = (p) => (p ? `$${Number(p).toFixed(2)}` : '-');

const decodeHtml = (text) => {
  const el = document.createElement('textarea');
  el.innerHTML = text;
  return el.value;
};
</script>