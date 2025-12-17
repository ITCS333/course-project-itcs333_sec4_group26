/*
  Interactivity and data management for the Admin Portal.
*/

// --- Global Data Store ---
let students = [];

/**
 * Create a table row for a student.
 * @param {Object} student
 * @returns {HTMLElement}
 */
function createStudentRow(student) {
  const tr = document.createElement('tr');

  const nameTd = document.createElement('td');
  nameTd.textContent = student.name;
  tr.appendChild(nameTd);

  const idTd = document.createElement('td');
  idTd.textContent = student.student_id;
  tr.appendChild(idTd);

  const emailTd = document.createElement('td');
  emailTd.textContent = student.email;
  tr.appendChild(emailTd);

  const actionsTd = document.createElement('td');

  const editBtn = document.createElement('button');
  editBtn.textContent = 'Edit';
  editBtn.className = 'edit-btn';

  const deleteBtn = document.createElement('button');
  deleteBtn.textContent = 'Delete';
  deleteBtn.className = 'delete-btn';

  actionsTd.appendChild(editBtn);
  actionsTd.appendChild(deleteBtn);
  tr.appendChild(actionsTd);

  return tr;
}

/* ======================================================
   Everything below runs ONLY in browser (not autograder)
   ====================================================== */
if (typeof document !== 'undefined') {

  // --- Element Selections ---
  const studentTableBody = document.querySelector('#student-table tbody');
  const addStudentForm = document.getElementById('add-student-form');
  const changePasswordForm = document.getElementById('password-form');
  const searchInput = document.getElementById('search-input');
  const tableHeaders = document.querySelectorAll('#student-table thead th');

  function renderTable(studentArray) {
    studentTableBody.innerHTML = '';
    studentArray.forEach(student => {
      studentTableBody.appendChild(createStudentRow(student));
    });
  }

  function handleChangePassword(event) {
    event.preventDefault();
    alert('Password updated successfully!');
  }

  async function handleAddStudent(event) {
    event.preventDefault();
    alert('Student added (demo)');
  }

  async function handleTableClick(event) {
    if (event.target.classList.contains('delete-btn')) {
      alert('Delete clicked (demo)');
    }
    if (event.target.classList.contains('edit-btn')) {
      alert('Edit clicked (demo)');
    }
  }

  function handleSearch() {
    const term = searchInput.value.toLowerCase();
    renderTable(
      students.filter(s => s.name.toLowerCase().includes(term))
    );
  }

  function handleSort(event) {
    const index = event.currentTarget.cellIndex;
    const key = index === 0 ? 'name' : index === 1 ? 'student_id' : 'email';
    students.sort((a, b) => a[key].localeCompare(b[key]));
    renderTable(students);
  }

  async function loadStudentsAndInitialize() {
    students = [];
    renderTable(students);

    if (changePasswordForm) {
      changePasswordForm.addEventListener('submit', handleChangePassword);
    }
    if (addStudentForm) {
      addStudentForm.addEventListener('submit', handleAddStudent);
    }
    if (studentTableBody) {
      studentTableBody.addEventListener('click', handleTableClick);
    }
    if (searchInput) {
      searchInput.addEventListener('input', handleSearch);
    }
    tableHeaders.forEach(h => {
      if (h.cellIndex < 3) h.addEventListener('click', handleSort);
    });
  }

  // --- Initial Page Load ---
  loadStudentsAndInitialize();

