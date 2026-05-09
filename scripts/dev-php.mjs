#!/usr/bin/env node
// Lance le site PHP en local via Docker Compose : MariaDB + PHP 8.4.
// Usage : npm run php:dev

import { spawn, spawnSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const composeFile = path.join(root, 'docker-compose.yml');
const schemaFile = path.join(root, 'ovh-build/install/schema.sql');
const seedFile = path.join(root, 'ovh-build/install/seed.sql');
const stateDir = path.join(root, '.local-state');
const installedFlag = path.join(stateDir, 'php-dev-db-installed');

const DB_CONTAINER = 'immobilier_mariadb';
const DB_NAME = 'immobilier';
const DB_USER = 'immobilier';
const DB_PASSWORD = 'immobilier';

function log(msg) {
  console.log(`\x1b[36m[php:dev]\x1b[0m ${msg}`);
}

function run(cmd, args, opts = {}) {
  const result = spawnSync(cmd, args, { stdio: 'inherit', ...opts });
  if (result.status !== 0) {
    throw new Error(`Command failed: ${cmd} ${args.join(' ')}`);
  }
  return result;
}

function dockerCompose(args) {
  return run('docker', ['compose', '-f', composeFile, ...args]);
}

function ensureFiles() {
  if (!fs.existsSync(schemaFile) || !fs.existsSync(seedFile)) {
    log('Préparation du build PHP local…');
    run('npm', ['run', 'ovh:prepare']);
  }
  fs.mkdirSync(stateDir, { recursive: true });
}

function startStack() {
  log('Build des images et démarrage des services Docker…');
  dockerCompose(['up', '-d', '--build']);
}

function importSqlFile(file) {
  const content = fs.readFileSync(file, 'utf8');
  const result = spawnSync(
    'docker',
    ['exec', '-i', DB_CONTAINER, 'mariadb', '-u', DB_USER, `-p${DB_PASSWORD}`, DB_NAME],
    { input: content, stdio: ['pipe', 'inherit', 'inherit'] },
  );
  if (result.status !== 0) {
    throw new Error(`Import SQL en échec : ${file}`);
  }
}

function importSchemaAndSeed() {
  if (fs.existsSync(installedFlag)) {
    log('Schéma + seed déjà importés (npm run php:db:reset pour repartir à zéro).');
    return;
  }
  log('Import du schéma…');
  importSqlFile(schemaFile);
  log('Import du seed…');
  importSqlFile(seedFile);
  fs.writeFileSync(installedFlag, new Date().toISOString());
  log('Base initialisée.');
}

function streamPhpLogs() {
  log('Site dispo : http://127.0.0.1:8000');
  log('Admin     : http://127.0.0.1:8000/admin/login.php');
  log('Ctrl+C pour décrocher des logs (les conteneurs restent up ; npm run php:stop pour tout arrêter).');

  const logs = spawn('docker', ['compose', '-f', composeFile, 'logs', '-f', 'php'], {
    stdio: 'inherit',
  });

  const stop = () => logs.kill('SIGTERM');
  process.on('SIGINT', stop);
  process.on('SIGTERM', stop);

  logs.on('exit', (code) => {
    process.exit(code ?? 0);
  });
}

(() => {
  try {
    ensureFiles();
    startStack();
    importSchemaAndSeed();
    streamPhpLogs();
  } catch (error) {
    console.error(`\x1b[31m[php:dev] ${error.message}\x1b[0m`);
    process.exit(1);
  }
})();
