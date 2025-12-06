// --- Element Selections ---
const listSection = document.querySelector('#week-list-section');

// --- Functions ---

/**
 * Create an <article> element for a given week object.
 * @param {Object} week - {id, title, startDate, description}
 * @returns {HTMLElement} article element
 */
function createWeekArticle(week) {
    // Create elements
    const article = document.createElement('article');

    const h2 = document.createElement('h2');
    h2.textContent = week.title;

    const startDateP = document.createElement('p');
    startDateP.textContent = `Starts on: ${week.startDate}`;

    const descP = document.createElement('p');
    descP.textContent = week.description;

    const link = document.createElement('a');
    link.href = `details.html?id=${week.id}`;
    link.textContent = 'View Details & Discussion';

    // Append elements to article
    article.appendChild(h2);
    article.appendChild(startDateP);
    article.appendChild(descP);
    article.appendChild(link);

    return article;
}

/**
 * Load weeks from JSON and populate the section
 */
async function loadWeeks() {
    try {
        const response = await fetch('weeks.json');
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const weeks = await response.json();

        // Clear any existing content
        listSection.innerHTML = '';

        // Populate section with articles
        weeks.forEach(week => {
            const article = createWeekArticle(week);
            listSection.appendChild(article);
        });
    } catch (error) {
        console.error('Failed to load weeks:', error);
        listSection.innerHTML = '<p>Failed to load weekly breakdown. Please try again later.</p>';
    }
}

// --- Initial Page Load ---
loadWeeks();
