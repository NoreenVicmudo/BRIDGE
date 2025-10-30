document.addEventListener("DOMContentLoaded", function() {
    function updateApprovalBadge() {
        fetch("/bridge/core/get_pending_count.php")
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const count = data.display;

                // Main badge
                const accountBadge = document.getElementById("accountBadge");
                if (accountBadge) {
                    accountBadge.textContent = count > 0 ? count : "";
                }

                // Approval submenu badge
                const approvalBadge = document.getElementById("approvalBadge");
                if (approvalBadge) {
                    approvalBadge.textContent = count > 0 ? count : "";
                }

                // Optional: separate badge in submenu
                const approvalBadgeMenu = document.getElementById("approvalBadgeMenu");
                if (approvalBadgeMenu) {
                    approvalBadgeMenu.textContent = count > 0 ? count : "";
                }

                // 🔄 Sync: also reload table
                if (typeof loadApprovalRequests === "function") {
                    loadApprovalRequests();
                }
            }
        })
        .catch(err => console.error("Error fetching badge count:", err));
    }

    updateApprovalBadge();

    // 🔄 Auto refresh every 3s (optional)
    setInterval(updateApprovalBadge, 3000);
});