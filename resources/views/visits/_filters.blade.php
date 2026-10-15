<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <label for="filter-visit-type">Tipo de visita</label>
        <select id="filter-visit-type" name="tipo_visita" class="form-control">
            <option value="">Todos</option>
            <option value="vehicular" @if(request('tipo_visita', old('tipo_visita')) === 'vehicular') selected @endif>Vehicular</option>
            <option value="peatonal" @if(request('tipo_visita', old('tipo_visita')) === 'peatonal') selected @endif>Peatonal</option>
        </select>
    </div>
    <div class="col-md-4 mb-3">
        <label for="filter-delivery">Repartidor</label>
        <select id="filter-delivery" name="es_repartidor" class="form-control">
            <option value="">Todos</option>
            <option value="1" @if((string) request('es_repartidor', old('es_repartidor')) === '1') selected @endif>Sí</option>
            <option value="0" @if((string) request('es_repartidor', old('es_repartidor')) === '0') selected @endif>No</option>
        </select>
    </div>
    @if($submit ?? false)
        <div class="col-md-4 mb-3 d-flex align-items-end"><button class="btn btn-primary" type="submit">Aplicar filtros</button></div>
    @endif
</div>
