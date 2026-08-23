import { chromium } from "playwright";

const baseUrl = process.env.SMOKE_BASE_URL;
const statusPageId = process.env.SMOKE_STATUS_PAGE_ID;
const statusPageSlug = process.env.SMOKE_STATUS_PAGE_SLUG;
const unsubscribeToken = process.env.SMOKE_UNSUBSCRIBE_TOKEN;

if (!baseUrl || !statusPageId || !statusPageSlug || !unsubscribeToken) {
    throw new Error("SMOKE_BASE_URL, SMOKE_STATUS_PAGE_ID, SMOKE_STATUS_PAGE_SLUG, and SMOKE_UNSUBSCRIBE_TOKEN are required.");
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
    await unsubscribePage.setExtraHTTPHeaders({ "X-Forwarded-For": "198.51.100.10" });
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

    const noJavaScriptPage = await browser.newPage({ javaScriptEnabled: false, viewport: { width: 1280, height: 800 } });
    await noJavaScriptPage.setExtraHTTPHeaders({ "X-Forwarded-For": "198.51.100.11" });
    const noJavaScriptResponse = await noJavaScriptPage.goto(`${baseUrl}/status/${statusPageId}`, { waitUntil: "domcontentloaded" });

    if (noJavaScriptResponse?.status() !== 200) {
        throw new Error(`JavaScript-free public status page returned ${noJavaScriptResponse?.status() ?? "no response"}.`);
    }

    await noJavaScriptPage.getByRole("heading", { level: 1, name: "SvelteKit Browser Smoke" }).waitFor();
    await noJavaScriptPage.getByLabel("Email address").fill("sveltekit-browser-subscription@example.test");
    await Promise.all([
        noJavaScriptPage.waitForNavigation(),
        noJavaScriptPage.getByRole("button", { name: "Subscribe" }).click(),
    ]);
    await noJavaScriptPage.getByRole("status").filter({ hasText: "Check your inbox to confirm your subscription." }).waitFor();
    await noJavaScriptPage.close();

    const legacyStatusPage = await browser.newPage({ viewport: { width: 1280, height: 800 } });
    await legacyStatusPage.setExtraHTTPHeaders({ "X-Forwarded-For": "198.51.100.12" });
    let receivedLegacyRedirect = false;
    let receivedLegacySubscriptionRedirect = false;
    legacyStatusPage.on("response", (response) => {
        if (new URL(response.url()).pathname === `/status/${statusPageSlug}` && response.status() === 301) {
            receivedLegacyRedirect = true;
        }

        if (response.request().method() === "POST" && new URL(response.url()).pathname === `/status/${statusPageSlug}` && response.status() === 307) {
            receivedLegacySubscriptionRedirect = true;
        }
    });
    await legacyStatusPage.goto(`${baseUrl}/status/${statusPageSlug}`, { waitUntil: "networkidle" });

    if (!receivedLegacyRedirect || legacyStatusPage.url() !== `${baseUrl}/status/${statusPageId}`) {
        throw new Error("Legacy public status page URL did not redirect to its canonical identifier.");
    }

    await Promise.all([
        legacyStatusPage.waitForNavigation(),
        legacyStatusPage.evaluate(({ email, slug }) => {
            const form = document.createElement("form");
            form.action = `/status/${slug}`;
            form.method = "POST";

            const emailInput = document.createElement("input");
            emailInput.name = "email";
            emailInput.type = "email";
            emailInput.value = email;
            form.append(emailInput);
            document.body.append(form);
            form.submit();
        }, { email: "legacy-sveltekit-browser-subscription@example.test", slug: statusPageSlug }),
    ]);

    if (!receivedLegacySubscriptionRedirect) {
        throw new Error("Legacy public status subscription did not preserve its POST request with a canonical redirect.");
    }

    await legacyStatusPage.close();
} finally {
    await browser.close();
}
