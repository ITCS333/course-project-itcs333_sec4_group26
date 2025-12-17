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
  actionsTd.appendChild(editBtn);

  const deleteBtn = document.createElement('button');
  deleteBtn.textContent = 'Delete';
  actionsTd.appendChild(deleteBtn);

  tr.appendChild(actionsTd);

  return tr;
}
aram {Array} studentArray

function renderTable(studentArray) {
  if (!studentTableBody) return;
  studentTableBody.innerHTML = '';

  studentArray.forEach(student => {
    const row = createStudentRow(student);
    studentTableBody.appendChild(row);
  });
}


function handleChangePassword(event) {}


function handleAddStudent(event) {}


function handleTableClick(event) {}


function handleSearch(event) {}


function handleSort(event) {}


async function loadStudentsAndInitialize() {

}

