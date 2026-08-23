document.addEventListener('DOMContentLoaded', () => {
  // Price and Time Duration Calculator for Booking Form
  const courtSelect = document.getElementById('court_id');
  const startTimeInput = document.getElementById('start_time');
  const endTimeInput = document.getElementById('end_time');
  const totalPriceDisplay = document.getElementById('total_price_display');
  const totalAmountInput = document.getElementById('total_amount');

  function calculatePrice() {
    if (!courtSelect || !startTimeInput || !endTimeInput || !totalPriceDisplay) return;

    const selectedOption = courtSelect.options[courtSelect.selectedIndex];
    const pricePerHour = parseFloat(selectedOption.getAttribute('data-price') || 0);

    const startTime = startTimeInput.value;
    const endTime = endTimeInput.value;

    if (startTime && endTime && pricePerHour > 0) {
      const start = new Date(`1970-01-01T${startTime}:00`);
      const end = new Date(`1970-01-01T${endTime}:00`);

      let diffHours = (end - start) / (1000 * 60 * 60);

      if (diffHours > 0) {
        const total = diffHours * pricePerHour;
        totalPriceDisplay.textContent = `$${total.toFixed(2)}`;
        if (totalAmountInput) totalAmountInput.value = total.toFixed(2);
      } else {
        totalPriceDisplay.textContent = '$0.00 (Invalid time)';
        if (totalAmountInput) totalAmountInput.value = '0.00';
      }
    }
  }

  if (courtSelect && startTimeInput && endTimeInput) {
    courtSelect.addEventListener('change', calculatePrice);
    startTimeInput.addEventListener('change', calculatePrice);
    endTimeInput.addEventListener('change', calculatePrice);
  }

  const editModal = document.getElementById('editCourtModal');
  const editForm = document.getElementById('editCourtForm');
  const editClose = editModal && editModal.querySelector('.modal-close');
  const editButtons = document.querySelectorAll('.btn-edit-court');

  function openEditModal(data) {
    if (!editModal) return;
    editModal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    document.getElementById('edit_court_id').value = data.id;
    document.getElementById('edit_court_name').value = data.name;
    document.getElementById('edit_court_number').value = data.number;
    document.getElementById('edit_type').value = data.type;
    document.getElementById('edit_description').value = data.description || '';
    document.getElementById('edit_price_per_hour').value = data.price;
    document.getElementById('edit_status').value = data.status;
  }

  function closeEditModal() {
    if (!editModal) return;
    editModal.classList.remove('is-open');
    document.body.style.overflow = '';
    if (editForm) editForm.reset();
  }

  editButtons.forEach((button) => {
    button.addEventListener('click', () => {
      openEditModal({
        id: button.dataset.courtId,
        name: button.dataset.courtName,
        number: button.dataset.courtNumber,
        type: button.dataset.type,
        description: button.dataset.description,
        price: button.dataset.price,
        status: button.dataset.status,
      });
    });
  });

  if (editClose) {
    editClose.addEventListener('click', closeEditModal);
  }

  if (editModal) {
    editModal.addEventListener('click', (event) => {
      if (event.target === editModal) {
        closeEditModal();
      }
    });
  }

  const deleteModal = document.getElementById('deleteConfirmModal');
  const deleteClose = deleteModal && deleteModal.querySelector('.modal-close');
  const deleteButtons = document.querySelectorAll('.btn-delete-court');
  const cancelDelete = document.querySelector('.btn-cancel-delete');
  const confirmDelete = document.querySelector('.btn-confirm-delete');
  const deleteCourtForm = document.getElementById('deleteCourtForm');
  const deleteCourtIdInput = document.getElementById('delete_court_id');

  function openDeleteModal(data) {
    if (!deleteModal) return;
    deleteModal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    deleteCourtIdInput.value = data.id;
    document.getElementById('deleteConfirmText').textContent = `Delete court “${data.name}”? This action cannot be undone.`;
  }

  function closeDeleteModal() {
    if (!deleteModal) return;
    deleteModal.classList.remove('is-open');
    document.body.style.overflow = '';
  }

  deleteButtons.forEach((button) => {
    button.addEventListener('click', () => {
      openDeleteModal({
        id: button.dataset.courtId,
        name: button.dataset.courtName,
      });
    });
  });

  if (deleteClose) {
    deleteClose.addEventListener('click', closeDeleteModal);
  }

  if (cancelDelete) {
    cancelDelete.addEventListener('click', closeDeleteModal);
  }

  if (deleteModal) {
    deleteModal.addEventListener('click', (event) => {
      if (event.target === deleteModal) {
        closeDeleteModal();
      }
    });
  }

  if (confirmDelete && deleteCourtForm) {
    confirmDelete.addEventListener('click', () => {
      deleteCourtForm.submit();
    });
  }

  const statusModal = document.getElementById('statusModal');
  const statusClose = statusModal && statusModal.querySelector('.modal-close');
  const statusOkay = document.querySelector('.btn-status-close');

  function closeStatusModal() {
    if (!statusModal) return;
    statusModal.classList.remove('is-open');
    statusModal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  if (statusModal && statusModal.classList.contains('is-open')) {
    statusModal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  }

  if (statusClose) {
    statusClose.addEventListener('click', closeStatusModal);
  }

  if (statusOkay) {
    statusOkay.addEventListener('click', closeStatusModal);
  }
});