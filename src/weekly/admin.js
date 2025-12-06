/*
  Requirement: Make the "Manage Weekly Breakdown" page interactive.
*/

// --- Global Data Store ---
let weeks = [];

// --- Element Selections ---
const weekForm = document.querySelector("#week-form");
const weeksTableBody = document.querySelector("#weeks-tbody");

// --- Functions ---

/**
 * Create a <tr> element representing a week row
 */
function createWeekRow(week) {
  const tr = document.createElement("tr");

  tr.innerHTML = `
    <td>${week.title}</td>
    <td>${week.description}</td>
    <td>
      <button class="edit-btn" data-id="${week.id}">Edit</button>
      <button class="delete-btn" data-id="${week.id}">Delete</button>
    </td>
  `;

  return tr;
}

/**
 * Refresh table rows based on global weeks array
 */
function renderTable() {
  weeksTableBody.innerHTML = ""; // Clear table

  weeks.forEach((week) => {
    const row = createWeekRow(week);
    weeksTableBody.appendChild(row);
  });
}

/**
 * Handle adding a new week from the form
 */
function handleAddWeek(event) {
  event.preventDefault();

  const title = document.querySelector("#week-title").value.trim();
  const startDate = document.querySelector("#week-start-date").value.trim();
  const description = document
    .querySelector("#week-description")
    .value.trim();
  const linksText = document.querySelector("#week-links").value.trim();
  const links = linksText ? linksText.split("\n") : [];

  if (!title || !startDate || !description) {
    alert("Please fill in all required fields.");
    return;
  }

  const newWeek = {
    id: `week_${Date.now()}`, // Create unique ID
    title,
    startDate,
    description,
    links,
  };

  weeks.push(newWeek); // Save into memory only
  renderTable();
  weekForm.reset();
}

/**
 * Handle delete button clicks using event delegation
 */
function handleTableClick(event) {
  if (!event.target.classList.contains("delete-btn")) return;

  const idToDelete = event.target.getAttribute("data-id");
  weeks = weeks.filter((week) => week.id !== idToDelete);

  renderTable();
}

/**
 * Load weeks.json, initialize events and render UI
 */
async function loadAndInitialize() {
  try {
    const response = await fetch("weeks.json");
    if (!response.ok) throw new Error("Failed to load weeks.json");

    weeks = await response.json();
  } catch (error) {
    console.warn("Could not load weeks.json. Starting with empty list.");
    weeks = [];
  }

  renderTable();
  weekForm.addEventListener("submit", handleAddWeek);
  weeksTableBody.addEventListener("click", handleTableClick);
}

// --- Initialize App ---
loadAndInitialize();
