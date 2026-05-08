import path from 'node:path';

function parseBoolean(value?: string) {
  return value === '1' || value === 'true' || value === 'yes';
}

export function isDatabaseConfigured() {
  return Boolean(
    import.meta.env.DB_HOST &&
      import.meta.env.DB_USER &&
      import.meta.env.DB_NAME
  );
}

export function getDatabaseConfig() {
  return {
    host: import.meta.env.DB_HOST ?? '127.0.0.1',
    port: Number(import.meta.env.DB_PORT ?? '3306'),
    user: import.meta.env.DB_USER ?? '',
    password: import.meta.env.DB_PASSWORD ?? '',
    database: import.meta.env.DB_NAME ?? '',
    ssl: parseBoolean(import.meta.env.DB_SSL)
  };
}

export function getSessionTtlSeconds() {
  return Number(import.meta.env.CMS_SESSION_TTL ?? '28800');
}

export function getUploadDirectory() {
  return path.resolve(process.cwd(), import.meta.env.CMS_UPLOAD_DIR ?? 'public/uploads/cms');
}

export function getUploadPublicBase() {
  return '/uploads/cms';
}