

// document.addEventListener('DOMContentLoaded', function() {
//     const liElements = $('ul.no-list-style.outlined-list.details-list.social-nav li');

//     // Retrieve priorities from localized termsData
//     const termsPriorities = termsData.priorities;

//     // Call reOrderTerms function
//     const sortedLis = reOrderTerms(liElements.toArray(), termsPriorities);

//     // Clear and append the sorted <li> elements back to the <ul>
//     $('ul.no-list-style.outlined-list.details-list.social-nav').empty().append(sortedLis);


// });


// function reOrderTerms(li_elements, terms_priorities) {
//     const sortedLis = li_elements.sort((a, b) => {
//         const slugA = $(a).data('slug'); // Ensure each <li> has a data-slug attribute
//         const slugB = $(b).data('slug');
//         return (terms_priorities[slugB] || 0) - (terms_priorities[slugA] || 0);
//     });
//     return sortedLis;
// }

// document.addEventListener('DOMContentLoaded', () => {

//     // Ensure termsData is available
//     if (typeof termsData === 'undefined') return;

//     const liElements = document.getElementsByClassName('ul.no-list-style.outlined-list.details-list.social-nav li');
//     const termsPriorities = termsData.priorities;
//     console.log(termsPriorities);
//     console.log(liElements);


//     // Reorder and update the list
//     const sortedLis = reOrderTerms(liElements.toArray(), termsPriorities);

//     console.log(sortedLis);

//     const ul = document.querySelector('ul.no-list-style.outlined-list.details-list.social-nav');

//     ul.html(sortedLis);

// });

// function reOrderTerms(li_elements, terms_priorities) {
//     // Sort the li elements based on the priority (High to Low)
//     const sortedLis = li_elements.sort((a, b) => {
//         // Extract slug from class or another attribute
//         const slugA = a.className.split(' ')[0]; // Assuming the slug is the first class
//         const slugB = b.className.split(' ')[0];
//         return (parseInt(terms_priorities[slugB]) || 0) - (parseInt(terms_priorities[slugA]) || 0);
//     });
//     return sortedLis;
// }

document.addEventListener('DOMContentLoaded', () => {

    // Ensure termsData is available
    if (typeof termsData === 'undefined') return;

    const liElements = document.querySelectorAll('.col-md-12.block-type-tags ul.no-list-style.outlined-list.details-list.social-nav li');
    const termsPriorities = termsData.priorities;
    // Reorder and update the list
    const sortedLis = reOrderTerms(Array.from(liElements), termsPriorities);


    const ul = document.querySelector('.col-md-12.block-type-tags ul.no-list-style.outlined-list.details-list.social-nav');

    // Clear existing list items
    ul.innerHTML = '';

    // Append sorted list items
    sortedLis.forEach(li => ul.appendChild(li));

});

function reOrderTerms(li_elements, terms_priorities) {
    // Sort the li elements based on the priority (High to Low)
    const sortedLis = li_elements.sort((a, b) => {
        // Extract slug from class or another attribute
        const slugA = a.querySelector('a').href.split('/').filter(Boolean).pop();
        const slugB = b.querySelector('a').href.split('/').filter(Boolean).pop();
        return (parseInt(terms_priorities[slugB]) || 0) - (parseInt(terms_priorities[slugA]) || 0);
    });
    return sortedLis;
}