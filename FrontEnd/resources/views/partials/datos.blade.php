<div id="datos" class="solo-centro">
    <div class="form-group">
        <label for="correo_tienda_select" class="preguntas">Correo Electrónico (@empresasadoc.com):</label>
        <select id="correo_tienda_select" name="correo_tienda_select" class="form-control" required>
            <option value="">Seleccione un correo</option>
            <option value="wilber.hernandez@empresasadoc.com">wilber.hernandez@empresasadoc.com</option>
            <option value="belen.perez@empresasadoc.com">belen.perez@empresasadoc.com</option>
            <option value="guillermo.gudiel@empresasadoc.com">guillermo.gudiel@empresasadoc.com</option>
            <option value="edwin.flores@empresasadoc.com">edwin.flores@empresasadoc.com</option>
            <option value="sandra.rivera@empresasadoc.com">sandra.rivera@empresasadoc.com</option>
            <option value="wilber.gonzalez@empresasadoc.com">wilber.gonzalez@empresasadoc.com</option>
            <option value="juan.chavez@empresasadoc.com">juan.chavez@empresasadoc.com</option>
            <option value="elizabeth.delaroca@empresasadoc.com">elizabeth.delaroca@empresasadoc.com</option>
            <option value="ingrid.herrera@empresasadoc.com">ingrid.herrera@empresasadoc.com</option>
            <option value="lesly.espinoza@empresasadoc.com">lesly.espinoza@empresasadoc.com</option>
            <option value="carlos.ruano@empresasadoc.com">carlos.ruano@empresasadoc.com</option>
            <option value="ingrid.ostorga@empresasadoc.com">ingrid.ostorga@empresasadoc.com</option>
            <option value="rebeca.infante@empresasadoc.com">rebeca.infante@empresasadoc.com</option>
            <option value="dennis.vargas@empresasadoc.com">dennis.vargas@empresasadoc.com</option>
            <option value="kendry.solorzano@empresasadoc.com">kendry.solorzano@empresasadoc.com</option>
            <option value="daniela.meza@empresasadoc.com">daniela.meza@empresasadoc.com</option>
            <option value="erick.cruz@empresasadoc.com">erick.cruz@empresasadoc.com</option>
            <option value="yilka.miranda@empresasadoc.com">yilka.miranda@empresasadoc.com</option>
            <option value="otro">Otro...</option>
        </select>
        <input type="email" id="correo_tienda_otro" name="correo_tienda" class="form-control mt-2" placeholder="Escribe el correo de la persona que realizó la visita" style="display:none;" pattern="^[a-zA-Z0-9._%+\-]+@empresasadoc.com$">
    </div>

    <div class="form-group" id="modalidad-group">
        <label class="preguntas">Selecciona la modalidad:</label>
        <div style="display: flex; gap: 20px; justify-content: center; margin-bottom: 20px;">
            <button type="button" class="boton modalidad-btn" data-modalidad="virtual">Virtual</button>
            <button type="button" class="boton modalidad-btn" data-modalidad="presencial">Presencial</button>
        </div>
        <input type="hidden" id="modalidad_visita" name="modalidad_visita" value="">
    </div>
    <button class="boton btnSiguiente">Continuar</button>
</div>
 