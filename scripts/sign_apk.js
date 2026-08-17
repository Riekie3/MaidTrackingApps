// One-off script: runs Bubblewrap's own build+sign orchestration
// (assembleRelease -> zipalign -> apksigner) non-interactively, reusing
// its exact logic instead of hand-rolling the signing step. Must be run
// with cwd = the TWA project directory (C:/Android/maidtrack-twa).
const CORE_PATH = 'C:/Users/user31a/AppData/Roaming/npm/node_modules/@bubblewrap/cli/node_modules/@bubblewrap/core';
const CLI_BUILD_PATH = 'C:/Users/user31a/AppData/Roaming/npm/node_modules/@bubblewrap/cli/dist/lib/cmds/build.js';
const { Config, GradleWrapper, util } = require(CORE_PATH);
const { build } = require(CLI_BUILD_PATH);

// This machine doesn't implicitly search cwd for bare command names (only
// explicit ".\" paths resolve), so GradleWrapper's bare 'gradlew.bat' fails
// with "not recognized" even though the file is right there ('gradleCmd' is
// set as an own instance property in the constructor, so patching the
// prototype field wouldn't shadow it — override the method instead).
GradleWrapper.prototype.executeGradleCommand = async function (args) {
    const env = this.androidSdkTools.getEnv();
    const cmd = process.platform === 'win32' ? '.\\gradlew.bat' : './gradlew';
    await util.executeFile(cmd, args, env, undefined, this.projectLocation);
};

// Windows Node processes can carry both a 'PATH' and a 'Path' env key.
// Deleting either triggers Node's case-insensitive env-key folding on
// Windows in confusing ways, so instead just make both identical and
// leave both in place — whichever one downstream code reads, it's correct.
{
    const merged = [process.env.PATH, process.env.Path].filter(Boolean).join(';');
    process.env.PATH = merged;
    process.env.Path = merged;
}

// Keystore/key password come from the environment — never hardcode a
// signing password into a committed script. See CREDENTIALS.md (gitignored,
// local only) for the actual test-keystore password used on this machine.
if (!process.env.BUBBLEWRAP_KEYSTORE_PASSWORD || !process.env.BUBBLEWRAP_KEY_PASSWORD) {
    console.error('Set BUBBLEWRAP_KEYSTORE_PASSWORD and BUBBLEWRAP_KEY_PASSWORD before running this script.');
    process.exit(1);
}

const config = new Config('C:/Android/jdk17/jdk-17.0.20+8', 'C:/Android/sdk');
const args = { skipSigning: false };
const stubPrompt = {
    printMessage: (msg) => console.log(msg),
    promptConfirm: async () => true,
    promptInput: async (msg, def) => def,
    promptPassword: async () => { throw new Error('should not be called — env passwords set'); },
    promptChoice: async (msg, choices, def) => def,
};

build(config, args, undefined, stubPrompt)
    .then((ok) => {
        console.log(ok ? 'BUILD+SIGN DONE' : 'BUILD returned false');
        process.exit(ok ? 0 : 1);
    })
    .catch((e) => {
        console.error(e);
        process.exit(1);
    });
