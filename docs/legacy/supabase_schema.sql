-- 1. Tabel untuk Artikel/Berita
CREATE TABLE IF NOT EXISTS posts (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    title TEXT NOT NULL,
    slug TEXT UNIQUE NOT NULL,
    date TEXT, -- Menyimpan format tanggal seperti "12 Okt 2023"
    image TEXT, -- URL gambar dari Supabase Storage
    excerpt TEXT,
    content TEXT, -- Konten HTML artikel
    whatsapp_number TEXT
);

-- 2. Tabel untuk Program di Navbar
CREATE TABLE IF NOT EXISTS programs (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    title TEXT NOT NULL,
    slug TEXT UNIQUE NOT NULL,
    image TEXT,
    excerpt TEXT,
    hero_title TEXT,
    hero_subtitle TEXT,
    content TEXT,
    whatsapp_number TEXT
);

-- 3. Mengaktifkan Row Level Security (RLS)
-- Ini agar tidak sembarang orang bisa mengubah data lewat API
ALTER TABLE posts ENABLE ROW LEVEL SECURITY;
ALTER TABLE programs ENABLE ROW LEVEL SECURITY;

-- 4. Kebijakan Akses: Siapa pun bisa membaca data (Public Read)
DROP POLICY IF EXISTS "Allow public read access on posts" ON posts;
CREATE POLICY "Allow public read access on posts" ON posts 
    FOR SELECT USING (true);

DROP POLICY IF EXISTS "Allow public read access on programs" ON programs;
CREATE POLICY "Allow public read access on programs" ON programs 
    FOR SELECT USING (true);

-- 5. Kebijakan Akses: Hanya Admin (User yang Login) yang bisa CRUD
-- Syarat: Anda harus sudah mendaftarkan email di Authentication > Users
DROP POLICY IF EXISTS "Allow authenticated full access on posts" ON posts;
CREATE POLICY "Allow authenticated full access on posts" ON posts 
    FOR ALL TO authenticated 
    USING (true) WITH CHECK (true);

DROP POLICY IF EXISTS "Allow authenticated full access on programs" ON programs;
CREATE POLICY "Allow authenticated full access on programs" ON programs 
    FOR ALL TO authenticated 
    USING (true) WITH CHECK (true);

-- 6. Kebijakan Akses untuk Storage (Penting agar bisa upload gambar)
-- Pastikan Anda sudah membuat bucket bernama 'images' di menu Storage dan centang "Public"

-- Membuat bucket 'images' secara otomatis jika belum ada
INSERT INTO storage.buckets (id, name, public)
VALUES ('images', 'images', true)
ON CONFLICT (id) DO NOTHING;

-- Izinkan siapa saja melihat gambar
DROP POLICY IF EXISTS "Gambar Bisa Dilihat Publik" ON storage.objects;
CREATE POLICY "Gambar Bisa Dilihat Publik" ON storage.objects 
    FOR SELECT USING (bucket_id = 'images');

-- Izinkan hanya admin yang login untuk mengunggah gambar
DROP POLICY IF EXISTS "Admin Bisa Kelola Gambar" ON storage.objects;
CREATE POLICY "Admin Bisa Kelola Gambar" ON storage.objects 
    FOR ALL TO authenticated 
    USING (bucket_id = 'images') 
    WITH CHECK (bucket_id = 'images');