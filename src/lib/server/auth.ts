import bcrypt from 'bcryptjs';
import { getSessionTtlSeconds } from './config';
import { withDb } from './db';

export interface AdminUser {
  id: number;
  name: string;
  email: string;
  role: string;
}

interface DbAdminUser extends AdminUser {
  password_hash: string;
  is_active: number;
}

export async function getAdminFromSession(session: AstroSessionLike) {
  const adminUserId = await session.get<number>('adminUserId');
  const adminUserEmail = await session.get<string>('adminUserEmail');
  const adminUserName = await session.get<string>('adminUserName');

  if (!adminUserId || !adminUserEmail || !adminUserName) {
    return null;
  }

  return {
    id: adminUserId,
    email: adminUserEmail,
    name: adminUserName,
    role: 'admin'
  } satisfies AdminUser;
}

export async function verifyAdminCredentials(email: string, password: string) {
  const normalizedEmail = email.trim().toLowerCase();

  if (!normalizedEmail || !password) {
    return null;
  }

  const user = await withDb(async (db) => {
    const [rows] = await db.query(
      'SELECT id, name, email, role, password_hash, is_active FROM cms_admin_users WHERE email = ? LIMIT 1',
      [normalizedEmail]
    );

    return Array.isArray(rows) ? (rows[0] as DbAdminUser | undefined) : undefined;
  }, undefined as DbAdminUser | undefined);

  if (!user || !user.is_active) {
    return null;
  }

  const passwordMatches = await bcrypt.compare(password, user.password_hash);

  if (!passwordMatches) {
    return null;
  }

  return {
    id: user.id,
    email: user.email,
    name: user.name,
    role: user.role
  } satisfies AdminUser;
}

export async function hashAdminPassword(password: string) {
  const value = password.trim();

  if (!value) {
    throw new Error('Le mot de passe ne peut pas être vide.');
  }

  return bcrypt.hash(value, 12);
}

export async function loginAdmin(session: AstroSessionLike, user: AdminUser) {
  await session.regenerate();
  const ttl = getSessionTtlSeconds();
  session.set('adminUserId', user.id, { ttl });
  session.set('adminUserEmail', user.email, { ttl });
  session.set('adminUserName', user.name, { ttl });
}

export function logoutAdmin(session: AstroSessionLike) {
  session.destroy();
}

export interface AstroSessionLike {
  get<T = unknown>(key: string): Promise<T | undefined>;
  set<T = unknown>(key: string, value: T, options?: { ttl?: number }): void;
  regenerate(): Promise<void>;
  destroy(): void;
}