// One-off script: scaffolds the MaidTrack TWA Android project using
// @bubblewrap/core directly, bypassing the interactive CLI wizard (all
// values are already known/decided, so there's nothing to ask a human).
// Not part of the app runtime — used once to generate android-twa/.
const path = require('path');
const fs = require('fs');

const CORE_PATH = 'C:/Users/user31a/AppData/Roaming/npm/node_modules/@bubblewrap/cli/node_modules/@bubblewrap/core';
const { TwaManifest, TwaGenerator, KeyTool, JdkHelper, Config, ConsoleLog } = require(CORE_PATH);

const targetDirectory = 'C:/Android/maidtrack-twa';
const manifestUrl = process.argv[2];
if (!manifestUrl) {
    console.error('Usage: node build_twa.js <manifest-url>');
    process.exit(1);
}

(async () => {
    // Windows Node processes can carry both a 'PATH' and a 'Path' env key;
    // Bubblewrap's JdkHelper only prepends the JDK bin dir to one of them.
    // Deleting either triggers Node's case-insensitive env-key folding on
    // Windows in confusing ways, so just make both identical instead.
    {
        const merged = [process.env.PATH, process.env.Path].filter(Boolean).join(';');
        process.env.PATH = merged;
        process.env.Path = merged;
    }

    if (!fs.existsSync(targetDirectory)) fs.mkdirSync(targetDirectory, { recursive: true });

    const twaManifest = await TwaManifest.fromWebManifest(manifestUrl);
    twaManifest.packageId = 'com.maidtrack.app';
    twaManifest.signingKey.path = path.join(targetDirectory, 'android.keystore');
    twaManifest.signingKey.alias = 'maidtrack';

    await twaManifest.saveToFile(path.join(targetDirectory, 'twa-manifest.json'));

    const twaGenerator = new TwaGenerator();
    const log = new ConsoleLog('Generating TWA');
    await twaGenerator.createTwaProject(targetDirectory, twaManifest, log, () => {});

    const config = new Config('C:/Android/jdk17/jdk-17.0.20+8', 'C:/Android/sdk');
    const jdkHelper = new JdkHelper(process, config);
    const keytool = new KeyTool(jdkHelper);
    await keytool.createSigningKey({
        fullName: 'MaidTrack Test',
        organizationalUnit: 'Engineering',
        organization: 'MaidTrack',
        country: 'MY',
        password: 'maidtrackTest2026',
        keypassword: 'maidtrackTest2026',
        alias: twaManifest.signingKey.alias,
        path: twaManifest.signingKey.path,
    });

    console.log('DONE: project generated at ' + targetDirectory);
})().catch((e) => {
    console.error(e);
    process.exit(1);
});
