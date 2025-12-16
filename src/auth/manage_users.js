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

