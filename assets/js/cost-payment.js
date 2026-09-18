document.addEventListener("DOMContentLoaded", function () {
    const finalCostInput = document.getElementById("final_cost");
    const paidAmountInput = document.getElementById("paid_amount");
    const balanceDisplay = document.getElementById("balanceDisplay");

    if (!finalCostInput || !paidAmountInput || !balanceDisplay) return;

    function updateBalance() {
        const finalCost = parseFloat(finalCostInput.value) || 0;
        const paidAmount = parseFloat(paidAmountInput.value) || 0;
        const balance = finalCost - paidAmount;
        balanceDisplay.textContent = balance.toFixed(2);
        balanceDisplay.style.color = balance < 0 ? "red" : "";
    }

    finalCostInput.addEventListener("input", updateBalance);
    paidAmountInput.addEventListener("input", updateBalance);
});