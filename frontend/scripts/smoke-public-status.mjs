import { chromium } from "playwright";

const baseUrl = process.env.SMOKE_BASE_URL;
const statusPageId = process.env.SMOKE_STATUS_PAGE_ID;
const unsubscribeToken = process.env.SMOKE_UNSUBSCRIBE_TOKEN;

if (!baseUrl || !statusPageId || !unsubscribeToken) {
    throw new Error("SMOKE_BASE_URL, SMOKE_STATUS_PAGE_ID, and SMOKE_UNSUBSCRIBE_TOKEN are required.");
}

const browser = await chromium.launch({ headless: true });

try {
    for (const viewport of [{ width: 1280, height: 800 }, { width: 390, height: 844 }]) {
        const page = await browser.newPage({ viewport });
        const consoleErrors = [];

        page.on("console", (message) => {
            if (message.type() === "error") {
                consoleErrors.push(message.text());
            }
        });

        const response = await page.goto(`${baseUrl}/status/${statusPageId}`, { waitUntil: "networkidle" });
        const navigationDuration = await page.evaluate(() => performance.getEntriesByType("navigation")[0]?.duration ?? 0);

        if (response?.status() !== 200) {
            throw new Error(`Public status page returned ${response?.status() ?? "no response"}.`);
        }

        if (navigationDuration > 1000) {
            throw new Error(`Public status page exceeded its 1000 ms rendering budget: ${navigationDuration.toFixed(1)} ms.`);
        }

        await page.getByRole("heading", { level: 1, name: "SvelteKit Browser Smoke" }).waitFor();
        await page.getByLabel("Email address").fill("smoke@example.test");
        await page.getByLabel("Email address").focus();

        const keyboardReachable = await page.evaluate(() => document.activeElement?.id === "subscriber-email");
        const noHorizontalOverflow = await page.evaluate(() => document.body.scrollWidth <= document.documentElement.clientWidth);

        if (!keyboardReachable || !noHorizontalOverflow || consoleErrors.length > 0) {
            throw new Error(JSON.stringify({ consoleErrors, keyboardReachable, noHorizontalOverflow, viewport }));
        }

        await page.close();
    }

    const unsubscribePage = await browser.newPage({ viewport: { width: 1280, height: 800 } });
    const unsubscribeResponse = await unsubscribePage.goto(`${baseUrl}/status/${statusPageId}/subscribers/unsubscribe/${unsubscribeToken}`, { waitUntil: "networkidle" });

    if (unsubscribeResponse?.status() !== 200) {
        throw new Error(`Unsubscribe page returned ${unsubscribeResponse?.status() ?? "no response"}.`);
    }

    await unsubscribePage.getByRole("heading", { level: 1, name: "Unsubscribe from updates" }).waitFor();
    await unsubscribePage.getByLabel("Email address").fill("sveltekit-browser-smoke@example.test");
    await unsubscribePage.getByRole("button", { name: "Unsubscribe" }).click();
    await unsubscribePage.waitForURL(`${baseUrl}/status/${statusPageId}?subscription=unsubscribed`);
    await unsubscribePage.getByRole("status").filter({ hasText: "You have been unsubscribed." }).waitFor();
    await unsubscribePage.close();
} finally {
    await browser.close();
}
