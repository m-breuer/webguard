import { readdir, stat } from "node:fs/promises";
import { resolve } from "node:path";

const maximumJavaScriptAssetBytes = 200 * 1024;
const maximumInitialJavaScriptBytes = 1024 * 1024;
const immutableDirectory = resolve("build/client/_app/immutable");

async function filesIn(directory) {
    const entries = await readdir(directory, { withFileTypes: true });
    const files = await Promise.all(entries.map(async (entry) => {
        const path = resolve(directory, entry.name);

        return entry.isDirectory() ? filesIn(path) : [path];
    }));

    return files.flat();
}

const javascriptFiles = (await filesIn(immutableDirectory)).filter((file) => file.endsWith(".js"));
const assets = await Promise.all(javascriptFiles.map(async (file) => ({ file, bytes: (await stat(file)).size })));
const totalBytes = assets.reduce((total, asset) => total + asset.bytes, 0);
const oversizedAssets = assets.filter((asset) => asset.bytes > maximumJavaScriptAssetBytes);

if (oversizedAssets.length > 0 || totalBytes > maximumInitialJavaScriptBytes) {
    const violations = oversizedAssets.map((asset) => `${asset.file}: ${asset.bytes} bytes`);

    if (totalBytes > maximumInitialJavaScriptBytes) {
        violations.push(`total immutable JavaScript: ${totalBytes} bytes`);
    }

    throw new Error(`SvelteKit production budget exceeded:\n${violations.join("\n")}`);
}

console.log(`SvelteKit production budgets passed: ${assets.length} JavaScript assets, ${totalBytes} bytes total.`);
