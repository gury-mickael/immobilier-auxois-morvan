import mysql from 'mysql2/promise';
import type { Pool } from 'mysql2/promise';
import { getDatabaseConfig, isDatabaseConfigured } from './config';

let pool: Pool | null = null;

export function getDbPool() {
  if (!isDatabaseConfigured()) {
    throw new Error('La base de données CMS n\'est pas configurée.');
  }

  if (!pool) {
    const config = getDatabaseConfig();

    pool = mysql.createPool({
      host: config.host,
      port: config.port,
      user: config.user,
      password: config.password,
      database: config.database,
      waitForConnections: true,
      connectionLimit: 10,
      namedPlaceholders: true,
      charset: 'utf8mb4',
      ssl: config.ssl ? { minVersion: 'TLSv1.2' } : undefined
    });
  }

  return pool;
}

export async function withDb<T>(callback: (db: Pool) => Promise<T>, fallback: T): Promise<T> {
  if (!isDatabaseConfigured()) {
    return fallback;
  }

  try {
    return await callback(getDbPool());
  } catch (error) {
    console.error('[cms] database query failed', error);
    return fallback;
  }
}