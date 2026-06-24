/**
 * Sort “social-nav” terms in single listing page by priority.
 * Customization by Ori.
 */
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    // Ensure localized data is available
    if (typeof termsData === 'undefined' || typeof termsData.priorities !== 'object') {
        return;
    }

    // Target the <ul> of tags on single listing
    var ul = document.querySelector('.col-md-12.block-type-tags ul.no-list-style.outlined-list.details-list.social-nav');
    if (!ul) {
        return;
    }

    // Collect <li> items into an array
    var items = Array.prototype.slice.call(ul.querySelectorAll('li'));
    var priorities = termsData.priorities;

    // Helper to extract term slug from the <a> href
    function extractSlug(li) {
        var link = li.querySelector('a');
        if (!link) {
            return '';
        }
        var segments = link.href.split('/').filter(Boolean);
        return segments.length ? segments.pop() : '';
    }

    // Sort items by descending priority
    items.sort(function(a, b) {
        var slugA = extractSlug(a);
        var slugB = extractSlug(b);
        var prA = parseInt(priorities[slugA], 10) || 0;
        var prB = parseInt(priorities[slugB], 10) || 0;
        return prB - prA;
    });

    // Clear and re-append in sorted order
    ul.innerHTML = '';
    items.forEach(function(li) {
        ul.appendChild(li);
    });
});
