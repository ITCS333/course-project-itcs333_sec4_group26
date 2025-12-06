/*
  Requirement: Populate the weekly detail page and discussion forum.
*/

// --- Global Data Store ---
let currentWeekId = null;
let currentComments = [];

// --- Element Selections ---
const weekTitle = document.querySelector("#week-title");
const weekStartDate = document.querySelector("#week-start-date");
const weekDescription = document.querySelector("#week-description");
const weekLinksList = document.querySelector("#week-links-list");
const commentList = document.querySelector("#comment-list");
const commentForm = document.querySelector("#comment-form");
const newCommentText = document.querySelector("#new-comment-text");

// --- Functions ---

/**
 * Read week ID from URL
 */
function getWeekIdFromURL() {
  const params = new URLSearchParams(window.location.search);
  return params.get("id");
}

/**
 * Show week data in HTML elements
 */
function renderWeekDetails(week) {
  weekTitle.textContent = week.title;
  weekStartDate.textContent = "Starts on: " + week.startDate;
  weekDescription.textContent = week.description;

  // clear old links
  weekLinksList.innerHTML = "";

  // add each link
  week.links.forEach((link) => {
    const li = document.createElement("li");
    const a = document.createElement("a");
    a.href = link;
    a.textContent = link;
    a.target = "_blank";
    li.appendChild(a);
    weekLinksList.appendChild(li);
  });
}

/**
 * Create a single comment <article>
 */
function createCommentArticle(comment) {
  const art = document.createElement("article");
  art.classList.add("comment");

  const p = document.createElement("p");
  p.textContent = comment.text;

  const footer = document.createElement("footer");
  footer.textContent = `Posted by: ${comment.author}`;

  art.appendChild(p);
  art.appendChild(footer);
  return art;
}

/**
 * Display comments stored in currentComments[]
 */
function renderComments() {
  commentList.innerHTML = ""; // reset
  currentComments.forEach((comment) => {
    const art = createCommentArticle(comment);
    commentList.appendChild(art);
  });
}

/**
 * Add a new comment (in-memory only)
 */
function handleAddComment(event) {
  event.preventDefault();

  const text = newCommentText.value.trim();
  if (!text) return;

  const newComment = {
    author: "Student",
    text,
  };

  currentComments.push(newComment);
  renderComments();
  newCommentText.value = "";
}

/**
 * Load week + comments from JSON files
 */
async function initializePage() {
  currentWeekId = getWeekIdFromURL();

  if (!currentWeekId) {
    weekTitle.textContent = "Week not found.";
    return;
  }

  try {
    const [weeksRes, commentsRes] = await Promise.all([
      fetch("weeks.json"),
      fetch("comments.json"),
    ]);

    const weeks = await weeksRes.json();
    const commentsData = await commentsRes.json();

    // find this week's data
    const week = weeks.find((w) => w.id === currentWeekId);

    // load comments if exist, or empty list otherwise
    currentComments = commentsData[currentWeekId] || [];

    if (week) {
      renderWeekDetails(week);
      renderComments();
      commentForm.addEventListener("submit", handleAddComment);
    } else {
      weekTitle.textContent = "Week not found.";
    }
  } catch (err) {
    console.error("Error loading data:", err);
    weekTitle.textContent = "Error loading data.";
  }
}

// --- Initial Page Load ---
initializePage();
