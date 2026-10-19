document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('zakatForm');
    const typeSelect = document.getElementById('zakatType');
    const companyTypeSelect = document.getElementById('companyType');
    const companyTypeGroup = document.getElementById('companyTypeGroup');
    const fieldsContainer = document.getElementById('zakatFields');
    const result = document.getElementById('zakatResult');
    const resetButton = document.getElementById('resetBtn');

    if (!form || !typeSelect || !companyTypeSelect || !companyTypeGroup || !fieldsContainer || !result) return;

    const INCOME_NISAB_YEARLY = 91681728;
    const INCOME_NISAB_MONTHLY = 7640144;
    const GOLD_NISAB_GRAMS = 85;
    const ZAKAT_RATE = 0.025;

    let goldPricePerGram = Math.round(INCOME_NISAB_YEARLY / GOLD_NISAB_GRAMS);
    let goldPriceSource = 'Acuan sementara BAZNAS 2026';

    const goldPriceElement = document.getElementById('goldPrice');
    const goldPriceMetaElement = document.getElementById('goldPriceMeta');
    const guidanceLabel = document.getElementById('zakatGuidanceLabel');
    const guidance = document.getElementById('zakatGuidance');
    const formTitle = document.getElementById('zakatFormTitle');
    const formDescription = document.getElementById('zakatFormDescription');
    const resultTitle = document.getElementById('zakatResultTitle');
    const resultDetails = document.getElementById('zakatResultDetails');
    const amountLabel = document.getElementById('zakatAmountLabel');
    const amount = document.getElementById('zakatAmount');
    const status = document.getElementById('nishabStatus');
    const calculationNote = document.getElementById('zakatCalculationNote');

    const types = {
        income: {
            label: 'Zakat Penghasilan',
            description: 'Masukkan gaji dan penghasilan halal lainnya yang diterima setiap bulan.',
            guidance: 'Dihitung dari gaji dan penghasilan halal lainnya. Zakat sebesar 2,5% ditunaikan apabila penghasilan mencapai nisab bulanan atau tahunan.',
            fields: [
                { id: 'monthlySalary', label: 'Gaji per bulan', hint: 'Penghasilan tetap dari pekerjaan utama.' },
                { id: 'monthlyOtherIncome', label: 'Penghasilan lain-lain per bulan', hint: 'Honorarium, tunjangan, atau pendapatan sampingan yang halal.' }
            ]
        },
        company: {
            label: 'Zakat Perusahaan',
            description: 'Pilih model usaha agar dasar perhitungan sesuai dengan kegiatan perusahaan.',
            guidance: 'Perusahaan jasa dihitung dari pendapatan sebelum pajak. Perusahaan dagang atau industri dihitung dari aktiva lancar dikurangi pasiva lancar.',
            fields: []
        },
        trade: {
            label: 'Zakat Perdagangan',
            description: 'Masukkan aset lancar dan laba perdagangan yang telah dimiliki selama satu haul.',
            guidance: 'Dasar zakat perdagangan adalah aset lancar ditambah laba. Jika mencapai nisab tahunan, zakatnya sebesar 2,5%.',
            fields: [
                { id: 'tradeCurrentAssets', label: 'Aset lancar', hint: 'Nilai kas, persediaan, piutang lancar, dan aset usaha yang mudah dicairkan.' },
                { id: 'tradeProfit', label: 'Laba', hint: 'Keuntungan bersih dari aktivitas perdagangan.' }
            ]
        },
        gold: {
            label: 'Zakat Emas',
            description: 'Masukkan jumlah emas yang dimiliki selama satu haul dan harga emas per gram.',
            guidance: 'Emas yang telah dimiliki selama satu haul wajib dizakati 2,5% apabila mencapai nisab 85 gram.',
            fields: [
                { id: 'goldWeight', label: 'Jumlah emas yang dimiliki', hint: 'Total emas yang telah dimiliki selama satu haul.', kind: 'number', suffix: 'gram' },
                { id: 'goldPriceInput', label: 'Harga emas per gram', hint: 'Harga sistem terisi otomatis dan tetap dapat disesuaikan.', autoGoldPrice: true }
            ]
        }
    };

    const companyFields = {
        service: [
            { id: 'companyPretaxIncome', label: 'Pendapatan sebelum pajak', hint: 'Total pendapatan perusahaan jasa sebelum pajak dalam satu tahun.' }
        ],
        industry: [
            { id: 'companyCurrentAssets', label: 'Aktiva lancar', hint: 'Kas, piutang, persediaan, dan aset lancar lainnya.' },
            { id: 'companyCurrentLiabilities', label: 'Pasiva lancar', hint: 'Utang jangka pendek dan kewajiban yang segera dibayar.' }
        ]
    };

    function formatRupiah(value) {
        return `Rp ${Math.max(0, Math.round(Number(value) || 0)).toLocaleString('id-ID')}`;
    }

    function formatRupiahInput(value) {
        const digits = String(value ?? '').replace(/\D/g, '');
        return digits ? `Rp ${Number(digits).toLocaleString('id-ID')}` : '';
    }

    function parseCurrency(value) {
        return Number(String(value ?? '').replace(/\D/g, '')) || 0;
    }

    function escapeHtml(value) {
        return String(value).replace(/[&<>'"]/g, character => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
        })[character]);
    }

    function fieldTemplate(field) {
        if (field.kind === 'number') {
            return `
                <div class="input-group">
                    <label for="${field.id}">${field.label}</label>
                    <div class="input-wrapper input-wrapper--suffix">
                        <input type="number" id="${field.id}" class="form-input" min="0" step="0.01" placeholder="0" inputmode="decimal" required>
                        <span>${field.suffix}</span>
                    </div>
                    <small class="calculator-field-hint">${field.hint}</small>
                </div>`;
        }

        const value = field.autoGoldPrice ? formatRupiahInput(goldPricePerGram) : '';
        const autoAttribute = field.autoGoldPrice ? ' data-auto-gold-price="true"' : '';
        return `
            <div class="input-group">
                <label for="${field.id}">${field.label}</label>
                <div class="input-wrapper">
                    <input type="text" id="${field.id}" class="form-input currency-input" value="${escapeHtml(value)}" placeholder="Rp 0" inputmode="numeric" autocomplete="off" required${autoAttribute}>
                </div>
                <small class="calculator-field-hint">${field.hint}</small>
            </div>`;
    }

    function bindCurrencyInputs() {
        fieldsContainer.querySelectorAll('.currency-input').forEach(input => {
            input.addEventListener('input', () => {
                input.value = formatRupiahInput(input.value);
                if (input.dataset.autoGoldPrice) input.dataset.userEdited = 'true';
            });
        });
    }

    function renderFields() {
        const selectedType = typeSelect.value;
        const config = types[selectedType];
        const isCompany = selectedType === 'company';
        companyTypeGroup.hidden = !isCompany;

        const fields = isCompany ? companyFields[companyTypeSelect.value] : config.fields;
        fieldsContainer.innerHTML = fields.map(fieldTemplate).join('');
        bindCurrencyInputs();

        formTitle.textContent = config.label;
        formDescription.textContent = config.description;
        guidanceLabel.textContent = config.label;
        guidance.textContent = config.guidance;
        hideResult();
    }

    function formatUpdateTime(value) {
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return '';
        return new Intl.DateTimeFormat('id-ID', {
            dateStyle: 'medium',
            timeStyle: 'short',
            timeZone: 'Asia/Jakarta'
        }).format(date);
    }

    function updateGoldInformation() {
        if (goldPriceElement) goldPriceElement.textContent = `${formatRupiah(goldPricePerGram)}/gram`;
        if (goldPriceMetaElement) goldPriceMetaElement.textContent = goldPriceSource;

        const input = document.getElementById('goldPriceInput');
        if (input && input.dataset.userEdited !== 'true') input.value = formatRupiahInput(goldPricePerGram);
    }

    function loadGoldPrice() {
        updateGoldInformation();
        fetch('../api/index.php?resource=gold_price', {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' }
        })
            .then(response => {
                if (!response.ok) throw new Error('Endpoint harga emas tidak tersedia.');
                return response.json();
            })
            .then(payload => {
                const data = payload?.data || {};
                const price = Number(data.price_per_gram);
                if (!Number.isFinite(price) || price <= 0) throw new Error('Data harga emas tidak valid.');

                goldPricePerGram = Math.round(price);
                const updatedAt = formatUpdateTime(data.updated_at);
                goldPriceSource = data.source || 'Sumber harga emas sistem';
                if (data.is_fallback) goldPriceSource += ' (fallback)';
                if (data.is_stale) goldPriceSource += ' (cache terakhir)';
                if (updatedAt) goldPriceSource += ` · Diperbarui ${updatedAt} WIB`;
                updateGoldInformation();
            })
            .catch(error => {
                console.error('Gagal mengambil harga emas terbaru.', error);
                goldPriceSource = 'Harga emas terbaru belum tersedia; sistem memakai acuan sementara BAZNAS 2026.';
                updateGoldInformation();
            });
    }

    function readCurrency(id) {
        return parseCurrency(document.getElementById(id)?.value);
    }

    function readNumber(id) {
        return Math.max(0, Number(document.getElementById(id)?.value) || 0);
    }

    function detailRow(label, value) {
        return `<div class="result-row"><span>${escapeHtml(label)}</span><strong>${escapeHtml(value)}</strong></div>`;
    }

    function calculateIncome() {
        const salary = readCurrency('monthlySalary');
        const otherIncome = readCurrency('monthlyOtherIncome');
        const monthlyTotal = salary + otherIncome;
        const annualTotal = monthlyTotal * 12;
        const reachesNisab = monthlyTotal >= INCOME_NISAB_MONTHLY || annualTotal >= INCOME_NISAB_YEARLY;
        const monthlyZakat = reachesNisab ? monthlyTotal * ZAKAT_RATE : 0;

        return {
            title: 'Zakat Penghasilan',
            amountLabel: 'Zakat per bulan',
            amount: monthlyZakat,
            details: [
                ['Jumlah penghasilan per bulan', formatRupiah(monthlyTotal)],
                ['Estimasi penghasilan per tahun', formatRupiah(annualTotal)],
                ['Estimasi zakat per tahun', formatRupiah(monthlyZakat * 12)]
            ],
            status: reachesNisab ? 'Penghasilan telah mencapai nisab.' : 'Penghasilan belum mencapai nisab.',
            statusType: reachesNisab ? 'success' : 'pending',
            note: reachesNisab
                ? `Perhitungan: 2,5% × ${formatRupiah(monthlyTotal)}.`
                : `Nisab BAZNAS 2026 adalah ${formatRupiah(INCOME_NISAB_MONTHLY)} per bulan atau ${formatRupiah(INCOME_NISAB_YEARLY)} per tahun.`
        };
    }

    function calculateCompany() {
        const isService = companyTypeSelect.value === 'service';
        const currentAssets = isService ? 0 : readCurrency('companyCurrentAssets');
        const currentLiabilities = isService ? 0 : readCurrency('companyCurrentLiabilities');
        const base = isService
            ? readCurrency('companyPretaxIncome')
            : Math.max(0, currentAssets - currentLiabilities);

        return {
            title: isService ? 'Zakat Perusahaan Jasa' : 'Zakat Perusahaan Dagang / Industri',
            amountLabel: 'Estimasi zakat perusahaan',
            amount: base * ZAKAT_RATE,
            details: isService
                ? [['Pendapatan sebelum pajak', formatRupiah(base)]]
                : [
                    ['Aktiva lancar', formatRupiah(currentAssets)],
                    ['Pasiva lancar', formatRupiah(currentLiabilities)],
                    ['Dasar perhitungan', formatRupiah(base)]
                ],
            status: 'Estimasi dihitung dengan kadar 2,5%.',
            statusType: 'success',
            note: isService
                ? `Perhitungan: 2,5% × pendapatan sebelum pajak ${formatRupiah(base)}.`
                : `Perhitungan: 2,5% × (aktiva lancar − pasiva lancar) ${formatRupiah(base)}.`
        };
    }

    function calculateTrade() {
        const currentAssets = readCurrency('tradeCurrentAssets');
        const profit = readCurrency('tradeProfit');
        const base = currentAssets + profit;
        const reachesNisab = base >= INCOME_NISAB_YEARLY;

        return {
            title: 'Zakat Perdagangan',
            amountLabel: 'Estimasi zakat perdagangan',
            amount: reachesNisab ? base * ZAKAT_RATE : 0,
            details: [
                ['Aset lancar', formatRupiah(currentAssets)],
                ['Laba', formatRupiah(profit)],
                ['Jumlah harta perdagangan', formatRupiah(base)]
            ],
            status: reachesNisab ? 'Harta perdagangan telah mencapai nisab.' : 'Harta perdagangan belum mencapai nisab.',
            statusType: reachesNisab ? 'success' : 'pending',
            note: reachesNisab
                ? `Perhitungan: 2,5% × ${formatRupiah(base)}.`
                : `Nisab tahunan yang digunakan adalah ${formatRupiah(INCOME_NISAB_YEARLY)}.`
        };
    }

    function calculateGold() {
        const weight = readNumber('goldWeight');
        const price = readCurrency('goldPriceInput');
        const goldValue = weight * price;
        const reachesNisab = weight >= GOLD_NISAB_GRAMS;

        return {
            title: 'Zakat Emas',
            amountLabel: 'Estimasi zakat emas',
            amount: reachesNisab ? goldValue * ZAKAT_RATE : 0,
            details: [
                ['Jumlah emas', `${weight.toLocaleString('id-ID', { maximumFractionDigits: 2 })} gram`],
                ['Harga emas per gram', formatRupiah(price)],
                ['Nilai emas', formatRupiah(goldValue)]
            ],
            status: reachesNisab ? 'Emas telah mencapai nisab 85 gram.' : 'Emas belum mencapai nisab 85 gram.',
            statusType: reachesNisab ? 'success' : 'pending',
            note: reachesNisab
                ? `Perhitungan: ${weight.toLocaleString('id-ID')} gram × ${formatRupiah(price)} × 2,5%.`
                : 'Zakat emas diperhitungkan setelah kepemilikan mencapai 85 gram dan telah memenuhi satu haul.'
        };
    }

    function showResult(calculation) {
        resultTitle.textContent = calculation.title;
        amountLabel.textContent = calculation.amountLabel;
        amount.textContent = formatRupiah(calculation.amount);
        resultDetails.innerHTML = calculation.details.map(([label, value]) => detailRow(label, value)).join('');
        status.textContent = calculation.status;
        status.dataset.status = calculation.statusType;
        calculationNote.textContent = calculation.note;
        result.classList.add('show');
        result.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function hideResult() {
        result.classList.remove('show');
        status.removeAttribute('data-status');
    }

    typeSelect.addEventListener('change', renderFields);
    companyTypeSelect.addEventListener('change', renderFields);

    form.addEventListener('submit', event => {
        event.preventDefault();
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const calculators = {
            income: calculateIncome,
            company: calculateCompany,
            trade: calculateTrade,
            gold: calculateGold
        };
        showResult(calculators[typeSelect.value]());
    });

    resetButton?.addEventListener('click', () => {
        form.reset();
        typeSelect.value = 'income';
        companyTypeSelect.value = 'service';
        renderFields();
    });

    renderFields();
    loadGoldPrice();
});
