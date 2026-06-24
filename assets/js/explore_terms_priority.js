function DisplaySortedElement(lis, ul_element, NUMBER_OF_TERMS_TO_SHOW ) {

    lis.forEach((li, index) => {
        li.classList.add('fade-transition'); // add fade class for each item
        if (index >= NUMBER_OF_TERMS_TO_SHOW) {
            li.classList.add('hidden-tag');
            li.style.opacity = '0';
        }
        ul_element.appendChild(li);
    });

    const arrowUp = `
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="16" viewBox="0 0 16 16" style='position:relative;top:5px;'>
            <polygon points="8,0 0,8 16,8 8,0" fill="#209C62" stroke="#209C62" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    `;

    const arrowDown = `
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="16" viewBox="0 0 16 16">
            <polygon points="8,16 0,8 16,8 8,16" fill="#209C62" stroke="#209C62" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    `;


    let isExpanded = false; // Variable to track whether the list is expanded
    const showMoreButton = document.createElement('li');
    showMoreButton.innerHTML = 'הצג הכל &nbsp;' + arrowDown; // Right arrow initially
    showMoreButton.className = 'show-more-button';
    showMoreButton.addEventListener('click', () => {
        
        // Toggle the state of the expansion
        isExpanded = !isExpanded;
    
        // Toggle the display and opacity of the extra tags
        lis.slice(NUMBER_OF_TERMS_TO_SHOW).forEach(tag => {
            tag.style.opacity = isExpanded ? '1' : '0';
            setTimeout(() => {
                tag.style.display = isExpanded ? 'list-item' : 'none';
            }, isExpanded ? 1 : 300);
        });
    
        // Update the button text
        showMoreButton.innerHTML = isExpanded ? 'הצג פחות &nbsp;' + arrowUp : 'הצג הכל &nbsp;' + arrowDown;
    });

    ul_element.appendChild(showMoreButton);

}

function reOrderTerms( li_elements, terms_priorities ) {
    // Sort the li elements based on the priority from the localized data (High Priority to Low Priority)
    // Time Complexity -- nlogn
    const sortedLis = li_elements.sort((a, b) => {
        const slugA = a.querySelector('input[type="checkbox"]').value;
        const slugB = b.querySelector('input[type="checkbox"]').value;
        return parseInt(terms_priorities[slugB]) - parseInt(terms_priorities[slugA]);
    });
    return sortedLis;
}

document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM is loaded');

    const explore_sidebar = document.getElementById( 'finderSearch' );

    console.log(explore_sidebar);
    
    if( explore_sidebar ) {

        /**
         * Here we handle tags and roads tax
         */

        const checkboxes_filters = explore_sidebar.querySelectorAll( 'div.checkboxes-filter' ); // Retrive all checkbox-filters
        // Extract tags and roads from the checkboxes filters
        const roads_and_tags = Array.from(checkboxes_filters).filter( tax => {
            const labelContent = tax.querySelector('label').textContent;
            tax['label'] = labelContent; // attach the label to the tax
            return labelContent === "העדפות" || labelContent === "קרוב לכביש";
        });

        if( roads_and_tags.length !== 0 ) {

            roads_and_tags.forEach(tax => {

                const ul_element = tax.querySelector('ul');
                const NUMBER_OF_TERMS_TO_SHOW = 15;
                const lis = Array.from(ul_element.querySelectorAll('li'));
                
                /**
                 * Sort the terms for each tax
                 */
                if (tax.label === "העדפות") { // tags terms   

                    const sortedLis = reOrderTerms( lis, explore_terms.tags );
                    DisplaySortedElement( sortedLis, ul_element, NUMBER_OF_TERMS_TO_SHOW);

                }
                else if (tax.label === "קרוב לכביש") { // roads terms

                    const sortedLis = reOrderTerms( lis, explore_terms.roads );
                    DisplaySortedElement( sortedLis, ul_element, NUMBER_OF_TERMS_TO_SHOW);

                }

            });

        }

    }

});