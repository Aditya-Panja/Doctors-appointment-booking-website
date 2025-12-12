/* ==========================================================================
   Main Event Listener
   ========================================================================== */
document.addEventListener('DOMContentLoaded', function() {
    
    /* --------------------------------------------------------------------------
       1. Global Password Eye Toggle (Login, Register, Reset)
       -------------------------------------------------------------------------- */
    const toggleButtons = document.querySelectorAll('.toggle-password-btn');

    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Find the input field inside the same wrapper
            const input = this.parentElement.querySelector('input');
            const icon = this.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

    /* --------------------------------------------------------------------------
       2. Homepage Search Bar (Dependent Dropdown)
       -------------------------------------------------------------------------- */
    const departmentSelect = document.getElementById('department-select');
    const doctorSelect = document.getElementById('doctor-select');

    if (departmentSelect && doctorSelect) {
        departmentSelect.addEventListener('change', function() {
            const selectedDepartment = this.value;
            doctorSelect.innerHTML = '<option value="">Loading...</option>';
            doctorSelect.disabled = true;

            if (!selectedDepartment) {
                doctorSelect.innerHTML = '<option value="">Select Department First</option>';
                return;
            }

            fetch('ajax_get_doctors.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'department=' + encodeURIComponent(selectedDepartment)
            })
            .then(response => response.json())
            .then(data => {
                doctorSelect.innerHTML = ''; 
                if (data.length > 0) {
                    doctorSelect.innerHTML = '<option value="">Select By Doctor</option>';
                    data.forEach(doctor => {
                        const option = document.createElement('option');
                        // Use doctor ID as value for details page
                        option.value = doctor.id; 
                        option.textContent = doctor.full_name;
                        doctorSelect.appendChild(option);
                    });
                    doctorSelect.disabled = false;
                } else {
                    doctorSelect.innerHTML = '<option value="">No Doctors Found</option>';
                }
            })
            .catch(error => {
                console.error('Error fetching doctors:', error);
                doctorSelect.innerHTML = '<option value="">Error loading doctors</option>';
            });
        });
    }

    /* --------------------------------------------------------------------------
       3. Patient Dashboard Filter (Dependent Dropdown)
       -------------------------------------------------------------------------- */
    const filterDept = document.getElementById('filter-dept-select');
    const filterDoc = document.getElementById('filter-doc-select');
    const doctorGrid = document.getElementById('doctors-grid-list');

    if (filterDept && filterDoc && doctorGrid) {
        
        // Department Change
        filterDept.addEventListener('change', function() {
            const department = this.value;
            filterDoc.innerHTML = '<option value="">All Doctors</option>';
            filterDoc.disabled = true;

            if (department) {
                fetch('ajax_get_doctors.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'department=' + encodeURIComponent(department)
                })
                .then(response => response.json())
                .then(doctors => {
                    if (doctors.length > 0) {
                        doctors.forEach(doctor => {
                            const option = document.createElement('option');
                            option.value = doctor.full_name; // Filter by name text
                            option.textContent = doctor.full_name;
                            filterDoc.appendChild(option);
                        });
                        filterDoc.disabled = false;
                    }
                    filterGrid(); 
                });
            } else {
                filterGrid(); 
            }
        });

        // Doctor Change
        filterDoc.addEventListener('change', filterGrid);

        // Filter Logic
        function filterGrid() {
            const deptFilter = filterDept.value.toLowerCase();
            const docFilter = filterDoc.value.toLowerCase();
            const doctorCards = doctorGrid.getElementsByClassName('doctor-card');

            for (let card of doctorCards) {
                const spec = card.querySelector('.doctor-dept').textContent.toLowerCase();
                const name = card.querySelector('h3').textContent.toLowerCase();

                const deptMatch = (deptFilter === "" || spec.includes(deptFilter));
                const docMatch = (docFilter === "" || name.includes(docFilter));

                if (deptMatch && docMatch) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            }
        }
    }

    /* --------------------------------------------------------------------------
       4. Admin Dashboard Filter (Dependent Dropdown)
       -------------------------------------------------------------------------- */
    const adminFilterDept = document.getElementById('admin-filter-dept');
    const adminFilterDoc = document.getElementById('admin-filter-doc');
    const adminTableBody = document.getElementById('appointments-table-body');

    if (adminFilterDept && adminFilterDoc && adminTableBody) {
        
        adminFilterDept.addEventListener('change', function() {
            const department = this.value;
            adminFilterDoc.innerHTML = '<option value="">All Doctors</option>';
            adminFilterDoc.disabled = true;

            if (department) {
                // Note path: ../ajax_get_doctors.php because admin is a subfolder
                fetch('../ajax_get_doctors.php', { 
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'department=' + encodeURIComponent(department)
                })
                .then(response => response.json())
                .then(doctors => {
                    if (doctors.length > 0) {
                        doctors.forEach(doctor => {
                            const option = document.createElement('option');
                            option.value = doctor.full_name; 
                            option.textContent = doctor.full_name;
                            adminFilterDoc.appendChild(option);
                        });
                        adminFilterDoc.disabled = false;
                    }
                    filterTable(); 
                });
            } else {
                filterTable(); 
            }
        });

        adminFilterDoc.addEventListener('change', filterTable);

        function filterTable() {
            const docFilter = adminFilterDoc.value.toLowerCase();
            const rows = adminTableBody.getElementsByTagName('tr');

            for (let row of rows) {
                const doctorNameCell = row.cells[1]; 
                if (doctorNameCell) {
                    const doctorName = doctorNameCell.textContent.toLowerCase();
                    if (docFilter === "" || doctorName.includes(docFilter)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            }
        }
    }

}); // End DOMContentLoaded

/**
 * Global Confirm Action
 */
function confirmAction() {
  return confirm("Are you sure you want to perform this action?");
}