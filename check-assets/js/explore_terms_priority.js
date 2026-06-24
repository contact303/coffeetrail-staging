/**
 * Explore Terms Priority Sorting and "Show More" functionality.
 * Customization by Your Name.
 */
(function() {
    'use strict';

    /**
     * Display a limited number of items, hide the rest,
     * and add a "Show More"/"Show Less" control.
     *
     * @param {HTMLElement[]} listItems Array of <li> elements.
     * @param {HTMLElement} container    The <ul> container.
     * @param {number} maxVisible        How many items to show initially.
     */
    function displaySortedElement(listItems, container, maxVisible) {
        listItems.forEach(function(li, index) {
            li.classList.add('fade-transition');
            if (index >= maxVisible) {
                li.classList.add('hidden-tag');
                li.style.display = 'none';
                li.style.opacity = '0';
            }
            container.appendChild(li);
        });

        var arrowUp = 
            '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="16" viewBox="0 0 16 16" ' +
            'style="position:relative;top:5px;">' +
            '<polygon points="8,0 0,8 16,8 8,0" fill="#209C62" stroke="#209C62" ' +
            'stroke-linecap="round" stroke-linejoin="round"/>' +
            '</svg>';

        var arrowDown = 
            '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="16" viewBox="0 0 16 16">' +
            '<polygon points="8,16 0,8 16,8 8,16" fill="#209C62" stroke="#209C62" ' +
            'stroke-linecap="round" stroke-linejoin="round"/>' +
            '</svg>';

        var isExpanded = false;
        var toggleItem = document.createElement('li');
        toggleItem.className = 'show-more-button';
        toggleItem.innerHTML = 'הצג הכל ' + arrowDown;

        toggleItem.addEventListener('click', function() {
            isExpanded = !isExpanded;
            listItems.slice(maxVisible).forEach(function(item) {
                item.style.opacity = isExpanded ? '1' : '0';
                setTimeout(function() {
                    item.style.display = isExpanded ? 'list-item' : 'none';
                }, isExpanded ? 0 : 300);
            });
            toggleItem.innerHTML = isExpanded
                ? 'הצג פחות ' + arrowUp
                : 'הצג הכל ' + arrowDown;
        });

        container.appendChild(toggleItem);
    }

    /**
     * Sort <li> elements based on a priority map.
     *
     * @param {HTMLElement[]} listI*
