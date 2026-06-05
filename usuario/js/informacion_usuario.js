document.addEventListener('DOMContentLoaded', function() {
    console.log('Página cargada correctamente');
    mostrarGasInfo();
});

function generarCampos() {
    const numeroInput = document.getElementById('numero_personas');
    const container = document.getElementById('personas-container');
    const numero = parseInt(numeroInput.value);
    
    console.log('Generando campos para:', numero);
    
    if (!numero || numero < 1 || numero > 30) {
        alert('Número debe estar entre 1 y 30');
        container.innerHTML = '';
        return;
    }

    container.innerHTML = '';

    for (let i = 1; i <= numero; i++) {
        const personaDiv = document.createElement('div');
        personaDiv.className = 'persona-fields';
        personaDiv.innerHTML = `
            <h4>Persona ${i}</h4>
            <input type="text" name="nombre_persona_${i}" placeholder="Nombre completo *" required>
            <input type="email" name="correo_persona_${i}" placeholder="Correo (opcional)">
            <input type="tel" name="telefono_persona_${i}" placeholder="Teléfono *" required>
            
            <div class="pregunta-group">
                <div class="pregunta-izquierda">
                    <div class="checkbox-group">
                        <input type="checkbox" id="medicinas_${i}" onchange="toggleDetalles('detalles_medicinas_${i}')" ${i === 1 ? 'checked' : ''}>
                        <label for="medicinas_${i}">¿Consume algún medicamento?</label>
                    </div>
                    <div class="checkbox-group">
                        <input type="checkbox" id="problemas_${i}" onchange="toggleDetalles('detalles_problemas_${i}')">
                        <label for="problemas_${i}">¿Presenta problemas médicos?</label>
                    </div>
                </div>
                <div class="botones-derecha">
                    <button type="button" class="btn-medicinas" onclick="toggleDetalles('detalles_medicinas_${i}')">Sí/No</button>
                    <button type="button" class="btn-problemas" onclick="toggleDetalles('detalles_problemas_${i}')">Sí/No</button>
                </div>
            </div>
            
            <div id="detalles_medicinas_${i}" class="detalles" style="display: none;">
                <textarea name="detalle_medicinas_${i}" placeholder="Detalle las medicinas..."></textarea>
            </div>
            
            <div id="detalles_problemas_${i}" class="detalles" style="display: none;">
                <textarea name="detalle_problemas_${i}" placeholder="Detalle los problemas médicos..."></textarea>
            </div>
        `;
        container.appendChild(personaDiv);
    }
}

function toggleDetalles(id) {
    const detalles = document.getElementById(id);
    if (detalles) {
        detalles.style.display = detalles.style.display === 'block' ? 'none' : 'block';
    }
}

function mostrarGasInfo() {
    const tipoGasSelect = document.getElementById('tipo_gas_select');
    const infoGasDiv = document.getElementById('info-gas');
    const textareaGas = document.getElementById('informacion_gas');
    
    console.log('mostrarGasInfo() ejecutada');
    console.log('Select value:', tipoGasSelect ? tipoGasSelect.value : 'NO ENCONTRADO');
    
    if (tipoGasSelect && infoGasDiv && textareaGas) {
        if (tipoGasSelect.value === 'bombonas') {
            infoGasDiv.style.display = 'block';
            textareaGas.required = true;
            console.log('✅ BOMBonAS - Textarea VISIBLE');
        } else {
            infoGasDiv.style.display = 'none';
            textareaGas.required = false;
            textareaGas.value = '';
            console.log('❌ TUBERÍA - Textarea OCULTA');
        }
    } else {
        console.log('❌ ERROR: Elementos no encontrados');
        console.log('tipo_gas_select:', !!tipoGasSelect);
        console.log('info-gas:', !!infoGasDiv);
        console.log('informacion_gas:', !!textareaGas);
    }
}
