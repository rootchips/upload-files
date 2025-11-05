import { defineStore } from "pinia";
import api from "@/plugins/axios";
import Echo from "laravel-echo";
import Pusher from "pusher-js";

export const useUploadStore = defineStore("uploads", {
    state: () => ({
        list: [],
        progress: {},
        realTime: false,
    }),

    getters: {
        uploading(state) {
            return state.list.some(
                (u) =>
                    (u.status === "pending" || u.status === "processing") &&
                    (state.progress[u.id] ?? 0) < 100
            );
        },
    },

    actions: {
        async fetchUploads() {
            const res = await api.get("/uploads");
            this.list = res.data;
        },

        async hydrateProgress() {
            const active = this.list.filter((u) => u.status !== "completed" && u.status !== "failed");
            if (!active.length) return;

            const calls = active.map(async (u) => {
                try {
                    const { data } = await api.get(`/uploads/${u.id}/progress`);
                    const pct = Number(data?.progress ?? 0);
                    if (!Number.isNaN(pct)) this.progress[u.id] = pct;
                } catch (error) {
                    //
                }
            });

            await Promise.allSettled(calls);
        },

        async uploadFile(file) {
            const form = new FormData();

            form.append("file", file);

            const res = await api.post("/uploads", form);

            this.list.unshift(res.data);

            this.progress[res.data.id] = 0;

            this.ensureRealtime();
        },

        ensureRealtime() {
            if (this.realTime) return;

            window.Pusher = Pusher;
            window.Echo = new Echo({
                broadcaster: "reverb",
                key: import.meta.env.VITE_REVERB_APP_KEY ?? "localkey",
                wsHost: import.meta.env.VITE_REVERB_HOST ?? "127.0.0.1",
                wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
                forceTLS: false,
                disableStats: true,
            });

            window.Echo.channel("uploads")
                .listen(".UploadProgressUpdated", (e) => {
                    this.progress[e.id] = e.progress;

                    const target = this.list.find((u) => u.id === e.id);

                    if (
                        target &&
                        target.status === "pending" &&
                        e.progress > 0
                    ) {
                        target.status = "processing";
                    }
                })
                .listen(".UploadStatusUpdated", (e) => {
                    const target = this.list.find((u) => u.id === e.id);

                    if (target) target.status = e.status;

                    if (e.status === "completed" || e.status === "failed") {
                        this.progress[e.id] =
                            e.status === "completed"
                                ? 100
                                : this.progress[e.id] ?? 0;
                    }
                });

            window.Echo.connector.pusher.connection.bind("connected", () => {
                this.hydrateProgress();
            });

            this.realTime = true;
        },
    },
});
