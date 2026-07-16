// d:\romipan\websiteddu\supabase-config.js
import { createClient } from 'https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2/+esm';

// Nilai asli sengaja dihapus saat migrasi ke backend PHP.
const SUPABASE_URL = 'https://PROJECT-ID.supabase.co';
const SUPABASE_ANON_KEY = 'REDACTED';

export const supabase = createClient(SUPABASE_URL, SUPABASE_ANON_KEY);
