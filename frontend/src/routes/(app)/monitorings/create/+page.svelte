<script lang="ts">
    import MonitoringForm from "$lib/components/MonitoringForm.svelte";
    import type { MonitoringFormOptions } from "$lib/api/monitoring";

    interface Props { data: { form: MonitoringFormOptions }; }
    let { data }: Props = $props();
</script>

<svelte:head><title>Create monitoring | WebGuard</title></svelte:head>

<main class="mx-auto w-[min(52rem,calc(100%_-_2rem))] py-6 sm:py-12">
    <header class="mb-8"><p class="m-0 text-[0.8125rem] font-extrabold tracking-[0.1em] text-wg-accent uppercase">Operations</p><h1 class="mt-2 text-[clamp(2rem,6vw,3rem)] leading-[1.1] font-bold">Create monitoring</h1><p class="mt-3 leading-6 text-wg-text-muted">Configure a monitoring and start collecting results.</p></header>
    <MonitoringForm options={data.form} action="/api/v1/internal/ui/monitorings" method="POST" />
</main>
