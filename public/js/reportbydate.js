"use strict";
var KTSelect2 = function() {
    $('#selectResidente').select2({
        theme: 'bootstrap4',

        placeholder: 'Seleccion un Residente..',
        value: 'background-color: red'

    });

    return {
        init: function() {

        }
    };
}();

jQuery(document).ready(function() {
    KTSelect2.init();
});