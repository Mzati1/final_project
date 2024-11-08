// Function to toggle between Edit and Save modes
function toggleEdit(button) {
  const row = button.closest("tr");
  const electionId = row.getAttribute("data-election-id");
  const fields = row.querySelectorAll(".editable-field");

  // Check if we're in Edit mode
  const isEditMode = button.textContent === "Edit";

  if (isEditMode) {
    // Change button to "Save"
    button.textContent = "Save";

    fields.forEach((field) => {
      const originalValue = field.textContent.trim();
      const fieldName = field.getAttribute("data-field-name");

      // Make fields editable based on field name
      if (fieldName === "start_date" || fieldName === "end_date") {
        field.innerHTML = `<input type="date" class="edit-input" value="${originalValue}">`;
      } else if (fieldName === "status") {
        field.innerHTML = `
                    <select class="edit-input">
                        <option value="open" ${
                          originalValue === "Open" ? "selected" : ""
                        }>Open</option>
                        <option value="closed" ${
                          originalValue === "Closed" ? "selected" : ""
                        }>Closed</option>
                    </select>
                `;
      } else if (fieldName === "election_name") {
        field.innerHTML = `<input type="text" class="edit-input" value="${originalValue}">`;
      }
    });
  } else {
    // Switch back to "Edit" and collect updated data
    button.textContent = "Edit";

    const updatedData = {};
    fields.forEach((field) => {
      const input = field.querySelector(".edit-input");
      const newValue = input.value;
      const fieldName = field.getAttribute("data-field-name");

      updatedData[fieldName] = newValue; // Store updated data for AJAX

      // Restore non-editable view with updated content
      field.textContent = newValue;
    });

    // Send AJAX request to update the election data
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "../../includes/functions/admin/editElection.php", true);
    xhr.setRequestHeader("Content-Type", "application/json;charset=UTF-8");
    xhr.onload = function () {
      if (xhr.status === 200) {
        console.log("Election data updated successfully.");
      } else {
        console.error("Failed to update election data.");
      }
    };
    xhr.send(JSON.stringify({ election_id: electionId, ...updatedData }));
  }
}

// Function to delete an election
function deleteElection(electionId) {
  const xhr = new XMLHttpRequest();
  xhr.open("POST", "delete-election.php", true);
  xhr.setRequestHeader("Content-Type", "application/json;charset=UTF-8");
  xhr.onload = function () {
    if (xhr.status === 200) {
      console.log("Election deleted successfully.");
      // Remove the deleted row from the table
      const row = document.querySelector(`#election-${electionId}`);
      row.remove();
    } else {
      console.error("Failed to delete election.");
    }
  };
  xhr.send(JSON.stringify({ election_id: electionId }));
}
