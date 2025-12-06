/**
 * Admin Portal JavaScript
 * Handles CRUD operations via API calls
 */

const addStudentForm = document.getElementById('add-student-form');
const searchInput = document.getElementById('search-input');
const studentTableBody = document.querySelector('#student-table tbody');
const tableHeaders = document.querySelectorAll('#student-table thead th[data-sort]');

let currentSort = { field: 'created_at', order: 'desc' };

// Add Student
if (addStudentForm) {
    addStudentForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = {
            student_id: document.getElementById('student-id').value.trim(),
            name: document.getElementById('student-name').value.trim(),
            email: document.getElementById('student-email').value.trim(),
            password: document.getElementById('default-password').value
        };
        
        try {
            const response = await fetch('index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert('Student added successfully!');
                addStudentForm.reset();
                document.getElementById('default-password').value = 'password123';
                location.reload(); // Reload to show new student
            } else {
                alert('Error: ' + result.message);
            }
        } catch (error) {
            console.error('Error adding student:', error);
            alert('Failed to add student. Please try again.');
        }
    });
}

// Delete Student
if (studentTableBody) {
    studentTableBody.addEventListener('click', async (e) => {
        if (e.target.classList.contains('delete-btn')) {
            const studentId = e.target.getAttribute('data-id');
            
            if (confirm('Are you sure you want to delete this student?')) {
                try {
                    const response = await fetch(`index.php?student_id=${studentId}`, {
                        method: 'DELETE'
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        alert('Student deleted successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + result.message);
                    }
                } catch (error) {
                    console.error('Error deleting student:', error);
                    alert('Failed to delete student. Please try again.');
                }
            }
        }
        
        // Edit Student
        if (e.target.classList.contains('edit-btn')) {
            const studentId = e.target.getAttribute('data-id');
            const row = e.target.closest('tr');
            
            const currentName = row.cells[0].textContent;
            const currentEmail = row.cells[2].textContent;
            
            const newName = prompt('Enter new name:', currentName);
            if (newName === null) return; // Cancelled
            
            const newEmail = prompt('Enter new email:', currentEmail);
            if (newEmail === null) return; // Cancelled
            
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
                        location.reload();
                    } else {
                        alert('Error: ' + result.message);
                    }
                } catch (error) {
                    console.error('Error updating student:', error);
                    alert('Failed to update student. Please try again.');
                }
            }
        }
    });
}

// Search Students
if (searchInput) {
    searchInput.addEventListener('input', () => {
        const searchTerm = searchInput.value.toLowerCase();
        const rows = studentTableBody.querySelectorAll('tr');
        
        rows.forEach(row => {
            if (row.cells.length < 2) return; // Skip empty rows
            
            const name = row.cells[0].textContent.toLowerCase();
            const studentId = row.cells[1].textContent.toLowerCase();
            const email = row.cells[2].textContent.toLowerCase();
            
            if (name.includes(searchTerm) || studentId.includes(searchTerm) || email.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
}

// Sort Table
tableHeaders.forEach(header => {
    header.addEventListener('click', () => {
        const sortField = header.getAttribute('data-sort');
        const rows = Array.from(studentTableBody.querySelectorAll('tr')).filter(row => row.cells.length > 1);
        
        // Toggle sort order
        if (currentSort.field === sortField) {
            currentSort.order = currentSort.order === 'asc' ? 'desc' : 'asc';
        } else {
            currentSort.field = sortField;
            currentSort.order = 'asc';
        }
        
        // Get column index
        const columnIndex = Array.from(header.parentElement.children).indexOf(header);
        
        // Sort rows
        rows.sort((a, b) => {
            let aValue = a.cells[columnIndex].textContent.trim();
            let bValue = b.cells[columnIndex].textContent.trim();
            
            // For dates, convert to timestamp
            if (sortField === 'created_at') {
                aValue = new Date(aValue).getTime();
                bValue = new Date(bValue).getTime();
            }
            
            if (currentSort.order === 'asc') {
                return aValue > bValue ? 1 : -1;
            } else {
                return aValue < bValue ? 1 : -1;
            }
        });
        
        // Re-append rows in sorted order
        rows.forEach(row => studentTableBody.appendChild(row));
        
        // Update header indicators
        tableHeaders.forEach(h => {
            h.textContent = h.textContent.replace(' ↑', '').replace(' ↓', '') + ' ↕';
        });
        header.textContent = header.textContent.replace(' ↕', '') + (currentSort.order === 'asc' ? ' ↑' : ' ↓');
    });
});
