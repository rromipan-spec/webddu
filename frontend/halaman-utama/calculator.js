document.addEventListener('DOMContentLoaded', function() {
    const zakatForm = document.getElementById('zakatForm');
    const zakatResult = document.getElementById('zakatResult');
    const resetBtn = document.getElementById('resetBtn');

    // --- PENGAMBILAN HARGA EMAS OTOMATIS ---

    // Nilai default jika API gagal (Rp 1.300.000/gram - Estimasi)
    let goldPricePerGram = 1300000; 
    const nishabGoldGram = 85;
    let nishabMonthly = (goldPricePerGram * nishabGoldGram) / 12;

    // Elemen UI untuk info nishab
    const goldPriceEl = document.getElementById('goldPrice');
    const nishabValueEl = document.getElementById('nishabValue');

    // Helper Function Format Rupiah
    function formatRupiah(num) {
        return 'Rp ' + num.toLocaleString('id-ID');
    }

    // Fungsi untuk memperbarui teks info nishab di HTML
    function updateNishabInfo(price, nishab) {
        if (goldPriceEl && nishabValueEl) {
            goldPriceEl.textContent = `${formatRupiah(price)}/gram`;
            nishabValueEl.textContent = formatRupiah(Math.round(nishab));
        }
    }

    // Ambil data harga emas dari API
    fetch('https://api.harga-emas.org/antam/')
        .then(response => response.json())
        .then(data => {
            // API ini mengembalikan array, kita ambil harga terbaru
            const antamPrice = parseInt(data[0].price);
            if (antamPrice) {
                goldPricePerGram = antamPrice;
                nishabMonthly = (goldPricePerGram * nishabGoldGram) / 12;
                updateNishabInfo(goldPricePerGram, nishabMonthly);
            }
        })
        .catch(error => {
            console.error('Gagal mengambil data harga emas, menggunakan harga default.', error);
            // Tetap tampilkan harga default jika API gagal
            updateNishabInfo(goldPricePerGram, nishabMonthly); 
        });
    
    // Inisialisasi tampilan awal (sebelum fetch selesai)
    updateNishabInfo(goldPricePerGram, nishabMonthly);

    // --- FORMAT INPUT RUPIAH SAAT MENGETIK ---
    const formInputs = ['income', 'bonus', 'debt'];
    
    formInputs.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', function(e) {
                this.value = formatRupiahTyping(this.value);
            });
        }
    });

    function formatRupiahTyping(angka) {
        let number_string = angka.replace(/[^,\d]/g, '').toString(),
            split = number_string.split(','),
            sisa = split[0].length % 3,
            rupiah = split[0].substr(0, sisa),
            ribuan = split[0].substr(sisa).match(/\d{3}/gi);

        if (ribuan) {
            let separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }

        rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
        return rupiah ? 'Rp ' + rupiah : '';
    }

    // Helper untuk mengubah string Rupiah kembali ke angka murni untuk perhitungan
    function parseRupiah(str) {
        return parseFloat(str.replace(/[^0-9]/g, '')) || 0;
    }

    // --- LOGIKA KALKULATOR & RESET ---

    if (zakatForm) {
        zakatForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Ambil nilai input, bersihkan format Rupiah, dan pastikan tidak negatif
            const income = parseRupiah(document.getElementById('income').value);
            const bonus = parseRupiah(document.getElementById('bonus').value);
            const debt = parseRupiah(document.getElementById('debt').value);

            const totalIncome = income + bonus;
            const netIncome = totalIncome - debt;
            
            // Hitung Estimasi Pendapatan Bersih Setahun: (Gaji x 12) + Bonus - (Hutang x 12)
            const annualNetIncome = (income * 12) + bonus - (debt * 12);

            let zakatMonthly = 0;
            let zakatYearly = 0;
            let nishabStatusText = "";
            let nishabStatusColor = "";

            // Cek Nishab menggunakan nilai nishabMonthly yang dinamis
            if (netIncome >= nishabMonthly) {
                zakatMonthly = netIncome * 0.025;
                nishabStatusText = "Mencapai Nishab (Wajib Zakat)";
                nishabStatusColor = "#27ae60"; // Hijau
            } else {
                zakatMonthly = 0;
                nishabStatusText = "Belum Mencapai Nishab (Tidak Wajib)";
                nishabStatusColor = "#d35400"; // Oranye
            }

            // Hitung Zakat Tahunan (Jika pendapatan setahun mencapai nishab tahunan)
            const nishabYearly = nishabMonthly * 12;
            if (annualNetIncome >= nishabYearly) {
                zakatYearly = annualNetIncome * 0.025;
            } else {
                zakatYearly = 0;
            }

            // Tampilkan Hasil
            document.getElementById('totalNetIncome').innerText = formatRupiah(netIncome < 0 ? 0 : netIncome);
            
            const annualNetIncomeEl = document.getElementById('annualNetIncome');
            if (annualNetIncomeEl) {
                annualNetIncomeEl.innerText = formatRupiah(annualNetIncome < 0 ? 0 : annualNetIncome);
            }
            
            const statusEl = document.getElementById('nishabStatus');
            statusEl.innerText = nishabStatusText;
            statusEl.style.color = nishabStatusColor;

            document.getElementById('zakatAmountMonthly').innerText = formatRupiah(zakatMonthly);
            document.getElementById('zakatAmountYearly').innerText = formatRupiah(zakatYearly);
            
            zakatResult.classList.add('show');
            zakatResult.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });

        if (resetBtn) {
            resetBtn.addEventListener('click', function() {
                zakatForm.reset();
                zakatResult.classList.remove('show');
            });
        }
    }
});