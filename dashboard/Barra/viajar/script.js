document.addEventListener("DOMContentLoaded", () => {
    const radioButtons = document.querySelectorAll('.radio-input');
    const checkoutPriceText = document.getElementById('checkout-price');
    const checkoutClassText = document.getElementById('checkout-class');

    function updateCheckout() {
        const selectedRadio = document.querySelector('.radio-input:checked');
        
        if (selectedRadio) {
            const parentLabel = selectedRadio.closest('.class-option');
            const currentPrice = parentLabel.querySelector('.class-price').textContent.trim();
            const currentClassName = parentLabel.querySelector('.class-name').textContent.trim();
            
            checkoutPriceText.textContent = currentPrice;
            checkoutClassText.textContent = currentClassName;
        }
    }

    radioButtons.forEach(radio => {
        radio.addEventListener('change', updateCheckout);
    });

    // Atualiza os valores assim que a página é carregada
    updateCheckout();
});