<template>
      <div class="bg-white rounded-lg shadow p-6">
            <!-- Header -->
            <div class="flex items-center justify-between mb-4">
                  <h2 class="text-lg font-semibold text-gray-800">Product List</h2>

                  <div class="flex gap-3 items-center">
                        <input v-model="productStore.search" @keyup.enter="productStore.fetch(1)" type="text"
                              placeholder="Search product..."
                              class="border border-gray-200 rounded px-3 py-1.5 text-sm focus:ring focus:ring-blue-200 focus:outline-none" />

                        <button @click="productStore.fetch(1)"
                              class="bg-blue-600 text-white text-sm px-3 py-1.5 rounded hover:bg-blue-700">
                              Search
                        </button>

                        <button @click="clearSearch" :disabled="productStore.loading || !productStore.search"
                              class="bg-gray-200 text-gray-700 text-sm px-3 py-1.5 rounded hover:bg-gray-300 disabled:opacity-50">
                              Clear Search
                        </button>

                        <span class="h-6 border-l border-gray-300 mx-2"></span>

                        <button @click="openModal" :disabled="productStore.loading || productStore.list.length === 0"
                              class="bg-red-600 text-white text-sm px-3 py-1.5 rounded hover:bg-red-700 disabled:opacity-50">
                              Clear All Products
                        </button>
                  </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                  <table class="min-w-full border-collapse border border-gray-200 text-sm">
                        <thead class="bg-gray-100">
                              <tr>
                                    <th
                                          class="border border-gray-200 p-3 text-left text-gray-700 text-sm font-semibold">
                                          #</th>
                                    <th
                                          class="border border-gray-200 p-3 text-left text-gray-700 text-sm font-semibold">
                                          Unique Key</th>
                                    <th
                                          class="border border-gray-200 p-3 text-left text-gray-700 text-sm font-semibold">
                                          Title</th>
                                    <th
                                          class="border border-gray-200 p-3 text-left text-gray-700 text-sm font-semibold">
                                          Color</th>
                                    <th
                                          class="border border-gray-200 p-3 text-left text-gray-700 text-sm font-semibold">
                                          Size</th>
                                    <th
                                          class="border border-gray-200 p-3 text-left text-gray-700 text-sm font-semibold">
                                          Price</th>
                              </tr>
                        </thead>
                        <tbody>
                              <tr v-for="(item, i) in productStore.list" :key="item.id" class="hover:bg-gray-50">
                                    <td class="border border-gray-200 p-3 text-sm text-gray-600">
                                          {{ i + 1 + (productStore.pagination.current_page - 1) *
                                          productStore.pagination.per_page }}
                                    </td>
                                    <td class="border border-gray-200 p-3 text-sm text-gray-600">{{ item.unique_key }}
                                    </td>
                                    <td class="border border-gray-200 p-3 text-sm text-gray-600">{{
                                          decodeHtml(item.title) }}</td>
                                    <td class="border border-gray-200 p-3 text-sm text-gray-600">{{ item.color_name ||
                                          '-' }}</td>
                                    <td class="border border-gray-200 p-3 text-sm text-gray-600">{{ item.size || '-' }}
                                    </td>
                                    <td class="border border-gray-200 p-3 text-sm text-gray-600 text-right">
                                          {{ formatPrice(item.piece_price) }}
                                    </td>
                              </tr>

                              <tr v-if="!productStore.loading && productStore.list.length === 0">
                                    <td colspan="6" class="text-center p-4 text-gray-400">No data found</td>
                              </tr>
                        </tbody>
                  </table>
            </div>

            <div class="flex items-center justify-between mt-4 text-sm text-gray-600">
                  <div>Showing {{ productStore.pagination.current_page }} / {{ productStore.pagination.last_page }}
                        pages</div>
                  <div class="flex gap-1">
                        <button @click="productStore.fetch(productStore.pagination.current_page - 1)"
                              :disabled="productStore.pagination.current_page <= 1 || productStore.loading"
                              class="px-3 py-1 border border-gray-200 rounded disabled:opacity-40 hover:bg-gray-100">
                              Prev
                        </button>
                        <button @click="productStore.fetch(productStore.pagination.current_page + 1)"
                              :disabled="productStore.pagination.current_page >= productStore.pagination.last_page || productStore.loading"
                              class="px-3 py-1 border border-gray-200 rounded disabled:opacity-40 hover:bg-gray-100">
                              Next
                        </button>
                  </div>
            </div>

            <div v-if="showModal"
                  class="fixed inset-0 backdrop-blur-sm bg-white/30 flex items-center justify-center z-50">
                  <div class="bg-white border border-gray-200 rounded-lg shadow-lg w-96 p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">Confirm Deletion</h3>
                        <p class="text-gray-600 text-sm mb-6">
                              Are you sure you want to <span class="font-semibold text-red-600">delete all
                                    products</span>?
                              This action cannot be undone.
                        </p>
                        <div class="flex justify-end gap-3">
                              <button @click="closeModal"
                                    class="px-4 py-2 text-sm bg-gray-200 rounded hover:bg-gray-300">
                                    Cancel
                              </button>
                              <button @click="confirmClear" :disabled="productStore.loading"
                                    class="px-4 py-2 text-sm bg-red-600 text-white rounded hover:bg-red-700 disabled:opacity-50">
                                    {{ productStore.loading ? 'Deleting...' : 'Confirm' }}
                              </button>
                        </div>
                  </div>
            </div>
      </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useProductStore } from "@/stores/product";

const productStore = useProductStore();
const showModal = ref(false);

onMounted(() => productStore.fetch());

const openModal = () => (showModal.value = true);
const closeModal = () => (showModal.value = false);

const clearSearch = () => {
      productStore.search = "";
      productStore.fetch(1);
};

const confirmClear = async () => {
      await productStore.clearAll();
      closeModal();
};

const formatPrice = (p) => (p != null ? `$${Number(p).toFixed(2)}` : "-");
const decodeHtml = (text) => {
      if (!text) return "";
      const el = document.createElement("textarea");
      el.innerHTML = text;
      return el.value;
};
</script>