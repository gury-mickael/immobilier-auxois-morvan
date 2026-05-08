import { mkdir, readdir, stat, writeFile } from 'node:fs/promises';
import path from 'node:path';
import { randomUUID } from 'node:crypto';
import { getUploadDirectory, getUploadPublicBase } from './config';

const ALLOWED_MIME_TYPES = new Set(['image/jpeg', 'image/png', 'image/webp']);
const ALLOWED_EXTENSIONS = new Set(['.jpg', '.jpeg', '.png', '.webp']);
const MAX_FILE_SIZE = 5 * 1024 * 1024;

export interface StoredMediaFile {
  fileName: string;
  originalName: string;
  publicUrl: string;
  mimeType: string;
  sizeBytes: number;
}

export async function ensureUploadDirectory() {
  await mkdir(getUploadDirectory(), { recursive: true });
}

function sanitizeStem(value: string) {
  return value
    .normalize('NFKD')
    .replace(/[^a-zA-Z0-9\s-]/g, '')
    .trim()
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-')
    .toLowerCase()
    .slice(0, 80);
}

function getExtension(file: File) {
  const originalExtension = path.extname(file.name).toLowerCase();

  if (ALLOWED_EXTENSIONS.has(originalExtension)) {
    return originalExtension;
  }

  if (file.type === 'image/jpeg') return '.jpg';
  if (file.type === 'image/png') return '.png';
  if (file.type === 'image/webp') return '.webp';

  return '';
}

export async function saveUploadedImage(file: File): Promise<StoredMediaFile> {
  if (!ALLOWED_MIME_TYPES.has(file.type)) {
    throw new Error('Format non autorisé. Utilisez JPG, PNG ou WebP.');
  }

  if (file.size <= 0 || file.size > MAX_FILE_SIZE) {
    throw new Error('Le fichier dépasse la taille maximale autorisée de 5 Mo.');
  }

  const extension = getExtension(file);

  if (!extension) {
    throw new Error('Extension de fichier non autorisée.');
  }

  await ensureUploadDirectory();

  const stem = sanitizeStem(path.basename(file.name, path.extname(file.name))) || 'image';
  const fileName = `${stem}-${randomUUID().slice(0, 8)}${extension}`;
  const targetPath = path.join(getUploadDirectory(), fileName);
  const buffer = Buffer.from(await file.arrayBuffer());

  await writeFile(targetPath, buffer, { flag: 'wx' });

  return {
    fileName,
    originalName: file.name,
    publicUrl: `${getUploadPublicBase()}/${fileName}`,
    mimeType: file.type,
    sizeBytes: file.size
  };
}

export async function listUploadedFiles() {
  await ensureUploadDirectory();
  const entries = await readdir(getUploadDirectory(), { withFileTypes: true });
  const files = await Promise.all(
    entries
      .filter((entry) => entry.isFile())
      .filter((entry) => ALLOWED_EXTENSIONS.has(path.extname(entry.name).toLowerCase()))
      .map(async (entry) => {
        const fullPath = path.join(getUploadDirectory(), entry.name);
        const fileStat = await stat(fullPath);

        return {
          fileName: entry.name,
          publicUrl: `${getUploadPublicBase()}/${entry.name}`,
          sizeBytes: fileStat.size,
          createdAt: fileStat.birthtime.toISOString()
        };
      })
  );

  return files.sort((left, right) => right.createdAt.localeCompare(left.createdAt));
}