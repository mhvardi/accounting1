document.addEventListener('DOMContentLoaded', function () {
    const coopSelect   = document.getElementById('cooperation_type');
    const modelSelect  = document.getElementById('commission_model');
    const basisSelect  = document.getElementById('commission_basis');

    const commissionBlock        = document.getElementById('commission-block');
    const percentBlock           = document.querySelector('.commission-percent-block');
    const categoriesBlock        = document.getElementById('commission-categories-block');
    const tiersBlock             = document.getElementById('commission-tiers-block');
    const tierRowsContainer      = document.getElementById('tier-rows');
    const addTierRowBtn          = document.getElementById('add-tier-row');

    function updateVisibility() {
        const coop   = coopSelect ? coopSelect.value : 'fixed';
        const model  = modelSelect ? modelSelect.value : 'none';
        const basis  = basisSelect ? basisSelect.value : 'contract_received';

        // نمایش بلوک پورسانت فقط اگر پورسانتی یا ترکیبی
        if (coop === 'commission' || coop === 'mixed') {
            if (commissionBlock) commissionBlock.style.display = '';
        } else {
            if (commissionBlock) commissionBlock.style.display = 'none';
            return;
        }

        // مدل پورسانت
        if (percentBlock) {
            percentBlock.style.display = (model === 'percent') ? '' : 'none';
        }

        if (tiersBlock) {
            tiersBlock.style.display = (model === 'tiered') ? 'block' : 'none';
        }

        if (categoriesBlock) {
            categoriesBlock.style.display = (basis === 'categories') ? 'block' : 'none';
        }
    }

    if (coopSelect)  coopSelect.addEventListener('change', updateVisibility);
    if (modelSelect) modelSelect.addEventListener('change', updateVisibility);
    if (basisSelect) basisSelect.addEventListener('change', updateVisibility);

    // دکمه افزودن پلکان
    if (addTierRowBtn && tierRowsContainer) {
        addTierRowBtn.addEventListener('click', function () {
            const div = document.createElement('div');
            div.className = 'grid tier-row';
            div.style.cssText = 'grid-template-columns:repeat(3,minmax(0,1fr));gap:6px;margin-bottom:4px;';
            div.innerHTML = `
                <input type="text" name="tier_min[]" class="form-input money-input" placeholder="حداقل فروش (تومان)">
                <input type="text" name="tier_max[]" class="form-input money-input" placeholder="حداکثر فروش (تومان)">
                <input type="text" name="tier_percent[]" class="form-input" placeholder="درصد پورسانت">
            `;
            tierRowsContainer.appendChild(div);
        });
    }

    // فرمت سه‌رقمی مبلغ‌ها (تومان)
    document.querySelectorAll('.money-input').forEach(function (input) {
        input.addEventListener('input', function () {
            let val = input.value.replace(/[^\d]/g, '');
            if (!val) {
                input.value = '';
                return;
            }
            const parts = [];
            while (val.length > 3) {
                parts.unshift(val.slice(-3));
                val = val.slice(0, -3);
            }
            parts.unshift(val);
            input.value = parts.join(',');
        });
    });

    updateVisibility();
});