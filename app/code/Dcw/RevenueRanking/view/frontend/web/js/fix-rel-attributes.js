/**
 * Copyright © Dotcom Weavers. All rights reserved.
 * 
 * Fix for "Anchor rel value: undefined" issue in Mirasvit layered navigation
 */

define([], function () {
    'use strict';

    return function () {
        // Function to fix rel attributes that have "undefined" values
        function fixRelAttributes() {
            // Find all anchor tags with rel="undefined"
            const anchorsWithUndefinedRel = document.querySelectorAll('a[rel="undefined"]');
            
            anchorsWithUndefinedRel.forEach(function(anchor) {
                // Remove the rel attribute if it's undefined
                anchor.removeAttribute('rel');
                console.log('Fixed undefined rel attribute on anchor:', anchor.href);
            });
            
            // Also check for any other problematic rel values
            const allAnchors = document.querySelectorAll('a[rel]');
            allAnchors.forEach(function(anchor) {
                const relValue = anchor.getAttribute('rel');
                if (relValue === 'undefined' || relValue === 'null' || relValue === '') {
                    anchor.removeAttribute('rel');
                    console.log('Fixed problematic rel attribute on anchor:', anchor.href);
                }
            });
        }
        
        // Run the fix when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fixRelAttributes);
        } else {
            fixRelAttributes();
        }
        
        // Also run the fix after AJAX updates (for Mirasvit layered navigation)
        document.addEventListener('contentUpdated', function() {
            setTimeout(fixRelAttributes, 100);
        });
        
        // Run periodically to catch any dynamically added elements
        setInterval(fixRelAttributes, 5000);
    };
});
