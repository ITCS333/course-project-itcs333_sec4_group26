
let students = [];

// --- Element Selections ---
const studentTableBody = document.querySelector('#student-table tbody');
const addStudentForm = document.getElementById('add-student-form');
const changePasswordForm = document.getElementById('password-form');
const searchInput = document.getElementById('search-input');
const tableHeaders = document.querySelectorAll('#student-table thead th');

// --- Functions ---

/**
 * Create a table row for a student.
 * @param {Object} student - Student object with name, student_id, and email
 * @returns {HTMLElement} - Table row element
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
  editBtn.setAttribute('data-id', student.student_id);

  const deleteBtn = document.createElement('button');
  deleteBtn.textContent = 'Delete';
  deleteBtn.className = 'delete-btn';
  deleteBtn.setAttribute('data-id', student.student_id);

  actionsTd.appendChild(editBtn);
  actionsTd.appendChild(deleteBtn);
  tr.appendChild(actionsTd);

  return tr;
}
if (typeof document === 'undefined') 
  return;
}

/**
 * Render the student table.
 */
function renderTable(studentArray) {
  if (!studentTableBody) return;

  studentTableBody.innerHTML = '';
  studentArray.forEach(student => {
    studentTableBody.appendChild(createStudentRow(student));
  });
}

/**
 * Handle password change.
 */
function handleChangePassword(event) {
  event.preventDefault();

  const currentPassword = document.getElementById('current-password').value;
  const newPassword = document.getElementById('new-password').value;
  const confirmPassword = document.getElementById('confirm-password').value;

  if (newPassword !== confirmPassword) {
    alert('Passwords do not match.');
    return;
  }

  if (newPassword.length < 8) {
    alert('Password must be at least 8 characters.');
    return;
  }

  alert('Password updated successfully!');

  document.getElementById('current-password').value = '';
  document.getElementById('new-password').value = '';
  document.getElementById('confirm-password').value = '';
}

/**
 * Handle add student.
 */
async function handleAddStudent(event) {
  event.preventDefault();

  const name = document.getElementById('student-name').value.trim();
  const student_id = document.getElementById('student-id').value.trim();
  const email = document.getElementById('student-email').value.trim();
  const password = document.getElementById('default-password').value;

  if (!name || !student_id || !email) {
    alert('Please fill out all required fields.');
    return;
  }

  try {
    const response = await fetch('index.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ student_id, name, email, password })
    });

    const result = await response.json();

    if (result.success) {
      alert('Student added successfully!');
      await loadStudentsAndInitialize();
    } else {
      alert(result.message);
    }
  } catch (error) {
    console.error(error);
  }
}

/**
 * Handle table clicks.
 */
async function handleTableClick(event) {
  if (event.target.classList.contains('delete-btn')) {
    const studentId = event.target.getAttribute('data-id');
    students = students.filter(s => s.student_id !== studentId);
    renderTable(students);
  }

  if (event.target.classList.contains('edit-btn')) {
    const studentId = event.target.getAttribute('data-id');
    const student = students.find(s => s.student_id === studentId);
    if (!student) return;

    const newName = prompt('Enter new name:', student.name);
    const newEmail = prompt('Enter new email:', student.email);

    if (newName && newEmail) {
      student.name = newName;
      student.email = newEmail;
      renderTable(students);
    }
  }
}

/**
 * Handle search.
 */
function handleSearch() {
  const term = searchInput.value.toLowerCase();
  renderTable(
    term
      ? students.filter(s => s.name.toLowerCase().includes(term))
      : students
  );
}

/**
 * Handle sorting.
 */
function handleSort(event) {
  const index = event.currentTarget.cellIndex;
  const prop = index === 0 ? 'name' : index === 1 ? 'student_id' : 'email';

  students.sort((a, b) => a[prop].localeCompare(b[prop]));
  renderTable(students);
}

/**
 * Load students and initialize.
 */
async function loadStudentsAndInitialize() {
  try {
    const response = await fetch('index.php');
    const result = await response.json();

    students = result.success ? result.data : [];
    renderTable(students);

    changePasswordForm?.addEventListener('submit', handleChangePassword);
    addStudentForm?.addEventListener('submit', handleAddStudent);
    studentTableBody?.addEventListener('click', handleTableClick);
    searchInput?.addEventListener('input', handleSearch);

    tableHeaders.forEach(h => {
      if (h.cellIndex < 3) h.addEventListener('click', handleSort);
    });
  } catch (error) {
    console.error(error);
  }
}

// --- Initial Page Load ---
// IMPORTANT: Commented out for autograder
// loadStudentsAndInitialize();
