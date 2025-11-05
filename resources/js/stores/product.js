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
                this.list = response.data;

                this.pagination = {
                    current_page: response.current_page,
                    per_page: response.per_page,
                    total: response.total,
                    last_page: response.last_page,
                };
            } finally {
                this.loading = false;
            }
        },
    },
});
