import { defineStore } from "pinia";
import api from "@/plugins/axios";

export const useProductStore = defineStore("products", {
    state: () => ({
        list: [],
        pagination: {
            current_page: 1,
            per_page: 10,
            total: 0,
            last_page: 1,
        },
        search: "",
        loading: false,
    }),
    actions: {
        async fetch(page = 1) {
            this.loading = true;
            try {
                const res = await api.get("/products", {
                    params: {
                        page,
                        per_page: this.pagination.per_page,
                        search: this.search || "",
                    },
                });
                const response = res.data;
                this.list = response.data || [];
                const meta = response.meta || {};
                this.pagination = {
                    current_page: meta.current_page ?? page,
                    per_page: meta.per_page ?? this.pagination.per_page,
                    total:
                        meta.total ??
                        (response.data ? response.data.length : 0),
                    last_page: meta.last_page ?? 1,
                };
            } finally {
                this.loading = false;
            }
        },
        async clearAll() {
            this.loading = true;
            try {
                await api.delete("/products/clear");
                this.list = [];
                this.pagination.total = 0;
            } finally {
                this.loading = false;
            }
        },
    },
});
