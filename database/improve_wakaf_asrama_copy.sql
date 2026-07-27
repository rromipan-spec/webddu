-- Jalankan satu kali melalui phpMyAdmin untuk memperbarui redaksi Program Wakaf Asrama Santri.
-- Query hanya menyentuh program dengan slug wakaf-asrama-santri.
UPDATE programs
SET
    excerpt = 'Wujudkan asrama yang layak, aman, dan nyaman bagi para Santri Yatim Dhuafa Penjaga Al-Qur''an Pesantren Daarul Uluum Bogor.',
    hero_title = 'Bangun Rumah Perjuangan Para Santri Penjaga Al-Qur''an',
    hero_subtitle = 'Wakaf Anda membantu menghadirkan tempat tinggal, belajar, beribadah, dan bertumbuh bagi generasi penjaga Al-Qur''an.',
    content = '<h2>Bangun Rumah Perjuangan Para Santri Penjaga Al-Qur’an</h2>
<p>Setiap hari, para santri yatim dan dhuafa di Pesantren Daarul Uluum Bogor belajar, beribadah, serta menghafal Al-Qur’an dengan penuh kesungguhan. Di tengah semangat itu, mereka masih membutuhkan tempat tinggal yang lebih layak, aman, dan nyaman untuk mendukung proses pendidikan serta pembinaan.</p>
<blockquote>Asrama bukan sekadar tempat beristirahat. Di sanalah para santri menata disiplin, memperkuat persaudaraan, menjaga hafalan, dan mempersiapkan diri menjadi generasi yang bermanfaat bagi umat.</blockquote>
<h2>Mengapa Program Ini Penting?</h2>
<ul>
<li>Menyediakan ruang tinggal yang aman dan layak bagi para santri.</li>
<li>Mendukung kegiatan belajar, ibadah, dan menghafal Al-Qur’an.</li>
<li>Menciptakan lingkungan pembinaan yang tertib, sehat, dan nyaman.</li>
<li>Menjadi sarana amal jariyah yang manfaatnya terus dirasakan.</li>
</ul>
<h2>Wakaf Anda Menjadi Manfaat Berkelanjutan</h2>
<p>Melalui Program Wakaf Asrama Santri, Dompet Dana Umat Daarul Uluum mengajak Ayah, Bunda, Kakak, dan seluruh Sahabat Kebaikan untuk ikut membangun serta merenovasi rumah perjuangan para santri. Setiap dukungan akan diarahkan untuk kebutuhan pembangunan sesuai tahapan program dan dilaporkan melalui kanal resmi lembaga.</p>
<h2>Mari Ambil Bagian</h2>
<p>Tidak harus menunggu mampu memberi dalam jumlah besar. Setiap wakaf yang ditunaikan dengan ikhlas akan menyempurnakan ikhtiar bersama dalam menghadirkan tempat terbaik bagi para penjaga Al-Qur’an.</p>
<p><strong>Klik “Wakaf Sekarang” untuk memperoleh informasi penyaluran melalui WhatsApp resmi Dompet Dana Umat.</strong></p>',
    seo_title = 'Wakaf Asrama Santri Penjaga Al-Qur''an | DDU',
    seo_description = 'Bantu bangun asrama layak bagi Santri Yatim Dhuafa Penjaga Al-Qur''an Pesantren Daarul Uluum Bogor melalui Program Wakaf Asrama DDU.'
WHERE slug = 'wakaf-asrama-santri';
