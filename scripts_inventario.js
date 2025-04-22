function showTab(tabId) {
    const tabs = document.querySelectorAll('.tab');
    const contents = document.querySelectorAll('.tab-content');
    
    tabs.forEach(tab => tab.classList.remove('active'));
    contents.forEach(content => content.classList.remove('active'));

    document.querySelector(`.tab[onclick="showTab('${tabId}')"]`).classList.add('active');
    document.getElementById(tabId).classList.add('active');

    if (tabId === 'historial') {
        startHistorialUpdates();
    } else {
        stopHistorialUpdates();
    }
}

function openModal(modalId) {
    document.getElementById(modalId).style.display = 'flex';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function openUpdateEquipoModal(id, marca, serial, estado) {
    document.getElementById('update_equipo_id').value = id;
    const marcaSelect = document.getElementById('update_marca');
    const customMarcaInput = document.getElementById('update_customMarca');
    if (['HP', 'Dell', 'Lenovo', 'Asus', 'Acer', 'Apple'].includes(marca)) {
        marcaSelect.value = marca;
        customMarcaInput.style.display = 'none';
    } else {
        marcaSelect.value = 'Otra';
        customMarcaInput.style.display = 'block';
        customMarcaInput.value = marca;
    }
    document.getElementById('update_serial').value = serial;
    document.getElementById('update_estado').value = estado;
    openModal('updateEquipoModal');
}

function openUpdateMaterialModal(id, nombre, tipo, stock) {
    document.getElementById('update_material_id').value = id;
    document.getElementById('update_nombre_material').value = nombre;
    document.getElementById('update_tipo').value = tipo;
    document.getElementById('update_stock').value = stock;
    openModal('updateMaterialModal');
}

window.onclick = function(event) {
    const modals = ['materialModal', 'updateEquipoModal', 'updateMaterialModal'];
    modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const container = document.querySelector('.container');
    if (container) {
        container.style.display = 'block';
    }

    const searchEquipos = document.getElementById('searchEquipos');
    searchEquipos.addEventListener('input', function() {
        filterTable('equiposTable', this.value);
    });

    const searchMateriales = document.getElementById('searchMateriales');
    searchMateriales.addEventListener('input', function() {
        filterTable('materialesTable', this.value);
    });

    const fechaInicioEquipos = document.getElementById('fechaInicioEquipos');
    const fechaFinEquipos = document.getElementById('fechaFinEquipos');
    fechaInicioEquipos.addEventListener('change', function() {
        filterTableByDate('equiposTable', fechaInicioEquipos.value, fechaFinEquipos.value);
    });
    fechaFinEquipos.addEventListener('change', function() {
        filterTableByDate('equiposTable', fechaInicioEquipos.value, fechaFinEquipos.value);
    });

    const fechaInicioMateriales = document.getElementById('fechaInicioMateriales');
    const fechaFinMateriales = document.getElementById('fechaFinMateriales');
    fechaInicioMateriales.addEventListener('change', function() {
        filterTableByDate('materialesTable', fechaInicioMateriales.value, fechaFinMateriales.value);
    });
    fechaFinMateriales.addEventListener('change', function() {
        filterTableByDate('materialesTable', fechaInicioMateriales.value, fechaFinMateriales.value);
    });

    sortTable('equiposTable', 0, true);
    sortTable('materialesTable', 0, true);
});

function toggleCustomMarca() {
    const marcaSelect = document.getElementById('marca');
    const customMarcaInput = document.getElementById('customMarca');
    customMarcaInput.style.display = marcaSelect.value === 'Otra' ? 'block' : 'none';
    if (marcaSelect.value !== 'Otra') {
        customMarcaInput.value = '';
    }
}

function toggleUpdateCustomMarca() {
    const marcaSelect = document.getElementById('update_marca');
    const customMarcaInput = document.getElementById('update_customMarca');
    customMarcaInput.style.display = marcaSelect.value === 'Otra' ? 'block' : 'none';
    if (marcaSelect.value !== 'Otra') {
        customMarcaInput.value = '';
    }
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.id = 'notification';
    notification.textContent = message;
    document.body.appendChild(notification);

    setTimeout(() => {
        const notificationElement = document.getElementById('notification');
        if (notificationElement) {
            notificationElement.classList.add('fade-out');
            setTimeout(() => notificationElement.remove(), 500);
        }
    }, 2000);
}

function addEquipo() {
    const form = document.getElementById('addEquipoForm');
    const formData = new FormData(form);
    formData.append('agregar_equipo_ajax', '1');
    let marca = formData.get('marca');
    if (marca === 'Otra') {
        marca = formData.get('custom_marca');
    }
    formData.set('marca', marca);

    // Mostrar indicador de carga en el botón
    const button = form.querySelector('button');
    const originalText = button.textContent;
    button.textContent = 'Agregando...';
    button.disabled = true;

    const startTime = performance.now();

    fetch('inventario.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const endTime = performance.now();
        console.log(`Tiempo total de agregar equipo: ${(endTime - startTime).toFixed(2)} ms`);
        console.log(`Tiempo de ejecución en el servidor: ${data.execution_time} ms`);

        // Restaurar el botón
        button.textContent = originalText;
        button.disabled = false;

        if (data.success) {
            const table = document.getElementById('equiposTable').getElementsByTagName('tbody')[0];
            const newRow = table.insertRow(0);
            newRow.setAttribute('data-id', data.id_equipo);
            newRow.setAttribute('data-fecha-creacion', new Date().toISOString().slice(0, 19).replace('T', ' '));
            newRow.classList.add('highlight');
            newRow.innerHTML = `
                <td class="marca">${marca}</td>
                <td class="serial">${formData.get('serial')}</td>
                <td class="estado">disponible</td>
                <td class="fecha-creacion">${new Date().toISOString().slice(0, 19).replace('T', ' ')}</td>
                <td class="action-buttons">
                    <button class="edit-btn" onclick="openUpdateEquipoModal('${data.id_equipo}', '${marca}', '${formData.get('serial')}', 'disponible')">Editar</button>
                    <button class="delete-btn" onclick="deleteEquipo('${data.id_equipo}')">Eliminar</button>
                </td>
            `;
            form.reset();
            document.getElementById('customMarca').style.display = 'none';

            // Mostrar notificación después de actualizar la tabla
            showNotification(data.message, 'success');
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        button.textContent = originalText;
        button.disabled = false;
        showNotification('Error al agregar equipo: ' + error, 'error');
    });
}

function updateEquipo() {
    const form = document.getElementById('updateEquipoForm');
    const formData = new FormData(form);
    let marca = formData.get('marca');
    if (marca === 'Otra') {
        marca = formData.get('custom_marca');
    }
    formData.set('marca', marca);

    // Mostrar indicador de carga en el botón
    const button = form.querySelector('button');
    const originalText = button.textContent;
    button.textContent = 'Actualizando...';
    button.disabled = true;

    const startTime = performance.now();

    fetch('inventario.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const endTime = performance.now();
        console.log(`Tiempo total de actualizar equipo: ${(endTime - startTime).toFixed(2)} ms`);
        console.log(`Tiempo de ejecución en el servidor: ${data.execution_time} ms`);

        // Restaurar el botón
        button.textContent = originalText;
        button.disabled = false;

        if (data.success) {
            const idEquipo = formData.get('id_equipo');
            const row = document.querySelector(`#equiposTable tbody tr[data-id='${idEquipo}']`);
            if (row) {
                row.querySelector('.marca').textContent = marca;
                row.querySelector('.serial').textContent = formData.get('serial');
                row.querySelector('.estado').textContent = formData.get('estado');
                row.classList.add('highlight');
                const editButton = row.querySelector('.edit-btn');
                editButton.setAttribute('onclick', `openUpdateEquipoModal('${idEquipo}', '${marca}', '${formData.get('serial')}', '${formData.get('estado')}')`);
            }

            closeModal('updateEquipoModal');
            showNotification(data.message, 'success');
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        button.textContent = originalText;
        button.disabled = false;
        showNotification('Error al actualizar equipo: ' + error, 'error');
    });
}

function deleteEquipo(id) {
    if (!confirm('¿Estás seguro de que deseas eliminar este equipo?')) {
        return;
    }

    const formData = new FormData();
    formData.append('eliminar_equipo', '1');
    formData.append('id_equipo', id);

    fetch('inventario.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        showNotification(data.message, data.success ? 'success' : 'error');
        if (data.success) {
            const row = document.querySelector(`#equiposTable tbody tr[data-id='${id}']`);
            if (row) {
                row.remove();
            }
        }
    })
    .catch(error => {
        showNotification('Error al eliminar equipo: ' + error, 'error');
    });
}

function addMaterial() {
    const form = document.getElementById('addMaterialForm');
    const formData = new FormData(form);
    formData.append('agregar_material_ajax', '1');

    // Mostrar indicador de carga en el botón
    const button = form.querySelector('button');
    const originalText = button.textContent;
    button.textContent = 'Agregando...';
    button.disabled = true;

    const startTime = performance.now();

    fetch('inventario.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const endTime = performance.now();
        console.log(`Tiempo total de agregar material: ${(endTime - startTime).toFixed(2)} ms`);
        console.log(`Tiempo de ejecución en el servidor: ${data.execution_time} ms`);

        // Restaurar el botón
        button.textContent = originalText;
        button.disabled = false;

        if (data.success) {
            const table = document.getElementById('materialesTable').getElementsByTagName('tbody')[0];
            const newRow = table.insertRow(0);
            newRow.setAttribute('data-id', data.id_material);
            newRow.setAttribute('data-fecha-creacion', new Date().toISOString().slice(0, 19).replace('T', ' '));
            newRow.classList.add('highlight');
            newRow.innerHTML = `
                <td class="nombre">${formData.get('nombre_material')}</td>
                <td class="tipo">${formData.get('tipo')}</td>
                <td class="stock">${formData.get('stock')}</td>
                <td class="fecha-creacion">${new Date().toISOString().slice(0, 19).replace('T', ' ')}</td>
                <td class="action-buttons">
                    <button class="edit-btn" onclick="openUpdateMaterialModal('${data.id_material}', '${formData.get('nombre_material')}', '${formData.get('tipo')}', '${formData.get('stock')}')">Editar</button>
                    <button class="delete-btn" onclick="deleteMaterial('${data.id_material}')">Eliminar</button>
                </td>
            `;
            closeModal('materialModal');
            showNotification(data.message, 'success');
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        button.textContent = originalText;
        button.disabled = false;
        showNotification('Error al agregar material: ' + error, 'error');
    });
}

function updateMaterial() {
    const form = document.getElementById('updateMaterialForm');
    const formData = new FormData(form);

    // Mostrar indicador de carga en el botón
    const button = form.querySelector('button');
    const originalText = button.textContent;
    button.textContent = 'Actualizando...';
    button.disabled = true;

    const startTime = performance.now();

    fetch('inventario.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const endTime = performance.now();
        console.log(`Tiempo total de actualizar material: ${(endTime - startTime).toFixed(2)} ms`);
        console.log(`Tiempo de ejecución en el servidor: ${data.execution_time} ms`);

        // Restaurar el botón
        button.textContent = originalText;
        button.disabled = false;

        if (data.success) {
            const idMaterial = formData.get('id_material');
            const row = document.querySelector(`#materialesTable tbody tr[data-id='${idMaterial}']`);
            if (row) {
                row.querySelector('.nombre').textContent = formData.get('nombre');
                row.querySelector('.tipo').textContent = formData.get('tipo');
                row.querySelector('.stock').textContent = formData.get('stock');
                row.classList.add('highlight');
                const editButton = row.querySelector('.edit-btn');
                editButton.setAttribute('onclick', `openUpdateMaterialModal('${idMaterial}', '${formData.get('nombre')}', '${formData.get('tipo')}', '${formData.get('stock')}')`);
            }

            closeModal('updateMaterialModal');
            showNotification(data.message, 'success');
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        button.textContent = originalText;
        button.disabled = false;
        showNotification('Error al actualizar material: ' + error, 'error');
    });
}

function deleteMaterial(id) {
    if (!confirm('¿Estás seguro de que deseas eliminar este material?')) {
        return;
    }

    const formData = new FormData();
    formData.append('eliminar_material_ajax', '1');
    formData.append('id_material', id);

    fetch('inventario.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        showNotification(data.message, data.success ? 'success' : 'error');
        if (data.success) {
            const row = document.querySelector(`#materialesTable tbody tr[data-id='${id}']`);
            if (row) {
                row.remove();
            }
        }
    })
    .catch(error => {
        showNotification('Error al eliminar material: ' + error, 'error');
    });
}

function filterTable(tableId, searchText) {
    const table = document.getElementById(tableId);
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    searchText = searchText.toLowerCase();

    for (let i = 0; i < rows.length; i++) {
        const cells = rows[i].getElementsByTagName('td');
        let match = false;

        for (let j = 0; j < cells.length - 1; j++) {
            const cellText = cells[j].textContent.toLowerCase();
            if (cellText.includes(searchText)) {
                match = true;
                break;
            }
        }

        rows[i].style.display = match ? '' : 'none';
    }

    if (tableId === 'equiposTable') {
        const fechaInicio = document.getElementById('fechaInicioEquipos').value;
        const fechaFin = document.getElementById('fechaFinEquipos').value;
        if (fechaInicio || fechaFin) {
            filterTableByDate(tableId, fechaInicio, fechaFin);
        }
    } else if (tableId === 'materialesTable') {
        const fechaInicio = document.getElementById('fechaInicioMateriales').value;
        const fechaFin = document.getElementById('fechaFinMateriales').value;
        if (fechaInicio || fechaFin) {
            filterTableByDate(tableId, fechaInicio, fechaFin);
        }
    }
}

function filterTableByDate(tableId, fechaInicio, fechaFin) {
    const table = document.getElementById(tableId);
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

    const startDate = fechaInicio ? new Date(fechaInicio) : null;
    const endDate = fechaFin ? new Date(fechaFin) : null;
    if (endDate) {
        endDate.setHours(23, 59, 59, 999);
    }

    for (let i = 0; i < rows.length; i++) {
        const fechaCreacionStr = rows[i].getAttribute('data-fecha-creacion');
        if (!fechaCreacionStr) continue;

        const fechaCreacion = new Date(fechaCreacionStr);
        let inDateRange = true;

        if (startDate && fechaCreacion < startDate) {
            inDateRange = false;
        }
        if (endDate && fechaCreacion > endDate) {
            inDateRange = false;
        }

        if (!inDateRange) {
            rows[i].style.display = 'none';
        } else {
            const searchText = tableId === 'equiposTable' ? document.getElementById('searchEquipos').value : document.getElementById('searchMateriales').value;
            if (searchText) {
                filterTable(tableId, searchText);
            } else {
                rows[i].style.display = '';
            }
        }
    }
}

function sortTable(tableId, column, initial = false) {
    const table = document.getElementById(tableId);
    const tbody = table.getElementsByTagName('tbody')[0];
    const rows = Array.from(tbody.getElementsByTagName('tr'));
    const th = table.getElementsByTagName('th')[column];
    const isAsc = th.classList.contains('asc') || initial;
    const direction = isAsc ? -1 : 1;

    rows.sort((a, b) => {
        let aValue = a.getElementsByTagName('td')[column].textContent.trim();
        let bValue = b.getElementsByTagName('td')[column].textContent.trim();

        if (column === 3) { // Fecha Creación
            aValue = new Date(a.getAttribute('data-fecha-creacion'));
            bValue = new Date(b.getAttribute('data-fecha-creacion'));
        } else if (column === 2 && tableId === 'materialesTable') { // Stock
            aValue = parseInt(aValue) || 0;
            bValue = parseInt(bValue) || 0;
        }

        return aValue > bValue ? direction : aValue < bValue ? -direction : 0;
    });

    while (tbody.firstChild) {
        tbody.removeChild(tbody.firstChild);
    }
    rows.forEach(row => tbody.appendChild(row));

    table.querySelectorAll('th').forEach(header => {
        header.classList.remove('asc', 'desc');
        header.querySelector('.sort-icon').textContent = '';
    });
    th.classList.add(isAsc ? 'desc' : 'asc');
}

let historialInterval;

function startHistorialUpdates() {
    if (!historialInterval) {
        historialInterval = setInterval(updateHistorialTable, 5000);
    }
    updateHistorialTable();
}

function stopHistorialUpdates() {
    if (historialInterval) {
        clearInterval(historialInterval);
        historialInterval = null;
    }
}

function updateHistorialTable() {
    fetch('inventario.php?obtener_historial=1&pagina_historial=<?php echo $pagina_historial; ?>')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const tbody = document.querySelector('#historialTable tbody');
                tbody.innerHTML = '';

                if (data.data.length === 0) {
                    tbody.innerHTML = "<tr><td colspan='6'>No hay cambios registrados.</td></tr>";
                } else {
                    data.data.forEach(row => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>${row.nombres ? row.nombres + ' ' + row.apellidos : 'N/A'}</td>
                            <td>${row.tabla_afectada || 'N/A'}</td>
                            <td>${row.accion || 'N/A'}</td>
                            <td>${row.id_registro || 'N/A'}</td>
                            <td>${row.fecha_accion || 'N/A'}</td>
                            <td>${row.detalles ? row.detalles : 'N/A'}</td>
                        `;
                        tbody.appendChild(tr);
                    });
                }
            } else {
                console.error('Error al actualizar historial:', data.message);
            }
        })
        .catch(error => console.error('Error al actualizar historial:', error));
}