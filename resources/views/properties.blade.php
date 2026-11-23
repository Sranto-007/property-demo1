<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Management</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f3f6fb;
            color: #1f2937;
        }

        header {
            background: #172554;
            color: white;
            padding: 24px 7%;
        }

        header h1 {
            margin: 0 0 8px;
        }

        header p {
            margin: 0;
            color: #dbeafe;
        }

        main {
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .card {
            background: white;
            padding: 24px;
            margin-bottom: 24px;
            border-radius: 12px;
            box-shadow: 0 4px 15px #0000000b;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font-size: 15px;
        }

        button {
            padding: 10px 15px;
            border: 0;
            border-radius: 7px;
            cursor: pointer;
            font-size: 14px;
        }

        .primary {
            background: #2563eb;
            color: white;
        }

        .edit {
            background: #fef3c7;
            color: #92400e;
            margin-right: 5px;
        }

        .delete {
            background: #fee2e2;
            color: #b91c1c;
        }

        .cancel {
            background: #e5e7eb;
            margin-left: 8px;
        }

        .actions {
            margin-top: 18px;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        th, td {
            text-align: left;
            padding: 14px 12px;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            background: #eff6ff;
        }

        #message {
            margin: 12px 0;
            white-space: pre-line;
        }

        .success {
            color: #15803d;
        }

        .error {
            color: #b91c1c;
        }

        @media (max-width: 600px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <header>
        <h1>Property Management</h1>
        <p>Manage your rental properties in one place.</p>
    </header>

    <main>
        <section class="card">
            <h2 id="formTitle">Add New Property</h2>
            <div id="message" role="status"></div>

            <form id="propertyForm">
                <div class="form-grid">
                    <div>
                        <label for="title">Property Title</label>
                        <input id="title" name="title"
                               placeholder="e.g. Two Bedroom Flat" required>
                    </div>

                    <div>
                        <label for="location">Location</label>
                        <input id="location" name="location"
                               placeholder="e.g. Mirpur, Dhaka" required>
                    </div>

                    <div>
                        <label for="rent">Monthly Rent (৳)</label>
                        <input id="rent" name="rent" type="number"
                               min="0" step="0.01" placeholder="15000" required>
                    </div>

                    <div>
                        <label for="bedrooms">Bedrooms</label>
                        <input id="bedrooms" name="bedrooms" type="number"
                               min="1" max="255" step="1" placeholder="2" required>
                    </div>
                </div>

                <div class="actions">
                    <button class="primary" id="saveButton" type="submit">
                        Add Property
                    </button>
                    <button class="cancel" id="cancelButton" type="button"
                            hidden>Cancel Edit</button>
                </div>
            </form>
        </section>

        <section class="card">
            <h2>All Properties</h2>
            <p id="propertyCount">Loading properties...</p>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Location</th>
                            <th>Monthly Rent</th>
                            <th>Bedrooms</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="propertyList">
                        <tr>
                            <td colspan="6">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script>
        const API_URL = '/api/properties';

        const form = document.getElementById('propertyForm');
        const list = document.getElementById('propertyList');
        const message = document.getElementById('message');
        const count = document.getElementById('propertyCount');
        const formTitle = document.getElementById('formTitle');
        const saveButton = document.getElementById('saveButton');
        const cancelButton = document.getElementById('cancelButton');

        let editingId = null;
        let properties = [];

        function showMessage(text, isError = false) {
            message.textContent = text;
            message.className = isError ? 'error' : 'success';
        }

        function escapeHTML(value) {
            return String(value ?? '').replace(/[&<>"']/g, char => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            })[char]);
        }

        async function request(url, options = {}) {
            const response = await fetch(url, {
                ...options,
                headers: {
                    'Accept': 'application/json',
                    ...(options.body ? {
                        'Content-Type': 'application/json'
                    } : {}),
                    ...options.headers
                }
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                let errorText = data.message || 'Request failed.';
                if (data.errors) {
                    errorText = Object.values(data.errors).flat().join('\n');
                }
                throw new Error(errorText);
            }

            return data;
        }

        async function loadProperties() {
            list.innerHTML = '<tr><td colspan="6">Loading...</td></tr>';

            try {
                const data = await request(API_URL);
                properties = Array.isArray(data) ? data : (data.data || []);
                renderProperties();
            } catch (error) {
                list.innerHTML =
                    '<tr><td colspan="6">Could not load properties.</td></tr>';
                count.textContent = 'Unable to load property list.';
                showMessage(error.message, true);
            }
        }

        function renderProperties() {
            count.textContent = `Total properties: ${properties.length}`;

            if (properties.length === 0) {
                list.innerHTML =
                    '<tr><td colspan="6">No properties yet. Add one above.</td></tr>';
                return;
            }

            list.innerHTML = properties.map(property => `
                <tr>
                    <td>${escapeHTML(property.id)}</td>
                    <td>${escapeHTML(property.title)}</td>
                    <td>${escapeHTML(property.location)}</td>
                    <td>৳${Number(property.rent).toLocaleString()}</td>
                    <td>${escapeHTML(property.bedrooms)}</td>
                    <td>
                        <button class="edit" data-action="edit"
                                data-id="${escapeHTML(property.id)}">
                            Edit
                        </button>
                        <button class="delete" data-action="delete"
                                data-id="${escapeHTML(property.id)}">
                            Delete
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            const payload = {
                title: document.getElementById('title').value.trim(),
                location: document.getElementById('location').value.trim(),
                rent: Number(document.getElementById('rent').value),
                bedrooms: Number(document.getElementById('bedrooms').value)
            };

            saveButton.disabled = true;

            try {
                if (editingId !== null) {
                    await request(`${API_URL}/${editingId}`, {
                        method: 'PUT',
                        body: JSON.stringify(payload)
                    });
                    showMessage('Property updated successfully!');
                } else {
                    await request(API_URL, {
                        method: 'POST',
                        body: JSON.stringify(payload)
                    });
                    showMessage('Property added successfully!');
                }

                resetForm();
                await loadProperties();
            } catch (error) {
                showMessage(error.message, true);
            } finally {
                saveButton.disabled = false;
            }
        });

        list.addEventListener('click', async function (event) {
            const button = event.target.closest('button[data-action]');
            if (!button) return;

            const id = button.dataset.id;
            const action = button.dataset.action;
            const property = properties.find(item => String(item.id) === id);

            if (!property) return;

            if (action === 'edit') {
                editingId = property.id;
                document.getElementById('title').value = property.title;
                document.getElementById('location').value = property.location;
                document.getElementById('rent').value = property.rent;
                document.getElementById('bedrooms').value = property.bedrooms;

                formTitle.textContent = 'Edit Property';
                saveButton.textContent = 'Update Property';
                cancelButton.hidden = false;
                showMessage('Editing property #' + id);
                form.scrollIntoView({ behavior: 'smooth' });
            }

            if (action === 'delete') {
                if (!confirm(`Delete "${property.title}"?`)) return;

                button.disabled = true;

                try {
                    await request(`${API_URL}/${id}`, {
                        method: 'DELETE'
                    });

                    if (String(editingId) === id) resetForm();

                    showMessage('Property deleted successfully!');
                    await loadProperties();
                } catch (error) {
                    showMessage(error.message, true);
                    button.disabled = false;
                }
            }
        });

        function resetForm() {
            form.reset();
            editingId = null;
            formTitle.textContent = 'Add New Property';
            saveButton.textContent = 'Add Property';
            cancelButton.hidden = true;
        }

        cancelButton.addEventListener('click', resetForm);

        loadProperties();
    </script>
</body>
</html>
