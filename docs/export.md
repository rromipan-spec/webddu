# Project Structure

```
about.css
about.html
export.md
index.html
style.css
```


## about.css

```css
/* ABOUT PAGE SPECIFIC STYLES */

/* Page Header (Static Hero) */
.page-header {
    position: relative;
    padding: 200px 0 150px;
    background-image: url('https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&w=1920&q=80');
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
    color: white;
    text-align: center;
}

/* History Section */
.history {
    padding: 80px 0;
    background-color: white;
}

.history-content {
    max-width: 900px;
    margin: 0 auto;
    text-align: center;
    font-size: 1.1rem;
    color: #4a5568;
}

.history-content p {
    margin-bottom: 25px;
    line-height: 1.8;
}

/* Vision & Mission Section */
.vision-mission {
    padding: 80px 0;
    background-color: #f0f5ff;
}

.vm-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
}

.vm-card {
    background: white;
    padding: 50px 40px;
    border-radius: 20px;
    box-shadow: 0 15px 30px rgba(0,0,0,0.05);
    transition: transform 0.3s ease;
    border-top: 5px solid #1e4b9c;
}

.vm-card:hover {
    transform: translateY(-10px);
}

.vm-icon {
    font-size: 3rem;
    margin-bottom: 20px;
}

.vm-card h3 {
    color: #0a2647;
    font-size: 2rem;
    margin-bottom: 20px;
}

.vm-list {
    list-style: none;
    text-align: left;
}

.vm-list li {
    margin-bottom: 15px;
    position: relative;
    padding-left: 30px;
    color: #2c3e5c;
}

.vm-list li::before {
    content: "✓";
    position: absolute;
    left: 0;
    color: #1e4b9c;
    font-weight: bold;
}

/* Legalitas Section */
.legalitas {
    padding: 80px 0;
    background-color: white;
    text-align: center;
}

.legal-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
    margin-top: 40px;
}

.legal-item {
    padding: 30px;
    border: 1px solid #eee;
    border-radius: 15px;
    transition: all 0.3s;
}

.legal-item:hover {
    border-color: #1e4b9c;
    box-shadow: 0 10px 20px rgba(30, 75, 156, 0.1);
}

.legal-icon {
    font-size: 3rem;
    margin-bottom: 15px;
}

.legal-item h4 {
    color: #0a2647;
    margin-bottom: 10px;
    font-size: 1.2rem;
}

.legal-item p {
    color: #666;
    font-family: monospace;
    font-size: 1rem;
}

/* Responsive */
@media (max-width: 768px) {
    .vm-grid {
        grid-template-columns: 1fr;
    }
    
    .page-header {
        padding: 160px 0 100px;
    }
}
```


## about.html

```html
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Kami - Dompet Dana Umat</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="about.css">
</head>
<body>
    <!-- Preloader -->
    <div class="preloader">
        <div class="spinner"></div>
    </div>

    <!-- Navigation -->
    <header class="main-header">
        <div class="container">
            <nav class="navbar">
                <div class="logo">
                    <img src="https://lh3.googleusercontent.com/d/1G2xVlrBuc4IU6ynaGf8Xg_c9Y7jSg3Wm" alt="Logo DDU">
                    <span>Dompet Dana Umat</span>
                </div>
                <div class="menu-toggle">
                    <span class="bar"></span>
                    <span class="bar"></span>
                    <span class="bar"></span>
                </div>
                <div class="nav-links">
                    <a href="index.html">Home</a>
                    <a href="about.html" class="active">About</a>
                    <a href="index.html#features">Features</a>
                    <a href="index.html#services">Services</a>
                    <a href="index.html#portfolio">Portfolio</a>
                    <a href="index.html#blog">Blog</a>
                    <a href="index.html#contact">Contact</a>
                </div>
            </nav>
        </div>
    </header>

    <!-- Page Header / Hero -->
    <section class="page-header">
        <div class="hero-overlay"></div>
        <div class="container hero-container">
            <div class="hero-content">
                <h5>PROFIL LEMBAGA</h5>
                <h1>Mengenal Lebih Dekat Dompet Dana Umat</h1>
                <p>Bersinergi membangun kemandirian umat melalui pengelolaan ZISWAF yang profesional, amanah, dan transparan.</p>
            </div>
        </div>
    </section>

    <!-- History / Profile Section -->
    <section class="history fade-in">
        <div class="container">
            <div class="section-header">
                <h2>Sejarah & Profil Singkat</h2>
                <p>Perjalanan kami dalam melayani dan memberdayakan umat.</p>
            </div>
            <div class="history-content">
                <p>Dompet Dana Umat (DDU) didirikan dengan semangat untuk menjembatani kepedulian para agniya (muzakki) dengan kebutuhan para dhuafa (mustahik). Berawal dari inisiatif sosial di lingkungan Yayasan Daarul Uluum, kini DDU telah berkembang menjadi lembaga pengelola dana umat yang terpercaya.</p>
                <p>Sejak awal berdiri, kami terus berkomitmen untuk menjaga amanah donatur dengan menerapkan sistem manajemen modern. Fokus kami tidak hanya pada penyaluran bantuan yang bersifat konsumtif (charity), tetapi juga pada program-program pemberdayaan (empowerment) yang berkelanjutan untuk memutus rantai kemiskinan.</p>
            </div>
        </div>
    </section>

    <!-- Vision Mission Section -->
    <section class="vision-mission fade-in">
        <div class="container">
            <div class="vm-grid">
                <div class="vm-card vision">
                    <div class="vm-icon">👁️</div>
                    <h3>Visi</h3>
                    <p>"Menjadi lembaga pengelola ZISWAF (Zakat, Infaq, Sedekah, dan Wakaf) yang amanah, profesional, dan terdepan dalam memandirikan umat."</p>
                </div>
                <div class="vm-card mission">
                    <div class="vm-icon">🚀</div>
                    <h3>Misi</h3>
                    <ul class="vm-list">
                        <li>Mengoptimalkan penghimpunan dana ZISWAF dari masyarakat secara luas.</li>
                        <li>Mengelola dana umat dengan sistem yang transparan, akuntabel, dan sesuai syariat.</li>
                        <li>Mendayagunakan dana ZISWAF melalui program pendidikan, kesehatan, ekonomi, dan dakwah.</li>
                        <li>Membangun sinergi dengan berbagai pihak untuk kemaslahatan umat.</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Legalitas Section -->
    <section class="legalitas fade-in">
        <div class="container">
            <div class="section-header">
                <h2>Legalitas Lembaga</h2>
                <p>Kami beroperasi di bawah payung hukum yang sah dan diakui negara.</p>
            </div>
            <div class="legal-grid">
                <div class="legal-item">
                    <div class="legal-icon">⚖️</div>
                    <h4>SK Kemenkumham</h4>
                    <p>AHU-000XXXX.AH.01.04.Tahun 20XX</p>
                </div>
                <div class="legal-item">
                    <div class="legal-icon">🕌</div>
                    <h4>Izin Operasional</h4>
                    <p>Nomor: XXX/Kemenag/20XX</p>
                </div>
                <div class="legal-item">
                    <div class="legal-icon">🏢</div>
                    <h4>NPWP Lembaga</h4>
                    <p>XX.XXX.XXX.X-XXX.XXX</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer Section -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <div class="footer-logo">
                        <img src="https://lh3.googleusercontent.com/d/1G2xVlrBuc4IU6ynaGf8Xg_c9Y7jSg3Wm" alt="Logo DDU">
                        <span>Dompet Dana Umat</span>
                    </div>
                    <p>Lembaga amil zakat yang terpercaya, amanah, dan profesional dalam mengelola dana umat untuk kesejahteraan masyarakat.</p>
                </div>
                <div class="footer-col">
                    <h4>Contact Info</h4>
                    <ul class="contact-info">
                        <li>📍 Jl. Raya Puncak No. 123, Bogor</li>
                        <li>📞 +62 812 3456 7890</li>
                        <li>✉️ info@dompetdanaumat.com</li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Follow Us</h4>
                    <div class="social-links">
                        <a href="#" class="social-icon">FB</a>
                        <a href="#" class="social-icon">IG</a>
                        <a href="#" class="social-icon">TW</a>
                        <a href="#" class="social-icon">YT</a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2023 Dompet Dana Umat. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <a href="#" class="back-to-top">↑</a>

    <script>
        // Preloader
        window.addEventListener('load', function() {
            const preloader = document.querySelector('.preloader');
            preloader.classList.add('hidden');
        });

        // Navbar Scroll Effect
        window.addEventListener('scroll', function() {
            const header = document.querySelector('.main-header');
            const backToTopButton = document.querySelector('.back-to-top');
            
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }

            if (window.scrollY > 300) {
                backToTopButton.classList.add('visible');
            } else {
                backToTopButton.classList.remove('visible');
            }
        });

        // Hamburger Menu
        const menuToggle = document.querySelector('.menu-toggle');
        const navLinks = document.querySelector('.nav-links');

        menuToggle.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            menuToggle.classList.toggle('active');
        });

        // Back to Top
        document.querySelector('.back-to-top').addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // Fade In Animation
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.fade-in').forEach((el) => observer.observe(el));
    </script>
</body>
</html>
```


## export.md

```md
# Project Structure

```
about.css
about.html
index.html
style.css
```


## about.css

```css
/* ABOUT PAGE SPECIFIC STYLES */

/* Page Header (Static Hero) */
.page-header {
    position: relative;
    padding: 200px 0 150px;
    background-image: url('https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&w=1920&q=80');
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
    color: white;
    text-align: center;
}

/* History Section */
.history {
    padding: 80px 0;
    background-color: white;
}

.history-content {
    max-width: 900px;
    margin: 0 auto;
    text-align: center;
    font-size: 1.1rem;
    color: #4a5568;
}

.history-content p {
    margin-bottom: 25px;
    line-height: 1.8;
}

/* Vision & Mission Section */
.vision-mission {
    padding: 80px 0;
    background-color: #f0f5ff;
}

.vm-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
}

.vm-card {
    background: white;
    padding: 50px 40px;
    border-radius: 20px;
    box-shadow: 0 15px 30px rgba(0,0,0,0.05);
    transition: transform 0.3s ease;
    border-top: 5px solid #1e4b9c;
}

.vm-card:hover {
    transform: translateY(-10px);
}

.vm-icon {
    font-size: 3rem;
    margin-bottom: 20px;
}

.vm-card h3 {
    color: #0a2647;
    font-size: 2rem;
    margin-bottom: 20px;
}

.vm-list {
    list-style: none;
    text-align: left;
}

.vm-list li {
    margin-bottom: 15px;
    position: relative;
    padding-left: 30px;
    color: #2c3e5c;
}

.vm-list li::before {
    content: "✓";
    position: absolute;
    left: 0;
    color: #1e4b9c;
    font-weight: bold;
}

/* Legalitas Section */
.legalitas {
    padding: 80px 0;
    background-color: white;
    text-align: center;
}

.legal-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
    margin-top: 40px;
}

.legal-item {
    padding: 30px;
    border: 1px solid #eee;
    border-radius: 15px;
    transition: all 0.3s;
}

.legal-item:hover {
    border-color: #1e4b9c;
    box-shadow: 0 10px 20px rgba(30, 75, 156, 0.1);
}

.legal-icon {
    font-size: 3rem;
    margin-bottom: 15px;
}

.legal-item h4 {
    color: #0a2647;
    margin-bottom: 10px;
    font-size: 1.2rem;
}

.legal-item p {
    color: #666;
    font-family: monospace;
    font-size: 1rem;
}

/* Responsive */
@media (max-width: 768px) {
    .vm-grid {
        grid-template-columns: 1fr;
    }
    
    .page-header {
        padding: 160px 0 100px;
    }
}
```


## about.html

```html
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Kami - Dompet Dana Umat</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="about.css">
</head>
<body>
    <!-- Preloader -->
    <div class="preloader">
        <div class="spinner"></div>
    </div>

    <!-- Navigation -->
    <header class="main-header">
        <div class="container">
            <nav class="navbar">
                <div class="logo">
                    <img src="https://lh3.googleusercontent.com/d/1G2xVlrBuc4IU6ynaGf8Xg_c9Y7jSg3Wm" alt="Logo DDU">
                    <span>Dompet Dana Umat</span>
                </div>
                <div class="menu-toggle">
                    <span class="bar"></span>
                    <span class="bar"></span>
                    <span class="bar"></span>
                </div>
                <div class="nav-links">
                    <a href="index.html">Home</a>
                    <a href="about.html" class="active">About</a>
                    <a href="index.html#features">Features</a>
                    <a href="index.html#services">Services</a>
                    <a href="index.html#portfolio">Portfolio</a>
                    <a href="index.html#blog">Blog</a>
                    <a href="index.html#contact">Contact</a>
                </div>
            </nav>
        </div>
    </header>

    <!-- Page Header / Hero -->
    <section class="page-header">
        <div class="hero-overlay"></div>
        <div class="container hero-container">
            <div class="hero-content">
                <h5>PROFIL LEMBAGA</h5>
                <h1>Mengenal Lebih Dekat Dompet Dana Umat</h1>
                <p>Bersinergi membangun kemandirian umat melalui pengelolaan ZISWAF yang profesional, amanah, dan transparan.</p>
            </div>
        </div>
    </section>

    <!-- History / Profile Section -->
    <section class="history fade-in">
        <div class="container">
            <div class="section-header">
                <h2>Sejarah & Profil Singkat</h2>
                <p>Perjalanan kami dalam melayani dan memberdayakan umat.</p>
            </div>
            <div class="history-content">
                <p>Dompet Dana Umat (DDU) didirikan dengan semangat untuk menjembatani kepedulian para agniya (muzakki) dengan kebutuhan para dhuafa (mustahik). Berawal dari inisiatif sosial di lingkungan Yayasan Daarul Uluum, kini DDU telah berkembang menjadi lembaga pengelola dana umat yang terpercaya.</p>
                <p>Sejak awal berdiri, kami terus berkomitmen untuk menjaga amanah donatur dengan menerapkan sistem manajemen modern. Fokus kami tidak hanya pada penyaluran bantuan yang bersifat konsumtif (charity), tetapi juga pada program-program pemberdayaan (empowerment) yang berkelanjutan untuk memutus rantai kemiskinan.</p>
            </div>
        </div>
    </section>

    <!-- Vision Mission Section -->
    <section class="vision-mission fade-in">
        <div class="container">
            <div class="vm-grid">
                <div class="vm-card vision">
                    <div class="vm-icon">👁️</div>
                    <h3>Visi</h3>
                    <p>"Menjadi lembaga pengelola ZISWAF (Zakat, Infaq, Sedekah, dan Wakaf) yang amanah, profesional, dan terdepan dalam memandirikan umat."</p>
                </div>
                <div class="vm-card mission">
                    <div class="vm-icon">🚀</div>
                    <h3>Misi</h3>
                    <ul class="vm-list">
                        <li>Mengoptimalkan penghimpunan dana ZISWAF dari masyarakat secara luas.</li>
                        <li>Mengelola dana umat dengan sistem yang transparan, akuntabel, dan sesuai syariat.</li>
                        <li>Mendayagunakan dana ZISWAF melalui program pendidikan, kesehatan, ekonomi, dan dakwah.</li>
                        <li>Membangun sinergi dengan berbagai pihak untuk kemaslahatan umat.</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Legalitas Section -->
    <section class="legalitas fade-in">
        <div class="container">
            <div class="section-header">
                <h2>Legalitas Lembaga</h2>
                <p>Kami beroperasi di bawah payung hukum yang sah dan diakui negara.</p>
            </div>
            <div class="legal-grid">
                <div class="legal-item">
                    <div class="legal-icon">⚖️</div>
                    <h4>SK Kemenkumham</h4>
                    <p>AHU-000XXXX.AH.01.04.Tahun 20XX</p>
                </div>
                <div class="legal-item">
                    <div class="legal-icon">🕌</div>
                    <h4>Izin Operasional</h4>
                    <p>Nomor: XXX/Kemenag/20XX</p>
                </div>
                <div class="legal-item">
                    <div class="legal-icon">🏢</div>
                    <h4>NPWP Lembaga</h4>
                    <p>XX.XXX.XXX.X-XXX.XXX</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer Section -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <div class="footer-logo">
                        <img src="https://lh3.googleusercontent.com/d/1G2xVlrBuc4IU6ynaGf8Xg_c9Y7jSg3Wm" alt="Logo DDU">
                        <span>Dompet Dana Umat</span>
                    </div>
                    <p>Lembaga amil zakat yang terpercaya, amanah, dan profesional dalam mengelola dana umat untuk kesejahteraan masyarakat.</p>
                </div>
                <div class="footer-col">
                    <h4>Contact Info</h4>
                    <ul class="contact-info">
                        <li>📍 Jl. Raya Puncak No. 123, Bogor</li>
                        <li>📞 +62 812 3456 7890</li>
                        <li>✉️ info@dompetdanaumat.com</li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Follow Us</h4>
                    <div class="social-links">
                        <a href="#" class="social-icon">FB</a>
                        <a href="#" class="social-icon">IG</a>
                        <a href="#" class="social-icon">TW</a>
                        <a href="#" class="social-icon">YT</a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2023 Dompet Dana Umat. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <a href="#" class="back-to-top">↑</a>

    <script>
        // Preloader
        window.addEventListener('load', function() {
            const preloader = document.querySelector('.preloader');
            preloader.classList.add('hidden');
        });

        // Navbar Scroll Effect
        window.addEventListener('scroll', function() {
            const header = document.querySelector('.main-header');
            const backToTopButton = document.querySelector('.back-to-top');
            
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }

            if (window.scrollY > 300) {
                backToTopButton.classList.add('visible');
            } else {
                backToTopButton.classList.remove('visible');
            }
        });

        // Hamburger Menu
        const menuToggle = document.querySelector('.menu-toggle');
        const navLinks = document.querySelector('.nav-links');

        menuToggle.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            menuToggle.classList.toggle('active');
        });

        // Back to Top
        document.querySelector('.back-to-top').addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // Fade In Animation
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.fade-in').forEach((el) => observer.observe(el));
    </script>
</body>
</html>
```


## index.html

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Dompet Dana Umat</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Preloader -->
    <div class="preloader">
        <div class="spinner"></div>
    </div>

    <!-- Navigation -->
    <header class="main-header">
        <div class="container">
            <nav class="navbar">
                <div class="logo">
                    <img src="https://lh3.googleusercontent.com/d/1G2xVlrBuc4IU6ynaGf8Xg_c9Y7jSg3Wm" alt="Logo DDU">
                    <span>Dompet Dana Umat</span>
                </div>
                <div class="menu-toggle">
                    <span class="bar"></span>
                    <span class="bar"></span>
                    <span class="bar"></span>
                </div>
                <div class="nav-links">
                    <a href="#home">Home</a>
                    <a href="about.html">About</a>
                    <a href="#features">Features</a>
                    <a href="#services">Services</a>
                    <a href="#portfolio">Portfolio</a>
                    <a href="#blog">Blog</a>
                    <a href="#contact">Contact</a>
                </div>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-slider">
            <div class="slide" style="background-image: url('https://lh3.googleusercontent.com/d/1kuC0kI5fPd_FA0emvuSlRcFSpXQb0KGE');"></div>
            <div class="slide" style="background-image: url('https://lh3.googleusercontent.com//d/1YgCHGRGZVYz-gpj4umxp4sxx7jIPPMR_');"></div>
            <div class="slide" style="background-image: url('https://lh3.googleusercontent.com/d/1ZEtIlPw4eOKxu5izFi197otsnkPHrdRf');"></div>
        </div>
        <div class="hero-overlay"></div>
        <div class="container hero-container">
            <div class="hero-content">
                <h5>Profil</h5>
                <h1>Dompet Dana Umat Daarul Uluum</h1>
                <p>Menjadi lembaga amil zakat yang amanah, profesional, dan terpercaya dalam mengelola dana umat untuk mewujudkan kesejahteraan masyarakat.</p>
                <a href="about.html" class="btn btn-primary">Selengkapnya →</a>
            </div>
        </div>5
    </section>

    <!-- About Section -->
    <section class="about fade-in">
        <div class="container about-container">
            <div class="about-image">
                <div class="about-img-wrapper">
                    <img src="https://lh3.googleusercontent.com/d/1AHa2dY7tquvKWYu6VwkD20jba-9Aa7zB" alt="Kegiatan Sosial" class="about-img-1">
                    <img src="https://lh3.googleusercontent.com/d/1lRGnGGq8IP8np_K6-9zWUPASUuT30q6-" alt="Memberi Bantuan" class="about-img-2">
                    <div class="experience-badge">
                        <span class="years">12+</span>
                        <span class="text">Tahun<br>Mengabdi</span>
                    </div>
                </div>
            </div>
            <div class="about-content">
                <h5>TENTANG KAMI</h5>
                <h2>Lembaga Amil Zakat Terpercaya Sejak 2012</h2>
                <p>Dompet Dana Umat adalah lembaga nirlaba yang berfokus pada pengelolaan dana Zakat, Infaq, Sedekah, dan Wakaf (ZISWAF) secara profesional untuk memberdayakan masyarakat dhuafa.</p>
                <ul class="about-list">
                    <li> Tata kelola yang transparan dan akuntabel.</li>
                    <li> Program yang berdampak dan berkelanjutan.</li>
                    <li> Penyaluran bantuan yang cepat dan tepat sasaran.</li>
                </ul>
                <a href="#contact" class="btn btn-secondary">Hubungi Kami →</a>
            </div>
        </div>
    </section>
    <!-- Testimonials Section -->
<section class="testimonials fade-in">
    <div class="container">
        <div class="section-header">
            <h2>Testimonials</h2>
            <p class="testimonials-subtitle">Trusted by leaders from various industries</p>
            <p class="testimonials-description">Learn why professionals trust our solutions to complete their customer journeys.</p>
        </div>
        
        <div class="testimonials-grid">
            <!-- Testimonial Card 1 -->
            <div class="testimonial-card">
                <div class="testimonial-quote">"</div>
                <p class="testimonial-text">Dompet Dana Umat telah membantu kami menyalurkan zakat perusahaan dengan tepat sasaran. Laporan yang transparan dan profesional membuat kami percaya untuk terus berkolaborasi.</p>
                <div class="testimonial-author">
                    <div class="author-avatar">
                        <img src="https://ui-avatars.com/api/?name=Ahmad+Fauzi&background=1e4b9c&color=fff&size=60" alt="Ahmad Fauzi">
                    </div>
                    <div class="author-info">
                        <h4>Ahmad Fauzi</h4>
                        <p>CEO, PT Berkah Abadi</p>
                    </div>
                </div>
            </div>
            
            <!-- Testimonial Card 2 -->
            <div class="testimonial-card">
                <div class="testimonial-quote">"</div>
                <p class="testimonial-text">Program pemberdayaan ekonomi dari DDU sangat membantu mustahik binaan kami. Mereka tidak hanya menerima bantuan, tapi juga dilatih hingga mandiri. Luar biasa!</p>
                <div class="testimonial-author">
                    <div class="author-avatar">
                        <img src="https://ui-avatars.com/api/?name=Siti+Nurhaliza&background=1e4b9c&color=fff&size=60" alt="Siti Nurhaliza">
                    </div>
                    <div class="author-info">
                        <h4>Siti Nurhaliza</h4>
                        <p>Manager Program, Yayasan Peduli</p>
                    </div>
                </div>
            </div>
            
            <!-- Testimonial Card 3 -->
            <div class="testimonial-card">
                <div class="testimonial-quote">"</div>
                <p class="testimonial-text">Sebagai donatur individu, saya sangat puas dengan transparansi DDU. Setiap donasi yang saya berikan selalu ada laporan penyalurannya. Amanah dan terpercaya.</p>
                <div class="testimonial-author">
                    <div class="author-avatar">
                        <img src="https://ui-avatars.com/api/?name=Mohammad+Rizki&background=1e4b9c&color=fff&size=60" alt="Mohammad Rizki">
                    </div>
                    <div class="author-info">
                        <h4>Mohammad Rizki</h4>
                        <p>Donatur Tetap</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="testimonials-footer">
            <a href="#" class="btn btn-primary">Read Success Stories →</a>
        </div>
    </div>
</section>

    <!-- Features Section -->
    <section class="features fade-in" id="features">
        <div class="container">
            <div class="section-header">
                <h2>Program Unggulan</h2>
                <p>Inisiatif nyata kami dalam memberdayakan umat dan membangun masyarakat yang lebih sejahtera.</p>
            </div>
            <div class="features-list">
                <!-- Feature 1 -->
                <div class="feature-item">
                    <div class="feature-image">
                        <img src="https://lh3.googleusercontent.com/d/1AHa2dY7tquvKWYu6VwkD20jba-9Aa7zB" alt="Penyaluran Bantuan">
                    </div>
                    <div class="feature-content">
                        <h3>Penyaluran Bantuan Langsung</h3>
                        <p>Kami memastikan bantuan sampai langsung ke tangan mereka yang membutuhkan. Melalui survei yang ketat dan pendataan yang akurat, setiap rupiah donasi Anda disalurkan kepada mustahik yang tepat sasaran, mulai dari sembako hingga bantuan tunai.</p>
                        <a href="#contact" class="btn-text">Dukung Program Ini →</a>
                    </div>
                </div>

                <!-- Feature 2 -->
                <div class="feature-item reverse">
                    <div class="feature-image">
                        <img src="https://lh3.googleusercontent.com/d/1lRGnGGq8IP8np_K6-9zWUPASUuT30q6-" alt="Santunan Yatim">
                    </div>
                    <div class="feature-content">
                        <h3>Senyum Bahagia Anak Yatim</h3>
                        <p>Program santunan rutin dan pembinaan karakter bagi anak-anak yatim dhuafa. Kami tidak hanya memberikan bantuan materi, tetapi juga dukungan moral dan pendidikan agar mereka tumbuh menjadi generasi yang mandiri dan berakhlak mulia.</p>
                        <a href="#contact" class="btn-text">Jadi Orang Tua Asuh →</a>
                    </div>
                </div>

                <!-- Feature 3 -->
                <div class="feature-item">
                    <div class="feature-image">
                        <img src="https://lh3.googleusercontent.com/d/1ZEtIlPw4eOKxu5izFi197otsnkPHrdRf" alt="Layanan Kesehatan">
                    </div>
                    <div class="feature-content">
                        <h3>Layanan Kesehatan Gratis</h3>
                        <p>Akses kesehatan adalah hak setiap insan. Kami menyelenggarakan pengobatan gratis, khitanan massal, dan penyuluhan kesehatan di daerah-daerah terpencil yang minim fasilitas medis, demi mewujudkan masyarakat yang sehat dan produktif.</p>
                        <a href="#contact" class="btn-text">Donasi Kesehatan →</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->

    <!-- What We Offer Section -->
    <section class="offer fade-in" id="services">
        <div class="container">
            <div class="section-header">
                <h2>Program Utama Kami</h2>
            </div>
            <div class="offer-grid">
                <div class="offer-card">
                    <div class="offer-icon">🕋</div>
                    <h3>Zakat</h3>
                    <p>Tunaikan kewajiban zakat Anda untuk membersihkan harta dan membantu sesama. Kami salurkan kepada 8 asnaf yang berhak.</p>
                    <a href="#" class="offer-link">Hitung Zakat →</a>
                </div>
                <div class="offer-card">
                    <div class="offer-icon">🤲</div>
                    <h3>Infaq & Sedekah</h3>
                    <p>Sedekah Anda menjadi harapan bagi mereka yang membutuhkan. Salurkan infaq terbaik Anda untuk program kemanusiaan.</p>
                    <a href="#" class="offer-link">Donasi Sekarang →</a>
                </div>
                <div class="offer-card">
                    <div class="offer-icon">🤝</div>
                    <h3>Kemanusiaan</h3>
                    <p>Bantuan cepat tanggap untuk korban bencana alam, krisis kemanusiaan, dan program sosial lainnya di seluruh Indonesia.</p>
                    <a href="#" class="offer-link">Lihat Aksi Kami →</a>
                </div>
                <div class="offer-card">
                    <div class="offer-icon">🕌</div>
                    <h3>Wakaf</h3>
                    <p>Investasi akhirat melalui wakaf produktif seperti pembangunan sumur, masjid, atau modal usaha untuk dhuafa.</p>
                    <a href="#" class="offer-link">Pelajari Wakaf →</a>
                </div>                
            </div>
        </div>
    </section>

    <!-- Portfolio Section -->
    <section class="portfolio fade-in" id="portfolio">
        <div class="container">
            <div class="section-header">
                <h2>Galeri Kegiatan</h2>
                <p>Dokumentasi penyaluran dana dan kegiatan sosial kami.</p>
            </div>
            <div class="portfolio-grid">
                <div class="portfolio-item"><img src="https://lh3.googleusercontent.com/d/1O77oVXz4jNjzKx-24DtfaYWjldfejdYZ" alt="Kegiatan 1"></div>
                <div class="portfolio-item"><img src="https://lh3.googleusercontent.com/d/1YgCHGRGZVYz-gpj4umxp4sxx7jIPPMR_" alt="Kegiatan 2"></div>
                <div class="portfolio-item"><img src="https://lh3.googleusercontent.com/d/1ZEtIlPw4eOKxu5izFi197otsnkPHrdRf" alt="Kegiatan 3"></div>
                <div class="portfolio-item"><img src="https://lh3.googleusercontent.com/d/1O77oVXz4jNjzKx-24DtfaYWjldfejdYZ" alt="Kegiatan 4"></div>
            </div>
        </div>
    </section>

    <!-- Blog Section -->
    <section class="blog fade-in" id="blog">
        <div class="container">
            <div class="section-header">
                <h2>Berita & Artikel</h2>
                <p>Informasi terbaru seputar kegiatan dan artikel inspiratif.</p>
            </div>
            <div class="blog-grid">
                <!-- Blog Item 1 -->
                <div class="blog-card">
                    <div class="blog-img">
                        <img src="https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?auto=format&fit=crop&w=500&q=60" alt="Blog 1">
                        <span class="blog-date">12 Okt 2023</span>
                    </div>
                    <div class="blog-content">
                        <h3>Penyaluran Zakat Fitrah 1444H</h3>
                        <p>Alhamdulillah, telah terlaksana penyaluran zakat fitrah kepada 500 mustahik di wilayah Bogor...</p>
                        <a href="#" class="read-more">Baca Selengkapnya →</a>
                    </div>
                </div>
                <!-- Blog Item 2 -->
                <div class="blog-card">
                    <div class="blog-img">
                        <img src="https://images.unsplash.com/photo-1532629345422-7515f3d16bb6?auto=format&fit=crop&w=500&q=60" alt="Blog 2">
                        <span class="blog-date">05 Sep 2023</span>
                    </div>
                    <div class="blog-content">
                        <h3>Beasiswa Pendidikan Yatim</h3>
                        <p>Program beasiswa untuk anak-anak yatim berprestasi kembali dibuka. Simak syarat dan ketentuannya...</p>
                        <a href="#" class="read-more">Baca Selengkapnya →</a>
                    </div>
                </div>
                <!-- Blog Item 3 -->
                <div class="blog-card">
                    <div class="blog-img">
                        <img src="https://images.unsplash.com/photo-1593113598332-cd288d649433?auto=format&fit=crop&w=500&q=60" alt="Blog 3">
                        <span class="blog-date">20 Agu 2023</span>
                    </div>
                    <div class="blog-content">
                        <h3>Wakaf Sumur Air Bersih</h3>
                        <p>Peresmian wakaf sumur air bersih di desa kekeringan. Air mengalir, pahala mengalir...</p>
                        <a href="#" class="read-more">Baca Selengkapnya →</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact fade-in" id="contact">
        <div class="container">
            <div class="section-header">
                <h2>Hubungi Kami</h2>
                <p>Punya pertanyaan atau ingin berdonasi? Kirimkan pesan kepada kami.</p>
            </div>
            <div class="contact-wrapper">
                <form class="contact-form">
                    <div class="form-group">
                        <input type="text" placeholder="Nama Lengkap" required>
                    </div>
                    <div class="form-group">
                        <input type="email" placeholder="Alamat Email" required>
                    </div>
                    <div class="form-group">
                        <textarea placeholder="Pesan Anda" rows="5" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Kirim Pesan</button>
                </form>
            </div>
        </div>
    </section>

    <!-- Footer Section -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <div class="footer-logo">
                        <img src="https://lh3.googleusercontent.com/d/1G2xVlrBuc4IU6ynaGf8Xg_c9Y7jSg3Wm" alt="Logo DDU">
                        <span>Dompet Dana Umat</span>
                    </div>
                    <p>Lembaga amil zakat yang terpercaya, amanah, dan profesional dalam mengelola dana umat untuk kesejahteraan masyarakat.</p>
                </div>
                <div class="footer-col">
                    <h4>Contact Info</h4>
                    <ul class="contact-info">
                        <li>📍 Jl. Raya Puncak No. 123, Bogor</li>
                        <li>📞 +62 812 3456 7890</li>
                        <li>✉️ info@dompetdanaumat.com</li>
                        <li>✉️ Admin@dompetdanaumat.com</li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Follow Us</h4>
                    <div class="social-links">
                        <a href="#" class="social-icon">FB</a>
                        <a href="#" class="social-icon">IG</a>
                        <a href="#" class="social-icon">TW</a>
                        <a href="#" class="social-icon">YT</a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2023 Dompet Dana Umat. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <a href="#" class="back-to-top">↑</a>

    <!-- WhatsApp Popup -->
    <a href="https://wa.me/6281234567890?text=Assalamualaikum,%20saya%20ingin%20bertanya%20tentang%20Dompet%20Dana%20Umat" class="whatsapp-popup" target="_blank">
        <img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg" alt="WhatsApp">
    </a>

    <!-- Lightbox -->
    <div id="lightbox" class="lightbox">
        <span class="close-lightbox">&times;</span>
        <img class="lightbox-content" id="lightbox-img">
    </div>

    <script>
        const backToTopButton = document.querySelector('.back-to-top');

        // Preloader Script
        window.addEventListener('load', function() {
            const preloader = document.querySelector('.preloader');
            preloader.classList.add('hidden');
        });

        window.addEventListener('scroll', function() {
            const header = document.querySelector('.main-header');
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }

            // Show/Hide Back to Top Button
            if (window.scrollY > 300) {
                backToTopButton.classList.add('visible');
            } else {
                backToTopButton.classList.remove('visible');
            }
        });

        // Hamburger Menu Script
        const menuToggle = document.querySelector('.menu-toggle');
        const navLinks = document.querySelector('.nav-links');

        menuToggle.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            menuToggle.classList.toggle('active');
        });

        // Close mobile menu when a link is clicked
        document.querySelectorAll('.nav-links a').forEach(link => {
            link.addEventListener('click', () => {
                navLinks.classList.remove('active');
                menuToggle.classList.remove('active');
            });
        });

        // Back to Top Click Event
        backToTopButton.addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Lightbox Script
        const lightbox = document.getElementById('lightbox');
        const lightboxImg = document.getElementById('lightbox-img');
        const portfolioItems = document.querySelectorAll('.portfolio-item img');
        const closeLightbox = document.querySelector('.close-lightbox');

        portfolioItems.forEach(item => {
            item.addEventListener('click', function() {
                lightbox.style.display = "flex";
                lightboxImg.src = this.src;
            });
        });

        closeLightbox.addEventListener('click', function() {
            lightbox.style.display = "none";
        });

        lightbox.addEventListener('click', function(e) {
            if (e.target !== lightboxImg) {
                lightbox.style.display = "none";
            }
        });

        // Scroll Animation Script
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1 // Trigger when 10% of the element is visible
        });

        const elementsToFadeIn = document.querySelectorAll('.fade-in');
        elementsToFadeIn.forEach((el) => observer.observe(el));

        // Active Nav Link on Scroll Script
        const sections = document.querySelectorAll('section[id]');
        const navObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const id = entry.target.id;
                    document.querySelectorAll('.nav-links a').forEach(link => {
                        link.classList.remove('active');
                    });
                    const activeLink = document.querySelector(`.nav-links a[href="#${id}"]`);
                    if (activeLink) {
                        activeLink.classList.add('active');
                    }
                }
            });
        }, { rootMargin: "-50% 0px -50% 0px" }); // Trigger di tengah layar

        sections.forEach(section => {
            navObserver.observe(section);
        });

        // Statistics Counter Animation
        const statsSection = document.querySelector('.stats');
        const statNumbers = document.querySelectorAll('.stat-number');
        let started = false;

        const statsObserver = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting && !started) {
                statNumbers.forEach(num => {
                    const target = +num.getAttribute('data-target');
                    const duration = 2000; // 2 seconds
                    const increment = target / (duration / 16); 
                    
                    let current = 0;
                    const updateCount = () => {
                        current += increment;
                        if (current < target) {
                            num.innerText = Math.ceil(current) + "+";
                            requestAnimationFrame(updateCount);
                        } else {
                            num.innerText = target + "+";
                        }
                    };
                    updateCount();
                });
                started = true;
            }
        });

        if(statsSection) {
            statsObserver.observe(statsSection);
        }
    </script>
</body>

</html>
```


## style.css

```css
/* ===== style.css ===== */
/* RESET & BASE */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
    scroll-padding-top: 100px; /* Mencegah konten tertutup navbar saat diklik */
}

body {
    font-family: 'Segoe UI', Roboto, system-ui, -apple-system, sans-serif;
    background-color: #f0f5ff;  /* Light blue background */
    color: #1e3a6b;
    line-height: 1.6;
}

.container {
    max-width: 1280px;
    margin: -2px auto;
    padding: 10px 80px;
}

/* TYPOGRAPHY */
h1 {
    font-size: 3.2rem;
    font-weight: 700;
    line-height: 1.2;
    color: #0a2647;
    margin-bottom: 20px;
}

h2 {
    font-size: 2.5rem;
    font-weight: 700;
    color: #0a2647;
    margin-bottom: 20px;
}

h3 {
    font-size: 1.5rem;
    font-weight: 600;
    color: #0a2647;
    margin-bottom: 12px;
}

h5 {
    font-size: 1rem;
    font-weight: 600;
    color: #2b6c9e;
    letter-spacing: 2px;
    text-transform: uppercase;
    margin-bottom: 10px;
}

p {
    color: #2c3e5c;
    margin-bottom: 20px;
}

/* NAVIGATION */
.main-header {
    position: fixed;
    top: 20px;
    top: 15px;
    left: 50%;
    transform: translateX(-50%);
    width: 95%;
    max-width: 1280px;
    border-radius: 10px;
    z-index: 1000;
    transition: all 0.3s ease;
}

.main-header.scrolled {
    background-color: rgba(10, 43, 94, 0.95); /* Warna biru gelap transparan */
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.main-header.scrolled .navbar {
    padding: 15px 0; /* Mengecilkan padding saat scroll */
    padding: 10px 0; /* Mengecilkan padding saat scroll */
    border-bottom: none;
}

.navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 25px 0;
    padding: 15px 0;
    flex-wrap: wrap;
    border-bottom: 1px solid rgba(255, 255, 255, 0.15);
}

.logo {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 1.8rem;
    font-size: 1.6rem;
    font-weight: 700;
    color: #ffffff;
    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.logo img {
    height: 40px;
    height: 35px;
}

.nav-links {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.nav-links a {
    text-decoration: none;
    color: rgba(255, 255, 255, 0.9);
    font-weight: 500;
    font-size: 1.1rem;
    font-size: 1rem;
    transition: color 0.3s;
    position: relative;
    line-height: 5px;
}

.nav-links a:hover {
    color: #c0c8cf;
}

.nav-links a.active {
    color: #ffffff;
    font-weight: 700;
}

/* HAMBURGER MENU */
.menu-toggle {
    display: none;
    flex-direction: column;
    cursor: pointer;
}

.menu-toggle .bar {
    width: 25px;
    height: 3px;
    background-color: white;
    margin: 3px 0;
    transition: all 0.3s ease-in-out;
}

/* Animasi Hamburger ke X */
.menu-toggle.active .bar:nth-child(1) {
    transform: translateY(9px) rotate(45deg);
}

.menu-toggle.active .bar:nth-child(2) {
    opacity: 0;
}

.menu-toggle.active .bar:nth-child(3) {
    transform: translateY(-9px) rotate(-45deg);
}

/* BUTTONS */
.btn {
    display: inline-block;
    padding: 12px 30px;
    text-decoration: none;
    font-weight: 600;
    border-radius: 6px;
    transition: all 0.3s;
}

.btn-primary {
    background-color: #1e4b9c;
    color: white;
    border: 2px solid #1e4b9c;
}

.btn-primary:hover {
    background-color: #0a2b5e;
    border-color: #0a2b5e;
}

.btn-secondary {
    background-color: transparent;
    color: #1e4b9c;
    border: 2px solid #1e4b9c;
}

.btn-secondary:hover {
    background-color: #1e4b9c;
    color: white;
}

/* HERO SECTION */
.hero {
    position: relative;
    padding: 250px 0 200px;
    background-color: #0a2b5e;
    overflow: hidden;
    color: white;
}

.hero-slider {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1;
}

.slide {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-size: cover;
    background-position: center;
    opacity: 0;
    animation: heroSlide 15s infinite;
}

.slide:nth-child(1) { animation-delay: 0s; }
.slide:nth-child(2) { animation-delay: 5s; }
.slide:nth-child(3) { animation-delay: 10s; }

@keyframes heroSlide {
    0% { opacity: 0; transform: scale(1.1); }
    4% { opacity: 1; transform: scale(1); }
    33% { opacity: 1; transform: scale(1); }
    37% { opacity: 0; transform: scale(1.1); }
    100% { opacity: 0; transform: scale(1.1); }
}

.hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(10, 38, 71, 0.75);
    z-index: 2;
}

.hero-container {
    position: relative;
    z-index: 3;
    display: block;
}

.hero-content {
    max-width: 700px;
}

.hero-content h1 {
    color: #ffffff;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
}

.hero-content h5 {
    color: #64b5f6;
    text-shadow: 0 1px 4px rgba(0, 0, 0, 0.4);
}

.hero-content p {
    font-size: 1.1rem;
    margin-bottom: 30px;
    color: #e0e0e0;
    text-shadow: 0 1px 4px rgba(0, 0, 0, 0.4);
}

/* FEATURES SECTION (Article Layout) */
.features {
    padding: 100px 0;
    background-color: #f0f5ff;
}

.features-list {
    display: flex;
    flex-direction: column;
    gap: 100px;
}

.feature-item {
    display: flex;
    align-items: center;
    gap: 60px;
}

.feature-item.reverse {
    flex-direction: row-reverse;
}

.feature-image {
    flex: 1;
    position: relative;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    height: 350px;
}

.feature-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.feature-item:hover .feature-image img {
    transform: scale(1.05);
}

.feature-content {
    flex: 1;
}

.feature-content h3 {
    font-size: 2rem;
    color: #0a2647;
    margin-bottom: 20px;
    line-height: 1.3;
}

.feature-content p {
    font-size: 1.1rem;
    color: #4a5568;
    margin-bottom: 25px;
    line-height: 1.8;
}

.btn-text {
    color: #1e4b9c;
    font-weight: 700;
    text-decoration: none;
    font-size: 1.05rem;
    display: inline-flex;
    align-items: center;
    transition: transform 0.3s;
}

.btn-text:hover {
    transform: translateX(5px);
    color: #0a2b5e;
}

/* ENHANCED ABOUT IMAGES */
.about-img-wrapper {
    position: relative;
    z-index: 1;
    padding-right: 20px; /* Ruang untuk gambar kedua */
    padding-bottom: 20px;
}

.about-img-1 {
    width: 85%;
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    display: block;
    object-fit: cover;
}

.about-img-2 {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 55%;
    border-radius: 15px;
    border: 8px solid white;
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    object-fit: cover;
}

.experience-badge {
    position: absolute;
    top: 40px;
    right: 40px;
    background: linear-gradient(135deg, #1e4b9c, #0a2b5e);
    color: white;
    width: 110px;
    height: 110px;
    border-radius: 50%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    box-shadow: 0 10px 25px rgba(30, 75, 156, 0.4);
    animation: floatBadge 3s ease-in-out infinite;
    z-index: 2;
    border: 4px solid rgba(255,255,255,0.2);
}

.experience-badge .years {
    font-size: 1.8rem;
    font-weight: 800;
    line-height: 1;
}

.experience-badge .text {
    font-size: 0.75rem;
    font-weight: 500;
    line-height: 1.2;
    margin-top: 2px;
}

@keyframes floatBadge {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

/* ABOUT SECTION */
.about {
    padding: 80px 0;
    background-color: white;
    position: relative;
    overflow: hidden;
}

/* Cloud Ornaments (Background) */
.about::before {
    content: "";
    position: absolute;
    top: -80px;
    left: -80px;
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, #e3f2fd 0%, rgba(255,255,255,0) 70%);
    border-radius: 50%;
    z-index: 0;
    opacity: 0.6;
    animation: floatCloud 15s infinite ease-in-out;
}

.about::after {
    content: "";
    position: absolute;
    bottom: -100px;
    right: -50px;
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, #e3f2fd 0%, rgba(255,255,255,0) 70%);
    border-radius: 50%;
    z-index: 0;
    opacity: 0.6;
    animation: floatCloud 20s infinite ease-in-out reverse;
}

@keyframes floatCloud {
    0% { transform: translate(0, 0); }
    50% { transform: translate(30px, 20px); }
    100% { transform: translate(0, 0); }
}

.about-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    align-items: center;
    position: relative;
    z-index: 1;
}

.about-list {
    list-style: none;
    margin: 25px 0;
}

.about-list li {
    margin-bottom: 12px;
    font-size: 1.1rem;
    color: #1e3a6b;
}

.about-list li::before {
    content: "✓";
    color: #1e4b9c;
    font-weight: bold;
    margin-right: 10px;
}

/* OFFER SECTION */
.offer {
    padding: 60px 0;
    background-color: #f0f5ff;
}

.section-header {
    text-align: center;
    margin-bottom: 50px;
}

.section-header h2 {
    font-size: 2.5rem;
    position: relative;
    display: inline-block;
    padding-bottom: 15px;
}

.section-header h2::after {
    content: "";
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 3px;
    background-color: #1e4b9c;
}

.offer-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 30px;
}

.offer-card {
    background: white;
    padding: 35px 25px;
    border-radius: 15px;
    text-align: center;
    box-shadow: 0 10px 30px rgba(0, 60, 120, 0.05);
    transition: transform 0.3s;
}

.offer-card:hover {
    transform: translateY(-5px);
}

.offer-icon {
    font-size: 3rem;
    margin-bottom: 15px;
}

.offer-card h3 {
    margin-bottom: 15px;
}

.offer-card p {
    font-size: 0.95rem;
    margin-bottom: 20px;
    color: #3a4e6b;
}

.offer-link {
    text-decoration: none;
    color: #1e4b9c;
    font-weight: 600;
    font-size: 1rem;
}

.offer-link:hover {
    color: #0a2b5e;
}

/* RESPONSIVE DESIGN */
@media (max-width: 1024px) {
    .hero-container,
    .about-container {
        grid-template-columns: 1fr;
    }
    
    .features-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .offer-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    h1 {
        font-size: 2.8rem;
    }
    
    h2 {
        font-size: 2.2rem;
    }
}

@media (max-width: 768px) {
    .main-header {
        position: fixed;
        top: 20px;
        top: 15px;
        left: 50%;
        transform: translateX(-50%);
        width: 95%;
        max-width: 1280px;
        border-radius: 10px;
        z-index: 1000;
        transition: all 0.3s ease;
    }

    .navbar {
        flex-direction: row;
        position: relative;
    }

    .menu-toggle {
        display: flex;
    }
    
    .nav-links {
        display: none; /* Sembunyikan menu secara default di HP */
        width: 100%;
        flex-direction: column;
        position: absolute;
        top: 100%;
        left: 0;
        background-color: #0a2b5e; /* Background biru gelap */
        padding: 20px 0;
        text-align: center;
        box-shadow: 0 10px 20px rgba(0,0,0,0.2);
    }
    
    .nav-links.active {
        display: flex; /* Tampilkan saat tombol diklik */
    }

    .features-grid,
    .offer-grid {
        grid-template-columns: 1fr;
    }

    .feature-item, .feature-item.reverse {
        flex-direction: column;
        gap: 30px;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .container {
        padding: 0 20px;
    }

    .hero {
        padding: 140px 0 70px; /* Mengurangi tinggi hero di HP */
    }

    .features, .about, .offer, .portfolio, .contact {
        padding: 40px 0; /* Mengurangi jarak antar section */
    }

    .logo {
        font-size: 1.2rem;
    }

    .logo img {
        height: 30px;
    }
    
    h1 {
        font-size: 1.8rem; /* Judul utama lebih kecil */
    }
    
    h2 {
        font-size: 1.5rem; /* Sub-judul lebih kecil */
    }

    h3 {
        font-size: 1.25rem;
    }

    p {
        font-size: 0.9rem; /* Teks paragraf lebih kecil & nyaman */
    }

    .feature-icon, .offer-icon {
        font-size: 2rem; /* Ikon tidak terlalu raksasa */
    }

    .btn {
        padding: 10px 24px;
        font-size: 0.9rem;
    }

    /* Responsive About Images */
    .about-img-wrapper {
        padding-right: 0;
        margin-bottom: 30px;
    }
    .about-img-1 {
        width: 100%;
    }
    .about-img-2 {
        width: 50%;
        bottom: -20px;
        right: -10px;
        border-width: 5px;
    }
    }


/* PORTFOLIO SECTION */
.portfolio {
    padding: 60px 0;
    background-color: white;
}

.portfolio-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.portfolio-item img {
    width: 100%;
    height: 250px;
    object-fit: cover;
    border-radius: 8px;
    transition: transform 0.3s ease;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    cursor: pointer;
}

.portfolio-item img:hover {
    transform: scale(1.03);
}

/* CONTACT SECTION */
.contact {
    padding: 60px 0 80px;
    background-color: #f0f5ff;
}

.contact-wrapper {
    max-width: 700px;
    margin: 0 auto;
    background: white;
    padding: 40px;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 60, 120, 0.05);
}

.form-group {
    margin-bottom: 20px;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-family: inherit;
    font-size: 1rem;
    outline: none;
    transition: border-color 0.3s;
}

.form-group input:focus,
.form-group textarea:focus {
    border-color: #1e4b9c;
}

/* BACK TO TOP BUTTON */
.back-to-top {
    position: fixed;
    bottom: 100px; /* Digeser ke atas agar tidak menutupi WA */
    right: 35px;   /* Diselaraskan tengah dengan tombol WA */
    width: 50px;
    height: 50px;
    background-color: #1e4b9c;
    color: white;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    text-decoration: none;
    font-size: 1.5rem;
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    z-index: 999;
}

.back-to-top.visible {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.back-to-top:hover {
    background-color: #0a2b5e;
    transform: translateY(-5px);
}

/* FOOTER */
.footer {
    background-color: #051a3d; /* Biru sangat gelap */
    color: #cfd8dc;
    padding: 70px 0 25px;
    font-size: 0.95rem;
}

.footer-grid {
    display: grid;
    grid-template-columns: 1.5fr 1fr 1fr 1fr;
    gap: 10px;
    margin-bottom: 50px;
}

.footer-logo {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
    color: white;
    font-weight: 700;
    font-size: 1.3rem;
}

.footer-logo img {
    height: 35px;
}

.footer-col h4 {
    color: white;
    font-size: 1.1rem;
    margin-bottom: 25px;
    font-weight: 600;
}

.footer-col ul {
    list-style: none;
}

.footer-col ul li {
    margin-bottom: 12px;
}

.footer-col a {
    color: #cfd8dc;
    text-decoration: none;
    transition: all 0.3s;
}

.footer-col a:hover {
    color: #64b5f6;
    padding-left: 5px;
}

.contact-info li {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
}

.social-links {
    display: flex;
    gap: 10px;
}

.social-icon {
    width: 40px;
    height: 40px;
    background: rgba(255,255,255,0.1);
    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: 50%;
    color: white !important; /* Override warna link default */
    font-size: 0.8rem;
    font-weight: bold;
}

.social-icon:hover {
    background: #1e4b9c;
    transform: translateY(-3px);
    padding-left: 0 !important;
}

.footer-bottom {
    border-top: 1px solid rgba(255,255,255,0.1);
    padding-top: 25px;
    text-align: center;
    font-size: 0.9rem;
}

@media (max-width: 900px) {
    .footer-grid {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 600px) {
    .footer-grid {
        grid-template-columns: 1fr;
    }
}

/* WHATSAPP POPUP */
.whatsapp-popup {
    position: fixed;
    bottom: 30px;
    right: 30px; /* Pindah ke pojok kanan */
    width: 60px;
    height: 60px;
    background-color: #25d366;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
    z-index: 1000;
    transition: all 0.3s ease;
    animation: pulse 2s infinite;
}

.whatsapp-popup:hover {
    transform: scale(1.1);
}

.whatsapp-popup img {
    width: 35px;
    height: 35px;
}

@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(37, 211, 102, 0.7); }
    70% { box-shadow: 0 0 0 15px rgba(37, 211, 102, 0); }
    100% { box-shadow: 0 0 0 0 rgba(37, 211, 102, 0); }
}

/* PRELOADER */
.preloader {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: #ffffff;
    z-index: 9999;
    display: flex;
    justify-content: center;
    align-items: center;
    transition: opacity 0.5s ease, visibility 0.5s ease;
}

.preloader.hidden {
    opacity: 0;
    visibility: hidden;
}

.spinner {
    width: 50px;
    height: 50px;
    border: 5px solid #f3f3f3;
    border-top: 5px solid #1e4b9c; /* Warna biru tema */
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* LIGHTBOX */
.lightbox {
    display: none;
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.9);
    justify-content: center;
    align-items: center;
}

.lightbox-content {
    margin: auto;
    display: block;
    max-width: 90%;
    max-height: 90%;
    border-radius: 5px;
    box-shadow: 0 0 20px rgba(255, 255, 255, 0.2);
    animation: zoom 0.3s;
}

@keyframes zoom {
    from {transform:scale(0.8); opacity: 0;}
    to {transform:scale(1); opacity: 1;}
}

.close-lightbox {
    position: absolute;
    top: 20px;
    right: 35px;
    color: #f1f1f1;
    font-size: 40px;
    font-weight: bold;
    transition: 0.3s;
    cursor: pointer;
}

.close-lightbox:hover {
    color: #bbb;
}

/* SCROLL ANIMATION */
.fade-in {
    opacity: 0;
    transform: translateY(30px);
    transition: opacity 0.6s ease-out, transform 0.6s ease-out;
}

.fade-in.visible {
    opacity: 1;
    transform: translateY(0);
}
```
```


## index.html

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Dompet Dana Umat</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Preloader -->
    <div class="preloader">
        <div class="spinner"></div>
    </div>

    <!-- Navigation -->
    <header class="main-header">
        <div class="container">
            <nav class="navbar">
                <div class="logo">
                    <img src="https://lh3.googleusercontent.com/d/1G2xVlrBuc4IU6ynaGf8Xg_c9Y7jSg3Wm" alt="Logo DDU">
                    <span>Dompet Dana Umat</span>
                </div>
                <div class="menu-toggle">
                    <span class="bar"></span>
                    <span class="bar"></span>
                    <span class="bar"></span>
                </div>
                <div class="nav-links">
                    <a href="#home">Home</a>
                    <a href="about.html">About</a>
                    <a href="#features">Features</a>
                    <a href="#services">Services</a>
                    <a href="#portfolio">Portfolio</a>
                    <a href="#blog">Blog</a>
                    <a href="#contact">Contact</a>
                </div>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-slider">
            <div class="slide" style="background-image: url('https://lh3.googleusercontent.com/d/1kuC0kI5fPd_FA0emvuSlRcFSpXQb0KGE');"></div>
            <div class="slide" style="background-image: url('https://lh3.googleusercontent.com//d/1YgCHGRGZVYz-gpj4umxp4sxx7jIPPMR_');"></div>
            <div class="slide" style="background-image: url('https://lh3.googleusercontent.com/d/1ZEtIlPw4eOKxu5izFi197otsnkPHrdRf');"></div>
        </div>
        <div class="hero-overlay"></div>
        <div class="container hero-container">
            <div class="hero-content">
                <h5>Profil</h5>
                <h1>Dompet Dana Umat Daarul Uluum</h1>
                <p>Menjadi lembaga amil zakat yang amanah, profesional, dan terpercaya dalam mengelola dana umat untuk mewujudkan kesejahteraan masyarakat.</p>
                <a href="about.html" class="btn btn-primary">Selengkapnya →</a>
            </div>
        </div>5
    </section>

    <!-- About Section -->
    <section class="about fade-in">
        <div class="container about-container">
            <div class="about-image">
                <div class="about-img-wrapper">
                    <img src="https://lh3.googleusercontent.com/d/1AHa2dY7tquvKWYu6VwkD20jba-9Aa7zB" alt="Kegiatan Sosial" class="about-img-1">
                    <img src="https://lh3.googleusercontent.com/d/1lRGnGGq8IP8np_K6-9zWUPASUuT30q6-" alt="Memberi Bantuan" class="about-img-2">
                    <div class="experience-badge">
                        <span class="years">12+</span>
                        <span class="text">Tahun<br>Mengabdi</span>
                    </div>
                </div>
            </div>
            <div class="about-content">
                <h5>TENTANG KAMI</h5>
                <h2>Lembaga Amil Zakat Terpercaya Sejak 2012</h2>
                <p>Dompet Dana Umat adalah lembaga nirlaba yang berfokus pada pengelolaan dana Zakat, Infaq, Sedekah, dan Wakaf (ZISWAF) secara profesional untuk memberdayakan masyarakat dhuafa.</p>
                <ul class="about-list">
                    <li> Tata kelola yang transparan dan akuntabel.</li>
                    <li> Program yang berdampak dan berkelanjutan.</li>
                    <li> Penyaluran bantuan yang cepat dan tepat sasaran.</li>
                </ul>
                <a href="#contact" class="btn btn-secondary">Hubungi Kami →</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features fade-in" id="features">
        <div class="container">
            <div class="section-header">
                <h2>Program Unggulan</h2>
                <p>Inisiatif nyata kami dalam memberdayakan umat dan membangun masyarakat yang lebih sejahtera.</p>
            </div>

            <!-- Tab Navigation -->
            <div class="feature-tabs">
                <button class="tab-link active" data-tab="tab-1">
                    <span class="tab-icon">🤝</span>
                    <span>Bantuan Langsung</span>
                </button>
                <button class="tab-link" data-tab="tab-2">
                    <span class="tab-icon">❤️</span>
                    <span>Santunan Yatim</span>
                </button>
                <button class="tab-link" data-tab="tab-3">
                    <span class="tab-icon">⚕️</span>
                    <span>Layanan Kesehatan</span>
                </button>
            </div>

            <!-- Tab Content -->
            <div class="feature-content-wrapper">
                <!-- Tab 1 Content -->
                <div id="tab-1" class="feature-tab-content active">
                    <div class="feature-image">
                        <img src="https://lh3.googleusercontent.com/d/1AHa2dY7tquvKWYu6VwkD20jba-9Aa7zB" alt="Penyaluran Bantuan">
                    </div>
                    <div class="feature-content">
                        <h3>Penyaluran Bantuan Langsung</h3>
                        <p>Kami memastikan bantuan sampai langsung ke tangan mereka yang membutuhkan. Melalui survei yang ketat dan pendataan yang akurat, setiap rupiah donasi Anda disalurkan kepada mustahik yang tepat sasaran, mulai dari sembako hingga bantuan tunai.</p>
                        <a href="#contact" class="btn-text">Dukung Program Ini →</a>
                    </div>
                </div>
                <!-- Tab 2 Content -->
                <div id="tab-2" class="feature-tab-content">
                    <div class="feature-image">
                        <img src="https://lh3.googleusercontent.com/d/1lRGnGGq8IP8np_K6-9zWUPASUuT30q6-" alt="Santunan Yatim">
                    </div>
                    <div class="feature-content">
                        <h3>Senyum Bahagia Anak Yatim</h3>
                        <p>Program santunan rutin dan pembinaan karakter bagi anak-anak yatim dhuafa. Kami tidak hanya memberikan bantuan materi, tetapi juga dukungan moral dan pendidikan agar mereka tumbuh menjadi generasi yang mandiri dan berakhlak mulia.</p>
                        <a href="#contact" class="btn-text">Jadi Orang Tua Asuh →</a>
                    </div>
                </div>
                <!-- Tab 3 Content -->
                <div id="tab-3" class="feature-tab-content">
                    <div class="feature-image">
                        <img src="https://lh3.googleusercontent.com/d/1ZEtIlPw4eOKxu5izFi197otsnkPHrdRf" alt="Layanan Kesehatan">
                    </div>
                    <div class="feature-content">
                        <h3>Layanan Kesehatan Gratis</h3>
                        <p>Akses kesehatan adalah hak setiap insan. Kami menyelenggarakan pengobatan gratis, khitanan massal, dan penyuluhan kesehatan di daerah-daerah terpencil yang minim fasilitas medis, demi mewujudkan masyarakat yang sehat dan produktif.</p>
                        <a href="#contact" class="btn-text">Donasi Kesehatan →</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->

    <!-- What We Offer Section -->
    <section class="offer fade-in" id="services">
        <div class="container">
            <div class="section-header">
                <h2>Program Utama Kami</h2>
            </div>
            <div class="offer-grid">
                <div class="offer-card">
                    <div class="offer-icon">🕋</div>
                    <h3>Zakat</h3>
                    <p>Tunaikan kewajiban zakat Anda untuk membersihkan harta dan membantu sesama. Kami salurkan kepada 8 asnaf yang berhak.</p>
                    <a href="#" class="offer-link">Hitung Zakat →</a>
                </div>
                <div class="offer-card">
                    <div class="offer-icon">🤲</div>
                    <h3>Infaq & Sedekah</h3>
                    <p>Sedekah Anda menjadi harapan bagi mereka yang membutuhkan. Salurkan infaq terbaik Anda untuk program kemanusiaan.</p>
                    <a href="#" class="offer-link">Donasi Sekarang →</a>
                </div>
                <div class="offer-card">
                    <div class="offer-icon">🤝</div>
                    <h3>Kemanusiaan</h3>
                    <p>Bantuan cepat tanggap untuk korban bencana alam, krisis kemanusiaan, dan program sosial lainnya di seluruh Indonesia.</p>
                    <a href="#" class="offer-link">Lihat Aksi Kami →</a>
                </div>
                <div class="offer-card">
                    <div class="offer-icon">🕌</div>
                    <h3>Wakaf</h3>
                    <p>Investasi akhirat melalui wakaf produktif seperti pembangunan sumur, masjid, atau modal usaha untuk dhuafa.</p>
                    <a href="#" class="offer-link">Pelajari Wakaf →</a>
                </div>                
            </div>
        </div>
    </section>

    <!-- Portfolio Section -->
    <section class="portfolio fade-in" id="portfolio">
        <div class="container">
            <div class="section-header">
                <h2>Galeri Kegiatan</h2>
                <p>Dokumentasi penyaluran dana dan kegiatan sosial kami.</p>
            </div>
            <div class="portfolio-grid">
                <div class="portfolio-item"><img src="https://lh3.googleusercontent.com/d/1O77oVXz4jNjzKx-24DtfaYWjldfejdYZ" alt="Kegiatan 1"></div>
                <div class="portfolio-item"><img src="https://lh3.googleusercontent.com/d/1YgCHGRGZVYz-gpj4umxp4sxx7jIPPMR_" alt="Kegiatan 2"></div>
                <div class="portfolio-item"><img src="https://lh3.googleusercontent.com/d/1ZEtIlPw4eOKxu5izFi197otsnkPHrdRf" alt="Kegiatan 3"></div>
                <div class="portfolio-item"><img src="https://lh3.googleusercontent.com/d/1O77oVXz4jNjzKx-24DtfaYWjldfejdYZ" alt="Kegiatan 4"></div>
            </div>
        </div>
    </section>

    <!-- Blog Section -->
    <section class="blog fade-in" id="blog">
        <div class="container">
            <div class="section-header">
                <h2>Berita & Artikel</h2>
                <p>Informasi terbaru seputar kegiatan dan artikel inspiratif.</p>
            </div>
            <div class="blog-grid">
                <!-- Blog Item 1 -->
                <div class="blog-card">
                    <div class="blog-img">
                        <img src="https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?auto=format&fit=crop&w=500&q=60" alt="Blog 1">
                        <span class="blog-date">12 Okt 2023</span>
                    </div>
                    <div class="blog-content">
                        <h3>Penyaluran Zakat Fitrah 1444H</h3>
                        <p>Alhamdulillah, telah terlaksana penyaluran zakat fitrah kepada 500 mustahik di wilayah Bogor...</p>
                        <a href="#" class="read-more">Baca Selengkapnya →</a>
                    </div>
                </div>
                <!-- Blog Item 2 -->
                <div class="blog-card">
                    <div class="blog-img">
                        <img src="https://images.unsplash.com/photo-1532629345422-7515f3d16bb6?auto=format&fit=crop&w=500&q=60" alt="Blog 2">
                        <span class="blog-date">05 Sep 2023</span>
                    </div>
                    <div class="blog-content">
                        <h3>Beasiswa Pendidikan Yatim</h3>
                        <p>Program beasiswa untuk anak-anak yatim berprestasi kembali dibuka. Simak syarat dan ketentuannya...</p>
                        <a href="#" class="read-more">Baca Selengkapnya →</a>
                    </div>
                </div>
                <!-- Blog Item 3 -->
                <div class="blog-card">
                    <div class="blog-img">
                        <img src="https://images.unsplash.com/photo-1593113598332-cd288d649433?auto=format&fit=crop&w=500&q=60" alt="Blog 3">
                        <span class="blog-date">20 Agu 2023</span>
                    </div>
                    <div class="blog-content">
                        <h3>Wakaf Sumur Air Bersih</h3>
                        <p>Peresmian wakaf sumur air bersih di desa kekeringan. Air mengalir, pahala mengalir...</p>
                        <a href="#" class="read-more">Baca Selengkapnya →</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact fade-in" id="contact">
        <div class="container">
            <div class="section-header">
                <h2>Hubungi Kami</h2>
                <p>Punya pertanyaan atau ingin berdonasi? Kirimkan pesan kepada kami.</p>
            </div>
            <div class="contact-wrapper">
                <form class="contact-form">
                    <div class="form-group">
                        <input type="text" placeholder="Nama Lengkap" required>
                    </div>
                    <div class="form-group">
                        <input type="email" placeholder="Alamat Email" required>
                    </div>
                    <div class="form-group">
                        <textarea placeholder="Pesan Anda" rows="5" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Kirim Pesan</button>
                </form>
            </div>
        </div>
    </section>

    <!-- Footer Section -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <div class="footer-logo">
                        <img src="https://lh3.googleusercontent.com/d/1G2xVlrBuc4IU6ynaGf8Xg_c9Y7jSg3Wm" alt="Logo DDU">
                        <span>Dompet Dana Umat</span>
                    </div>
                    <p>Lembaga amil zakat yang terpercaya, amanah, dan profesional dalam mengelola dana umat untuk kesejahteraan masyarakat.</p>
                </div>
                <div class="footer-col">
                    <h4>Contact Info</h4>
                    <ul class="contact-info">
                        <li>📍 Jl. Raya Puncak No. 123, Bogor</li>
                        <li>📞 +62 812 3456 7890</li>
                        <li>✉️ info@dompetdanaumat.com</li>
                        <li>✉️ Admin@dompetdanaumat.com</li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Follow Us</h4>
                    <div class="social-links">
                        <a href="#" class="social-icon">FB</a>
                        <a href="#" class="social-icon">IG</a>
                        <a href="#" class="social-icon">TW</a>
                        <a href="#" class="social-icon">YT</a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2023 Dompet Dana Umat. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <a href="#" class="back-to-top">↑</a>

    <!-- WhatsApp Popup -->
    <a href="https://wa.me/6281234567890?text=Assalamualaikum,%20saya%20ingin%20bertanya%20tentang%20Dompet%20Dana%20Umat" class="whatsapp-popup" target="_blank">
        <img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg" alt="WhatsApp">
    </a>

    <!-- Lightbox -->
    <div id="lightbox" class="lightbox">
        <span class="close-lightbox">&times;</span>
        <img class="lightbox-content" id="lightbox-img">
    </div>

    <script>
        const backToTopButton = document.querySelector('.back-to-top');

        // Preloader Script
        window.addEventListener('load', function() {
            const preloader = document.querySelector('.preloader');
            preloader.classList.add('hidden');
        });

        window.addEventListener('scroll', function() {
            const header = document.querySelector('.main-header');
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }

            // Show/Hide Back to Top Button
            if (window.scrollY > 300) {
                backToTopButton.classList.add('visible');
            } else {
                backToTopButton.classList.remove('visible');
            }
        });

        // Hamburger Menu Script
        const menuToggle = document.querySelector('.menu-toggle');
        const navLinks = document.querySelector('.nav-links');

        menuToggle.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            menuToggle.classList.toggle('active');
        });

        // Close mobile menu when a link is clicked
        document.querySelectorAll('.nav-links a').forEach(link => {
            link.addEventListener('click', () => {
                navLinks.classList.remove('active');
                menuToggle.classList.remove('active');
            });
        });

        // Back to Top Click Event
        backToTopButton.addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Lightbox Script
        const lightbox = document.getElementById('lightbox');
        const lightboxImg = document.getElementById('lightbox-img');
        const portfolioItems = document.querySelectorAll('.portfolio-item img');
        const closeLightbox = document.querySelector('.close-lightbox');

        portfolioItems.forEach(item => {
            item.addEventListener('click', function() {
                lightbox.style.display = "flex";
                lightboxImg.src = this.src;
            });
        });

        closeLightbox.addEventListener('click', function() {
            lightbox.style.display = "none";
        });

        lightbox.addEventListener('click', function(e) {
            if (e.target !== lightboxImg) {
                lightbox.style.display = "none";
            }
        });

        // Scroll Animation Script
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1 // Trigger when 10% of the element is visible
        });

        const elementsToFadeIn = document.querySelectorAll('.fade-in');
        elementsToFadeIn.forEach((el) => observer.observe(el));

        // Active Nav Link on Scroll Script
        const sections = document.querySelectorAll('section[id]');
        const navObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const id = entry.target.id;
                    document.querySelectorAll('.nav-links a').forEach(link => {
                        link.classList.remove('active');
                    });
                    const activeLink = document.querySelector(`.nav-links a[href="#${id}"]`);
                    if (activeLink) {
                        activeLink.classList.add('active');
                    }
                }
            });
        }, { rootMargin: "-50% 0px -50% 0px" }); // Trigger di tengah layar

        sections.forEach(section => {
            navObserver.observe(section);
        });

        // Statistics Counter Animation
        const statsSection = document.querySelector('.stats');
        const statNumbers = document.querySelectorAll('.stat-number');
        let started = false;

        const statsObserver = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting && !started) {
                statNumbers.forEach(num => {
                    const target = +num.getAttribute('data-target');
                    const duration = 2000; // 2 seconds
                    const increment = target / (duration / 16); 
                    
                    let current = 0;
                    const updateCount = () => {
                        current += increment;
                        if (current < target) {
                            num.innerText = Math.ceil(current) + "+";
                            requestAnimationFrame(updateCount);
                        } else {
                            num.innerText = target + "+";
                        }
                    };
                    updateCount();
                });
                started = true;
            }
        });

        if(statsSection) {
            statsObserver.observe(statsSection);
        }

        // Feature Tabs Script
        const tabLinks = document.querySelectorAll('.tab-link');
        const tabContents = document.querySelectorAll('.feature-tab-content');

        tabLinks.forEach(link => {
            link.addEventListener('click', () => {
                // Remove active class from all
                tabLinks.forEach(l => l.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));

                // Add active class to clicked link and corresponding content
                link.classList.add('active');
                const tabId = link.getAttribute('data-tab');
                document.getElementById(tabId).classList.add('active');
            });
        });
    </script>
</body>

</html>
```


## style.css

```css
/* ===== style.css ===== */
/* RESET & BASE */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
    scroll-padding-top: 100px; /* Mencegah konten tertutup navbar saat diklik */
}

body {
    font-family: 'Segoe UI', Roboto, system-ui, -apple-system, sans-serif;
    background-color: #f0f5ff;  /* Light blue background */
    color: #1e3a6b;
    line-height: 1.6;
}

.container {
    max-width: 1280px;
    margin: -2px auto;
    padding: 10px 80px;
}

/* TYPOGRAPHY */
h1 {
    font-size: 3.2rem;
    font-weight: 700;
    line-height: 1.2;
    color: #0a2647;
    margin-bottom: 20px;
}

h2 {
    font-size: 2.5rem;
    font-weight: 700;
    color: #0a2647;
    margin-bottom: 20px;
}

h3 {
    font-size: 1.5rem;
    font-weight: 600;
    color: #0a2647;
    margin-bottom: 12px;
}

h5 {
    font-size: 1rem;
    font-weight: 600;
    color: #2b6c9e;
    letter-spacing: 2px;
    text-transform: uppercase;
    margin-bottom: 10px;
}

p {
    color: #2c3e5c;
    margin-bottom: 20px;
}

/* NAVIGATION */
.main-header {
    position: fixed;
    top: 15px;
    left: 50%;
    transform: translateX(-50%);
    width: 95%;
    max-width: 1280px;
    border-radius: 10px;
    z-index: 1000;
    transition: all 0.3s ease;
}

.main-header.scrolled {
    background-color: rgba(10, 43, 94, 0.95); /* Warna biru gelap transparan */
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.main-header.scrolled .navbar {
    padding: 10px 0; /* Mengecilkan padding saat scroll */
    border-bottom: none;
}

.navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 0;
    flex-wrap: wrap;
    border-bottom: 1px solid rgba(255, 255, 255, 0.15);
}

.logo {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 1.6rem;
    font-weight: 700;
    color: #ffffff;
    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.logo img {
    height: 35px;
}

.nav-links {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.nav-links a {
    text-decoration: none;
    color: rgba(255, 255, 255, 0.9);
    font-weight: 500;
    font-size: 1rem;
    transition: color 0.3s;
    position: relative;
    line-height: 5px;
}

.nav-links a:hover {
    color: #c0c8cf;
}

.nav-links a.active {
    color: #ffffff;
    font-weight: 700;
}

/* HAMBURGER MENU */
.menu-toggle {
    display: none;
    flex-direction: column;
    cursor: pointer;
}

.menu-toggle .bar {
    width: 25px;
    height: 3px;
    background-color: white;
    margin: 3px 0;
    transition: all 0.3s ease-in-out;
}

/* Animasi Hamburger ke X */
.menu-toggle.active .bar:nth-child(1) {
    transform: translateY(9px) rotate(45deg);
}

.menu-toggle.active .bar:nth-child(2) {
    opacity: 0;
}

.menu-toggle.active .bar:nth-child(3) {
    transform: translateY(-9px) rotate(-45deg);
}

/* BUTTONS */
.btn {
    display: inline-block;
    padding: 12px 30px;
    text-decoration: none;
    font-weight: 600;
    border-radius: 6px;
    transition: all 0.3s;
}

.btn-primary {
    background-color: #1e4b9c;
    color: white;
    border: 2px solid #1e4b9c;
}

.btn-primary:hover {
    background-color: #0a2b5e;
    border-color: #0a2b5e;
}

.btn-secondary {
    background-color: transparent;
    color: #1e4b9c;
    border: 2px solid #1e4b9c;
}

.btn-secondary:hover {
    background-color: #1e4b9c;
    color: white;
}

/* HERO SECTION */
.hero {
    position: relative;
    padding: 250px 0 200px;
    background-color: #0a2b5e;
    overflow: hidden;
    color: white;
}

.hero-slider {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1;
}

.slide {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-size: cover;
    background-position: center;
    opacity: 0;
    animation: heroSlide 15s infinite;
}

.slide:nth-child(1) { animation-delay: 0s; }
.slide:nth-child(2) { animation-delay: 5s; }
.slide:nth-child(3) { animation-delay: 10s; }

@keyframes heroSlide {
    0% { opacity: 0; transform: scale(1.1); }
    4% { opacity: 1; transform: scale(1); }
    33% { opacity: 1; transform: scale(1); }
    37% { opacity: 0; transform: scale(1.1); }
    100% { opacity: 0; transform: scale(1.1); }
}

.hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(10, 38, 71, 0.75);
    z-index: 2;
}

.hero-container {
    position: relative;
    z-index: 3;
    display: block;
}

.hero-content {
    max-width: 700px;
}

.hero-content h1 {
    color: #ffffff;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
}

.hero-content h5 {
    color: #64b5f6;
    text-shadow: 0 1px 4px rgba(0, 0, 0, 0.4);
}

.hero-content p {
    font-size: 1.1rem;
    margin-bottom: 30px;
    color: #e0e0e0;
    text-shadow: 0 1px 4px rgba(0, 0, 0, 0.4);
}

/* FEATURES SECTION (Tabbed Layout) */
.features {
    padding: 100px 0;
    background-color: #f0f5ff;
}

.feature-tabs {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-bottom: 50px;
    flex-wrap: wrap;
}

.tab-link {
    padding: 15px 30px;
    border: 2px solid #dbe4f0;
    background-color: white;
    color: #4a5568;
    border-radius: 50px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 10px;
}

.tab-link:hover {
    background-color: #e3f2fd;
    border-color: #a9cceb;
}

.tab-link.active {
    background-color: #1e4b9c;
    color: white;
    border-color: #1e4b9c;
    box-shadow: 0 10px 20px rgba(30, 75, 156, 0.2);
    transform: translateY(-2px);
}

.tab-icon {
    font-size: 1.2rem;
}

.feature-content-wrapper {
    position: relative;
    background: white;
    padding: 50px;
    border-radius: 20px;
    box-shadow: 0 20px 50px rgba(0, 60, 120, 0.08);
}

.feature-tab-content {
    display: none;
    grid-template-columns: 1fr 1fr;
    gap: 50px;
    align-items: center;
    animation: fadeInContent 0.5s ease-in-out;
}

.feature-tab-content.active {
    display: grid;
}

@keyframes fadeInContent {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.feature-image {
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 15px 30px rgba(0,0,0,0.1);
    height: 380px;
}

.feature-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.feature-content h3 {
    font-size: 2.2rem;
    color: #0a2647;
    margin-bottom: 20px;
}

.feature-content p {
    font-size: 1.1rem;
    color: #4a5568;
    line-height: 1.8;
    margin-bottom: 30px;
}

.btn-text {
    color: #1e4b9c;
    font-weight: 700;
    text-decoration: none;
    font-size: 1.05rem;
    display: inline-flex;
    align-items: center;
    transition: transform 0.3s;
}

.btn-text:hover {
    transform: translateX(5px);
    color: #0a2b5e;
}

/* ENHANCED ABOUT IMAGES */
.about-img-wrapper {
    position: relative;
    z-index: 1;
    padding-right: 20px; /* Ruang untuk gambar kedua */
    padding-bottom: 20px;
}

.about-img-1 {
    width: 85%;
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    display: block;
    object-fit: cover;
}

.about-img-2 {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 55%;
    border-radius: 15px;
    border: 8px solid white;
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    object-fit: cover;
}

.experience-badge {
    position: absolute;
    top: 40px;
    right: 40px;
    background: linear-gradient(135deg, #1e4b9c, #0a2b5e);
    color: white;
    width: 110px;
    height: 110px;
    border-radius: 50%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    box-shadow: 0 10px 25px rgba(30, 75, 156, 0.4);
    animation: floatBadge 3s ease-in-out infinite;
    z-index: 2;
    border: 4px solid rgba(255,255,255,0.2);
}

.experience-badge .years {
    font-size: 1.8rem;
    font-weight: 800;
    line-height: 1;
}

.experience-badge .text {
    font-size: 0.75rem;
    font-weight: 500;
    line-height: 1.2;
    margin-top: 2px;
}

@keyframes floatBadge {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

/* ABOUT SECTION */
.about {
    padding: 80px 0;
    background-color: white;
    position: relative;
    overflow: hidden;
}

/* Cloud Ornaments (Background) */
.about::before {
    content: "";
    position: absolute;
    top: -80px;
    left: -80px;
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, #e3f2fd 0%, rgba(255,255,255,0) 70%);
    border-radius: 50%;
    z-index: 0;
    opacity: 0.6;
    animation: floatCloud 15s infinite ease-in-out;
}

.about::after {
    content: "";
    position: absolute;
    bottom: -100px;
    right: -50px;
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, #e3f2fd 0%, rgba(255,255,255,0) 70%);
    border-radius: 50%;
    z-index: 0;
    opacity: 0.6;
    animation: floatCloud 20s infinite ease-in-out reverse;
}

@keyframes floatCloud {
    0% { transform: translate(0, 0); }
    50% { transform: translate(30px, 20px); }
    100% { transform: translate(0, 0); }
}

.about-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    align-items: center;
    position: relative;
    z-index: 1;
}

.about-list {
    list-style: none;
    margin: 25px 0;
}

.about-list li {
    margin-bottom: 12px;
    font-size: 1.1rem;
    color: #1e3a6b;
}

.about-list li::before {
    content: "✓";
    color: #1e4b9c;
    font-weight: bold;
    margin-right: 10px;
}

/* OFFER SECTION */
.offer {
    padding: 60px 0;
    background-color: #f0f5ff;
}

.section-header {
    text-align: center;
    margin-bottom: 50px;
}

.section-header h2 {
    font-size: 2.5rem;
    position: relative;
    display: inline-block;
    padding-bottom: 15px;
}

.section-header h2::after {
    content: "";
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 3px;
    background-color: #1e4b9c;
}

.offer-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 30px;
}

.offer-card {
    background: white;
    padding: 35px 25px;
    border-radius: 15px;
    text-align: center;
    box-shadow: 0 10px 30px rgba(0, 60, 120, 0.05);
    transition: transform 0.3s;
}

.offer-card:hover {
    transform: translateY(-5px);
}

.offer-icon {
    font-size: 3rem;
    margin-bottom: 15px;
}

.offer-card h3 {
    margin-bottom: 15px;
}

.offer-card p {
    font-size: 0.95rem;
    margin-bottom: 20px;
    color: #3a4e6b;
}

.offer-link {
    text-decoration: none;
    color: #1e4b9c;
    font-weight: 600;
    font-size: 1rem;
}

.offer-link:hover {
    color: #0a2b5e;
}

/* RESPONSIVE DESIGN */
@media (max-width: 1024px) {
    .hero-container,
    .about-container {
        grid-template-columns: 1fr;
    }
    
    .features-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .offer-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    h1 {
        font-size: 2.8rem;
    }
    
    h2 {
        font-size: 2.2rem;
    }
}

@media (max-width: 768px) {
    .main-header {
        position: fixed;
        top: 15px;
        left: 50%;
        transform: translateX(-50%);
        width: 95%;
        max-width: 1280px;
        border-radius: 10px;
        z-index: 1000;
        transition: all 0.3s ease;
    }

    .navbar {
        flex-direction: row;
        position: relative;
    }

    .menu-toggle {
        display: flex;
    }
    
    .nav-links {
        display: none; /* Sembunyikan menu secara default di HP */
        width: 100%;
        flex-direction: column;
        position: absolute;
        top: 100%;
        left: 0;
        background-color: #0a2b5e; /* Background biru gelap */
        padding: 20px 0;
        text-align: center;
        box-shadow: 0 10px 20px rgba(0,0,0,0.2);
    }
    
    .nav-links.active {
        display: flex; /* Tampilkan saat tombol diklik */
    }

    .features-grid,
    .offer-grid {
        grid-template-columns: 1fr;
    }

    .feature-tab-content {
        grid-template-columns: 1fr;
        gap: 30px;
    }
    .feature-image {
        height: 250px;
    }
    .feature-content-wrapper {
        padding: 30px;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .container {
        padding: 0 20px;
    }

    .hero {
        padding: 140px 0 70px; /* Mengurangi tinggi hero di HP */
    }

    .features, .about, .offer, .portfolio, .contact {
        padding: 40px 0; /* Mengurangi jarak antar section */
    }

    .logo {
        font-size: 1.2rem;
    }

    .logo img {
        height: 30px;
    }
    
    h1 {
        font-size: 1.8rem; /* Judul utama lebih kecil */
    }
    
    h2 {
        font-size: 1.5rem; /* Sub-judul lebih kecil */
    }

    h3 {
        font-size: 1.25rem;
    }

    p {
        font-size: 0.9rem; /* Teks paragraf lebih kecil & nyaman */
    }

    .feature-icon, .offer-icon {
        font-size: 2rem; /* Ikon tidak terlalu raksasa */
    }

    .btn {
        padding: 10px 24px;
        font-size: 0.9rem;
    }

    /* Responsive About Images */
    .about-img-wrapper {
        padding-right: 0;
        margin-bottom: 30px;
    }
    .about-img-1 {
        width: 100%;
    }
    .about-img-2 {
        width: 50%;
        bottom: -20px;
        right: -10px;
        border-width: 5px;
    }
    }


/* PORTFOLIO SECTION */
.portfolio {
    padding: 60px 0;
    background-color: white;
}

.portfolio-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.portfolio-item img {
    width: 100%;
    height: 250px;
    object-fit: cover;
    border-radius: 8px;
    transition: transform 0.3s ease;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    cursor: pointer;
}

.portfolio-item img:hover {
    transform: scale(1.03);
}

/* CONTACT SECTION */
.contact {
    padding: 60px 0 80px;
    background-color: #f0f5ff;
}

.contact-wrapper {
    max-width: 700px;
    margin: 0 auto;
    background: white;
    padding: 40px;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 60, 120, 0.05);
}

.form-group {
    margin-bottom: 20px;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-family: inherit;
    font-size: 1rem;
    outline: none;
    transition: border-color 0.3s;
}

.form-group input:focus,
.form-group textarea:focus {
    border-color: #1e4b9c;
}

/* BACK TO TOP BUTTON */
.back-to-top {
    position: fixed;
    bottom: 100px; /* Digeser ke atas agar tidak menutupi WA */
    right: 35px;   /* Diselaraskan tengah dengan tombol WA */
    width: 50px;
    height: 50px;
    background-color: #1e4b9c;
    color: white;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    text-decoration: none;
    font-size: 1.5rem;
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    z-index: 999;
}

.back-to-top.visible {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.back-to-top:hover {
    background-color: #0a2b5e;
    transform: translateY(-5px);
}

/* FOOTER */
.footer {
    background-color: #051a3d; /* Biru sangat gelap */
    color: #cfd8dc;
    padding: 70px 0 25px;
    font-size: 0.95rem;
}

.footer-grid {
    display: grid;
    grid-template-columns: 1.5fr 1fr 1fr 1fr;
    gap: 10px;
    margin-bottom: 50px;
}

.footer-logo {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
    color: white;
    font-weight: 700;
    font-size: 1.3rem;
}

.footer-logo img {
    height: 35px;
}

.footer-col h4 {
    color: white;
    font-size: 1.1rem;
    margin-bottom: 25px;
    font-weight: 600;
}

.footer-col ul {
    list-style: none;
}

.footer-col ul li {
    margin-bottom: 12px;
}

.footer-col a {
    color: #cfd8dc;
    text-decoration: none;
    transition: all 0.3s;
}

.footer-col a:hover {
    color: #64b5f6;
    padding-left: 5px;
}

.contact-info li {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
}

.social-links {
    display: flex;
    gap: 10px;
}

.social-icon {
    width: 40px;
    height: 40px;
    background: rgba(255,255,255,0.1);
    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: 50%;
    color: white !important; /* Override warna link default */
    font-size: 0.8rem;
    font-weight: bold;
}

.social-icon:hover {
    background: #1e4b9c;
    transform: translateY(-3px);
    padding-left: 0 !important;
}

.footer-bottom {
    border-top: 1px solid rgba(255,255,255,0.1);
    padding-top: 25px;
    text-align: center;
    font-size: 0.9rem;
}

@media (max-width: 900px) {
    .footer-grid {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 600px) {
    .footer-grid {
        grid-template-columns: 1fr;
    }
}

/* WHATSAPP POPUP */
.whatsapp-popup {
    position: fixed;
    bottom: 30px;
    right: 30px; /* Pindah ke pojok kanan */
    width: 60px;
    height: 60px;
    background-color: #25d366;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
    z-index: 1000;
    transition: all 0.3s ease;
    animation: pulse 2s infinite;
}

.whatsapp-popup:hover {
    transform: scale(1.1);
}

.whatsapp-popup img {
    width: 35px;
    height: 35px;
}

@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(37, 211, 102, 0.7); }
    70% { box-shadow: 0 0 0 15px rgba(37, 211, 102, 0); }
    100% { box-shadow: 0 0 0 0 rgba(37, 211, 102, 0); }
}

/* PRELOADER */
.preloader {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: #ffffff;
    z-index: 9999;
    display: flex;
    justify-content: center;
    align-items: center;
    transition: opacity 0.5s ease, visibility 0.5s ease;
}

.preloader.hidden {
    opacity: 0;
    visibility: hidden;
}

.spinner {
    width: 50px;
    height: 50px;
    border: 5px solid #f3f3f3;
    border-top: 5px solid #1e4b9c; /* Warna biru tema */
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* LIGHTBOX */
.lightbox {
    display: none;
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.9);
    justify-content: center;
    align-items: center;
}

.lightbox-content {
    margin: auto;
    display: block;
    max-width: 90%;
    max-height: 90%;
    border-radius: 5px;
    box-shadow: 0 0 20px rgba(255, 255, 255, 0.2);
    animation: zoom 0.3s;
}

@keyframes zoom {
    from {transform:scale(0.8); opacity: 0;}
    to {transform:scale(1); opacity: 1;}
}

.close-lightbox {
    position: absolute;
    top: 20px;
    right: 35px;
    color: #f1f1f1;
    font-size: 40px;
    font-weight: bold;
    transition: 0.3s;
    cursor: pointer;
}

.close-lightbox:hover {
    color: #bbb;
}

/* SCROLL ANIMATION */
.fade-in {
    opacity: 0;
    transform: translateY(30px);
    transition: opacity 0.6s ease-out, transform 0.6s ease-out;
}

.fade-in.visible {
    opacity: 1;
    transform: translateY(0);
}

/* BLOG SECTION */
.blog {
    padding: 60px 0;
    background-color: white;
}

.blog-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
}

.blog-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    transition: transform 0.3s ease;
    border: 1px solid #eee;
}

.blog-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.1);
}

.blog-img {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.blog-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.blog-card:hover .blog-img img {
    transform: scale(1.1);
}

.blog-date {
    position: absolute;
    top: 15px;
    right: 15px;
    background: #1e4b9c;
    color: white;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.blog-content {
    padding: 25px;
}

.read-more {
    display: inline-block;
    margin-top: 15px;
    color: #1e4b9c;
    font-weight: 600;
    text-decoration: none;
    transition: padding-left 0.3s;
}

.read-more:hover {
    padding-left: 5px;
    color: #0a2b5e;
}
```

