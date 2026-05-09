import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const envPath = path.join(root, '.env.deploy');

function parseEnvFile(filePath) {
  if (!fs.existsSync(filePath)) {
    throw new Error('Fichier .env.deploy introuvable.');
  }

  const values = {};
  const lines = fs.readFileSync(filePath, 'utf8').split(/\r?\n/);

  for (const line of lines) {
    const trimmed = line.trim();
    if (!trimmed || trimmed.startsWith('#') || !trimmed.includes('=')) {
      continue;
    }

    const [key, ...parts] = trimmed.split('=');
    values[key.trim()] = parts.join('=').trim().replace(/^['"]|['"]$/g, '');
  }

  return values;
}

function run(command, args, options = {}) {
  const result = spawnSync(command, args, {
    stdio: 'inherit',
    cwd: root,
    shell: false,
    ...options,
  });

  if (result.status !== 0) {
    throw new Error(`Commande échouée : ${command}`);
  }
}

function commandExists(command) {
  return spawnSync('bash', ['-lc', `command -v ${command} >/dev/null 2>&1`], { stdio: 'ignore' }).status === 0;
}

function quoteLftp(value) {
  return String(value).replaceAll('\\', '\\\\').replaceAll('"', '\\"');
}

const env = { ...process.env, ...parseEnvFile(envPath) };
const protocol = env.OVH_DEPLOY_PROTOCOL || 'sftp';
const host = env.OVH_DEPLOY_HOST;
const user = env.OVH_DEPLOY_USER;
const password = env.OVH_DEPLOY_PASSWORD;
const port = env.OVH_DEPLOY_PORT || (protocol === 'sftp' ? '22' : '21');
const remoteDir = env.OVH_DEPLOY_REMOTE_DIR || '/';
const localDir = env.OVH_DEPLOY_LOCAL_DIR || 'ovh-build';
const skipBuild = process.argv.includes('--no-build');

for (const [key, value] of Object.entries({ OVH_DEPLOY_HOST: host, OVH_DEPLOY_USER: user, OVH_DEPLOY_PASSWORD: password })) {
  if (!value) {
    throw new Error(`${key} est manquant dans .env.deploy.`);
  }
}

if (!commandExists('lftp')) {
  throw new Error('lftp est requis. Installe-le avec : sudo apt-get update && sudo apt-get install -y lftp');
}

if (!skipBuild) {
  run('npm', ['run', 'ovh:prepare']);
}

const localPath = path.resolve(root, localDir);
if (!fs.existsSync(localPath)) {
  throw new Error(`Dossier local introuvable : ${localPath}`);
}

const targetUrl = `${protocol}://${host}:${port}`;
const commands = [
  'set net:max-retries 2',
  'set net:timeout 20',
  protocol === 'sftp' ? 'set sftp:auto-confirm yes' : '',
  `open -u "${quoteLftp(user)}","${quoteLftp(password)}" "${quoteLftp(targetUrl)}"`,
  remoteDir === '.' || remoteDir === 'www' ? '' : `mkdir -p "${quoteLftp(remoteDir)}"`,
  `mirror -R --only-newer --verbose --parallel=3 --exclude-glob .env --exclude-glob .env.example.txt --exclude-glob "uploads/cms/*" "${quoteLftp(localPath)}" "${quoteLftp(remoteDir)}"`,
  'bye',
].filter(Boolean).join('; ');

run('lftp', ['-c', commands], { stdio: ['ignore', 'inherit', 'inherit'] });
console.log('Déploiement OVH terminé.');
