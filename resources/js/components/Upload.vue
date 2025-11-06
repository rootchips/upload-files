<template>
      <div class="bg-white rounded-lg shadow p-6">
            <div class="border-2 border-dashed rounded-lg p-8 flex items-center justify-between transition-colors duration-150"
                  :class="[
                        isDragging ? 'bg-gray-100 border-gray-400' : 'border-gray-300 hover:border-blue-400',
                        loading ? 'cursor-not-allowed' : 'cursor-pointer'
                  ]" role="button" tabindex="0" @click="!loading && triggerFile()"
                  @keydown.enter.prevent="!loading && triggerFile()" @keydown.space.prevent="!loading && triggerFile()"
                  @dragenter.stop.prevent="onDragEnter" @dragover.stop.prevent="onDragOver"
                  @dragleave.stop.prevent="onDragLeave" @drop.stop.prevent="onDrop">
                  <div class="flex-1 text-left">
                        <p class="text-gray-600 font-medium mb-0">Select files / Drag and drop</p>
                        <input ref="fileInput" id="uploader" type="file" class="hidden" accept=".csv" multiple
                              @change="onFileSelect" />
                        <p v-if="error" class="text-red-600 text-sm mt-3">{{ error }}</p>
                  </div>

                  <button @click.stop="triggerFile()" :disabled="loading"
                        class="ml-6 px-4 py-2 rounded text-white transition-colors"
                        :class="loading ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700'">
                        <span v-if="!loading">Upload Files</span>
                        <span v-else class="flex items-center justify-center gap-2">
                              <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                          stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor"
                                          d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                              </svg>
                              Uploading...
                        </span>
                  </button>
            </div>

            <div class="mt-6">
                  <table class="min-w-full border-collapse border border-gray-200 rounded-lg">
                        <thead class="bg-gray-100">
                              <tr>
                                    <th
                                          class="border border-gray-200 p-3 text-left text-gray-700 text-sm font-semibold">
                                          Time</th>
                                    <th
                                          class="border border-gray-200 p-3 text-left text-gray-700 text-sm font-semibold">
                                          File Name</th>
                                    <th
                                          class="border border-gray-200 p-3 text-left text-gray-700 text-sm font-semibold">
                                          Status / Progress</th>
                              </tr>
                        </thead>
                        <tbody>
                              <tr v-for="item in uploads" :key="item.id" class="hover:bg-gray-50 transition-colors">
                                    <td class="border border-gray-200 p-3 text-sm text-gray-600">
                                          <div>{{ formatTime(item.created_at) }}</div>
                                          <div class="text-xs text-gray-400">({{ timeAgo(item.created_at) }})</div>
                                    </td>
                                    <td class="border border-gray-200 p-3 text-sm text-gray-700">{{ item.file_name }}
                                    </td>
                                    <td class="border border-gray-200 p-3 text-sm">
                                          <template v-if="progress[item.id] && item.status !== 'completed'">
                                                <div class="w-full bg-gray-200 rounded-full h-2">
                                                      <div class="bg-blue-600 h-2 rounded-full transition-all duration-200"
                                                            :style="{ width: progress[item.id] + '%' }"></div>
                                                </div>
                                                <div class="text-xs text-gray-500 mt-1">{{ progress[item.id] }}%</div>
                                          </template>
                                          <template v-else>
                                                <span :class="{
                                                      'text-yellow-600': item.status === 'pending' || item.status === 'processing',
                                                      'text-green-600': item.status === 'completed',
                                                      'text-red-600': item.status === 'failed',
                                                }">
                                                      {{ item.status }}
                                                </span>
                                          </template>
                                    </td>
                              </tr>

                              <tr v-if="uploads.length === 0">
                                    <td colspan="3" class="text-center text-gray-400 p-4">No files uploaded yet.</td>
                              </tr>
                        </tbody>
                  </table>
            </div>
      </div>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import { useUploadStore } from "@/stores/upload";
import { storeToRefs } from "pinia";

const isDragging = ref(false);
const dragCount = ref(0);
const fileInput = ref(null);
const store = useUploadStore();
const { list: uploads, progress } = storeToRefs(store);
const error = ref("");
const loading = computed(() => store.uploading);

const triggerFile = () => !loading.value && fileInput.value?.click();

const hasFiles = (e) => Array.from(e.dataTransfer?.types || []).includes("Files");

const onDragEnter = (e) => {
      if (loading.value || !hasFiles(e)) return;
      dragCount.value++;
      isDragging.value = true;
};

const onDragOver = (e) => {
      if (loading.value || !hasFiles(e)) return;
      e.dataTransfer.dropEffect = "copy";
      isDragging.value = true;
};

const onDragLeave = (e) => {
      if (loading.value || !hasFiles(e)) return;
      dragCount.value = Math.max(0, dragCount.value - 1);
      if (dragCount.value === 0) isDragging.value = false;
};

const onDrop = (e) => {
      if (!hasFiles(e)) return;
      dragCount.value = 0;
      isDragging.value = false;
      if (loading.value) return;

      const files = Array.from(e.dataTransfer.files || []).filter(f => f.name.endsWith(".csv"));
      if (files.length) handleMultipleFiles(files);
};

const onFileSelect = (e) => {
      const files = Array.from(e.target.files || []);
      if (files.length) handleMultipleFiles(files);
};

const handleMultipleFiles = async (files) => {
      error.value = "";
      
      for (const file of files) {
            await handleFile(file);
      }

      fileInput.value.value = "";
};

const handleFile = async (file) => {
      const allowed = ["text/csv", "application/vnd.ms-excel"];
      if (!allowed.includes(file.type) && !file.name.endsWith(".csv")) {
            error.value = "Only CSV files are allowed.";
            return;
      }
      if (file.size > 100 * 1024 * 1024) {
            error.value = "File too large. Maximum 100MB allowed.";
            return;
      }
      try {
            await store.uploadFile(file);
      } catch (err) {
            console.error(err);
            error.value = "Upload failed. Please try again.";
      }
};

const formatTime = (datetime) => {
      if (!datetime) return "-";
      return new Date(datetime).toLocaleString("en-MY", { hour12: true });
};

const timeAgo = (datetime) => {
      if (!datetime) return "-";
      const diff = (new Date() - new Date(datetime)) / 60000;
      if (diff < 1) return "just now";
      if (diff < 60) return `${Math.floor(diff)} minutes ago`;
      if (diff < 1440) return `${Math.floor(diff / 60)} hours ago`;
      return `${Math.floor(diff / 1440)} days ago`;
};

onMounted(async () => {
      await store.fetchUploads();
      await store.hydrateProgress();
      store.ensureRealtime();
});
</script>

<style scoped>
table {
      width: 100%;
}
</style>