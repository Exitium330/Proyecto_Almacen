<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario</title>
    <link rel="stylesheet" href="Css/inventario.css?v=<?php echo time(); ?>">
    <script>
    document.addEventListener("DOMContentLoaded", function () {
    if (localStorage.getItem("modoOscuro") === "enabled") {
        document.body.classList.add("dark-mode");
        console.log("Modo oscuro activado");
    } else {
        document.body.classList.remove("dark-mode");
        console.log("Modo oscuro desactivado");
    }
});
        </script>
 
</head>
<body onload="cargarDatos()">
    <div class="container">
        <h1>Inventario</h1>
        
        <section>
            <h2>Equipos</h2>
            <table>
                <thead>
                    <tr>
                        <th>Marca</th>
                        <th>Serial</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="equipos-body"></tbody>
            </table>
            <button class="add-btn" onclick="mostrarFormulario('equipos-form')">Agregar Equipo</button>
            <div id="equipos-form" class="form-container">
                <select id="equipo-marca">
                    <option value="HP">HP</option>
                    <option value="Dell">Dell</option>
                    <option value="Lenovo">Lenovo</option>
                </select>
                <input type="text" id="equipo-serial" placeholder="Serial">
                <select id="equipo-estado">
                    <option value="Disponible">Disponible</option>
                    <option value="Prestado">Prestado</option>
                    <option value="Deteriorado">Deteriorado</option>
                </select>
                <button onclick="agregarEquipo()">Guardar</button>
            </div>
        </section>
        
        <section>
            <h2>Materiales</h2>
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Stock</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="materiales-body"></tbody>
            </table>
            <button class="add-btn" onclick="mostrarFormulario('materiales-form')">Agregar Material</button>
            <div id="materiales-form" class="form-container">
                <input type="text" id="material-nombre" placeholder="Nombre">
                <select id="material-tipo">
                    <option value="Consumible">Consumible</option>
                    <option value="No Consumible">No Consumible</option>
                </select>
                <input type="number" id="material-stock" placeholder="Stock">
                <button onclick="agregarMaterial()">Guardar</button>
            </div>
        </section>
    </div>
    
    <script>
        function mostrarFormulario(id) {
            document.getElementById(id).style.display = 'block';
        }

        function guardarDatos() {
            localStorage.setItem('equipos', document.getElementById('equipos-body').innerHTML);
            localStorage.setItem('materiales', document.getElementById('materiales-body').innerHTML);
        }

        function cargarDatos() {
            document.getElementById('equipos-body').innerHTML = localStorage.getItem('equipos') || '';
            document.getElementById('materiales-body').innerHTML = localStorage.getItem('materiales') || '';
        }

        function agregarEquipo() {
            const marca = document.getElementById('equipo-marca').value;
            const serial = document.getElementById('equipo-serial').value;
            const estado = document.getElementById('equipo-estado').value;
            
            const tabla = document.getElementById('equipos-body');
            const fila = `<tr><td contenteditable="true">${marca}</td><td contenteditable="true">${serial}</td><td contenteditable="true">${estado}</td><td><button onclick="eliminarFila(this)" class="delete-btn">Eliminar</button></td></tr>`;
            tabla.innerHTML += fila;
            guardarDatos();
        }

        function agregarMaterial() {
            const nombre = document.getElementById('material-nombre').value;
            const tipo = document.getElementById('material-tipo').value;
            const stock = document.getElementById('material-stock').value;
            
            const tabla = document.getElementById('materiales-body');
            const fila = `<tr><td contenteditable="true">${nombre}</td><td contenteditable="true">${tipo}</td><td contenteditable="true">${stock}</td><td><button onclick="eliminarFila(this)" class="delete-btn">Eliminar</button></td></tr>`;
            tabla.innerHTML += fila;
            guardarDatos();
        }

        function eliminarFila(btn) {
            btn.parentElement.parentElement.remove();
            guardarDatos();
        }
    </script>
    <script>
        function toggleDarkMode() {
            document.body.classList.toggle("dark-mode");
        }
    </script>
</body>
</html>


