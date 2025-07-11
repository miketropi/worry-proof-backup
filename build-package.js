const fs = require("fs");
const path = require("path");
const archiver = require("archiver");

// ====== CONFIG =========
const pluginFile = "worry-proof-backup.php"; // name of main plugin file
const pluginSlug = "worry-proof-backup";     // slug of zip file
const excludeList = [
  "node_modules",
  "src",
  ".git",
  ".gitignore",
  ".DS_Store",
  ".vscode",
  ".idea",
  ".env",
  ".env.local",
  ".env.development.local",
  ".env.test.local",
  "package-lock.json",
  "package.json",
  "postcss.config.js",
  "tailwind.config.js",
  "webpack.mix.js",
  "build-package.js",
  "wp-backup.zip"
];
// ========================

// 1. Read version from plugin header
function getPluginVersion(filePath) {
  const content = fs.readFileSync(filePath, "utf-8");
  const match = content.match(/^\s*\*\s*Version:\s*(.+)$/m);
  return match ? match[1].trim() : "0.0.0";
}

const version = getPluginVersion(pluginFile);
const zipFileName = `${pluginSlug}.zip`;

// 2. Setup zip
const output = fs.createWriteStream(zipFileName);
const archive = archiver("zip", { zlib: { level: 9 } });

output.on("close", () => {
  console.log(`✅ ${zipFileName} created: ${archive.pointer()} total bytes`);
});

archive.on("warning", (err) => {
  if (err.code === "ENOENT") {
    console.warn(err);
  } else {
    throw err;
  }
});

archive.on("error", (err) => {
  throw err;
});

archive.pipe(output);

// 3. Build list file to include
function shouldInclude(filePath) {
  return !excludeList.some(exclude => filePath.startsWith(exclude));
}

function addFilesFromDir(dirPath) {
  fs.readdirSync(dirPath).forEach(file => {
    const fullPath = path.join(dirPath, file);
    const relativePath = path.relative(".", fullPath);

    if (!shouldInclude(relativePath)) return;

    const stats = fs.statSync(fullPath);
    if (stats.isDirectory()) {
      addFilesFromDir(fullPath);
    } else {
      archive.file(fullPath, { name: relativePath });
    }
  });
}

addFilesFromDir(".");
archive.finalize();
