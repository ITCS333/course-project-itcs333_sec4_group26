let students = [];
const studentTableBody = document.querySelector('#student-table tbody');
const addStudentForm = document.getElementById('add-student-form');
const changePasswordForm = document.getElementById('password-form');
const searchInput = document.getElementById('search-input');
const tableHeaders = document.querySelectorAll('#student-table thead th');

function createStudentRow(student){
  const tr=document.createElement('tr');
  const nameTd=document.createElement('td'); nameTd.textContent=student.name; tr.appendChild(nameTd);
  const idTd=document.createElement('td'); idTd.textContent=student.student_id; tr.appendChild(idTd);
  const emailTd=document.createElement('td'); emailTd.textContent=student.email; tr.appendChild(emailTd);
  const actionsTd=document.createElement('td');
  const editBtn=document.createElement('button'); editBtn.textContent='Edit'; editBtn.className='edit-btn'; editBtn.setAttribute('data-id',student.student_id);
  const deleteBtn=document.createElement('button'); deleteBtn.textContent='Delete'; deleteBtn.className='delete-btn'; deleteBtn.setAttribute('data-id',student.student_id);
  actionsTd.appendChild(editBtn); actionsTd.appendChild(deleteBtn); tr.appendChild(actionsTd);
  return tr;
}

function renderTable(studentArray){
  studentTableBody.innerHTML='';
  studentArray.forEach(student=>studentTableBody.appendChild(createStudentRow(student)));
}

function handleChangePassword(event){ event.preventDefault(); }

async function handleAddStudent(event){ event.preventDefault(); }

async function handleTableClick(event){ }

function handleSearch(event){ }

function handleSort(event){ }

async function loadStudentsAndInitialize(){
  renderTable(students);
  if(changePasswordForm) changePasswordForm.addEventListener('submit', handleChangePassword);
  if(addStudentForm) addStudentForm.addEventListener('submit', handleAddStudent);
  if(studentTableBody) studentTableBody.addEventListener('click', handleTableClick);
  if(searchInput) searchInput.addEventListener('input', handleSearch);
  tableHeaders.forEach(th=>{ if(th.cellIndex<3) th.addEventListener('click', handleSort); });
}

loadStudentsAndInitialize();
