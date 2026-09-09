document.addEventListener('DOMContentLoaded', function() {
    // Parent container to observe
    var targetNode = document.querySelector('.checkout-index-index');
    if (!targetNode) return;

    var observer = new MutationObserver(function(mutationsList, observer) {
        var consent = document.querySelector('.kl_sms_consent-field');
        var newsletter = document.querySelector('.checkout-index-index .terms-conditions');

        if (consent && newsletter) {
            consent.insertAdjacentElement('afterend', newsletter);
            observer.disconnect();
        }
    });

    observer.observe(targetNode, { childList: true, subtree: true });
});