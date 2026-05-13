document.addEventListener('DOMContentLoaded', function () {
	function initAdminUsersTable() {
		const exportBtn = document.getElementById('export-pdf');
		const resetBtn = document.getElementById('reset-filters');
		const search = document.getElementById('user-search');
		const role = document.getElementById('filter-role');
		const blocked = document.getElementById('filter-blocked');
		const tbody = document.getElementById('users-tbody');
		const rows = tbody ? Array.from(tbody.querySelectorAll('.user-row')) : [];
		const sortableHeaders = Array.from(document.querySelectorAll('th.sortable'));
		let sortState = { key: 'id', direction: 'asc' };

		if (!tbody || rows.length === 0) {
			return;
		}

		if (tbody.dataset.adminUsersInit === '1') {
			return;
		}
		tbody.dataset.adminUsersInit = '1';

		function updateExportHref() {
			if (!exportBtn) return;
			const exportUrl = exportBtn.dataset.exportUrl;
			if (!exportUrl) return;

			const params = new URLSearchParams();
			if (search && search.value.trim() !== '') {
				params.set('search', search.value.trim());
			}
			if (role && role.value !== '') {
				params.set('role', role.value);
			}
			if (blocked && blocked.value !== '') {
				params.set('blocked', blocked.value);
			}

			const target = params.toString() ? `${exportUrl}?${params.toString()}` : exportUrl;
			exportBtn.setAttribute('href', target);
		}

		function getRowValue(row, key) {
			if (key === 'id') return Number(row.dataset.userId || 0);
			if (key === 'fullName') return (row.dataset.fullname || '').toLowerCase();
			if (key === 'email') return (row.dataset.email || '').toLowerCase();
			if (key === 'role') return (row.dataset.role || '').toLowerCase();
			if (key === 'verified') return row.dataset.verified === '1' ? 1 : 0;
			if (key === 'blocked') return row.dataset.blocked === '1' ? 1 : 0;
			return '';
		}

		function applyFilters() {
			const searchValue = search ? search.value.trim().toLowerCase() : '';
			const roleValue = role ? role.value.toUpperCase() : '';
			const blockedValue = blocked ? blocked.value : '';

			rows.forEach(function (row) {
				const text = [
					row.dataset.userId || '',
					row.dataset.fullname || '',
					row.dataset.email || '',
					row.dataset.role || '',
				].join(' ').toLowerCase();

				let visible = true;
				if (searchValue !== '' && !text.includes(searchValue)) {
					visible = false;
				}
				if (visible && roleValue !== '' && (row.dataset.role || '').toUpperCase() !== roleValue) {
					visible = false;
				}
				if (visible && blockedValue !== '') {
					if (blockedValue === 'blocked' && row.dataset.blocked !== '1') visible = false;
					if (blockedValue === 'unblocked' && row.dataset.blocked !== '0') visible = false;
				}

				row.classList.toggle('is-hidden', !visible);
			});

			updateExportHref();
		}

		function applySort(key) {
			if (sortState.key === key) {
				sortState.direction = sortState.direction === 'asc' ? 'desc' : 'asc';
			} else {
				sortState.key = key;
				sortState.direction = 'asc';
			}

			const direction = sortState.direction === 'asc' ? 1 : -1;
			const sortedRows = rows.slice().sort(function (a, b) {
				const va = getRowValue(a, sortState.key);
				const vb = getRowValue(b, sortState.key);

				if (typeof va === 'number' && typeof vb === 'number') {
					return (va - vb) * direction;
				}

				return String(va).localeCompare(String(vb)) * direction;
			});

			sortedRows.forEach(function (row) {
				tbody.appendChild(row);
			});

			sortableHeaders.forEach(function (th) {
				th.dataset.sortDirection = th.dataset.sortKey === sortState.key ? sortState.direction : '';
			});
		}

		updateExportHref();
		applyFilters();
		applySort(sortState.key);

		[search, role, blocked].forEach(function (el) {
			if (!el) return;
			el.addEventListener('input', applyFilters);
			el.addEventListener('change', applyFilters);
		});

		sortableHeaders.forEach(function (th) {
			th.addEventListener('click', function () {
				if (!th.dataset.sortKey) return;
				applySort(th.dataset.sortKey);
			});
		});

		if (resetBtn) {
			resetBtn.addEventListener('click', function (e) {
				e.preventDefault();
				if (search) search.value = '';
				if (role) role.value = '';
				if (blocked) blocked.value = '';
				sortState = { key: 'id', direction: 'asc' };
				applySort(sortState.key);
				applyFilters();
				updateExportHref();
			});
		}

		if (exportBtn) {
			exportBtn.addEventListener('click', function () {
				updateExportHref();
			});
		}
	}

	initAdminUsersTable();

	document.addEventListener('turbo:load', function () {
		initAdminUsersTable();
	});
});
