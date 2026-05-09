import fs from 'node:fs/promises';
import path from 'node:path';

const root = process.cwd();
const sourceDir = path.join(root, 'ovh');
const buildDir = path.join(root, 'ovh-build');
const publicDir = path.join(root, 'public');
const snapshotPath = path.join(root, 'data/content-snapshot.json');

async function exists(targetPath) {
  try {
    await fs.access(targetPath);
    return true;
  } catch {
    return false;
  }
}

async function copyIfExists(sourcePath, destinationPath) {
  if (!(await exists(sourcePath))) {
    return;
  }

  await fs.cp(sourcePath, destinationPath, { recursive: true, force: true });
}

async function copyRequired(sourcePath, destinationPath, description) {
  if (!(await exists(sourcePath))) {
    throw new Error(`${description} introuvable : ${sourcePath}`);
  }

  await fs.cp(sourcePath, destinationPath, { recursive: true, force: true });
}

async function main() {
  await fs.rm(buildDir, { recursive: true, force: true });
  await fs.cp(sourceDir, buildDir, { recursive: true, force: true });

  await copyIfExists(path.join(publicDir, 'uploads'), path.join(buildDir, 'uploads'));
  await copyIfExists(path.join(publicDir, 'favicon.ico'), path.join(buildDir, 'favicon.ico'));
  await copyIfExists(path.join(publicDir, 'favicon.svg'), path.join(buildDir, 'favicon.svg'));
  await copyRequired(path.join(root, 'db', 'schema.sql'), path.join(buildDir, 'install', 'schema.sql'), 'Schéma SQL');
  await copyRequired(path.join(root, 'db', 'ovh-seed.sql'), path.join(buildDir, 'install', 'seed.sql'), 'Seed SQL');
  await copyRequired(snapshotPath, path.join(buildDir, 'data', 'content-snapshot.json'), 'Snapshot de contenu PHP');

  await fs.mkdir(path.join(buildDir, 'uploads', 'cms'), { recursive: true });

  const envExample = path.join(buildDir, '.env.example');
  if (await exists(envExample)) {
    await fs.rename(envExample, path.join(buildDir, '.env.example.txt'));
  }

  console.log(`Dossier de déploiement prêt : ${buildDir}`);
  console.log('Contenu prêt à uploader sur OVH :');
  console.log('- fichiers PHP du mini-CMS');
  console.log('- images historiques depuis public/uploads');
  console.log('- favicons');
  console.log('- install/schema.sql et install/seed.sql pour l\'import initial');
  console.log('- data/content-snapshot.json pour les blocs publics PHP hors base');
  console.log('- dossier uploads/cms pour les futurs uploads');
}

main().catch((error) => {
  console.error(error);
  process.exit(1);
});