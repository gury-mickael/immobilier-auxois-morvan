import type { APIRoute } from 'astro';
import { logoutAdmin } from '../../lib/server/auth';

export const GET: APIRoute = async ({ session, url }) => {
  logoutAdmin(session);
  return Response.redirect(new URL('/admin/login', url), 302);
};