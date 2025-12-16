let students = [];
const studentTableBody = document.querySelector('#student-table tbody');
const addStudentForm = document.getElementById('add-student-form');
const changePasswordForm = document.getElementById('password-form');
const searchInput = document.getElementById('search-input');
const tableHeaders = document.querySelectorAll('#student-table thead th');

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

function renderTable(studentArray) {
  studentTableBody.innerHTML = '';
  studentArray.forEach(student => studentTableBody.appendChild(createStudentRow(student)));
}

function handleChangePassword(event) {
  event.preventDefault();
  const currentPassword = document.getElementById('current-password').value;
  const newPassword = document.getElementById('new-password').value;
  const confirmPassword = document.getElementById('confirm-password').value;
  if (newPassword !== confirmPassword) return;
  if (newPassword.length < 8) return;
  document.getElementById('current-password').value = '';
  document.getElementById('new-password').value = '';
  document.getElementById('confirm-password').value = '';
}

async function handleAddStudent(event) {
  event.preventDefault();
  const name = document.getElementById('student-name').value.trim();
  const student_id = document.getElementById('student-id').value.trim();
  const email = document.getElementById('student-email').value.trim();
  const password = document.getElementById('default-password').value;
  if (!name || !student_id || !email) return;
  students.push({ name, student_id, email });
  document.getElementById('student-name').value = '';
  document.getElementById('student-id').value = '';
  document.getElementById('student-email').value = '';
  document.getElementById('default-password').value = 'password123';
  renderTable(students);
}

function handleTableClick(event) {
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
    if (newName !== null) student.name = newName;
    const newEmail = prompt('Enter new email:', student.email);
    if (newEmail !== null) student.email = newEmail;
    renderTable(students);
  }
}

function handleSearch(event) {
  const searchTerm = searchInput.value.toLowerCase();
  const filtered = students.filter(s => s.name.toLowerCase().includes(searchTerm));
  renderTable(filtered);
}

function handleSort(event) {
  const index = event.currentTarget.cellIndex;
  const th = event.currentTarget;
  let dir = th.getAttribute('data-sort-dir') || 'asc';
  dir = dir === 'asc' ? 'desc' : 'asc';
  th.setAttribute('data-sort-dir', dir);
  let prop = index === 0 ? 'name' : index === 1 ? 'student_id' : 'email';
  students.sort((a, b) => {
    let cmp = a[prop].localeCompare(b[prop], undefined, { numeric: prop === 'student_id' });
    return dir === 'asc' ? cmp : -cmp;
  });
  renderTable(students);
}

function loadStudentsAndInitialize() {
  renderTable(students);
  if (changePasswordForm) changePasswordForm.addEventListener('submit', handleChangePassword);
  if (addStudentForm) addStudentForm.addEventListener('submit', handleAddStudent);
  if (studentTableBody) studentTableBody.addEventListener('click', handleTableClick);
  if (searchInput) searchInput.addEventListener('input', handleSearch);
  tableHeaders.forEach(th => {
    if (th.cellIndex < 3) {
      th.addEventListener('click', handleSort);
    }
  });
}

loadStudentsAndInitialize();
