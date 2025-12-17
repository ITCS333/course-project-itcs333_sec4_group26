/*
  Interactivity and data management for the Admin Portal.

  Instructions:
  1. Link this file to your HTML using a <script> tag with the 'defer' attribute.
     Example: <script src="manage_users.js" defer></script>
  2. All data management is done by manipulating the 'students' array
     and re-rendering the table.
*/

// --- Global Data Store ---
// This array will be populated with data fetched from 'students.json'.
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

/**
 * Render the student table with the provided array.
 * @param {Array} studentArray - Array of student objects
 */
function renderTable(studentArray) {
  studentTableBody.innerHTML = '';
  
  studentArray.forEach(student => {
    const row = createStudentRow(student);
    studentTableBody.appendChild(row);
  });
}

/**
 * Handle password change form submission.
 * @param {Event} event - Form submit event
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
 * Handle add student form submission.
 * @param {Event} event - Form submit event
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
      document.getElementById('student-name').value = '';
      document.getElementById('student-id').value = '';
      document.getElementById('student-email').value = '';
      document.getElementById('default-password').value = 'password123';
      
      // Reload students from database
      await loadStudentsAndInitialize();
    } else {
      alert('Error: ' + result.message);
    }
  } catch (error) {
    console.error('Error adding student:', error);
    alert('Failed to add student. Please try again.');
  }
}

/**
 * Handle clicks on table buttons (edit/delete).
 * @param {Event} event - Click event
 */
async function handleTableClick(event) {
  if (event.target.classList.contains('delete-btn')) {
    const studentId = event.target.getAttribute('data-id');
    
    if (confirm('Are you sure you want to delete this student?')) {
      try {
        const response = await fetch(`index.php?student_id=${studentId}`, {
          method: 'DELETE'
        });
        
        const result = await response.json();
        
        if (result.success) {
          alert('Student deleted successfully!');
          await loadStudentsAndInitialize();
        } else {
          alert('Error: ' + result.message);
        }
      } catch (error) {
        console.error('Error deleting student:', error);
        alert('Failed to delete student. Please try again.');
      }
    }
  }
  
  if (event.target.classList.contains('edit-btn')) {
    const studentId = event.target.getAttribute('data-id');
    const student = students.find(s => s.student_id === studentId);
    
    if (student) {
      const newName = prompt('Enter new name:', student.name);
      if (newName === null) return;
      
      const newEmail = prompt('Enter new email:', student.email);
      if (newEmail === null) return;
      
      if (newName && newEmail) {
        try {
          const response = await fetch('index.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              student_id: studentId,
              name: newName,
              email: newEmail
            })
          });
          
          const result = await response.json();
          
          if (result.success) {
            alert('Student updated successfully!');
            await loadStudentsAndInitialize();
          } else {
            alert('Error: ' + result.message);
          }
        } catch (error) {
          console.error('Error updating student:', error);
          alert('Failed to update student. Please try again.');
        }
      }
    }
  }
}

/**
 * Handle search input to filter students.
 * @param {Event} event - Input event
 */
function handleSearch(event) {
  const searchTerm = searchInput.value.toLowerCase();
  
  if (searchTerm === '') {
    renderTable(students);
    return;
  }
  
  const filteredStudents = students.filter(student => 
    student.name.toLowerCase().includes(searchTerm)
  );
  
  renderTable(filteredStudents);
}

/**
 * Handle sorting when table headers are clicked.
 * @param {Event} event - Click event
 */
function handleSort(event) {
  const columnIndex = event.currentTarget.cellIndex;
  const th = event.currentTarget;
  
  let sortDir = th.getAttribute('data-sort-dir') || 'asc';
  sortDir = sortDir === 'asc' ? 'desc' : 'asc';
  th.setAttribute('data-sort-dir', sortDir);
  
  let property;
  if (columnIndex === 0) property = 'name';
  else if (columnIndex === 1) property = 'student_id';
  else if (columnIndex === 2) property = 'email';
  else return;
  
  students.sort((a, b) => {
    let comparison = 0;
    
    if (property === 'student_id') {
      comparison = a[property].localeCompare(b[property], undefined, { numeric: true });
    } else {
      comparison = a[property].localeCompare(b[property]);
    }
    
    return sortDir === 'asc' ? comparison : -comparison;
  });
  
  renderTable(students);
}

/**
 * Load student data and initialize event listeners.
 */
async function loadStudentsAndInitialize() {
  try {
    const response = await fetch('index.php');
    
    if (!response.ok) {
      console.error('Failed to load students data');
      return;
    }
    
    const result = await response.json();
    
    if (result.success) {
      students = result.data;
    } else {
      console.error('Error loading students:', result.message);
      students = [];
    }
    
    renderTable(students);
    
    // Set up event listeners
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
    
    tableHeaders.forEach(header => {
      if (header.cellIndex < 3) {
        header.addEventListener('click', handleSort);
        header.style.cursor = 'pointer';
      }
    });
  } catch (error) {
    console.error('Error loading students:', error);
  }
}

// --- Initial Page Load ---
// Call the main async function to start the application.
loadStudentsAndInitialize();
