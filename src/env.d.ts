/// <reference path="../.astro/types.d.ts" />

declare namespace App {
  interface SessionData {
    adminUserId: number;
    adminUserEmail: string;
    adminUserName: string;
  }
}

interface ImportMetaEnv {
  readonly DB_HOST?: string;
  readonly DB_PORT?: string;
  readonly DB_USER?: string;
  readonly DB_PASSWORD?: string;
  readonly DB_NAME?: string;
  readonly DB_SSL?: string;
  readonly CMS_UPLOAD_DIR?: string;
  readonly CMS_SESSION_TTL?: string;
}

interface ImportMeta {
  readonly env: ImportMetaEnv;
}