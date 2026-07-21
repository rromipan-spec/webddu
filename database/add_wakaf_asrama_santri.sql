-- Jalankan file ini melalui phpMyAdmin setelah database/schema.sql diimpor.
-- Jika slug yang sama sudah ada, data program akan diperbarui tanpa membuat duplikat.

INSERT INTO programs (
    title,
    slug,
    image,
    excerpt,
    hero_title,
    hero_subtitle,
    content,
    whatsapp_number,
    whatsapp_message
) VALUES (
    'Wakaf Asrama Santri',
    'wakaf-asrama-santri',
    'https://lh3.googleusercontent.com/d/1kuC0kI5fPd_FA0emvuSlRcFSpXQb0KGE',
    'Bangun rumah perjuangan yang layak, nyaman, dan aman bagi para santri yatim dhuafa penjaga Al-Qur’an Pesantren Daarul Uluum Bogor.',
    'Bangun Rumah Perjuangan Para Santri Yatim Dhuafa Penjaga Al-Qur’an',
    'Wakaf asrama untuk mendukung tempat tinggal, pembinaan, ibadah, dan perjuangan menuntut ilmu para santri.',
    '<h2>Wakaf Asrama Santri</h2>
<p>Setiap hari, para Santri Yatim Dhuafa Penjaga Al-Qur’an Pesantren Daarul Uluum Bogor menempuh perjalanan menuntut ilmu, menghafal Al-Qur’an, memperdalam ilmu agama, serta mempersiapkan diri menjadi generasi yang bermanfaat bagi umat. Namun, keterbatasan fasilitas asrama masih menjadi tantangan dalam proses pendidikan mereka.</p>
<p>Melalui <strong>Program Wakaf Asrama Santri</strong>, Dompet Dana Umat Daarul Uluum mengajak Ayah, Bunda, Kakak, dan para dermawan untuk bersama-sama membangun serta merenovasi asrama yang layak, nyaman, dan aman sebagai tempat tinggal sekaligus pusat pembinaan bagi para santri.</p>
<h3>Rumah untuk Belajar, Beribadah, dan Bertumbuh</h3>
<p>Di asrama inilah para santri menjalani berbagai kegiatan pembinaan:</p>
<ul>
<li>Belajar dan memperdalam ilmu agama.</li>
<li>Beribadah dan menghafal Al-Qur’an.</li>
<li>Membangun akhlak mulia dan kemandirian.</li>
<li>Mempersiapkan diri menjadi penerus dakwah Islam.</li>
</ul>
<blockquote><strong>InsyaAllah, setiap bata yang terpasang akan menjadi amal jariyah yang pahalanya terus mengalir selama dimanfaatkan oleh para santri.</strong></blockquote>
<h3>Jadilah Bagian dari Perjuangan Mereka</h3>
<p>Klik <strong>“Wakaf Sekarang”</strong> dan ikut membangun rumah perjuangan bagi para Santri Yatim Dhuafa Penjaga Al-Qur’an Pesantren Daarul Uluum Bogor.</p>',
    '6285121277046',
    'Assalamualaikum, saya ingin ikut berwakaf untuk Program Wakaf Asrama Santri Pesantren Daarul Uluum Bogor.'
)
ON DUPLICATE KEY UPDATE
    title = VALUES(title),
    image = VALUES(image),
    excerpt = VALUES(excerpt),
    hero_title = VALUES(hero_title),
    hero_subtitle = VALUES(hero_subtitle),
    content = VALUES(content),
    whatsapp_number = VALUES(whatsapp_number),
    whatsapp_message = VALUES(whatsapp_message);
