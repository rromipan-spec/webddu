document.addEventListener('DOMContentLoaded', function() {
    const zakatForm = document.getElementById('zakatForm');
    const zakatResult = document.getElementById('zakatResult');
    const resetBtn = document.getElementById('resetBtn');

    // --- PENGAMBILAN HARGA EMAS OTOMATIS ---

    // Acuan sementara BAZNAS 2026 digunakan sampai endpoint harga harian merespons.
    let nishabYearly = 91681728;
    let goldPricePerGram = Math.round(nishabYearly / 85);
    let nishabMonthly = 7640144;

    // Elemen UI untuk info nishab
    const goldPriceEl = document.getElementById('goldPrice');
    const nishabValueEl = document.getElementById('nishabValue');
    const nishabYearlyValueEl = document.getElementById('nishabYearlyValue');
    const goldPriceMetaEl = document.getElementById('goldPriceMeta');

    // Helper Function Format Rupiah
    function formatRupiah(num) {
        return 'Rp ' + Math.max(0, Math.round(Number(num) || 0)).toLocaleString('id-ID');
    }

    // Fungsi untuk memperbarui teks info nishab di HTML
    function updateNishabInfo(price, monthlyNishab, yearlyNishab, meta = '') {
        if (goldPriceEl && nishabValueEl) {
            goldPriceEl.textContent = `${formatRupiah(price)}/gram`;
            nishabValueEl.textContent = formatRupiah(monthlyNishab);
        }
        if (nishabYearlyValueEl) nishabYearlyValueEl.textContent = formatRupiah(yearlyNishab);
        if (goldPriceMetaEl) goldPriceMetaEl.textContent = meta;
    }

    function formatUpdateTime(value) {
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return '';
        return new Intl.DateTimeFormat('id-ID', {
            dateStyle: 'medium', timeStyle: 'short', timeZone: 'Asia/Jakarta'
        }).format(date);
    }

    updateNishabInfo(
        goldPricePerGram,
        nishabMonthly,
        nishabYearly,
        'Acuan sementara BAZNAS 2026. Sedang memuat harga emas terbaru...'
    );

    // Backend mengambil harga spot 24K dan kurs, lalu menyimpannya dalam cache privat.
    fetch('../api/index.php?resource=gold_price', {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' }
    })
        .then(response => {
            if (!response.ok) throw new Error('Endpoint harga emas tidak tersedia.');
            return response.json();
        })
        .then(result => {
            const data = result?.data || {};
            const price = Number(data.price_per_gram);
            const monthly = Number(data.nishab_monthly);
            const yearly = Number(data.nishab_yearly);
            if (!Number.isFinite(price) || price <= 0 || !Number.isFinite(monthly) || monthly <= 0 || !Number.isFinite(yearly) || yearly <= 0) {
                throw new Error('Data harga emas tidak valid.');
            }
            goldPricePerGram = Math.round(price);
            nishabMonthly = Math.round(monthly);
            nishabYearly = Math.round(yearly);

            const updated = formatUpdateTime(data.updated_at);
            let status = data.source || 'Sumber harga emas';
            if (data.is_fallback) status += ' (fallback)';
            if (data.is_stale) status += ' (cache terakhir)';
            if (updated) status += ` · Diperbarui ${updated} WIB`;
            updateNishabInfo(goldPricePerGram, nishabMonthly, nishabYearly, status);
        })
        .catch(error => {
            console.error('Gagal mengambil harga emas terbaru.', error);
            updateNishabInfo(
                goldPricePerGram,
                nishabMonthly,
                nishabYearly,
                'Menggunakan acuan nisab BAZNAS 2026 karena harga harian belum tersedia.'
            );
        });

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

            // Bonus/THR dianggap sebagai pendapatan tambahan setahun, bukan pendapatan bulanan.
            const netIncome = Math.max(0, income - debt);
            const annualNetIncome = Math.max(0, (netIncome * 12) + bonus);

            let zakatMonthly = 0;
            let zakatYearly = 0;
            let nishabStatusText = "";
            let nishabStatusColor = "";

            // Estimasi bulanan mengikuti nisab bulanan yang berlaku.
            if (netIncome >= nishabMonthly) {
                zakatMonthly = netIncome * 0.025;
            }

            // Estimasi tahunan selalu dihitung dari pendapatan bersih setahun.
            if (annualNetIncome >= nishabYearly) {
                zakatYearly = annualNetIncome * 0.025;
                nishabStatusText = "Mencapai Estimasi Nisab Tahunan";
                nishabStatusColor = "#168451";
            } else {
                nishabStatusText = "Belum Mencapai Estimasi Nisab Tahunan";
                nishabStatusColor = "#9A6B13";
            }

            // Tampilkan Hasil
            document.getElementById('totalNetIncome').innerText = formatRupiah(netIncome);
            
            const annualNetIncomeEl = document.getElementById('annualNetIncome');
            if (annualNetIncomeEl) {
                annualNetIncomeEl.innerText = formatRupiah(annualNetIncome);
            }
            
            const statusEl = document.getElementById('nishabStatus');
            statusEl.innerText = nishabStatusText;
            statusEl.style.color = nishabStatusColor;

            document.getElementById('zakatAmountMonthly').innerText = formatRupiah(zakatMonthly);
            document.getElementById('zakatAmountYearly').innerText = formatRupiah(zakatYearly);
            const calculationNoteEl = document.getElementById('zakatCalculationNote');
            if (calculationNoteEl) {
                calculationNoteEl.textContent = zakatYearly > 0
                    ? `Estimasi zakat setahun adalah 2,5% dari ${formatRupiah(annualNetIncome)}.`
                    : `Pendapatan bersih setahun belum mencapai estimasi nisab ${formatRupiah(nishabYearly)}.`;
            }
            
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
